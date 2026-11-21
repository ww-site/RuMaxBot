<?php

declare(strict_types=1);

namespace RuMaxBot;

use Illuminate\Support\ServiceProvider;
use RuMaxBot\Console\InstallCommand;
use RuMaxBot\Console\SendTestMessageCommand;
use RuMaxBot\Contracts\RuMaxBotClientInterface;
use RuMaxBot\Services\RuMaxBotClient;
use RuMaxBot\Services\WebhookSignatureVerifier;

final class RuMaxBotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/rumaxbot.php', 'rumaxbot');

        $this->app->singleton(RuMaxBotClientInterface::class, function (): RuMaxBotClient {
            /** @var array{base_url: string, token: string|null, timeout: int} $config */
            $config = config('rumaxbot');

            return new RuMaxBotClient(
                baseUrl: $config['base_url'],
                token: (string) ($config['token'] ?? ''),
                timeout: $config['timeout'],
            );
        });

        $this->app->singleton(WebhookSignatureVerifier::class, function (): WebhookSignatureVerifier {
            /** @var string|null $secret */
            $secret = config('rumaxbot.webhook_secret');

            return new WebhookSignatureVerifier(secret: (string) ($secret ?? ''));
        });

        $this->app->alias(RuMaxBotClientInterface::class, 'rumaxbot');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                SendTestMessageCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/rumaxbot.php' => config_path('rumaxbot.php'),
            ], 'rumaxbot-config');
        }
    }
}
