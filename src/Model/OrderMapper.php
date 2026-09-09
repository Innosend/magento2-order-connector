<?php
/**
 * Copyright (c) Falcon Media (info@falconmedia.nl)
 *
 * @author Falcon Media
 */

declare(strict_types=1);

namespace Innosend\OrderConnector\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\Data\OrderInterface;
use Innosend\PickupPoints\Helper\ShippingInformation;
use Psr\Log\LoggerInterface;

/**
 * Map Magento order to Innosend format
 */
class OrderMapper
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ShippingInformation|null
     */
    private $shippingInformation;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     * @param ShippingInformation|null $shippingInformation
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LoggerInterface $logger,
        ?ShippingInformation $shippingInformation = null
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
        $this->shippingInformation = $shippingInformation;
    }

    /**
     * Map order to Innosend format
     *
     * @param OrderInterface $order
     * @return array
     */
    public function map(OrderInterface $order): array
    {
        $shippingAddress = $order->getShippingAddress();
        $billingAddress = $order->getBillingAddress();

        $orderData = [
            'external_id' => $order->getIncrementId(),
            'order_number' => $order->getIncrementId(),
            'created_at' => $order->getCreatedAt(),
            'currency' => $order->getOrderCurrencyCode(),
            'total' => (float) $order->getGrandTotal(),
            'subtotal' => (float) $order->getSubtotal(),
            'shipping_amount' => (float) $order->getShippingAmount(),
            'tax_amount' => (float) $order->getTaxAmount(),
            'discount_amount' => abs((float) $order->getDiscountAmount()),
            'customer' => [
                'email' => $order->getCustomerEmail(),
                'firstname' => $order->getCustomerFirstname(),
                'lastname' => $order->getCustomerLastname(),
            ],
            'billing_address' => $this->mapAddress($billingAddress),
            'shipping_address' => $this->mapAddress($shippingAddress),
            'items' => $this->mapItems($order),
            'payment_method' => $order->getPayment() ? $order->getPayment()->getMethod() : null,
        ];

        // Add pickup point if available (separate street, zipcode, city for Innosend API)
        $pickupPointData = $this->getPickupPointData($order);
        if ($pickupPointData) {
            $orderData['pickup_point'] = [
                'id' => $pickupPointData['pickup_point_id'] ?? null,
                'courier' => $pickupPointData['pickup_point_carrier'] ?? null,
                'name' => $pickupPointData['pickup_point_name'] ?? null,
                'address' => $pickupPointData['pickup_point_address'] ?? null,
                'pickup_point_street' => $pickupPointData['pickup_point_street'] ?? null,
                'pickup_point_zipcode' => $pickupPointData['pickup_point_zipcode'] ?? null,
                'pickup_point_city' => $pickupPointData['pickup_point_city'] ?? null,
            ];

            // Set checkout_courier when pickup point is available
            if (!empty($pickupPointData['pickup_point_carrier'])) {
                $orderData['checkout_courier'] = $pickupPointData['pickup_point_carrier'];
            }
        }

        return $orderData;
    }

    /**
     * Get pickup point data from order
     *
     * @param OrderInterface $order
     * @return array|null
     */
    private function getPickupPointData(OrderInterface $order): ?array
    {
        // Try to get pickup point data from extension attributes first
        $extensionAttributes = $order->getExtensionAttributes();
        if ($extensionAttributes && $extensionAttributes->getInnosendPickupPoint()) {
            $pickupPoint = $extensionAttributes->getInnosendPickupPoint();
            return [
                'pickup_point_id' => $pickupPoint->getPickupPointId(),
                'pickup_point_carrier' => $pickupPoint->getCourierCode(),
                'pickup_point_name' => $pickupPoint->getPickupPointName(),
                'pickup_point_address' => $pickupPoint->getPickupPointAddress(),
                'pickup_point_street' => method_exists($pickupPoint, 'getPickupPointStreet')
                    ? $pickupPoint->getPickupPointStreet() : null,
                'pickup_point_zipcode' => method_exists($pickupPoint, 'getPickupPointZipcode')
                    ? $pickupPoint->getPickupPointZipcode() : null,
                'pickup_point_city' => method_exists($pickupPoint, 'getPickupPointCity')
                    ? $pickupPoint->getPickupPointCity() : null,
            ];
        }

        // Fallback: try to get from fm_innosend_order table
        // Only works if ShippingInformation helper is available (PickupPoints module installed)
        if (!$this->shippingInformation || !$order->getId()) {
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
                ->where('order_id = ?', $order->getId());
            $shippingInformationJson = $connection->fetchOne($select);

            if ($shippingInformationJson) {
                $shippingInfo = $this->shippingInformation->parseShippingInformation($shippingInformationJson);
                $pickupPointData = $this->shippingInformation->extractPickupPoint($shippingInfo);

                if ($pickupPointData) {
                    return $pickupPointData;
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to get pickup point from fm_innosend_order table', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Map address
     *
     * @param \Magento\Sales\Api\Data\OrderAddressInterface|null $address
     * @return array|null
     */
    private function mapAddress(?\Magento\Sales\Api\Data\OrderAddressInterface $address): ?array
    {
        if (!$address) {
            return null;
        }

        return [
            'firstname' => $address->getFirstname(),
            'lastname' => $address->getLastname(),
            'company' => $address->getCompany(),
            'street' => is_array($address->getStreet()) ? implode(' ', $address->getStreet()) : $address->getStreet(),
            'city' => $address->getCity(),
            'postcode' => $address->getPostcode(),
            'region' => $address->getRegion(),
            'country' => $address->getCountryId(),
            'telephone' => $address->getTelephone(),
        ];
    }

    /**
     * Map order items
     *
     * @param OrderInterface $order
     * @return array
     */
    private function mapItems(OrderInterface $order): array
    {
        $items = [];

        foreach ($order->getAllItems() as $item) {
            if ($item->getParentItem()) {
                continue; // Skip child items
            }

            $items[] = [
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'qty' => (int) $item->getQtyOrdered(),
                'price' => (float) $item->getPrice(),
                'row_total' => (float) $item->getRowTotal(),
                'tax_amount' => (float) $item->getTaxAmount(),
            ];
        }

        return $items;
    }
}
