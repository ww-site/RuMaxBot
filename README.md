# RuMaxBot

Composer-пакет для быстрого подключения **Laravel 12** к API [rumaxbot.ru](https://rumaxbot.ru/) (MAX Messenger).

Репозиторий: [github.com/ww-site/RuMaxBot](https://github.com/ww-site/RuMaxBot)

## Установка

```bash
composer require ww-site/rumaxbot
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
        "ww-site/rumaxbot": "@dev"
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

## Тестирование

Пакет поставляется с **явными тестами** на двух уровнях.

### Живая проверка после установки

Отправка **реального** сообщения в MAX (нужен `RUMAXBOT_API_TOKEN` в `.env`):

```bash
php artisan rumaxbot:test
php artisan rumaxbot:test --message="Привет из Laravel"
php artisan rumaxbot:test --message="<b>HTML</b>" --format=html
```

Команда: `rumaxbot:test` (`SendTestMessageCommand`).

Успех:

```
Sending test message via RuMaxBot...
Message queued successfully. Status: pending
Message ID: 019e79d1-...
```

### Автотесты пакета (PHPUnit, без реального API)

HTTP мокается через `Http::fake()` — к rumaxbot.ru запросы не уходят.

```bash
composer install
vendor/bin/phpunit
```

| Файл | Покрытие |
|------|----------|
| `tests/Unit/RuMaxBotClientTest.php` | клиент: успешная отправка, парсинг `data.status`, ошибка 401, пустой токен, legacy-формат ответа |
| `tests/Feature/SendTestMessageCommandTest.php` | команда `rumaxbot:test`: успех, `--message`, ошибка API |

### Автотесты в Laravel-проекте

Если в проекте есть `tests/Feature/RuMaxBotTest.php` (как в дистрибутиве laracomposer):

```bash
php artisan test --filter RuMaxBot
```

Проверяется регистрация в DI, команда, клиент, ошибки без токена и при 401.

## Лицензия

MIT
