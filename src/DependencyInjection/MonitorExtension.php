<?php

namespace QuietGuard\Monitor\Symfony\DependencyInjection;

use QuietGuard\Monitor\Config;
use QuietGuard\Monitor\Http\CurlHttpClient;
use QuietGuard\Monitor\Payload\ExceptionPayloadBuilder;
use QuietGuard\Monitor\Reporter;
use QuietGuard\Monitor\Support\Scrubber;
use QuietGuard\Monitor\Symfony\EventSubscriber\ExceptionSubscriber;
use QuietGuard\Monitor\Symfony\Logging\MonitorHandler;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
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

        // Les services sont TOUJOURS définis, et « enabled » agit à l'exécution.
        //
        // Un retour anticipé ici ne définissait aucun service, alors que le
        // README enseigne dans le même document un handler monolog pointant sur
        // monitor.log_handler ET la possibilité de mettre enabled: false. Écrire
        // config/packages/dev/monitor.yaml avec enabled: false, la chose
        // évidente à faire, cassait donc la compilation du conteneur avec « You
        // have requested a non-existent service monitor.log_handler ». Le
        // commentaire du handler montre que ce mode d'échec avait déjà été
        // raisonné un niveau plus bas.
        $enabled = (bool) ($config['enabled'] ?? true);

        $env = '%kernel.environment%';

        $container->setDefinition('monitor.config', new Definition(Config::class, [
            $config['url'],
            $config['key'],
            $config['timeout'],
            $config['release'],
            $config['environments'],
            $config['trace_limit'],
            $config['redact'],
            $config['redact_custom'],
            $config['code_snippets'],
        ]));

        $container->setDefinition('monitor.http_client', new Definition(CurlHttpClient::class));
        $container->setDefinition('monitor.scrubber', new Definition(Scrubber::class, [$config['scrub']]));
        $container->setDefinition('monitor.payload_builder', new Definition(ExceptionPayloadBuilder::class, [
            $config['trace_limit'],
            $config['release'],
            $config['code_snippets'],
        ]));

        // Le logger, cinquième argument, qui n'était pas passé : c'est la SEULE
        // chose qui émette un diagnostic dans ce client. Sans lui, une clé
        // fausse ou une URL fausse ne produisent rien du tout, nulle part, et
        // le client n'a pas de console où l'apprendre. Ignoré s'il n'existe
        // pas, pour ne pas exiger monolog d'une application qui s'en passe.
        $container->setDefinition('monitor.reporter', new Definition(Reporter::class, [
            new Reference('monitor.config'),
            new Reference('monitor.http_client'),
            new Reference('monitor.scrubber'),
            new Reference('monitor.payload_builder'),
            new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
        ]));

        $subscriber = new Definition(ExceptionSubscriber::class, [
            new Reference('monitor.reporter'),
            new Reference('monitor.config'),
            $env,
            $enabled,
        ]);
        $subscriber->addTag('kernel.event_subscriber');
        $container->setDefinition('monitor.exception_subscriber', $subscriber);

        // Always registered while the bundle is enabled: a monolog.yaml handler
        // pointing at this service must not break container compilation when
        // logs.enabled is toggled off; the handler no-ops instead.
        $handler = new Definition(MonitorHandler::class, [
            new Reference('monitor.reporter'),
            $config['logs']['level'],
            true,
            $config['logs']['max_batch'],
            $env,
            $config['release'],
            new Reference('monitor.config'),
            $config['logs']['enabled'] ?? false,
        ]);
        $container->setDefinition('monitor.log_handler', $handler);
    }

    public function getAlias(): string
    {
        return 'monitor';
    }
}
