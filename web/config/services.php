<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => 'us-east-1',
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

	'facebook' => [
		'client_id' => '1931636373733391',
		'client_secret' => '7b2cb1bbb7c15f016e8a26dd0808f796',
		'redirect' => 'https://www.rank-tennis.com/login/facebook/callback',
	],

	'baidu' => [
		'client_id' => 'omgsA2i9BSrxEc4qnGNK33PA',
		'client_secret' => 'KPPQCr8ivQvPo60q0FvLoa27v66PgaUL',
		'redirect' => 'https://www.rank-tennis.com/login/baidu/callback',
	],

	'weibo' => [
		'client_id' => '2305556995',
		'client_secret' => '21b6562c4d7ec2219c8b7e5cc58ab3b8',
		'redirect' => 'https://www.rank-tennis.com/login/weibo/callback',
	],

	'google' => [
		'client_id' => '133520891610-aehifa5d39dtmagqg4cdvbqk52bfqu6n.apps.googleusercontent.com',
		'client_secret' => 'fqFibpjkFS1rycbHMBiQv6zp',
		'redirect' => 'https://www.rank-tennis.com/login/google/callback',
	],

	'weixin' => [
		'client_id' => '',
		'client_secret' => '',
		'redirect' => 'https://www.rank-tennis.com/login/weixin/callback',
	],

	'wangqiu' => [
		'client_id' => '',
		'client_secret' => '',
		'redirect' => 'https://www.rank-tennis.com/login/wangqiu/callback',
	],
];
