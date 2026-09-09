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
  'fcm' => [
        'project_id' => env('FCM_PROJECT_ID'),
        'access_token' => env('FCM_ACCESS_TOKEN'),
    ],

    'iot' => [
    'queue_driver' => env('IOT_QUEUE_DRIVER', 'sqs'), // redis | sqs
    'queue_name' => env('IOT_QUEUE_NAME', 'ademnea-iot-ingest'),
    'dead_letter_name' => env('IOT_QUEUE_NAME', 'ademnea-iot-ingest') . '-dlq',
    'redis_connection' => env('IOT_REDIS_CONNECTION', 'default'),
    'max_delivery_attempts' => (int) env('IOT_MAX_DELIVERY_ATTEMPTS', 5),
    'sqs_queue_url' => env('IOT_SQS_QUEUE_URL'),
    'aws_region' => env('AWS_DEFAULT_REGION', 'eu-north-1'),
],


's3' => [
    'driver' => 's3',
    'key' => env('AWS_S3_ACCESS_KEY_ID'),
    'secret' => env('AWS_S3_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'),
    'endpoint' => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    
],


    'africastalking' => [
        'api_key' => env('AFRICASTALKING_API_KEY'),
        'username' => env('AFRICASTALKING_USERNAME'),
           ],
];
