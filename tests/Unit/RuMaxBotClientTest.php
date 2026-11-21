<?php

declare(strict_types=1);

namespace RuMaxBot\Tests\Unit;

use Illuminate\Support\Facades\Http;
use RuMaxBot\Contracts\RuMaxBotClientInterface;
use RuMaxBot\DataTransferObjects\InlineButton;
use RuMaxBot\DataTransferObjects\InlineKeyboardAttachment;
use RuMaxBot\DataTransferObjects\SendMessageData;
use RuMaxBot\Enums\MessageFormat;
use RuMaxBot\Exceptions\RuMaxBotException;
use RuMaxBot\Tests\TestCase;

final class RuMaxBotClientTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function apiSuccessPayload(?string $externalMessageId = null): array
    {
        $data = [
            'id' => '019e79d1-5177-7082-9192-64e6a16cbd63',
            'status' => 'pending',
        ];

        if ($externalMessageId !== null) {
            $data['external_message_id'] = $externalMessageId;
        }

        return [
            'message' => 'Message queued successfully.',
            'data' => $data,
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
                && $request['format'] === 'markdown'
                && ! isset($request['external_message_id'])
                && ! isset($request['attachments']);
        });
    }

    public function test_send_message_with_buttons_and_external_id(): void
    {
        $externalId = '550e8400-e29b-41d4-a716-446655440000';

        Http::fake([
            'rumaxbot.ru/api/v1/messages' => Http::response($this->apiSuccessPayload($externalId), 202),
        ]);

        $client = $this->app->make(RuMaxBotClientInterface::class);

        $response = $client->send(new SendMessageData(
            text: 'Выберите действие',
            format: MessageFormat::Markdown,
            externalMessageId: $externalId,
            attachments: [
                InlineKeyboardAttachment::make([
                    [
                        InlineButton::callback('Принять', 'order:accept'),
                        InlineButton::callback('Отклонить', 'order:reject'),
                    ],
                    [
                        InlineButton::link('Сайт', 'https://rumaxbot.ru'),
                    ],
                ]),
            ],
        ));

        $this->assertSame('pending', $response->status);
        $this->assertSame($externalId, $response->externalMessageId);

        Http::assertSent(function ($request) use ($externalId): bool {
            return $request['external_message_id'] === $externalId
                && is_array($request['attachments'])
                && $request['attachments'][0]['type'] === 'inline_keyboard'
                && $request['attachments'][0]['payload']['buttons'][0][0]['payload'] === 'order:accept'
                && $request['attachments'][0]['payload']['buttons'][1][0]['type'] === 'link';
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
