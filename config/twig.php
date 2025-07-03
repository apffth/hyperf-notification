<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    /*
    |--------------------------------------------------------------------------
    | Twig 模板引擎配置
    |--------------------------------------------------------------------------
    |
    | 这里配置 Twig 模板引擎的相关设置。
    |
    */

    // 模板路径配置
    'paths' => [
        // 邮件模板路径
        'emails' => BASE_PATH . '/storage/emails',
        // 通用模板路径
        'views' => BASE_PATH . '/storage/views',
    ],

    // Twig 环境选项
    'options' => [
        // 是否启用调试模式
        'debug' => env('APP_DEBUG', false),

        // 是否启用缓存
        'cache' => env('TWIG_CACHE', true),

        // 缓存路径
        'cache_path' => BASE_PATH . '/runtime/twig/cache',

        // 是否启用自动重载（开发环境）
        'auto_reload' => env('TWIG_AUTO_RELOAD', true),

        // 是否启用严格变量
        'strict_variables' => true,

        // 字符集
        'charset' => 'UTF-8',

        // 默认时区
        'timezone' => env('APP_TIMEZONE', 'Asia/Taipei'),
    ],

    // 全局变量
    'globals' => [
        'app_name' => env('APP_NAME', 'Hyperf App'),
    ],

    // 扩展配置
    'extensions' => [
        // 可以在这里注册自定义的 Twig 扩展
    ],

    // 邮件模板配置
    'email_templates' => [
        // 默认邮件布局模板
        'layout' => 'emails/layout.html.twig',

        // 邮件模板路径
        'path' => 'emails',

        // 邮件主题前缀
        'subject_prefix' => env('MAIL_SUBJECT_PREFIX', ''),

        // 邮件主题后缀
        'subject_suffix' => env('MAIL_SUBJECT_SUFFIX', ''),
    ],
];
