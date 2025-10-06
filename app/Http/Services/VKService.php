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

        $text = trim($text);

        if (!is_numeric($text)) {
            if (strpos($text, 'vk.ru') !== false || strpos($text, 'vk.ru') !== false || strpos($text, 'id') !== false) {
                $this->sendMessage($chatId, 'Пожалуйста, пришлите id пользователя или ссылку на его страничку');
                die;
            }
        }

        $vkId = $this->clearVkUrl($text);

        if (!is_numeric($vkId)) {

            $token = VkUsers::first()->token;

            $response = $this->getUserInfo($vkId, $token);
            $response = json_decode($response, true);

            if (isset($response['response'][0]['id'])) {
                $vkId = $response['response'][0]['id'];
            }

            if (!is_numeric($vkId)) {
                $this->sendMessage($chatId, 'Пожалуйста, пришлите id пользователя или ссылку на его страничку');
                die;
            }
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
                    'tg_id' => $tgUser->id,
                    'vk_id' => $vkId,
                ],[
                ]
            );

            if ($vkUser->wasRecentlyCreated) {
                $this->sendMessage($chatId, 'Пользователь успешно добавлен в отслеживание. Как только у него появятся новые друзья или он кого-то удалит, бот пришлет вам уведомление.');
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
        $params = [
            'user_id' => $userId,
            'v' => 5.199,
            'access_token' => $token,
        ];

        $queryString = http_build_query($params);

        $url = 'https://api.vk.ru/method/friends.get?'.$queryString;

        $data = file_get_contents($url);

        return $data;
    }

    public function getUserInfo($userId, $token)
    {
        $params = [
            'user_ids' => $userId,
            'v' => 5.199,
            'access_token' => $token,
        ];

        $queryString = http_build_query($params);

        $url = 'https://api.vk.ru/method/users.get?'.$queryString;

        $data = file_get_contents($url);

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
            "id",
            "/"];

        $replace = "";

        // Делаем проверку на массив
        if (is_array($url))
        {

            $url = array_diff($url, array('',' '));

            foreach($url as $key => &$value){
                $value = str_ireplace($delete, $replace, $value);
            }

            return $url;
        }

        $url = str_replace($delete, $replace, $url);
        $url = trim($url);

        return $url;
    }
}
