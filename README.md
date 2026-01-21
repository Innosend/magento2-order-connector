# Innosend Order Connector Module for Magento 2

This module handles automatic order synchronization with the Innosend platform.

## Installation

```bash
composer require innosend/magento2-order-connector
php bin/magento module:enable Innosend_OrderConnector
php bin/magento setup:upgrade
php bin/magento cache:flush
```

## Configuration

1. Navigate to **Stores > Configuration > Innosend > Order Synchronization**
2. Enable order sync
3. Configure sync options:
   - Automatic sync
   - Sync on order place
   - Retry failed syncs
   - Status sync settings

## Features

- Automatic order synchronization on order placement
- Retry mechanism for failed syncs
- Status and tracking information sync
- Cron job for background processing

## Requirements

- Magento 2.4.x
- PHP 7.3 - 8.3
- Innosend_Integration module

## Support

For support, please refer to the documentation in the `docs/` directory.



















