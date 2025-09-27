# Changelog

The format of this file is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-09-27

### Added
- Added support for Nextcloud 32
- Outbound proxy is now used if configured in nextcloud config.php 

### Removed
- Removed support for Nextcloud versions < 31.0.3 (newer versions of 31 are still supported)

### Changed
- WebPush servers in your local network (local IPs) are no longer allowed unless you have configured your instance to allow local network connections

## [0.0.3] - 2025-06-11

### Added
- Added support for VAPID
- Added push message encryption
- Added support for `Push-Dont-Notify` header
- Added support for addressbooks
- Added support for shared calendars
- Added support for Nextcloud 31

### Removed
- Removed support for Nextcloud 28 and 29

### Changed
- It is now highly recommended to install either the GMP or the BCMath php extension to speed up cryptography calculations for the new encryption support
- Due to big changes in the database schema all existing subscriptions from previous alpha releases will get cleared when updating. DAVx⁵ will re-register them in a background job automatically, but that might take some time. If you want your subscriptions to work with minimal interruption de-select your push provider in the DAVx⁵ settings after updating and select it again. This way all subscriptions get re-created immediately.

## [0.0.2] - 2025-01-23

### Added
- Added current sync token to push message
- Added push messages for events that were moved to trash, restored from trash or were moved to a different calendar
- Added periodical clean up of expired and failing (for example push server has gone offline and could not be reached multiple pushes in a row) subscriptions
- Added occ admin commands

### Changed
- Improved compatibility with WebPush/UnifiedPush servers
- Improved error management

## [0.0.1] - 2024-08-25

### Added
- Added basic push functionality
