<?php

namespace App\Http\Controllers;

use App\Http\Services\VKService;
use App\Services\StepService;
use Longman\TelegramBot\Telegram;

class TelegramController extends Controller
{
    public function __construct(
        public readonly VKService $vkService,
    )
    {}

    public function __invoke()
    {
        // Получаем сырые POST-данные от Telegram (JSON)
        $input = file_get_contents('php://input');
        $update = json_decode($input, true);

        // Проверка на пустой запрос (Telegram иногда шлет для теста)
        if (!$update || !isset($update['update_id']) || !isset($update['message'])) {
            $this->vkService->error();
        }

        $this->vkService->handle($update['message']);
    }


    public function setWebhook()
    {
        $botToken = env('TELEGRAM_TOKEN');
        $botUsername = 'pingcheck';
        $webhook_url = 'https://ping-check.ru/bot'; // Полный HTTPS URL к bot.php

        $telegram = new Telegram($botToken, $botUsername);
        $result = $telegram->setWebhook($webhook_url);

        if ($result->isOk()) {
            echo "Webhook установлен успешно: " . $result->getDescription() . "\n";
        } else {
            echo "Ошибка установки webhook: " . $result->getDescription() . "\n";
        }
    }

}
