<?php
/**
 * Copyright (c) Falcon Media (info@falconmedia.nl)
 *
 * @author Falcon Media
 */

declare(strict_types=1);

namespace Innosend\OrderConnector\Model\Cron;

use Innosend\OrderConnector\Model\Config;
use Innosend\OrderConnector\Model\OrderSync;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * Cron job for syncing failed orders
 */
class SyncOrders
{
    /**
     * @var OrderSync
     */
    private $orderSync;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param OrderSync $orderSync
     * @param Config $config
     * @param CollectionFactory $orderCollectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        OrderSync $orderSync,
        Config $config,
        CollectionFactory $orderCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->orderSync = $orderSync;
        $this->config = $config;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * Execute cron job
     *
     * @return void
     */
    public function execute(): void
    {
        if (!$this->config->isEnabled() || !$this->config->isRetryFailedEnabled()) {
            return;
        }

        // This is a placeholder - in a real implementation, you would:
        // 1. Query orders that failed to sync (stored in a custom table or extension attribute)
        // 2. Retry syncing them
        // 3. Update retry count
        // 4. Mark as failed if max attempts reached

        $this->logger->info('Innosend order sync cron executed');
    }
}



















