<?php

return [

    'default' => env('QUEUE_CONNECTION', 'sync'),

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('QUEUE_REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => 'localhost',
            'queue' => 'default',
            'retry_after' => 90,
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],
    ],

    'batching' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'job_batches',
    ],

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],

    'queues' => [
        'default' => [
            'connection' => env('QUEUE_CONNECTION', 'redis'),
            'queue' => 'default',
            'tries' => 3,
            'timeout' => 60,
        ],
        'exchange_rates' => [
            'connection' => env('QUEUE_CONNECTION', 'redis'),
            'queue' => 'exchange_rates',
            'tries' => 5,
            'timeout' => 120,
        ],
        'locale_sync' => [
            'connection' => env('QUEUE_CONNECTION', 'redis'),
            'queue' => 'locale_sync',
            'tries' => 3,
            'timeout' => 60,
        ],
        'cache_warmup' => [
            'connection' => env('QUEUE_CONNECTION', 'redis'),
            'queue' => 'cache_warmup',
            'tries' => 2,
            'timeout' => 300,
        ],
    ],

];
