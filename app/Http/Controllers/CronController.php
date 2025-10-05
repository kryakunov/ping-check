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

            $userFriends = $this->vkService->getUserFriends($user->vk_id, $user->token);

            $userFriendsPrettyData = json_decode($userFriends, true);

            if ($user->data) {
                $oldData = json_decode($user->data, true)['response']['items'];

                $newFriends = array_diff($oldData, $userFriendsPrettyData['response']['items']);
                $deleteFriends = array_diff($userFriendsPrettyData['response']['items'], $oldData);

                if (!empty($newFriends)) {
                    foreach ($newFriends as $newFriend) {
                        History::create([
                            'owner_id' => $user->id,
                            'action' => 1,
                            'data' => $newFriend,
                        ]);

                        $userInfo = $this->vkService->getUserInfo($newFriend, $user->token);
                        $userInfoPrettyData = json_decode($userInfo, true);

                        $userName = $userInfoPrettyData['response'][0]['first_name'] . ' ' . $userInfoPrettyData['response'][0]['last_name'];

                        $this->vkService->sendMessage($user->TgUser->chat_id, 'Добавлен новый друг ' . $userName . ' https://vk.com/id' . $newFriend);
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
                        $userInfoPrettyData = json_decode($userInfo, true);

                        $userName = $userInfoPrettyData['response'][0]['first_name'] . ' ' . $userInfoPrettyData['response'][0]['last_name'];

                        $this->vkService->sendMessage($user->TgUser->chat_id, 'Удален пользователь ' . $userName . ' https://vk.com/id' . $deleteFriend);
                    }
                }
            }

            if ($userFriendsPrettyData['response']['count'] > 0) {
                $user->data = $userFriends;
                $user->save();
            }

        }
    }
}
