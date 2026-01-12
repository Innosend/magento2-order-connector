# Innosend Order Connector Module - Technical Guide

## Architecture

The module synchronizes orders with Innosend using observers and cron jobs.

## Components

### Models

- `OrderSync` - Main synchronization service
- `OrderMapper` - Maps Magento order to Innosend format
- `StatusSync` - Handles status and tracking sync
- `Config` - Configuration management

### Observers

- `SalesOrderPlaceAfter` - Syncs order after placement

### Cron

- `SyncOrders` - Retries failed syncs

## Order Mapping

Orders are mapped to Innosend format:

```php
[
    'external_id' => 'ORDER-00001',
    'order_number' => 'ORDER-00001',
    'created_at' => '2024-01-01 12:00:00',
    'currency' => 'EUR',
    'total' => 100.00,
    'customer' => [...],
    'billing_address' => [...],
    'shipping_address' => [...],
    'items' => [...],
    'pickup_point' => [...] // if selected
]
```

## API Endpoints

- `POST /orders` - Create order
- `GET /orders/{id}/status` - Get order status
- `GET /orders/{id}/tracking` - Get tracking info

## Extension Points

### Custom Order Mapper

```xml
<preference for="Innosend\OrderConnector\Model\OrderMapper" type="Your\Module\Model\CustomOrderMapper"/>
```

### Custom Sync Service

```xml
<preference for="Innosend\OrderConnector\Model\OrderSync" type="Your\Module\Model\CustomOrderSync"/>
```

## Error Handling

Failed syncs are logged with:
- Order ID
- Error message
- Timestamp
- Retry count

## Requirements

- Innosend_Base module
- Magento 2.4.x
- PHP 7.3 - 8.3



















