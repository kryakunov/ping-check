<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VkUsers extends Model
{
    protected $table = 'vk_users';
    protected $guarded = [];

    public function TgUser()
    {

        return $this->hasOne(TgUsers::class , 'id' , 'tg_id');
    }
}
