# Innosend Order Connector Module - Technische Gids

## Architectuur

De module synchroniseert orders met Innosend met behulp van observers en cron jobs.

## Componenten

### Models

- `OrderSync` - Hoofd synchronisatieservice
- `OrderMapper` - Mapt Magento order naar Innosend formaat
- `StatusSync` - Handelt status en tracking sync af
- `Config` - Configuratiebeheer

### Observers

- `SalesOrderPlaceAfter` - Synchroniseert order na plaatsing

### Cron

- `SyncOrders` - Retry mislukte syncs

## Order Mapping

Orders worden gemapt naar Innosend formaat:

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
    'pickup_point' => [...] // indien geselecteerd
]
```

## API Endpoints

- `POST /orders` - Maak order
- `GET /orders/{id}/status` - Haal order status op
- `GET /orders/{id}/tracking` - Haal tracking info op

## Uitbreidingspunten

### Aangepaste Order Mapper

```xml
<preference for="Innosend\OrderConnector\Model\OrderMapper" type="Your\Module\Model\CustomOrderMapper"/>
```

### Aangepaste Sync Service

```xml
<preference for="Innosend\OrderConnector\Model\OrderSync" type="Your\Module\Model\CustomOrderSync"/>
```

## Foutafhandeling

Mislukte syncs worden gelogd met:
- Order ID
- Foutmelding
- Timestamp
- Retry count

## Vereisten

- Innosend_Base module
- Magento 2.4.x
- PHP 7.3 - 8.3



















