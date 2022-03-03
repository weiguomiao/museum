<?php
// 事件定义文件
return [
    'bind'      => [

    ],

    'listen'    => [
        'AppInit'  => [],
        'HttpRun'  => [
            \app\common\event\Cors::class,
        ],
        'HttpEnd'  => [],
        'LogLevel' => [],
        'LogWrite' => [],
    ],
];
