<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'iot' => [
    'queue_driver' => env('IOT_QUEUE_DRIVER', 'redis'), // redis | sqs
    'queue_name' => env('IOT_QUEUE_NAME', 'ademnea-iot-queue'),
    'dead_letter_name' => env('IOT_QUEUE_NAME', 'ademnea-iot-queue') . '-dlq',
    'redis_connection' => env('IOT_REDIS_CONNECTION', 'default'),
    'max_delivery_attempts' => (int) env('IOT_MAX_DELIVERY_ATTEMPTS', 5),
    'sqs_queue_url' => env('IOT_SQS_QUEUE_URL'),
    'aws_region' => env('AWS_DEFAULT_REGION', 'eu-west-1'),
],

];
