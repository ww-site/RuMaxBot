<?php

declare(strict_types=1);

namespace RuMaxBot\Tests\Unit;

use Illuminate\Support\Facades\Http;
use RuMaxBot\Contracts\RuMaxBotClientInterface;
use RuMaxBot\DataTransferObjects\SendMessageData;
use RuMaxBot\Enums\MessageFormat;
use RuMaxBot\Exceptions\RuMaxBotException;
use RuMaxBot\Tests\TestCase;

final class RuMaxBotClientTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function apiSuccessPayload(): array
    {
        return [
            'message' => 'Message queued successfully.',
            'data' => [
                'id' => '019e79d1-5177-7082-9192-64e6a16cbd63',
                'status' => 'pending',
            ],
        ];
    }

    public function test_send_message_successfully(): void
    {
        Http::fake([
            'rumaxbot.ru/api/v1/messages' => Http::response($this->apiSuccessPayload(), 202),
        ]);

        $client = $this->app->make(RuMaxBotClientInterface::class);

        $response = $client->send(new SendMessageData(
            text: 'Deploy succeeded',
            format: MessageFormat::Markdown,
        ));

        $this->assertSame('pending', $response->status);
        $this->assertSame('019e79d1-5177-7082-9192-64e6a16cbd63', $response->id);
        $this->assertSame('Message queued successfully.', $response->message);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://rumaxbot.ru/api/v1/messages'
                && $request->hasHeader('Authorization', 'Bearer mbk_test_token')
                && $request['text'] === 'Deploy succeeded'
                && $request['format'] === 'markdown';
        });
    }

    public function test_send_message_throws_on_api_error(): void
    {
        Http::fake([
            'rumaxbot.ru/api/v1/messages' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        $client = $this->app->make(RuMaxBotClientInterface::class);

        $this->expectException(RuMaxBotException::class);
        $this->expectExceptionMessage('RuMaxBot API request failed with HTTP 401');

        $client->send(new SendMessageData(text: 'Hello'));
    }

    public function test_send_message_throws_when_token_missing(): void
    {
        config()->set('rumaxbot.token', '');

        $client = $this->app->make(RuMaxBotClientInterface::class);

        $this->expectException(RuMaxBotException::class);
        $this->expectExceptionMessage('RuMaxBot API token is not configured');

        $client->send(new SendMessageData(text: 'Hello'));
    }

    public function test_send_message_supports_legacy_flat_response_format(): void
    {
        Http::fake([
            'rumaxbot.ru/api/v1/messages' => Http::response(['status' => 'pending'], 202),
        ]);

        $client = $this->app->make(RuMaxBotClientInterface::class);

        $response = $client->send(new SendMessageData(text: 'Hello'));

        $this->assertSame('pending', $response->status);
        $this->assertNull($response->id);
    }
}
