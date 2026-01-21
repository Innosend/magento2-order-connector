<?php
/**
 * Copyright (c) Falcon Media (info@falconmedia.nl)
 *
 * @author Falcon Media
 */

declare(strict_types=1);

namespace Innosend\OrderConnector\Service;

use Innosend\OrderConnector\Model\OrderMapper;
use Innosend\PickupPoints\Helper\ShippingInformation;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

/**
 * Service to fetch orders from external Magento 2 API and combine with pickup point data
 */
class CustomerOrderService
{
    /**
     * @var string
     */
    private $baseUrl = '';

    /**
     * @var string
     */
    private $apiUser = '';

    /**
     * @var string
     */
    private $apiPassword = '';

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var OrderMapper
     */
    private $orderMapper;

    /**
     * @var ShippingInformation|null
     */
    private $shippingInformation;

    /**
     * @var string|null
     */
    private $adminToken = null;

    /**
     * @param Curl $curl
     * @param Json $json
     * @param LoggerInterface $logger
     * @param ResourceConnection $resourceConnection
     * @param OrderMapper $orderMapper
     * @param ShippingInformation|null $shippingInformation
     */
    public function __construct(
        Curl $curl,
        Json $json,
        LoggerInterface $logger,
        ResourceConnection $resourceConnection,
        OrderMapper $orderMapper,
        ?ShippingInformation $shippingInformation = null
    ) {
        $this->curl = $curl;
        $this->json = $json;
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
        $this->orderMapper = $orderMapper;
        $this->shippingInformation = $shippingInformation;
    }

    /**
     * Set API credentials
     *
     * @param string $baseUrl
     * @param string $apiUser
     * @param string $apiPassword
     * @return void
     */
    public function setCredentials(string $baseUrl, string $apiUser, string $apiPassword): void
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiUser = $apiUser;
        $this->apiPassword = $apiPassword;
        $this->adminToken = null; // Reset token
    }

    /**
     * Get admin token
     *
     * @return string|null
     */
    private function getAdminToken(): ?string
    {
        if ($this->adminToken) {
            return $this->adminToken;
        }

        $tokenUrl = $this->baseUrl . '/rest/V1/integration/admin/token';
        $tokenData = ['username' => $this->apiUser, 'password' => $this->apiPassword];

        try {
            $this->curl->setHeaders(['Content-Type: application/json']);
            $this->curl->post($tokenUrl, $this->json->serialize($tokenData));
            $tokenResponse = $this->curl->getBody();
            $statusCode = $this->curl->getStatus();

            if ($statusCode === 200 && !empty($tokenResponse)) {
                $this->adminToken = trim($tokenResponse, '"');
                return $this->adminToken;
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to get admin token', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Make API request
     *
     * @param string $endpoint
     * @param string $method
     * @param array $data
     * @return array
     * @throws LocalizedException
     */
    private function makeRequest(string $endpoint, string $method = 'GET', array $data = []): array
    {
        $token = $this->getAdminToken();
        if (!$token) {
            throw new LocalizedException(__('Failed to authenticate with customer API'));
        }

        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ];

        $this->curl->setHeaders($headers);
        $this->curl->setTimeout(30);

        switch ($method) {
            case 'POST':
                $this->curl->post($url, $this->json->serialize($data));
                break;
            case 'PUT':
                $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'PUT');
                $this->curl->post($url, $this->json->serialize($data));
                break;
            default:
                $this->curl->get($url);
                break;
        }

        $responseBody = $this->curl->getBody();
        $statusCode = $this->curl->getStatus();

        if ($statusCode >= 400) {
            throw new LocalizedException(
                __('Customer API error: HTTP %1 - %2', $statusCode, $responseBody)
            );
        }

        if (empty($responseBody)) {
            return [];
        }

        try {
            $decoded = $this->json->unserialize($responseBody);
            return is_array($decoded) ? $decoded : [];
        } catch (\Exception $e) {
            throw new LocalizedException(
                __('Invalid JSON response from customer API: %1', $e->getMessage())
            );
        }
    }

    /**
     * Check if order uses pickup points shipping method
     *
     * @param array $orderData
     * @return bool
     */
    private function isPickupPointOrder(array $orderData): bool
    {
        // Check shipping_assignments for shipping method
        if (isset($orderData['extension_attributes']['shipping_assignments'][0]['shipping']['method'])) {
            $shippingMethod = $orderData['extension_attributes']['shipping_assignments'][0]['shipping']['method'];
            return strpos($shippingMethod, 'innosend_pickup_points') === 0;
        }

        // Fallback: check shipping_description
        if (isset($orderData['shipping_description'])) {
            return stripos($orderData['shipping_description'], 'Afhaalpunt') !== false ||
                   stripos($orderData['shipping_description'], 'Service Point') !== false;
        }

        return false;
    }

    /**
     * Get pickup point data from extension attributes in API response
     *
     * @param array $orderData
     * @return array|null
     */
    private function getPickupPointFromExtensionAttributes(array $orderData): ?array
    {
        // Check if extension attributes contain pickup point data
        if (isset($orderData['extension_attributes']['innosend_pickup_point'])) {
            $pickupPoint = $orderData['extension_attributes']['innosend_pickup_point'];
            return [
                'pickup_point_id' => $pickupPoint['pickup_point_id'] ?? null,
                'pickup_point_carrier' => $pickupPoint['courier_code'] ?? null,
                'pickup_point_name' => $pickupPoint['pickup_point_name'] ?? null,
                'pickup_point_address' => $pickupPoint['pickup_point_address'] ?? null,
            ];
        }

        return null;
    }

    /**
     * Get pickup point data from database
     *
     * @param int $orderId
     * @return array|null
     */
    private function getPickupPointFromDatabase(int $orderId): ?array
    {
        if (!$this->shippingInformation) {
            return null;
        }

        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('fm_innosend_order');

            if (!$connection->isTableExists($tableName)) {
                return null;
            }

            $select = $connection->select()
                ->from($tableName, 'shipping_information')
                ->where('order_id = ?', $orderId);
            $shippingInformationJson = $connection->fetchOne($select);

            if ($shippingInformationJson) {
                $shippingInfo = $this->shippingInformation->parseShippingInformation($shippingInformationJson);
                $pickupPointData = $this->shippingInformation->extractPickupPoint($shippingInfo);

                if ($pickupPointData) {
                    return $pickupPointData;
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to get pickup point from database', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Fetch orders with pickup points
     *
     * @param int $pageSize
     * @param int $currentPage
     * @return array
     * @throws LocalizedException
     */
    public function fetchOrdersWithPickupPoints(int $pageSize = 20, int $currentPage = 1): array
    {
        // Build search criteria to filter on shipping method
        $endpoint = sprintf(
            'rest/V1/orders?searchCriteria[pageSize]=%d&searchCriteria[currentPage]=%d',
            $pageSize,
            $currentPage
        );

        $response = $this->makeRequest($endpoint);
        $orders = $response['items'] ?? [];

        // Filter and enrich orders with pickup point data
        $result = [];
        foreach ($orders as $order) {
            if ($this->isPickupPointOrder($order)) {
                // First, try to get pickup point from extension attributes (if plugin is active on remote server)
                $pickupPointData = $this->getPickupPointFromExtensionAttributes($order);
                
                // Fallback: try to get from local database (only works if same database)
                if (!$pickupPointData) {
                    $orderId = $order['entity_id'] ?? null;
                    if ($orderId) {
                        $pickupPointData = $this->getPickupPointFromDatabase($orderId);
                    }
                }
                
                if ($pickupPointData) {
                    $order['pickup_point'] = [
                        'id' => $pickupPointData['pickup_point_id'],
                        'courier' => $pickupPointData['pickup_point_carrier'],
                        'name' => $pickupPointData['pickup_point_name'],
                        'address' => $pickupPointData['pickup_point_address'],
                    ];

                    // Add checkout_courier
                    if (!empty($pickupPointData['pickup_point_carrier'])) {
                        $order['checkout_courier'] = $pickupPointData['pickup_point_carrier'];
                    }
                }

                $result[] = $order;
            }
        }

        return [
            'items' => $result,
            'total_count' => count($result),
            'search_criteria' => $response['search_criteria'] ?? [],
        ];
    }

    /**
     * Fetch single order by increment ID
     *
     * @param string $incrementId
     * @return array|null
     * @throws LocalizedException
     */
    public function fetchOrderByIncrementId(string $incrementId): ?array
    {
        $endpoint = sprintf(
            'rest/V1/orders?searchCriteria[filter_groups][0][filters][0][field]=increment_id&searchCriteria[filter_groups][0][filters][0][value]=%s&searchCriteria[pageSize]=1',
            urlencode($incrementId)
        );

        $response = $this->makeRequest($endpoint);
        $orders = $response['items'] ?? [];

        if (empty($orders)) {
            return null;
        }

        $order = $orders[0];

        // Enrich with pickup point data if applicable
        if ($this->isPickupPointOrder($order)) {
            // First, try to get pickup point from extension attributes (if plugin is active on remote server)
            $pickupPointData = $this->getPickupPointFromExtensionAttributes($order);
            
            // Fallback: try to get from local database (only works if same database)
            if (!$pickupPointData) {
                $orderId = $order['entity_id'] ?? null;
                if ($orderId) {
                    $pickupPointData = $this->getPickupPointFromDatabase($orderId);
                }
            }
            
            if ($pickupPointData) {
                $order['pickup_point'] = [
                    'id' => $pickupPointData['pickup_point_id'],
                    'courier' => $pickupPointData['pickup_point_carrier'],
                    'name' => $pickupPointData['pickup_point_name'],
                    'address' => $pickupPointData['pickup_point_address'],
                ];

                if (!empty($pickupPointData['pickup_point_carrier'])) {
                    $order['checkout_courier'] = $pickupPointData['pickup_point_carrier'];
                }
            }
        }

        return $order;
    }

    /**
     * Map order to Innosend format
     *
     * @param array $orderData
     * @return array
     */
    public function mapToInnosendFormat(array $orderData): array
    {
        // Convert order data array to a format that OrderMapper can use
        // Since OrderMapper expects OrderInterface, we'll create a simplified mapping
        $mappedData = [
            'external_id' => $orderData['increment_id'] ?? '',
            'order_number' => $orderData['increment_id'] ?? '',
            'created_at' => $orderData['created_at'] ?? '',
            'currency' => $orderData['order_currency_code'] ?? 'EUR',
            'total' => (float) ($orderData['grand_total'] ?? 0),
            'subtotal' => (float) ($orderData['subtotal'] ?? 0),
            'shipping_amount' => (float) ($orderData['shipping_amount'] ?? 0),
            'tax_amount' => (float) ($orderData['tax_amount'] ?? 0),
            'discount_amount' => abs((float) ($orderData['discount_amount'] ?? 0)),
            'customer' => [
                'email' => $orderData['customer_email'] ?? '',
                'firstname' => $orderData['customer_firstname'] ?? '',
                'lastname' => $orderData['customer_lastname'] ?? '',
            ],
            'billing_address' => $this->mapAddress($orderData['billing_address'] ?? []),
            'shipping_address' => $this->mapAddress(
                $orderData['extension_attributes']['shipping_assignments'][0]['shipping']['address'] ?? []
            ),
            'items' => $this->mapItems($orderData['items'] ?? []),
            'payment_method' => $orderData['payment']['method'] ?? null,
        ];

        // Add pickup point if available
        if (isset($orderData['pickup_point'])) {
            $mappedData['pickup_point'] = $orderData['pickup_point'];
        }

        // Add checkout_courier if available
        if (isset($orderData['checkout_courier'])) {
            $mappedData['checkout_courier'] = $orderData['checkout_courier'];
        }

        return $mappedData;
    }

    /**
     * Map address
     *
     * @param array $address
     * @return array|null
     */
    private function mapAddress(array $address): ?array
    {
        if (empty($address)) {
            return null;
        }

        $street = $address['street'] ?? '';
        if (is_array($street)) {
            $street = implode(' ', $street);
        }

        return [
            'firstname' => $address['firstname'] ?? '',
            'lastname' => $address['lastname'] ?? '',
            'company' => $address['company'] ?? null,
            'street' => $street,
            'city' => $address['city'] ?? '',
            'postcode' => $address['postcode'] ?? '',
            'region' => $address['region'] ?? null,
            'country' => $address['country_id'] ?? '',
            'telephone' => $address['telephone'] ?? '',
        ];
    }

    /**
     * Map items
     *
     * @param array $items
     * @return array
     */
    private function mapItems(array $items): array
    {
        $mappedItems = [];
        foreach ($items as $item) {
            if (isset($item['parent_item_id'])) {
                continue; // Skip child items
            }

            $mappedItems[] = [
                'sku' => $item['sku'] ?? '',
                'name' => $item['name'] ?? '',
                'qty' => (int) ($item['qty_ordered'] ?? 0),
                'price' => (float) ($item['price'] ?? 0),
                'row_total' => (float) ($item['row_total'] ?? 0),
                'tax_amount' => (float) ($item['tax_amount'] ?? 0),
            ];
        }
        return $mappedItems;
    }
}
