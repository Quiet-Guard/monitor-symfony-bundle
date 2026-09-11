# Changelog

All notable changes to `quiet-guard/monitor-symfony-bundle`.

## Unreleased

### Added

- The `code_snippets` option (default `true`): application frames carry a
  few lines of source around their line; `false` sends file and line only.
  Requires `quiet-guard/monitor-php` with `SourceSnippet`.

## v0.2.2

### Changed

- The `url` option is optional: left empty it resolves to the hosted service.
  The bundle's own source did not change; it builds the core `Config`, and the
  core now fills an absent address. Set `url` only to reach a self-hosted
  instance. Requires `quiet-guard/monitor-php` `^0.2.2`.

## v0.2.1

### Fixed

- `enabled: false` no longer breaks container compilation. Switching the bundle
  off defined no services at all, so a `config/packages/dev/monitor.yaml`
  override, which is the obvious way to do it, failed with "You have requested a
  non-existent service monitor.log_handler" whenever a Monolog handler pointed at
  it. The services are always defined and the switch acts at runtime.
- The reporter receives the application logger when the container has one. It
  was constructed without it, and the logger is the only thing in this bundle
  that emits a diagnostic, so a wrong key or a wrong address produced nothing
  anywhere. With the core this release requires, a refused report also names its
  status and address.
- `logs.max_batch` is held to the server's limit of 500 at send time. A larger
  value was accepted here and refused on every send, so nothing arrived. It is
  clamped rather than refused at compile time, so an application that boots
  today still boots.

### Added

- `redact` and `redact_custom` are configurable. Value masking has been on by
  default since the bundle shipped, declared in no configuration tree, so any
  attempt to change it failed compilation with "Unrecognized option" and
  `[redacted:phone]` in a message could not be explained or turned off. An empty
  `redact` list disables it; the default is the shared list of shapes.

### Changed

- Requires `quiet-guard/monitor-php` `^0.2.1`.
