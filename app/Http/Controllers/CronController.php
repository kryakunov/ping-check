<?php

namespace App\Http\Controllers;

use App\Models\VkUsers;
use Illuminate\Http\Request;

class CronController extends Controller
{
    public function check()
    {
        $users = VkUsers::all();

        dd($users);
    }
}
