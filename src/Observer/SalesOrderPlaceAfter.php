<?php
/**
 * Copyright (c) Falcon Media (info@falconmedia.nl)
 *
 * @author Falcon Media
 */

declare(strict_types=1);

namespace Innosend\OrderConnector\Observer;

use Innosend\OrderConnector\Model\Config;
use Innosend\OrderConnector\Model\OrderSync;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

/**
 * Observer to sync order after placement
 */
class SalesOrderPlaceAfter implements ObserverInterface
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param OrderSync $orderSync
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        OrderSync $orderSync,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->orderSync = $orderSync;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        if (!$this->config->isEnabled() || !$this->config->isSyncOnPlaceEnabled()) {
            return;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        if (!$order) {
            return;
        }

        try {
            $this->orderSync->syncOrder($order);
        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to sync order on place',
                [
                    'order_id' => $order->getEntityId(),
                    'increment_id' => $order->getIncrementId(),
                    'error' => $e->getMessage(),
                ]
            );

            // Don't throw exception to prevent order placement failure
        }
    }
}



















