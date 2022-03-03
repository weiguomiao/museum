<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        // 接口文档
        'interface_doc' => \mytools\annotation\interfacedoc\InterfaceDocCommand::class,
        // RBAC权限
        'auth' => \mytools\annotation\authmanage\AuthManagerCommand::class,
        // 系统安装
        'setup' => \setup\SetUpCommand::class,
        //生成预约平台任务
        'task' => 'app\command\Task',
    ],
];
