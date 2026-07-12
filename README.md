# LaravelMonitor: Symfony bundle

Report exceptions, application logs and dependency snapshots from a Symfony
application to your
[LaravelMonitor](https://github.com/La-boite-a-code/LaravelMonitor) server.
Built on the framework-agnostic core `laboiteacode/monitor-php`.

## Requirements

- PHP 8.2+
- Symfony 6.4 or 7.x (`config`, `dependency-injection`, `event-dispatcher`, `http-kernel`)
- `monolog/monolog` 3.x (optional, only for log forwarding)

## Installation

In the **application you want to monitor**:

```bash
composer require laboiteacode/monitor-symfony-bundle
```

Until the package is published on Packagist, add a VCS repository to the
application's `composer.json` first:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/La-boite-a-code/LaravelMonitor" }
    ]
}
```

Register the bundle (Symfony Flex usually does this automatically):

```php
// config/bundles.php
return [
    // ...
    LaBoiteACode\Monitor\Symfony\MonitorBundle::class => ['all' => true],
];
```

## Configuration

```yaml
# config/packages/monitor.yaml
monitor:
    enabled: true
    url: '%env(MONITOR_URL)%'
    key: '%env(MONITOR_KEY)%'
    timeout: 3                  # HTTP timeout in seconds
    release: '%env(default::MONITOR_RELEASE)%'   # e.g. a git SHA
    trace_limit: 0              # 0 = full trace (default); a positive value trims
    environments: ['prod']      # empty = report from all environments
    scrub: ['password', 'token', 'secret', 'authorization', 'cookie', 'api_key']
    logs:
        enabled: false          # opt-in log forwarding
        level: warning          # minimum Monolog level to forward
        max_batch: 200          # flush the buffer past this many records
```

`key` is the per-project API key generated in the LaravelMonitor dashboard
(shown only once at creation). With `enabled: false` the bundle registers
nothing at all.

## What it wires

- `monitor.reporter`: the shared `LaBoiteACode\Monitor\Reporter` service
  (exceptions, logs, dependencies) over a dependency-free curl transport.
- `monitor.exception_subscriber`: listens on `kernel.exception` at low priority
  (-64) and reports unhandled throwables with the request method and URL.
  Additive: it never alters the response or stops propagation.
- `monitor.log_handler` (only when `logs.enabled` is true): a Monolog handler
  that buffers records and ships them in batches, on `max_batch` or when the
  handler closes. Records carrying an exception are skipped: those flow through
  the exception pipeline instead.

Reporting is fail-safe by design: transport or configuration errors are
swallowed and never break the host application.

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

Full documentation is served by your LaravelMonitor server under `/docs`
(for example `https://monitor.example.com/docs`), including a dedicated
section for this bundle.

## License

MIT. See [LICENSE](LICENSE).
