# Changelog

All notable changes to `filament-media` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- `branch-alias: dev-main → 0.1.x-dev` in `composer.json` so `dev-main` satisfies `^0.1` constraints during pre-release development.

### Fixed
- `media.picker.lightbox_opacity` default was set to `90` but the field's documented default and existing unit test both expected `80`. The mismatch was introduced in commit `cb2df3a` and unnoticed because the failing test had never run. Config value now matches the documented default of `80`.

[Unreleased]: https://github.com/Codenzia/filament-media/commits/main
