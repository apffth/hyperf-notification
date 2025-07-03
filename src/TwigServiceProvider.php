<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification;

use Hyperf\Contract\ContainerInterface;
use Hyperf\Di\Annotation\Inject;
use Symfony\Bridge\Twig\Mime\BodyRenderer;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\BodyRendererInterface;
use Twig\Environment;
use Twig\Extension\CoreExtension;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

use function Hyperf\Config\config;

class TwigServiceProvider
{
    #[Inject]
    protected ContainerInterface $container;

    /**
     * 创建 Twig 环境实例.
     */
    public function createTwigEnvironment(): Environment
    {
        $config = config('twig', []);

        // 创建文件系统加载器
        $loader = new FilesystemLoader();

        // 添加模板路径
        $paths = $config['paths'] ?? [];
        foreach ($paths as $namespace => $path) {
            if (is_numeric($namespace)) {
                // 默认命名空间
                $loader->addPath($path);
            } else {
                // 命名空间路径
                $loader->addPath($path, $namespace);
            }
        }

        // 创建 Twig 环境
        $options = $config['options'] ?? [];
        $twig    = new Environment($loader, [
            'cache'            => $options['cache'] ? $options['cache_path'] : false,
            'debug'            => $options['debug']                       ?? false,
            'auto_reload'      => $options['auto_reload']           ?? false,
            'strict_variables' => $options['strict_variables'] ?? false,
            'charset'          => $options['charset']                   ?? 'UTF-8',
        ]);

        // 设置时区
        if (isset($options['timezone'])) {
            $twig->getExtension(CoreExtension::class)->setTimezone($options['timezone']);
        }

        // 添加调试扩展（仅在调试模式下）
        if ($options['debug'] ?? false) {
            $twig->addExtension(new DebugExtension());
        }

        // 添加全局变量
        $globals = $config['globals'] ?? [];
        foreach ($globals as $name => $value) {
            $twig->addGlobal($name, $value);
        }

        // 注册自定义扩展
        $this->registerExtensions($twig, $config['extensions'] ?? []);

        return $twig;
    }

    /**
     * 创建 BodyRenderer 实例
     * 这是 Symfony Mailer 与 Twig 集成的关键组件.
     */
    public function createBodyRenderer(): BodyRendererInterface
    {
        $twig = $this->createTwigEnvironment();
        return new BodyRenderer($twig);
    }

    /**
     * 渲染 TemplatedEmail
     * 这是正确使用 Symfony Mailer + Twig 的方式.
     */
    public function renderTemplatedEmail(TemplatedEmail $email): void
    {
        $bodyRenderer = $this->createBodyRenderer();
        $bodyRenderer->render($email);
    }

    /**
     * 渲染邮件模板
     */
    public function renderEmailTemplate(string $template, array $context = []): string
    {
        $twig = $this->createTwigEnvironment();
        return $twig->render($template, $context);
    }

    /**
     * 检查模板是否存在.
     */
    public function templateExists(string $template): bool
    {
        $twig = $this->createTwigEnvironment();
        return $twig->getLoader()->exists($template);
    }

    /**
     * 注册自定义 Twig 扩展.
     */
    protected function registerExtensions(Environment $twig, array $extensions): void
    {
        foreach ($extensions as $extension) {
            if (is_string($extension) && class_exists($extension)) {
                $twig->addExtension(new $extension());
            } elseif (is_object($extension)) {
                $twig->addExtension($extension);
            }
        }
    }
}
