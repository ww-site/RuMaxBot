<?php

declare(strict_types=1);

namespace RuMaxBot\Tests\Feature;

use Illuminate\Support\Facades\Http;
use RuMaxBot\Tests\TestCase;

final class SendTestMessageCommandTest extends TestCase
{
    public function test_command_sends_test_message_successfully(): void
    {
        Http::fake([
            'rumaxbot.ru/api/v1/messages' => Http::response([
                'message' => 'Message queued successfully.',
                'data' => ['id' => 'msg-1', 'status' => 'pending'],
            ], 202),
        ]);

        $this->artisan('rumaxbot:test')
            ->expectsOutputToContain('Sending test message via RuMaxBot...')
            ->expectsOutputToContain('Message queued successfully. Status: pending')
            ->assertSuccessful();

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://rumaxbot.ru/api/v1/messages'
                && is_string($request['text'])
                && str_contains($request['text'], 'RuMaxBot');
        });
    }

    public function test_command_accepts_custom_message(): void
    {
        Http::fake([
            'rumaxbot.ru/api/v1/messages' => Http::response([
                'message' => 'Message queued successfully.',
                'data' => ['id' => 'msg-1', 'status' => 'pending'],
            ], 202),
        ]);

        $this->artisan('rumaxbot:test', ['--message' => 'Hello from CI'])
            ->assertSuccessful();

        Http::assertSent(fn ($request): bool => $request['text'] === 'Hello from CI');
    }

    public function test_command_fails_when_api_returns_error(): void
    {
        Http::fake([
            'rumaxbot.ru/api/v1/messages' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        $this->artisan('rumaxbot:test')
            ->expectsOutputToContain('Failed to send test message')
            ->assertFailed();
    }
}
