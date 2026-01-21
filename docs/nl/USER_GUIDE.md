# Innosend Order Connector Module - Gebruikershandleiding

## Overzicht

De Innosend Order Connector module synchroniseert automatisch orders met het Innosend platform. Het handelt order creatie, statusupdates en tracking informatie af.

## Installatie

```bash
composer require innosend/magento2-order-connector
php bin/magento module:enable Innosend_OrderConnector
php bin/magento setup:upgrade
php bin/magento cache:flush
```

## Configuratie

1. Ga naar **Stores > Configuration > Innosend > Order Synchronization**
2. **Enable Order Sync**
3. **Automatic Sync** - Automatische synchronisatie inschakelen
4. **Sync on Order Place** - Direct synchroniseren bij order plaatsing
5. **Retry Failed Syncs** - Retry mechanisme inschakelen
6. **Max Retry Attempts** - Maximum retry pogingen (standaard: 3)
7. **Enable Status Sync** - Status en tracking sync inschakelen
8. **Status Sync Interval** - Interval in minuten (standaard: 60)

## Functionaliteiten

- Automatische order synchronisatie bij plaatsing
- Retry mechanisme voor mislukte syncs
- Status en tracking informatie sync
- Cron job voor achtergrondverwerking
- Uitgebreide logging

## Hoe Het Werkt

1. Klant plaatst order
2. Order wordt automatisch gesynchroniseerd naar Innosend (indien ingeschakeld)
3. Order data bevat:
   - Klant informatie
   - Factuur- en verzendadressen
   - Order items
   - Afhaalpunt (indien geselecteerd)
   - Betaalmethode
4. Statusupdates worden periodiek opgehaald
5. Tracking informatie wordt gesynchroniseerd wanneer beschikbaar

## Monitoring

### Logs

Controleer de volgende logs voor sync status:
- `var/log/system.log` - Algemene sync informatie
- `var/log/exception.log` - Sync fouten

### Cron

Zorg dat Magento cron draait:
```bash
php bin/magento cron:run
```

## Probleemoplossing

### Orders Worden Niet Gesynchroniseerd

- Verifieer API-configuratie in Integration module
- Controleer "Enable Order Sync" is ingeschakeld
- Verifieer dat cron draait
- Controleer logs op foutmeldingen

### Mislukte Syncs

- Controleer API-connectiviteit
- Verifieer order data formaat
- Bekijk error logs
- Controleer retry mechanisme is ingeschakeld

## Support

Voor technische ondersteuning, raadpleeg de Technische Gids of neem contact op met support@innosend.com



















