# Innosend Order Connector Module - User Guide

## Overview

The Innosend Order Connector module automatically synchronizes orders with the Innosend platform. It handles order creation, status updates, and tracking information.

## Installation

```bash
composer require innosend/magento2-order-connector
php bin/magento module:enable Innosend_OrderConnector
php bin/magento setup:upgrade
php bin/magento cache:flush
```

## Configuration

1. Navigate to **Stores > Configuration > Innosend > Order Synchronization**
2. **Enable Order Sync**
3. **Automatic Sync** - Enable automatic synchronization
4. **Sync on Order Place** - Sync immediately when order is placed
5. **Retry Failed Syncs** - Enable retry mechanism
6. **Max Retry Attempts** - Maximum retry attempts (default: 3)
7. **Enable Status Sync** - Enable status and tracking sync
8. **Status Sync Interval** - Interval in minutes (default: 60)

## Features

- Automatic order synchronization on placement
- Retry mechanism for failed syncs
- Status and tracking information sync
- Cron job for background processing
- Comprehensive logging

## How It Works

1. Customer places order
2. Order is automatically synced to Innosend (if enabled)
3. Order data includes:
   - Customer information
   - Billing and shipping addresses
   - Order items
   - Pickup point (if selected)
   - Payment method
4. Status updates are fetched periodically
5. Tracking information is synced when available

## Monitoring

### Logs

Check the following logs for sync status:
- `var/log/system.log` - General sync information
- `var/log/exception.log` - Sync errors

### Cron

Ensure Magento cron is running:
```bash
php bin/magento cron:run
```

## Troubleshooting

### Orders Not Syncing

- Verify API configuration in Integration module
- Check "Enable Order Sync" is enabled
- Verify cron is running
- Check logs for error messages

### Failed Syncs

- Check API connectivity
- Verify order data format
- Review error logs
- Check retry mechanism is enabled

## Support

For technical support, please refer to the Technical Guide or contact support@innosend.com



















