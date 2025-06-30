<?php

namespace Examples\CustomChannels;

use Hyperf\Notification\Channels\ChannelInterface;
use Hyperf\Notification\Notification;

/**
 * 示例：自定义短信通知渠道
 *
 * 这个示例展示了如何创建自定义的通知渠道
 */
class SmsChannel implements ChannelInterface
{
    /**
     * 短信服务配置
     */
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'api_key' => '',
            'api_secret' => '',
            'sign_name' => '',
            'template_code' => '',
        ], $config);
    }

    /**
     * 发送通知
     */
    public function send($notifiable, Notification $notification)
    {
        // 获取短信内容
        $message = $notification->toSms($notifiable);

        // 获取手机号码
        $phone = $this->getPhoneNumber($notifiable);

        if (empty($phone)) {
            throw new \InvalidArgumentException('Phone number not found for notifiable.');
        }

        // 发送短信
        $this->sendSms($phone, $message);
    }

    /**
     * 获取手机号码
     */
    protected function getPhoneNumber($notifiable): ?string
    {
        // 如果 notifiable 有 routeNotificationFor 方法，使用它
        if (method_exists($notifiable, 'routeNotificationFor')) {
            $phone = $notifiable->routeNotificationFor('sms');
            if ($phone) {
                return $phone;
            }
        }

        // 尝试从 notifiable 的属性中获取手机号码
        if (property_exists($notifiable, 'phone')) {
            return $notifiable->phone;
        }

        if (property_exists($notifiable, 'mobile')) {
            return $notifiable->mobile;
        }

        if (property_exists($notifiable, 'phone_number')) {
            return $notifiable->phone_number;
        }

        return null;
    }

    /**
     * 发送短信
     */
    protected function sendSms(string $phone, array $message): void
    {
        // 这里实现具体的短信发送逻辑
        // 例如：调用阿里云短信服务、腾讯云短信服务等

        $content = $message['content'] ?? '';
        $template = $message['template'] ?? '';
        $data = $message['data'] ?? [];

        // 模拟发送短信
        echo "📱 发送短信到: {$phone}\n";
        echo "📝 内容: {$content}\n";
        echo "📋 模板: {$template}\n";
        echo "📊 数据: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
        echo "✅ 短信发送成功！\n\n";

        // 实际项目中，这里应该调用真实的短信服务 API
        // 例如：
        // $this->callSmsApi($phone, $template, $data);
    }

    /**
     * 调用短信服务 API（示例）
     */
    protected function callSmsApi(string $phone, string $template, array $data): void
    {
        // 这里实现具体的 API 调用
        // 例如使用阿里云短信服务：
        /*
        $client = new \AlibabaCloud\Client\AlibabaCloud;
        $client::accessKeyClient($this->config['api_key'], $this->config['api_secret'])
            ->regionId('cn-hangzhou')
            ->asDefaultClient();

        try {
            $result = \AlibabaCloud\Client\AlibabaCloud::rpc()
                ->product('Dysmsapi')
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'PhoneNumbers' => $phone,
                        'SignName' => $this->config['sign_name'],
                        'TemplateCode' => $template,
                        'TemplateParam' => json_encode($data),
                    ],
                ])
                ->request();

            $response = $result->toArray();

            if ($response['Code'] !== 'OK') {
                throw new \Exception('SMS sending failed: ' . $response['Message']);
            }
        } catch (\Exception $e) {
            throw new \Exception('SMS API error: ' . $e->getMessage());
        }
        */
    }
}
