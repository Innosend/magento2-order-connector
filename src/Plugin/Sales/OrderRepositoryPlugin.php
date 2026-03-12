<?php
/**
 * Copyright (c) Falcon Media (info@falconmedia.nl)
 *
 * @author Falcon Media
 */

declare(strict_types=1);

namespace Innosend\OrderConnector\Plugin\Sales;

use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;
use Psr\Log\LoggerInterface;

/**
 * Plugin to load pickup point extension attributes for orders via REST API
 * 
 * This plugin requires the Innosend_PickupPoints module to be installed.
 * If not available, dependencies will be null and it will gracefully skip loading pickup point data.
 * Dependencies are injected via di.xml when PickupPoints module is available.
 */
class OrderRepositoryPlugin
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Innosend\PickupPoints\Api\Data\OrderPickupPointInterfaceFactory|null
     */
    private $pickupPointFactory;

    /**
     * @var \Innosend\PickupPoints\Helper\ShippingInformation|null
     */
    private $shippingInformation;

    /**
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     * @param \Innosend\PickupPoints\Api\Data\OrderPickupPointInterfaceFactory|null $pickupPointFactory
     * @param \Innosend\PickupPoints\Helper\ShippingInformation|null $shippingInformation
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LoggerInterface $logger,
        \Innosend\PickupPoints\Api\Data\OrderPickupPointInterfaceFactory $pickupPointFactory = null,
        \Innosend\PickupPoints\Helper\ShippingInformation $shippingInformation = null
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
        $this->pickupPointFactory = $pickupPointFactory;
        $this->shippingInformation = $shippingInformation;
    }

    /**
     * Load pickup point extension attributes after order is retrieved
     *
     * @param OrderRepository $subject
     * @param OrderInterface $order
     * @return OrderInterface
     */
    public function afterGet(
        OrderRepository $subject,
        OrderInterface $order
    ): OrderInterface {
        $this->loadPickupPointExtensionAttributes($order);
        return $order;
    }

    /**
     * Load pickup point extension attributes for order list
     *
     * @param OrderRepository $subject
     * @param \Magento\Sales\Api\Data\OrderSearchResultInterface $searchResult
     * @return \Magento\Sales\Api\Data\OrderSearchResultInterface
     */
    public function afterGetList(
        OrderRepository $subject,
        \Magento\Sales\Api\Data\OrderSearchResultInterface $searchResult
    ): \Magento\Sales\Api\Data\OrderSearchResultInterface {
        foreach ($searchResult->getItems() as $order) {
            $this->loadPickupPointExtensionAttributes($order);
        }
        return $searchResult;
    }

    /**
     * Load pickup point extension attributes for order
     *
     * @param OrderInterface $order
     * @return void
     */
    private function loadPickupPointExtensionAttributes(OrderInterface $order): void
    {
        // Skip if PickupPoints module dependencies are not available
        if (!$this->pickupPointFactory || !$this->shippingInformation) {
            return;
        }

        // Only process if shipping method is innosend_pickup_points
        $shippingMethod = $order->getShippingMethod();
        if (!$shippingMethod || strpos($shippingMethod, 'innosend_pickup_points') !== 0) {
            return;
        }

        $extensionAttributes = $order->getExtensionAttributes();

        // Always load from database so we can apply street/zipcode/city (and fallback parsing for legacy data)
        if (!$order->getId()) {
            return;
        }

        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('fm_innosend_order');

            if (!$connection->isTableExists($tableName)) {
                return;
            }

            $select = $connection->select()
                ->from($tableName, 'shipping_information')
                ->where('order_id = ?', $order->getId());
            $shippingInformationJson = $connection->fetchOne($select);

            if ($shippingInformationJson && $this->shippingInformation && $this->pickupPointFactory) {
                $shippingInfo = $this->shippingInformation->parseShippingInformation($shippingInformationJson);
                $pickupPointData = $this->shippingInformation->extractPickupPoint($shippingInfo);

                if ($pickupPointData) {
                    // For existing orders: if street/zipcode/city are missing, derive from address (e.g. "Street, Postcode, City")
                    $address = $pickupPointData['pickup_point_address'] ?? null;
                    $street = $pickupPointData['pickup_point_street'] ?? null;
                    $zipcode = $pickupPointData['pickup_point_zipcode'] ?? null;
                    $city = $pickupPointData['pickup_point_city'] ?? null;
                    if ($address && ($street === null || $zipcode === null || $city === null)) {
                        $parsed = $this->parseAddressIntoStreetZipcodeCity($address);
                        $street = $street ?? $parsed['street'];
                        $zipcode = $zipcode ?? $parsed['zipcode'];
                        $city = $city ?? $parsed['city'];
                    }

                    // Create pickup point object (OrderConnector preference provides extended model with street/zipcode/city)
                    $pickupPoint = $this->pickupPointFactory->create();
                    $pickupPoint->setPickupPointId($pickupPointData['pickup_point_id'] ?? null);
                    $pickupPoint->setCourierCode($pickupPointData['pickup_point_carrier'] ?? null);
                    $pickupPoint->setPickupPointName($pickupPointData['pickup_point_name'] ?? null);
                    $pickupPoint->setPickupPointAddress($pickupPointData['pickup_point_address'] ?? null);
                    $pickupPoint->setPickupPointStreet($street);
                    $pickupPoint->setPickupPointZipcode($zipcode);
                    $pickupPoint->setPickupPointCity($city);

                    // Set extension attributes
                    if (!$extensionAttributes) {
                        $extensionAttributes = $order->getExtensionAttributes();
                    }
                    if ($extensionAttributes) {
                        $extensionAttributes->setInnosendPickupPoint($pickupPoint);
                        $order->setExtensionAttributes($extensionAttributes);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to load pickup point extension attributes', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Parse combined address string into street, zipcode, city (for legacy orders without separate fields).
     * Expected format: "Street, Postcode, City" e.g. "Barndesteeg 9-C, 1012BV, AMSTERDAM"
     *
     * @param string $address
     * @return array{street: string|null, zipcode: string|null, city: string|null}
     */
    private function parseAddressIntoStreetZipcodeCity(string $address): array
    {
        $result = ['street' => null, 'zipcode' => null, 'city' => null];
        $address = trim($address);
        if ($address === '') {
            return $result;
        }

        $parts = array_map('trim', explode(',', $address));
        if (count($parts) >= 3) {
            $result['street'] = $parts[0] !== '' ? $parts[0] : null;
            $result['zipcode'] = $parts[1] !== '' ? $parts[1] : null;
            $result['city'] = $parts[2] !== '' ? $parts[2] : null;
        } elseif (count($parts) === 2) {
            $result['street'] = $parts[0] !== '' ? $parts[0] : null;
            // Second part could be "Postcode City" or just city
            $second = $parts[1];
            if (preg_match('/^([A-Z0-9\s\-]+)\s+([A-Za-z\s]+)$/u', $second, $m)) {
                $result['zipcode'] = trim($m[1]);
                $result['city'] = trim($m[2]);
            } else {
                $result['city'] = $second !== '' ? $second : null;
            }
        } else {
            $result['street'] = $address;
        }

        return $result;
    }
}
