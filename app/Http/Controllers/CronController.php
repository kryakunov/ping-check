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

            $params = [
                'user_id' => $user->vk_id,
                'v' => 5.199,
                'access_token' => $user->token,
            ];

            $queryString = http_build_query($params);

            $url = 'https://api.vk.ru/method/friends.get?'.$queryString;

            $data = file_get_contents($url);

            $prettyData = json_decode($data, true);

            if ($user->data) {
                $oldData = json_decode($user->data, true)['response']['items'];

                $newFriends = array_diff($oldData, $prettyData['response']['items']);

                if (!empty($newFriends)) {
                    foreach ($newFriends as $newFriend) {
                        History::create([
                            'owner_id' => $user->id,
                            'action' => 1,
                            'data' => $newFriend,
                        ]);

                        $this->vkService->sendMessage($user->TgUser->chat_id, 'Добавлен новый друг ' . $newFriend);
                    }
                }
            }

            $user->data = $data;
            $user->save();


        }
    }
}
