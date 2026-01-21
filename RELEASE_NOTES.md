# Release Notes - Innosend Order Connector Module v1.0.3

## Overview
The Innosend Order Connector module automatically synchronizes orders with the Innosend platform. It handles order creation, status updates, and tracking information synchronization.

## Version 1.0.3

### Features

#### Core Functionality
- **Order Synchronization**
  - Automatic order synchronization on order placement
  - Real-time order sync via observer pattern
  - Background processing via cron jobs
  - Comprehensive error handling and logging

#### Order Mapping
- **Complete Order Data Mapping**
  - Customer information mapping
  - Billing and shipping address mapping
  - Order items with product details
  - Pickup point information (when selected via PickupPoints module)
  - Payment method information
  - Order totals and currency

#### Status Synchronization
- **Status and Tracking Sync**
  - Periodic status updates from Innosend
  - Tracking information synchronization
  - Configurable sync interval
  - Automatic retry mechanism for failed syncs

#### Retry Mechanism
- **Failed Sync Handling**
  - Automatic retry for failed synchronizations
  - Configurable maximum retry attempts
  - Exponential backoff for retries
  - Detailed error logging

#### API Integration
- **REST API Support**
  - Order creation via POST endpoint
  - Status retrieval via GET endpoint
  - Tracking information retrieval
  - Error handling and response validation

#### Extension Attributes
- **Pickup Point Integration**
  - Automatic pickup point data inclusion in order sync
  - Integration with Innosend_PickupPoints module
  - Extension attribute support for order data

### Technical Details

#### Dependencies
- **Required Modules**
  - `Innosend_Integration` >= 1.0.0
  - `Magento_Sales` >= 102.0.0
  - `Magento_Store` >= 101.0.0

- **PHP Compatibility**: PHP 7.3, 7.4, 8.1, 8.2, 8.3
- **Magento Compatibility**: Magento 2.4.2+ (Framework >=102.0.0)

#### Components
- **Models**
  - `OrderMapper` - Maps Magento order to Innosend format
  - `StatusSync` - Handles status and tracking synchronization
  - `Config` - Configuration management
  - `CustomerOrderService` - Order retrieval and processing service

- **Observers**
  - `SalesOrderPlaceAfter` - Triggers order sync after order placement

- **Cron Jobs**
  - `StatusSync` - Periodic status and tracking sync
  - Retry mechanism for failed syncs

- **CLI Commands**
  - `innosend:test:order-mapper` - Test order mapping functionality

#### Database Schema
- No additional database tables required
- Uses Magento's standard order and extension attribute system

### Installation

```bash
composer require innosend/magento2-order-connector
php bin/magento module:enable Innosend_OrderConnector
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

### Configuration

Navigate to **Stores > Configuration > Innosend > Order Synchronization** to configure:

1. **Enable Order Sync** - Enable/disable order synchronization
2. **Automatic Sync** - Enable automatic synchronization
3. **Sync on Order Place** - Sync immediately when order is placed
4. **Retry Failed Syncs** - Enable retry mechanism for failed syncs
5. **Max Retry Attempts** - Maximum number of retry attempts (default: 3)
6. **Enable Status Sync** - Enable status and tracking synchronization
7. **Status Sync Interval** - Interval in minutes for status sync (default: 60)

### Usage

#### Automatic Synchronization
1. Customer places order in Magento
2. Order is automatically synchronized to Innosend (if enabled)
3. Order data is mapped to Innosend format
4. Pickup point information is included (if selected)
5. Status updates are fetched periodically

#### Manual Testing
Use the CLI command to test order mapping:
```bash
php bin/magento innosend:test:order-mapper <order_increment_id>
```

#### Developer Usage

**Access Order Mapper:**
```php
use Innosend\OrderConnector\Model\OrderMapper;

$orderMapper = $objectManager->get(OrderMapper::class);
$innosendOrder = $orderMapper->mapOrder($magentoOrder);
```

**Access Status Sync:**
```php
use Innosend\OrderConnector\Model\StatusSync;

$statusSync = $objectManager->get(StatusSync::class);
$statusSync->syncOrderStatus($orderId);
```

### Monitoring

#### Logs
Check the following logs for sync status:
- `var/log/system.log` - General sync information
- `var/log/exception.log` - Sync errors and exceptions

#### Cron
Ensure Magento cron is running for background processing:
```bash
php bin/magento cron:run
```

### Known Limitations
- Order sync requires valid API configuration in Integration module
- Status sync requires active cron jobs
- Failed syncs are retried up to configured maximum attempts

### Troubleshooting

#### Orders Not Syncing
- Verify API configuration in Integration module
- Check "Enable Order Sync" is enabled in configuration
- Verify cron is running
- Check logs for error messages
- Verify order data format

#### Failed Syncs
- Check API connectivity
- Verify order data format
- Review error logs
- Check retry mechanism is enabled
- Verify maximum retry attempts setting

#### Status Sync Not Working
- Verify "Enable Status Sync" is enabled
- Check cron is running
- Verify status sync interval is configured
- Check API endpoint accessibility

### Support

For technical support, please refer to the Technical Guide in `docs/en/TECHNICAL_GUIDE.md` or contact support@innosend.com

### Documentation

- User Guide: `docs/en/USER_GUIDE.md`
- Technical Guide: `docs/en/TECHNICAL_GUIDE.md`
- Support: `docs/en/SUPPORT.md`
