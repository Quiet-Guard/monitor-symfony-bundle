<?php

namespace LaBoiteACode\Monitor\Symfony\Logging;

use LaBoiteACode\Monitor\Reporter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

/**
 * Monolog handler that buffers records and ships them to LaravelMonitor in
 * batches (on max-batch or on close). Records carrying an exception are skipped:
 * those flow through the exception pipeline instead.
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
    ) {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
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

        if (count($this->buffer) >= $this->maxBatch) {
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
