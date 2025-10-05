<?php

namespace App\Http\Services;

use App\Models\TgUsers;
use App\Models\User;
use App\Models\VkUsers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class VKService
{
    public function __construct(

    )
    {}

    public function handle($message)
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
       // $caption = $message['caption'] ?? 'no';
        $userName = $message['from']['first_name'] ?? ($message['from']['username'] ?? 'Unknown');
        $userLogin = $message['from']['username'] ?? null;
        $userId = $message['from']['id'] ?? '';


        if (empty($text)) {
            $this->error();
        }


        $vkId = trim(ltrim($text, 'id'));
        if (!is_numeric($vkId)) {
            $this->sendMessage($chatId, 'Неверный id');
            die;
        }

        try {

            $tgUser = TgUsers::updateOrCreate(
                [
                    'chat_id' => $chatId,
                    'user_id' => $userId,
                ],
                [
                    'name' => $userName,
                    'user_login' => $userLogin,
                ]
            );

            $vkUser = VkUsers::updateOrCreate(
                [
                    'user_id' => $userId,
                    'vk_id' => $vkId,
                ],[
                ]
            );

            if ($vkUser->wasRecentlyCreated) {
                $this->sendMessage($chatId, 'Пользователь успешно добавлен в отслеживание');
            } else {
                $this->sendMessage($chatId, 'Этот пользователь уже отслеживается');
            }


        } catch (\Exception $e) {
            file_put_contents('errors.txt', $e->getMessage() . "\n" . $userName . "\n" . $userId);
        }



    }

    protected function sendMessage($chatId, $message): bool
    {
        $botToken = env('TELEGRAM_TOKEN');
        $botApiUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";

        Http::post($botApiUrl, [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ]);

        return true;
    }

    public function error(): void
    {
        http_response_code(200);
        echo 'OK';
        exit;
    }
}
