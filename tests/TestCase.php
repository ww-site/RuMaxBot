<?php

declare(strict_types=1);

namespace RuMaxBot\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use RuMaxBot\RuMaxBotServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            RuMaxBotServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('rumaxbot', [
            'base_url' => 'https://rumaxbot.ru',
            'token' => 'mbk_test_token',
            'timeout' => 30,
        ]);
    }
}
