<?php

declare(strict_types=1);

namespace RuMaxBot\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;

final class InstallCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'rumaxbot:install
                            {--force : Overwrite existing files}
                            {--only= : Comma-separated: migration,model,controller,routes,example}';

    /**
     * @var string
     */
    protected $description = 'Publish RuMaxBot webhook scaffolding (migration, model, controller, routes)';

    public function __construct(
        private readonly Filesystem $files,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $components = $this->resolveComponents();

        $this->info('Installing RuMaxBot scaffolding...');

        foreach ($components as $component) {
            match ($component) {
                'migration' => $this->publishMigration(),
                'model' => $this->publishStub(
                    'model.RuMaxBotMessage.stub',
                    app_path('Models/RuMaxBotMessage.php'),
                    'Model',
                ),
                'controller' => $this->publishStub(
                    'controller.RuMaxBotWebhookController.stub',
                    app_path('Http/Controllers/RuMaxBotWebhookController.php'),
                    'Controller',
                ),
                'routes' => $this->publishStub(
                    'routes.rumaxbot.stub',
                    base_path('routes/rumaxbot.php'),
                    'Routes',
                ),
                'example' => $this->publishStub(
                    'example.SendWithStore.stub',
                    base_path('app/RuMaxBot/SendWithStore.example.php'),
                    'Example',
                ),
                default => $this->warn("Unknown component: {$component}"),
            };
        }

        $this->newLine();
        $this->components->info('RuMaxBot install finished.');
        $this->line('Next steps:');
        $this->line('  1. Add RUMAXBOT_WEBHOOK_SECRET to .env');
        $this->line('  2. php artisan migrate');
        $this->line('  3. Register routes/rumaxbot.php (see comments inside the file)');
        $this->line('  4. Set webhook URL in rumaxbot.ru cabinet to: {APP_URL}/rumaxbot/webhook');

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function resolveComponents(): array
    {
        /** @var string|null $only */
        $only = $this->option('only');

        if ($only === null || $only === '') {
            return ['migration', 'model', 'controller', 'routes'];
        }

        $parts = array_values(array_filter(array_map(
            static fn (string $value): string => strtolower(trim($value)),
            explode(',', $only),
        )));

        return $parts !== [] ? $parts : ['migration', 'model', 'controller', 'routes'];
    }

    private function publishMigration(): void
    {
        $targetDir = database_path('migrations');
        $this->files->ensureDirectoryExists($targetDir);

        $existing = $this->files->glob($targetDir.DIRECTORY_SEPARATOR.'*_create_rumaxbot_messages_table.php');

        if ($existing !== [] && ! $this->option('force')) {
            $this->components->twoColumnDetail('Migration', '<fg=yellow;options=bold>SKIPPED</> (already exists)');

            return;
        }

        if ($existing !== [] && $this->option('force')) {
            foreach ($existing as $file) {
                $this->files->delete($file);
            }
        }

        $timestamp = Carbon::now()->format('Y_m_d_His');
        $target = $targetDir.DIRECTORY_SEPARATOR."{$timestamp}_create_rumaxbot_messages_table.php";

        $this->files->copy($this->stubPath('migration.create_rumaxbot_messages_table.stub'), $target);
        $this->components->twoColumnDetail('Migration', '<fg=green;options=bold>DONE</>');
        $this->line("  {$target}");
    }

    private function publishStub(string $stubName, string $target, string $label): void
    {
        if ($this->files->exists($target) && ! $this->option('force')) {
            $this->components->twoColumnDetail($label, '<fg=yellow;options=bold>SKIPPED</> (already exists)');

            return;
        }

        $this->files->ensureDirectoryExists(dirname($target));
        $this->files->copy($this->stubPath($stubName), $target);
        $this->components->twoColumnDetail($label, '<fg=green;options=bold>DONE</>');
        $this->line("  {$target}");
    }

    private function stubPath(string $stubName): string
    {
        return dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR.$stubName;
    }
}
