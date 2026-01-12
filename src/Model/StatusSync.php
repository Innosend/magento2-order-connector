<?php
/**
 * Copyright (c) Falcon Media (info@falconmedia.nl)
 *
 * @author Falcon Media
 */

declare(strict_types=1);

namespace Innosend\OrderConnector\Model;

use Innosend\Base\Api\ClientInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Status synchronization service
 */
class StatusSync
{
    /**
     * @var ClientInterface
     */
    private $apiClient;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ClientInterface $apiClient
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        ClientInterface $apiClient,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger
    ) {
        $this->apiClient = $apiClient;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    /**
     * Sync order status from Innosend
     *
     * @param string $innosendOrderId
     * @return array Status data
     * @throws LocalizedException
     */
    public function syncStatus(string $innosendOrderId): array
    {
        if (!$this->apiClient->isEnabled()) {
            throw new LocalizedException(__('Innosend API is not enabled.'));
        }

        try {
            $response = $this->apiClient->get('orders/' . $innosendOrderId . '/status');

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Error syncing order status: ' . $e->getMessage());
            throw new LocalizedException(
                __('Error syncing order status: %1', $e->getMessage())
            );
        }
    }

    /**
     * Get tracking information
     *
     * @param string $innosendOrderId
     * @return array Tracking data
     * @throws LocalizedException
     */
    public function getTrackingInfo(string $innosendOrderId): array
    {
        if (!$this->apiClient->isEnabled()) {
            throw new LocalizedException(__('Innosend API is not enabled.'));
        }

        try {
            $response = $this->apiClient->get('orders/' . $innosendOrderId . '/tracking');

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Error fetching tracking info: ' . $e->getMessage());
            throw new LocalizedException(
                __('Error fetching tracking info: %1', $e->getMessage())
            );
        }
    }
}



















