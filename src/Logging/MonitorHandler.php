<?php

namespace QuietGuard\Monitor\Symfony\Logging;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use QuietGuard\Monitor\Config;
use QuietGuard\Monitor\Reporter;

/**
 * Monolog handler that buffers records and ships them to Quiet Guard in
 * batches (on max-batch or on close). Records carrying an exception are skipped:
 * those flow through the exception pipeline instead.
 *
 * The handler service is always registered while the bundle is enabled, so a
 * monolog.yaml still pointing at it never breaks container compilation; with
 * logs disabled (or outside the allowed environments) it simply drops records.
 */
class MonitorHandler extends AbstractProcessingHandler
{
    /** @var array<int, array<string, mixed>> */
    private array $buffer = [];

    public function __construct(
        private readonly Reporter $reporter,
        int|string|Level $level = Level::Warning,
        bool $bubble = true,
        private readonly int $maxBatch = 200,
        private readonly ?string $environment = null,
        private readonly ?string $release = null,
        private readonly ?Config $config = null,
        private readonly bool $enabled = true,
    ) {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        if (! $this->enabled) {
            return;
        }

        // The environments allowlist gates logs exactly like exceptions.
        if ($this->config !== null && ! $this->config->reportsFrom($this->environment)) {
            return;
        }

        if (isset($record->context['exception'])) {
            return;
        }

        $this->buffer[] = [
            'level' => strtolower($record->level->getName()),
            'message' => $record->message,
            'context' => $record->context,
            'channel' => $record->channel,
            'environment' => $this->environment,
            'release' => $this->release,
            'logged_at' => $record->datetime->format(DATE_ATOM),
        ];

        // Ramené à la limite du serveur, qui refuse un lot de plus de 500 : une
        // valeur au-dessus était acceptée par la configuration et refusée à
        // chaque envoi, donc rien ne partait jamais et rien ne le disait. Un
        // plancher de 1 parce que 0 ou négatif ne vide jamais.
        if (count($this->buffer) >= min(500, max(1, $this->maxBatch))) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        if ($this->buffer === []) {
            return;
        }

        $batch = $this->buffer;
        $this->buffer = [];
        $this->reporter->sendLogs($batch);
    }

    public function close(): void
    {
        $this->flush();
        parent::close();
    }
}
