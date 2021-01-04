<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\User;

class VerificationCode extends Model
{
    //
	use Notifiable, SoftDeletes;

    public function routeNotificationFor()
    {
        return $this->account;
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
