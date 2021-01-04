<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Name extends Model
{
    //
	protected $primaryKey = 'pid';
	protected $keyType = 'string';
	public $timestamps = false;
	protected $fillable = ['pid', 'name', 'highest', 'priority', 'ioc', 'first', 'last', 'gender', 'rank'];
}
