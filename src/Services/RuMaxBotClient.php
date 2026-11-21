<?php

declare(strict_types=1);

namespace RuMaxBot\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuMaxBot\Contracts\RuMaxBotClientInterface;
use RuMaxBot\DataTransferObjects\SendMessageData;
use RuMaxBot\DataTransferObjects\SendMessageResponseData;
use RuMaxBot\Exceptions\RuMaxBotException;

final readonly class RuMaxBotClient implements RuMaxBotClientInterface
{
    public function __construct(
        private string $baseUrl,
        private string $token,
        private int $timeout,
    ) {}

    /**
     * @throws RuMaxBotException
     */
    public function send(SendMessageData $message): SendMessageResponseData
    {
        if ($this->token === '') {
            throw new RuMaxBotException('RuMaxBot API token is not configured. Set RUMAXBOT_API_TOKEN in .env');
        }

        $url = rtrim($this->baseUrl, '/').'/api/v1/messages';

        try {
            $response = Http::withToken($this->token)
                ->timeout($this->timeout)
                ->acceptJson()
                ->asJson()
                ->post($url, $message->toArray());

            if ($response->failed()) {
                Log::error('RuMaxBot API request failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new RuMaxBotException(
                    'RuMaxBot API request failed with HTTP '.$response->status(),
                    $response->status(),
                );
            }

            /** @var array<string, mixed> $payload */
            $payload = $response->json() ?? [];

            $parsed = $this->parseResponsePayload($payload);

            if ($parsed === null) {
                Log::error('RuMaxBot API returned unexpected response', [
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new RuMaxBotException('RuMaxBot API returned unexpected response format');
            }

            return $parsed;
        } catch (ConnectionException|RequestException $exception) {
            Log::error('RuMaxBot API connection error', [
                'url' => $url,
                'message' => $exception->getMessage(),
            ]);

            throw new RuMaxBotException(
                'RuMaxBot API connection error: '.$exception->getMessage(),
                previous: $exception,
            );
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function parseResponsePayload(array $payload): ?SendMessageResponseData
    {
        $status = null;
        $id = null;
        $externalMessageId = null;

        $message = isset($payload['message']) && is_string($payload['message'])
            ? $payload['message']
            : null;

        if (isset($payload['data']) && is_array($payload['data'])) {
            /** @var array<string, mixed> $data */
            $data = $payload['data'];

            if (isset($data['status']) && is_string($data['status'])) {
                $status = $data['status'];
            }

            if (isset($data['id']) && is_string($data['id'])) {
                $id = $data['id'];
            }

            if (isset($data['external_message_id']) && is_string($data['external_message_id'])) {
                $externalMessageId = $data['external_message_id'];
            }
        }

        if ($status === null && isset($payload['status']) && is_string($payload['status'])) {
            $status = $payload['status'];
        }

        if ($status === null) {
            return null;
        }

        return new SendMessageResponseData(
            status: $status,
            id: $id,
            message: $message,
            externalMessageId: $externalMessageId,
        );
    }
}
