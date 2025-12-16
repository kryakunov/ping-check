<?php

namespace App\Http\Services;

use App\Models\TgUsers;
use App\Models\User;
use App\Models\VkUsers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VKService
{
    public function __construct(
    )
    {}

    public function handle($message)
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $text = trim($text);
        $userName = $message['from']['first_name'] ?? ($message['from']['username'] ?? 'Unknown');
        $userLogin = $message['from']['username'] ?? null;
        $userId = $message['from']['id'] ?? '';

        if (empty($text)) {
            $this->error();
        }

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

        Log::error('[ userId: ' . $userId . ' userLogin: ' . $userLogin . ' ] Text: ' . $text);


        if (!is_numeric($text)) {
            if (
                strpos($text, 'vk.ru') === false &&
                strpos($text, 'vk.com') === false &&
                strpos($text, 'id') === false
            ) {
                $this->sendMessage($chatId, 'Пожалуйста, пришлите id пользователя или ссылку на его страничку');
                die;
            }
        }

        $vkId = $this->clearVkUrl($text);
        $vkUserName = '';

        if (!is_numeric($vkId)) {

            $token = VkUsers::first()->token;

            $response = $this->getUserInfo($vkId, $token);


            if (isset($response['response'][0]['id'])) {
                $vkId = $response['response'][0]['id'];
                $vkUserName = $response['response'][0]['first_name'] . ' ' . $response['response'][0]['last_name'];
            }

            if (!is_numeric($vkId)) {
                $this->sendMessage($chatId, 'Пожалуйста, пришлите id пользователя или ссылку на его страничку');
                die;
            }

            if (isset($response['response'][0]['can_access_closed']) && $response['response'][0]['can_access_closed'] == false) {
                $msg = 'У пользователя ' . $vkUserName . ' закрытый профиль. Не могу получить список друзей';
                $this->sendMessage($chatId, $msg);
                die;
            }
        }

        try {

            $vkUser = VkUsers::updateOrCreate(
                [
                    'tg_id' => $tgUser->id,
                    'vk_id' => $vkId,
                ],[
                    'name' => $vkUserName,
                    'active' => 1,
                ]
            );

            if ($vkUser->wasRecentlyCreated) {
                $this->sendMessage($chatId, 'Пользователь '.$vkUserName.' успешно добавлен в отслеживание. Как только у него появятся новые друзья или он кого-то удалит, бот пришлет вам уведомление.');
            } else {
                $this->sendMessage($chatId, 'Этот пользователь уже отслеживается');
            }


        } catch (\Exception $e) {
            file_put_contents('errors.txt', $e->getMessage() . "\n" . $userName . "\n" . $userId);
        }

    }

    public function sendMessage($chatId, $message): bool
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

    public function getUserFriends($userId, $token)
    {
        sleep(1);

        $params = [
            'user_id' => $userId,
            'v' => 5.199,
            'access_token' => $token,
        ];

        $queryString = http_build_query($params);

        $url = 'https://api.vk.ru/method/friends.get?'.$queryString;

        $data = json_decode(file_get_contents($url), true);

        return $data;
    }

    public function getUserInfo($userId, $token)
    {
        sleep(2);

        $params = [
            'user_ids' => $userId,
            'v' => 5.199,
            'fields' => 'is_closed',
            'access_token' => $token,
        ];

        $queryString = http_build_query($params);

        $url = 'https://api.vk.ru/method/users.get?'.$queryString;

        $data =  json_decode(file_get_contents($url), true);

        return $data;
    }

    public function clearVkUrl($url)
    {
        $delete = [
            "vk.ru",
            "vk.com",
            "https://",
            "http://",
            " ",
            "/"];

        $replace = "";

        $url = str_replace($delete, $replace, $url);
        $url = trim($url);

        // удаляем префикс id и оставляем только цифры
        if (strlen($url) > 2 && $url[0] == 'i' && $url[1] == 'd' && is_numeric($url[2])) {
            $url = ltrim($url, 'id');
        }

        return $url;
    }
}
