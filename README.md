# RuMaxBot

Composer-пакет для быстрого подключения **Laravel 12** к API [rumaxbot.ru](https://rumaxbot.ru/) (MAX Messenger).

## Установка

```bash
composer require laracomposer/rumaxbot
```

Для локальной разработки из этого репозитория:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../COMPLETED/001_maxbot_pack"
        }
    ],
    "require": {
        "laracomposer/rumaxbot": "@dev"
    }
}
```

Опубликовать конфиг (опционально):

```bash
php artisan vendor:publish --tag=rumaxbot-config
```

## Настройка (.env)

```dotenv
RUMAXBOT_BASE_URL=https://rumaxbot.ru
RUMAXBOT_API_TOKEN=mbk_your_api_key_here
RUMAXBOT_TIMEOUT=30
```

| Переменная | Описание | По умолчанию |
|------------|----------|--------------|
| `RUMAXBOT_BASE_URL` | Базовый URL сервиса | `https://rumaxbot.ru` |
| `RUMAXBOT_API_TOKEN` | Bearer-токен из ЛК rumaxbot.ru | — |
| `RUMAXBOT_TIMEOUT` | Таймаут HTTP-запроса (сек) | `30` |

Ошибки API и сетевые сбои пишутся в стандартный Laravel log (`storage/logs/laravel.log`).

## Быстрая проверка после установки

Отправить тестовое сообщение в MAX одной командой:

```bash
php artisan rumaxbot:test
```

Своё сообщение:

```bash
php artisan rumaxbot:test --message="Привет из Laravel"
php artisan rumaxbot:test --message="<b>HTML</b>" --format=html
```

Успех:

```
Sending test message via RuMaxBot...
Message queued successfully. Status: pending
```

## Использование

### Через фасад

```php
use RuMaxBot\DataTransferObjects\SendMessageData;
use RuMaxBot\Enums\MessageFormat;
use RuMaxBot\Facades\RuMaxBot;

RuMaxBot::send(new SendMessageData(
    text: '🚀 Деплой прошёл успешно!',
    format: MessageFormat::Markdown,
));
```

### Через DI

```php
use RuMaxBot\Contracts\RuMaxBotClientInterface;
use RuMaxBot\DataTransferObjects\SendMessageData;

final readonly class DeployNotifier
{
    public function __construct(
        private RuMaxBotClientInterface $ruMaxBot,
    ) {}

    public function notifySuccess(): void
    {
        $this->ruMaxBot->send(new SendMessageData(text: 'Deploy OK'));
    }
}
```

## API

Пакет отправляет `POST /api/v1/messages`:

```json
{
    "text": "Сообщение",
    "format": "markdown"
}
```

Поддерживаемые форматы: `markdown`, `html`.

Успешный ответ: `202 Accepted`:

```json
{
    "message": "Message queued successfully.",
    "data": {
        "id": "019e79d1-...",
        "status": "pending"
    }
}
```

## Тесты

### В Laravel-проекте (после `composer require`)

Автотесты с моком HTTP — без реального API:

```bash
php artisan test --filter RuMaxBot
```

Или:

```bash
vendor/bin/phpunit --filter RuMaxBot
```

### В папке пакета

```bash
composer install
vendor/bin/phpunit
```

## Лицензия

MIT
