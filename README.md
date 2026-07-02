# LaravelMonitor: Symfony bundle

Report exceptions, logs and dependency snapshots from a Symfony application to a
LaravelMonitor server. Built on the framework-agnostic core
`laboiteacode/monitor-php`.

## Install

```bash
composer require laboiteacode/monitor-symfony-bundle
```

Register the bundle (Symfony Flex usually does this automatically):

```php
// config/bundles.php
return [
    // ...
    LaBoiteACode\Monitor\Symfony\MonitorBundle::class => ['all' => true],
];
```

## Configure

```yaml
# config/packages/monitor.yaml
monitor:
    url: '%env(MONITOR_URL)%'
    key: '%env(MONITOR_KEY)%'
    release: '%env(default::MONITOR_RELEASE)%'
    environments: ['prod']      # empty = report from all
    logs:
        enabled: true
        level: warning
        max_batch: 200
```

## What it wires

- `ExceptionSubscriber`: listens on `kernel.exception` and reports unhandled
  throwables (additive; never alters the response).
- `MonitorHandler`: a Monolog handler that batches log records and ships them on
  flush/close. Add it to your Monolog config to enable log forwarding.
- `monitor.reporter`: the shared `Reporter` service (exceptions, logs,
  dependencies) over a dependency-free curl transport.

## Dependency scanning

Send `composer.lock` to the server the same way the Laravel client does, e.g. a
small console command or a CI step POSTing to `/api/v1/dependencies` with the
project key. The ingestion API is platform-neutral.
