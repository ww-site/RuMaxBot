<?php

declare(strict_types=1);

namespace RuMaxBot\Tests\Feature;

use Illuminate\Support\Facades\File;
use RuMaxBot\Tests\TestCase;

final class InstallCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        File::delete(app_path('Models/RuMaxBotMessage.php'));
        File::delete(app_path('Http/Controllers/RuMaxBotWebhookController.php'));
        File::delete(base_path('routes/rumaxbot.php'));
        File::delete(base_path('app/RuMaxBot/SendWithStore.example.php'));

        foreach (File::glob(database_path('migrations/*_create_rumaxbot_messages_table.php')) ?: [] as $file) {
            File::delete($file);
        }
    }

    public function test_install_publishes_scaffolding(): void
    {
        $this->artisan('rumaxbot:install')
            ->assertSuccessful();

        $this->assertFileExists(app_path('Models/RuMaxBotMessage.php'));
        $this->assertFileExists(app_path('Http/Controllers/RuMaxBotWebhookController.php'));
        $this->assertFileExists(base_path('routes/rumaxbot.php'));

        $migrations = File::glob(database_path('migrations/*_create_rumaxbot_messages_table.php'));
        $this->assertNotFalse($migrations);
        $this->assertCount(1, $migrations);
    }

    public function test_install_skips_existing_files_without_force(): void
    {
        $this->artisan('rumaxbot:install')->assertSuccessful();
        $this->artisan('rumaxbot:install')
            ->expectsOutputToContain('SKIPPED')
            ->assertSuccessful();
    }

    public function test_install_only_model(): void
    {
        $this->artisan('rumaxbot:install', ['--only' => 'model'])
            ->assertSuccessful();

        $this->assertFileExists(app_path('Models/RuMaxBotMessage.php'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/RuMaxBotWebhookController.php'));
    }
}
