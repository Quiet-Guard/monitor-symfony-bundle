<?php

namespace LaBoiteACode\Monitor\Symfony\EventSubscriber;

use LaBoiteACode\Monitor\Config;
use LaBoiteACode\Monitor\Reporter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Reports unhandled exceptions to Quiet Guard. Additive: it never alters the
 * response or stops propagation.
 */
class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Reporter $reporter,
        private readonly Config $config,
        private readonly ?string $environment = null,
    ) {}

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function getSubscribedEvents(): array
    {
        // Low priority so framework listeners run first; we only observe.
        return [KernelEvents::EXCEPTION => ['onException', -64]];
    }

    public function onException(ExceptionEvent $event): void
    {
        if (! $this->config->reportsFrom($this->environment)) {
            return;
        }

        $throwable = $event->getThrowable();

        // Expected HTTP errors (404 bot probes, 403s...) are not defects and
        // would burn the event quota; only 5xx HTTP exceptions are reported.
        if ($throwable instanceof HttpExceptionInterface && $throwable->getStatusCode() < 500) {
            return;
        }

        $request = $event->getRequest();

        $this->reporter->reportException($throwable, [
            'environment' => $this->environment,
            'request' => [
                'method' => $request->getMethod(),
                'url' => $request->getUri(),
            ],
        ]);
    }
}
