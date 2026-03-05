# Changelog

All notable changes to the Innosend Order Connector module will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.1] - 2026-03-05

### Changed
- Version bump to 1.1.1; exact dependency on `innosend/magento2-integration` 1.1.1.
- CLI command `innosend:test:order-with-pickup-point` for testing orders with pickup point data.

## [1.0.3] - 2025-01-21

### Added
- Status synchronization functionality
- Tracking information sync
- Configurable status sync interval
- Retry mechanism for failed syncs
- Maximum retry attempts configuration
- Comprehensive error logging
- CLI command for testing order mapping (`innosend:test:order-mapper`)
- Pickup point integration support
- Extension attribute support for order data

### Changed
- Updated dependency on `innosend/magento2-integration` to require >= 1.0.0
- Improved order mapping accuracy
- Enhanced error handling and logging
- Optimized API request handling

### Fixed
- Fixed order sync timing issues
- Resolved pickup point data inclusion in order sync
- Fixed extension attribute loading
- Improved error messages and logging

### Removed
- Removed automatic order sync on order placement (now configurable)
- Removed hardcoded sync intervals (now configurable)

## [1.0.2] - 2024-XX-XX

### Added
- Initial release with core order synchronization
- Order mapping functionality
- Observer-based order sync
- Basic configuration options
- Integration with Innosend_Integration module

### Changed
- N/A

### Fixed
- N/A

## [1.0.1] - 2024-XX-XX

### Added
- Initial beta release

### Changed
- N/A

### Fixed
- N/A

## [1.0.0] - 2024-XX-XX

### Added
- Initial release
