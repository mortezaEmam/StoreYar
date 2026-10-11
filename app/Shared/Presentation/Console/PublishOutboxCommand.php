<?php

declare(strict_types=1);

namespace StoreYar\Shared\Presentation\Console;

use Illuminate\Console\Command;
use StoreYar\Shared\Application\Outbox\OutboxProcessor;

final class PublishOutboxCommand extends Command
{
    protected $signature = 'outbox:publish
                            {--limit=100 : Max number of outbox rows to process}';

    protected $description = 'Publish unpublished domain events from the transactional outbox';

    public function handle(OutboxProcessor $processor): int
    {
        $limit = (int) $this->option('limit');

        if ($limit < 1) {
            $this->error('Limit must be at least 1.');

            return self::FAILURE;
        }

        $result = $processor->process($limit);

        $this->info(sprintf(
            'Outbox publish finished. processed=%d failed=%d',
            $result['processed'],
            $result['failed'],
        ));

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
