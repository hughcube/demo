<?php

return [
    /**
     * default config
     */
    'defaults' => [],

    'robots' => [
        'default' => [
            'enabled' => env('DING_TALK_ROBOT_ENABLED', true),
            'url' => env('DING_TALK_ROBOT_URL', ''),
            'secret' => env('DING_TALK_ROBOT_SECRET', ''),
        ],
    ],
];
