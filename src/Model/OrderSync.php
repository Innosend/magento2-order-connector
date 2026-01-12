<?php
/**
 * Copyright (c) Falcon Media (info@falconmedia.nl)
 *
 * @author Falcon Media
 */

declare(strict_types=1);

namespace Innosend\OrderConnector\Model;

use Innosend\Base\Api\ClientInterface;
use Innosend\OrderConnector\Model\OrderMapper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

/**
 * Order synchronization service
 */
class OrderSync
{
    /**
     * @var ClientInterface
     */
    private $apiClient;

    /**
     * @var OrderMapper
     */
    private $orderMapper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ClientInterface $apiClient
     * @param OrderMapper $orderMapper
     * @param LoggerInterface $logger
     */
    public function __construct(
        ClientInterface $apiClient,
        OrderMapper $orderMapper,
        LoggerInterface $logger
    ) {
        $this->apiClient = $apiClient;
        $this->orderMapper = $orderMapper;
        $this->logger = $logger;
    }

    /**
     * Sync order to Innosend
     *
     * @param OrderInterface $order
     * @return array Response data
     * @throws LocalizedException
     */
    public function syncOrder(OrderInterface $order): array
    {
        if (!$this->apiClient->isEnabled()) {
            throw new LocalizedException(__('Innosend API is not enabled.'));
        }

        try {
            $orderData = $this->orderMapper->map($order);
            $response = $this->apiClient->post('orders', $orderData);

            $this->logger->info(
                'Order synced to Innosend',
                [
                    'order_id' => $order->getEntityId(),
                    'increment_id' => $order->getIncrementId(),
                    'innosend_order_id' => $response['id'] ?? null,
                ]
            );

            return $response;
        } catch (\Exception $e) {
            $this->logger->error(
                'Error syncing order to Innosend',
                [
                    'order_id' => $order->getEntityId(),
                    'increment_id' => $order->getIncrementId(),
                    'error' => $e->getMessage(),
                ]
            );

            throw new LocalizedException(
                __('Error syncing order to Innosend: %1', $e->getMessage())
            );
        }
    }
}



















