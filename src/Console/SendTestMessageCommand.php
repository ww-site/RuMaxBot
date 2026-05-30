<?php

declare(strict_types=1);

namespace RuMaxBot\Console;

use Illuminate\Console\Command;
use RuMaxBot\Contracts\RuMaxBotClientInterface;
use RuMaxBot\DataTransferObjects\SendMessageData;
use RuMaxBot\Enums\MessageFormat;
use RuMaxBot\Exceptions\RuMaxBotException;

final class SendTestMessageCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'rumaxbot:test
                            {--message= : Custom message text}
                            {--format=markdown : Message format (markdown|html)}';

    /**
     * @var string
     */
    protected $description = 'Send a test message via RuMaxBot API';

    public function handle(RuMaxBotClientInterface $client): int
    {
        /** @var string $appName */
        $appName = config('app.name', 'Laravel');

        /** @var string|null $customMessage */
        $customMessage = $this->option('message');
        $message = $customMessage !== null && $customMessage !== ''
            ? $customMessage
            : "🧪 Тестовое сообщение RuMaxBot из {$appName}";

        /** @var string $formatValue */
        $formatValue = $this->option('format');
        $format = MessageFormat::tryFrom($formatValue) ?? MessageFormat::Markdown;

        if ($formatValue !== '' && MessageFormat::tryFrom($formatValue) === null) {
            $this->warn("Unknown format '{$formatValue}', using markdown.");
        }

        $this->info('Sending test message via RuMaxBot...');

        try {
            $response = $client->send(new SendMessageData(
                text: $message,
                format: $format,
            ));

            $this->info("Message queued successfully. Status: {$response->status}");

            if ($response->id !== null) {
                $this->line("Message ID: {$response->id}");
            }

            return self::SUCCESS;
        } catch (RuMaxBotException $exception) {
            $this->error('Failed to send test message: '.$exception->getMessage());
            $this->line('Check RUMAXBOT_API_TOKEN in .env and storage/logs/laravel.log for details.');

            return self::FAILURE;
        }
    }
}
