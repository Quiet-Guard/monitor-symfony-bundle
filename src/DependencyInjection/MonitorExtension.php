<?php

namespace LaBoiteACode\Monitor\Symfony\DependencyInjection;

use LaBoiteACode\Monitor\Config;
use LaBoiteACode\Monitor\Http\CurlHttpClient;
use LaBoiteACode\Monitor\Payload\ExceptionPayloadBuilder;
use LaBoiteACode\Monitor\Reporter;
use LaBoiteACode\Monitor\Support\Scrubber;
use LaBoiteACode\Monitor\Symfony\EventSubscriber\ExceptionSubscriber;
use LaBoiteACode\Monitor\Symfony\Logging\MonitorHandler;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

class MonitorExtension extends Extension
{
    /**
     * @param  array<int, array<string, mixed>>  $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration, $configs);

        if (! ($config['enabled'] ?? true)) {
            return;
        }

        $env = '%kernel.environment%';

        $container->setDefinition('monitor.config', new Definition(Config::class, [
            $config['url'],
            $config['key'],
            $config['timeout'],
            $config['release'],
            $config['environments'],
            $config['trace_limit'],
        ]));

        $container->setDefinition('monitor.http_client', new Definition(CurlHttpClient::class));
        $container->setDefinition('monitor.scrubber', new Definition(Scrubber::class, [$config['scrub']]));
        $container->setDefinition('monitor.payload_builder', new Definition(ExceptionPayloadBuilder::class, [
            $config['trace_limit'],
            $config['release'],
        ]));

        $container->setDefinition('monitor.reporter', new Definition(Reporter::class, [
            new Reference('monitor.config'),
            new Reference('monitor.http_client'),
            new Reference('monitor.scrubber'),
            new Reference('monitor.payload_builder'),
        ]));

        $subscriber = new Definition(ExceptionSubscriber::class, [
            new Reference('monitor.reporter'),
            new Reference('monitor.config'),
            $env,
        ]);
        $subscriber->addTag('kernel.event_subscriber');
        $container->setDefinition('monitor.exception_subscriber', $subscriber);

        if ($config['logs']['enabled'] ?? false) {
            $handler = new Definition(MonitorHandler::class, [
                new Reference('monitor.reporter'),
                $config['logs']['level'],
                true,
                $config['logs']['max_batch'],
                $env,
                $config['release'],
            ]);
            $container->setDefinition('monitor.log_handler', $handler);
        }
    }

    public function getAlias(): string
    {
        return 'monitor';
    }
}
