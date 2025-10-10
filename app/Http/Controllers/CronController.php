<?php

namespace App\Http\Controllers;

use App\Http\Services\VKService;
use App\Models\History;
use App\Models\VkUsers;
use Illuminate\Http\Request;

class CronController extends Controller
{
    public function __construct(
        protected readonly VkService $vkService
    )
    {

    }
    public function __invoke()
    {
        $users = VkUsers::all();

        foreach($users as $user){
            sleep(1);
            $userFriends = $this->vkService->getUserFriends($user->vk_id, $user->token);

            $userFriendsPrettyData = json_decode($userFriends, true);

            if ($user->data) {
                $oldData = json_decode($user->data, true)['response']['items'];

                $newFriends = array_diff($userFriendsPrettyData['response']['items'], $oldData);
                $deleteFriends = array_diff($oldData, $userFriendsPrettyData['response']['items']);

                if (!empty($newFriends)) {
                    foreach ($newFriends as $newFriend) {
                        History::create([
                            'owner_id' => $user->id,
                            'action' => 1,
                            'data' => $newFriend,
                        ]);

                        $userInfo = $this->vkService->getUserInfo($newFriend, $user->token);
                        sleep(1);
                        $userInfoPrettyData = json_decode($userInfo, true);

                        $userName = $userInfoPrettyData['response'][0]['first_name'] . ' ' . $userInfoPrettyData['response'][0]['last_name'];

                        $msg = 'У пользователя ' . $user->name . ' добавлен новый друг ' . $userName . ' https://vk.com/id' . $newFriend;
                        $this->vkService->sendMessage($user->TgUser->chat_id, $msg);
                    }
                }

                if (!empty($deleteFriends)) {
                    foreach ($deleteFriends as $deleteFriend) {
                        History::create([
                            'owner_id' => $user->id,
                            'action' => 2,
                            'data' => $deleteFriend,
                        ]);

                        $userInfo = $this->vkService->getUserInfo($deleteFriend, $user->token);
                        sleep(1);
                        $userInfoPrettyData = json_decode($userInfo, true);

                        $userName = $userInfoPrettyData['response'][0]['first_name'] . ' ' . $userInfoPrettyData['response'][0]['last_name'];

                        $msg = 'У пользователя ' . $user->name . ' удален из друзей ' . $userName . ' https://vk.com/id' . $deleteFriend;
                        $this->vkService->sendMessage($user->TgUser->chat_id, $msg);
                    }
                }
            }

            if ($userFriendsPrettyData['response']['count'] > 0) {
                $user->data = $userFriends;
                $user->save();
            }

            file_put_contents('log.txt', date('d-m-Y-H-i-s') . " [ check ok ] " . FILE_APPEND);
        }
    }
}
