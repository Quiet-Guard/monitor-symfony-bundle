# Quiet Guard: Symfony bundle

Report exceptions and application logs from a Symfony application to your
Quiet Guard server.
Built on the framework-agnostic core `quiet-guard/monitor-php`, the same
engine that powers the Laravel SDK and the WordPress plugin. Dependency
snapshots can be sent through the platform-neutral API (see below).

## Requirements

- PHP 8.2+ with `ext-curl` and `ext-sodium` (required by the core)
- Symfony 6.4, 7.x or 8.x (`config`, `dependency-injection`, `event-dispatcher`, `http-kernel`)
- `monolog/monolog` 3.x (optional, only for log forwarding)

## Installation

```bash
composer require quiet-guard/monitor-symfony-bundle
```

Register the bundle by hand. Symfony Flex applies a recipe published in its own
index, and no recipe exists for this bundle, so nothing registers it for you:

```php
// config/bundles.php
return [
    // ...
    QuietGuard\Monitor\Symfony\MonitorBundle::class => ['all' => true],
];
```

## Configuration

```yaml
# config/packages/monitor.yaml
monitor:
    enabled: true
    url: '%env(default::MONITOR_URL)%'   # empty = the hosted service, https://quietguard.dev
    key: '%env(MONITOR_KEY)%'
    timeout: 3                  # HTTP timeout in seconds
    release: '%env(default::MONITOR_RELEASE)%'   # e.g. a git SHA
    trace_limit: 0              # 0 = full trace (default); a positive value trims
    code_snippets: true         # source lines around application frames; false sends file and line only
    environments: ['prod']      # empty = report from all environments
    scrub: ['password', 'passphrase', 'token', 'secret', 'authorization', 'cookie', 'referer', 'referrer', 'api_key']
    logs:
        enabled: false          # opt-in log forwarding
        level: warning          # minimum Monolog level to forward
        max_batch: 200          # flush past this many records, held to the server's 500
    redact: [email, iban, nir, card, phone]   # value shapes masked before sending; [] disables
    redact_custom:
        order: '/ORD-\d+/'                    # label => pattern, masked as [redacted:order]
```

`key` is the per-project API key generated in the Quiet Guard dashboard
(shown only once at creation). The services are always defined and
`enabled: false` acts at runtime, so a per-environment override such as
`config/packages/dev/monitor.yaml` compiles even when a `monolog.yaml`
references `monitor.log_handler`.

Value masking by shape (`email`, `iban`, `nir`, `card`, `phone`) is on by
default and is configured by the `redact` and `redact_custom` keys of the same
tree; an empty `redact` list turns it off.

## What it wires

- `monitor.reporter`: the shared `QuietGuard\Monitor\Reporter` service
  (exceptions, logs, dependencies) over a dependency-free curl transport.
- `monitor.exception_subscriber`: listens on `kernel.exception` at low priority
  (-64) and reports unhandled throwables with the request method and URL.
  Additive: it never alters the response or stops propagation. Expected HTTP
  errors (`HttpExceptionInterface` with a status below 500, such as 404 bot
  probes) are skipped so they never burn your event quota; 5xx HTTP exceptions
  are reported.
- `monitor.log_handler`: a Monolog handler that buffers records and ships them
  in batches, on `max_batch` or when the handler closes. Records carrying an
  exception are skipped: those flow through the exception pipeline instead.
  The service is always registered, whatever `enabled` and `logs.enabled` say,
  so a `monolog.yaml` pointing at it keeps compiling in every environment; the
  handler simply drops records when logging is off. The `environments` allowlist
  applies to logs exactly like exceptions.

Reporting is fail-safe by design: transport errors are swallowed at runtime and
never break the host application. Configuration mistakes (an unknown option, an
invalid Monolog level) surface at container compile time or boot, on purpose:
they are deploy-time errors, not production noise.

## Forwarding logs

Enable `logs.enabled` in the bundle config, then point a Monolog handler at the
`monitor.log_handler` service:

```yaml
# config/packages/monolog.yaml
monolog:
    handlers:
        monitor:
            type: service
            id: monitor.log_handler
```

## Dependency scanning

Send `composer.lock` to the server the same way the Laravel client does, e.g. a
small console command or a CI step POSTing to `/api/v1/dependencies` with the
project key. The ingestion API is platform-neutral.

## Privacy

The keys listed under `scrub` (passwords, tokens, cookies...) are masked
recursively in every payload before anything leaves the application, and
stack-trace frame arguments are never sent: only file, line, function, class
and call type.

## Documentation

Full documentation is served by your Quiet Guard server under `/docs`
(for example `https://monitor.example.com/docs`), including a dedicated
section for this bundle.

## License

MIT. See [LICENSE](LICENSE).
