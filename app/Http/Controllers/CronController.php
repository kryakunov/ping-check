<?php

namespace App\Http\Controllers;

use App\Http\Services\VKService;
use App\Models\History;
use App\Models\VkUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CronController extends Controller
{
    public function __construct(
        protected readonly VkService $vkService
    )
    {
    }

    public function __invoke()
    {

        $users = VkUsers::where(['active' => 1])->whereNotNull('token')->get();

        foreach($users as $user){
            $userFriends = $this->vkService->getUserFriends($user->vk_id, $user->token);

            // Если ошибка
            if (isset($userFriends['error']) || !isset($userFriends['response']))
            {
                // Приватный профиль
                if (isset($userFriends['error']['error_code']) && $userFriends['error']['error_code'] == 30) {
                    $msg = 'У пользователя ' . $user->name . ' закрытый профиль. Не могу получить список друзей';
                    $this->vkService->sendMessage($user->TgUser->chat_id, $msg);

                    $user->private = 1;
                    $user->save();
                }

                Log::error($userFriends);
                continue;
            }

            // Если друзья есть в базе
            if ($user->data)
            {
                $oldFriends = json_decode($user->data, true)['response']['items'];
                $currentFriends = $userFriends['response']['items'];

                $newFriends = array_diff($currentFriends, $oldFriends);
                $deleteFriends = array_diff($oldFriends, $currentFriends);

                if (!empty($newFriends)) {
                    foreach ($newFriends as $newFriend) {

                        $userName = '[ имя ]';

                        // TODO переписать запрос на получение массовой информации о пользователях
                        $userInfo = $this->vkService->getUserInfo($newFriend, $user->token);
                        if (isset($userInfo['response'])) {
                            $userName = $userInfo['response'][0]['first_name'] . ' ' . $userInfo['response'][0]['last_name'];
                        }

                        $msg = 'У пользователя ' . $user->name . ' добавлен новый друг ' . $userName . ' https://vk.com/id' . $newFriend;
                        $this->vkService->sendMessage($user->TgUser->chat_id, $msg);

                        History::create([
                            'owner_id' => $user->id,
                            'action' => 1,
                            'data' => $newFriend,
                        ]);
                    }
                }

                if (!empty($deleteFriends)) {
                    foreach ($deleteFriends as $deleteFriend) {

                        $userName = '[ имя ]';

                        // TODO переписать запрос на получение массовой информации о пользователях
                        $userInfo = $this->vkService->getUserInfo($deleteFriend, $user->token);
                        if (isset($userInfo['response'])) {
                            $userName = $userInfo['response'][0]['first_name'] . ' ' . $userInfo['response'][0]['last_name'];
                        }

                        $msg = 'У пользователя ' . $user->name . ' удален из друзей ' . $userName . ' https://vk.com/id' . $deleteFriend;
                        $this->vkService->sendMessage($user->TgUser->chat_id, $msg);

                        History::create([
                            'owner_id' => $user->id,
                            'action' => 2,
                            'data' => $deleteFriend,
                        ]);
                    }
                }
            }

            if ($userFriends['response']['count'] > 0)
            {
                $user->data = $userFriends;
                $user->save();
            }

            file_put_contents('log.txt', date('d-m-Y-H-i-s') . " [ check ok ] " . FILE_APPEND);
        }
    }
}
