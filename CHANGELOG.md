# Changelog

All notable changes to `quiet-guard/monitor-symfony-bundle`.

## v0.3.1

### Security

- The reported URL is masked. `request.url`, built from `Request::getUri()`,
  travelled whole, with a reset link's token in its query or its path. The
  query values the `scrub` list names become `%5Bscrubbed%5D` now, and so does
  every path segment made of forty letters or digits in a row.
- The `hash` of a login link and the `_hash` of a URL signed by the
  `UriSigner` are masked in the reported URL whatever the `scrub` list holds,
  with Azure's `sig` and `signature`: a login link works for whoever has it
  until it expires. The names are matched exactly and in an address only, so
  a `content_hash` parameter, a `hash` key and a line calling `hash()` stay.
- A key spelled with hyphens is masked like its underscore spelling
  (`x-api-key` under `api_key`), and a JSON object or array written as a
  string is opened and masked by key instead of travelling as it came.
- `signature` joins the default `scrub` list, as in the Laravel SDK, and so
  does `password_confirmation`, which `password` already covered.

### Changed

- The message of a forwarded log goes through the scrubber too: when it starts
  with a URL or is JSON, the values it carries are masked like a context
  value's, and the rest of its text is kept.

## v0.3.0

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
