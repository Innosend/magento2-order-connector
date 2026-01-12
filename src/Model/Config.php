<?php
/**
 * Copyright (c) Falcon Media (info@falconmedia.nl)
 *
 * @author Falcon Media
 */

declare(strict_types=1);

namespace Innosend\OrderConnector\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Configuration model for Order Connector
 */
class Config
{
    /**
     * Configuration paths
     */
    public const XML_PATH_ENABLED = 'innosend/order/enabled';
    public const XML_PATH_AUTO_SYNC = 'innosend/order/auto_sync';
    public const XML_PATH_SYNC_ON_PLACE = 'innosend/order/sync_on_place';
    public const XML_PATH_RETRY_FAILED = 'innosend/order/retry_failed';
    public const XML_PATH_RETRY_ATTEMPTS = 'innosend/order/retry_attempts';
    public const XML_PATH_STATUS_SYNC_ENABLED = 'innosend/order/status_sync_enabled';
    public const XML_PATH_STATUS_SYNC_INTERVAL = 'innosend/order/status_sync_interval';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Check if order sync is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if auto sync is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isAutoSyncEnabled(?int $storeId = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_AUTO_SYNC,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if sync on place is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isSyncOnPlaceEnabled(?int $storeId = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_SYNC_ON_PLACE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if retry failed is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isRetryFailedEnabled(?int $storeId = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_RETRY_FAILED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get max retry attempts
     *
     * @param int|null $storeId
     * @return int
     */
    public function getRetryAttempts(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_RETRY_ATTEMPTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 3;
    }

    /**
     * Check if status sync is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isStatusSyncEnabled(?int $storeId = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_STATUS_SYNC_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get status sync interval in minutes
     *
     * @param int|null $storeId
     * @return int
     */
    public function getStatusSyncInterval(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_STATUS_SYNC_INTERVAL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 60;
    }
}



















