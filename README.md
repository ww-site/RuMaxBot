# RuMaxBot

Composer-пакет для быстрого подключения **Laravel 12** к API [rumaxbot.ru](https://rumaxbot.ru/) (MAX Messenger).

Репозиторий пакета: [github.com/ww-site/RuMaxBot](https://github.com/ww-site/RuMaxBot)

Сайт шлюза: [rumaxbot.ru](https://rumaxbot.ru) · Новости: [rumaxbot.ru/novoe](https://rumaxbot.ru/novoe)

---

## Установка

```bash
composer require ww-site/rumaxbot
```

Локально из дистрибутива laracomposer (path):

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../COMPLETED/001_maxbot_pack"
        }
    ],
    "require": {
        "ww-site/rumaxbot": "@dev"
    }
}
```

```bash
php artisan vendor:publish --tag=rumaxbot-config
```

## Настройка (.env)

```dotenv
RUMAXBOT_BASE_URL=https://rumaxbot.ru
RUMAXBOT_API_TOKEN=mbk_your_api_key_here
RUMAXBOT_TIMEOUT=30
RUMAXBOT_WEBHOOK_SECRET=your_webhook_secret
```

| Переменная | Описание | По умолчанию |
|------------|----------|--------------|
| `RUMAXBOT_BASE_URL` | Боевой URL шлюза | `https://rumaxbot.ru` |
| `RUMAXBOT_API_TOKEN` | Bearer-ключ из ЛК (`mbk_…`) | — |
| `RUMAXBOT_TIMEOUT` | Таймаут HTTP (сек) | `30` |
| `RUMAXBOT_WEBHOOK_SECRET` | Секрет API-ключа для HMAC вебхука | — |

Ошибки пишутся в `storage/logs/laravel.log`.

---

## Поток данных (туда и обратно)

```
Ваше Laravel-приложение                    rumaxbot.ru                         MAX
─────────────────────                    ───────────                         ───
1) POST /api/v1/messages  ─────────────►  очередь
   + сохранить external_message_id
                                          2) доставка ботом  ───────────────►  чат + кнопки
                                                                          3) пользователь
                                                                             нажал кнопку
4) POST /rumaxbot/webhook ◄─────────────  исходящий вебхук
   найти запись по external_message_id
   выполнить действие
```

`external_message_id` — ваш UUID: уходит в API и возвращается в вебхуке. Без локального хранения склейка невозможна → `php artisan rumaxbot:install`.

---

## 1. Туда: отправка сообщения

### Аутентификация

```http
POST https://rumaxbot.ru/api/v1/messages
Authorization: Bearer mbk_ваш_ключ
Content-Type: application/json
Accept: application/json
```

### Payload запроса — простое сообщение

```json
{
  "text": "🚀 Деплой прошёл успешно!",
  "format": "markdown"
}
```

### Payload запроса — с кнопками и склейкой

```json
{
  "text": "Выберите действие",
  "format": "markdown",
  "external_message_id": "550e8400-e29b-41d4-a716-446655440000",
  "attachments": [
    {
      "type": "inline_keyboard",
      "payload": {
        "buttons": [
          [
            { "type": "callback", "text": "Принять", "payload": "order:accept" },
            { "type": "callback", "text": "Отклонить", "payload": "order:reject" }
          ],
          [
            { "type": "callback", "text": "Перезвонить", "payload": "order:callback" },
            { "type": "link", "text": "Сайт", "url": "https://rumaxbot.ru" }
          ]
        ]
      }
    }
  ]
}
```

| Поле | Тип | Обяз. | Описание |
|------|-----|-------|----------|
| `text` | string | да | 1–4096 символов |
| `format` | string | нет | `markdown` \| `html` |
| `external_message_id` | uuid | нет | ваш ID; вернётся в вебхуке |
| `attachments` | array | нет | только `inline_keyboard`; кнопки `callback` / `link` |

Старый контракт `{ "text": "…" }` **без изменений** — новые поля необязательны.

### Payload ответа API (`202 Accepted`)

```json
{
  "message": "Message queued successfully.",
  "data": {
    "id": "019e79d1-5177-7082-9192-64e6a16cbd63",
    "status": "pending",
    "external_message_id": "550e8400-e29b-41d4-a716-446655440000",
    "channel": {
      "id": "019e79ca-4085-73f2-a566-c139858c7884",
      "name": "основной уведомитель",
      "type": "dialog"
    },
    "attempts": 0,
    "sent_at": null,
    "created_at": "2026-11-21T12:00:00+00:00"
  }
}
```

Другие коды: `401` ключ · `409` канал не готов · `422` валидация · `429` суточный лимит.

### Код в Laravel

```php
use RuMaxBot\DataTransferObjects\SendMessageData;
use RuMaxBot\Enums\MessageFormat;
use RuMaxBot\Facades\RuMaxBot;

// простое
$response = RuMaxBot::send(new SendMessageData(
    text: '🚀 Деплой прошёл успешно!',
    format: MessageFormat::Markdown,
));
// $response->status === 'pending'
// $response->id === '019e79d1-...'
```

С кнопками + сохранением в БД (после `rumaxbot:install`):

```php
use App\Models\RuMaxBotMessage;
use Illuminate\Support\Str;
use RuMaxBot\DataTransferObjects\InlineButton;
use RuMaxBot\DataTransferObjects\InlineKeyboardAttachment;
use RuMaxBot\DataTransferObjects\SendMessageData;
use RuMaxBot\Enums\MessageFormat;
use RuMaxBot\Enums\MessageStatus;
use RuMaxBot\Facades\RuMaxBot;

$externalId = (string) Str::uuid();

$stored = RuMaxBotMessage::query()->create([
    'external_message_id' => $externalId,
    'text' => 'Выберите действие',
    'format' => MessageFormat::Markdown,
    'status' => MessageStatus::Queued,
    'context' => ['order_id' => 123],
]);

$response = RuMaxBot::send(new SendMessageData(
    text: $stored->text,
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

$stored->forceFill(['rumaxbot_message_id' => $response->id])->save();
```

Через DI:

```php
use RuMaxBot\Contracts\RuMaxBotClientInterface;

$client->send(new SendMessageData(text: 'Deploy OK'));
```

---

## 2. Обратно: исходящий вебхук (нажатие кнопки)

Настраивается **на API-ключе** в кабинете rumaxbot.ru (не путать с webhook MAX → шлюз).

После нажатия `callback`-кнопки шлюз шлёт POST на ваш URL.

### Заголовки запроса к вам

```http
POST https://ваш-сервер/rumaxbot/webhook
Content-Type: application/json
User-Agent: MaxBot-Webhook/1.0
X-MaxBot-Event: message_callback
X-MaxBot-Delivery: 019e79d1-aaaa-bbbb-cccc-ddddeeeeffff
X-MaxBot-Signature: sha256=a1b2c3d4e5f6...
```

Проверка подписи: `hash_hmac('sha256', $rawBody, $webhookSecret)` сравнить с hex после `sha256=`.  
В пакете: `WebhookSignatureVerifier` / контроллер из `rumaxbot:install`.

### Payload вебхука — нажатие кнопки

```json
{
  "event": "message_callback",
  "timestamp": 1730000000,
  "channel_id": "019e79ca-4085-73f2-a566-c139858c7884",
  "api_key_id": "019e79cb-1111-2222-3333-444455556666",
  "external_message_id": "550e8400-e29b-41d4-a716-446655440000",
  "chat_id": "123456789",
  "callback": {
    "callback_id": "cb_abc123",
    "payload": "order:accept",
    "text": "Принять"
  },
  "user": {
    "user_id": 123,
    "name": "Иван",
    "username": null
  },
  "message": {
    "mid": "mid.xxx",
    "text": "Выберите действие"
  }
}
```

| Поле | Описание |
|------|----------|
| `external_message_id` | тот же UUID, что ушёл в `POST /messages` |
| `callback.payload` | значение кнопки (`order:accept`) |
| `callback.text` | подпись кнопки на экране |
| `message.mid` | id сообщения в MAX |
| `user` | кто нажал |

Тест из кабинета шлёт `event: "webhook.test"` (без бизнес-логики).

### Что делает контроллер из `rumaxbot:install`

1. Проверяет `X-MaxBot-Signature`
2. Ищет `RuMaxBotMessage` по `external_message_id`
3. Пишет `callback_payload`, `callback_text`, `answered_at`, статус `answered`
4. Оставляет TODO под вашу логику (`match` по `order:accept` и т.д.)

Ответ вашего приложения шлюзу (успех):

```json
{ "ok": true }
```

Ошибки контроллера-заготовки: `401` подпись · `404` нет записи · `422` нет `external_message_id`.

---

## Scaffolding: `rumaxbot:install`

```bash
php artisan rumaxbot:install
php artisan rumaxbot:install --force
php artisan rumaxbot:install --only=migration,model,controller,routes
php artisan rumaxbot:install --only=example
```

| Файл | Назначение |
|------|------------|
| `database/migrations/*_create_rumaxbot_messages_table.php` | таблица корреляции |
| `app/Models/RuMaxBotMessage.php` | модель |
| `app/Http/Controllers/RuMaxBotWebhookController.php` | приём вебхука |
| `routes/rumaxbot.php` | `POST /rumaxbot/webhook` |

Дальше:

1. `RUMAXBOT_WEBHOOK_SECRET` в `.env`
2. `php artisan migrate`
3. Подключить `routes/rumaxbot.php` в `bootstrap/app.php` (см. комментарии в файле)
4. В ЛК rumaxbot.ru: webhook URL = `{APP_URL}/rumaxbot/webhook`

---

## Быстрая проверка

```bash
php artisan rumaxbot:test
php artisan rumaxbot:test --message="Привет из Laravel"
php artisan rumaxbot:test --message="<b>HTML</b>" --format=html
```

```
Sending test message via RuMaxBot...
Message queued successfully. Status: pending
Message ID: 019e79d1-...
```

---

## Тестирование

### Автотесты пакета

```bash
composer install
vendor/bin/phpunit
```

| Файл | Покрытие |
|------|----------|
| `tests/Unit/RuMaxBotClientTest.php` | отправка, кнопки, `external_message_id`, ошибки |
| `tests/Unit/WebhookSignatureVerifierTest.php` | HMAC, разбор вебхука |
| `tests/Feature/SendTestMessageCommandTest.php` | `rumaxbot:test` |
| `tests/Feature/InstallCommandTest.php` | `rumaxbot:install` |

### В Laravel-проекте

```bash
php artisan test --filter RuMaxBot
```

---

## Лицензия

MIT. Пакет и шлюз не аффилированы с MAX Messenger.
