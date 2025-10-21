<?php

namespace App\Services\DeployFlow\Steps;

class NotifyStepExecutor extends BaseStepExecutor
{
    protected function performExecution(): array
    {
        $channels = $this->getConfig('channels', ['email']);
        $message = $this->getConfig('message', 'Deployment completed successfully');
        $includeLogs = $this->getConfig('include_logs', false);

        $this->log('info', "Sending notifications to: " . implode(', ', $channels));
        $this->log('info', "Message: {$message}");
        
        // Simulate notification sending
        $notificationResult = $this->simulateNotifications($channels, $message, $includeLogs);
        
        return [
            'channels' => $channels,
            'message' => $message,
            'include_logs' => $includeLogs,
            'notifications_sent' => $notificationResult['sent'],
            'notifications_failed' => $notificationResult['failed'],
            'delivery_status' => $notificationResult['status'],
        ];
    }

    public function getDescription(): string
    {
        return 'Send Notifications';
    }

    public function getConfigSchema(): array
    {
        return [
            'channels' => [
                'type' => 'array',
                'required' => false,
                'default' => ['email'],
                'description' => 'Notification channels',
                'options' => ['email', 'slack', 'discord', 'webhook', 'sms'],
            ],
            'message' => [
                'type' => 'string',
                'required' => false,
                'default' => 'Deployment completed successfully',
                'description' => 'Notification message',
            ],
            'include_logs' => [
                'type' => 'boolean',
                'required' => false,
                'default' => false,
                'description' => 'Include execution logs in notification',
            ],
        ];
    }

    protected function simulateNotifications(array $channels, string $message, bool $includeLogs): array
    {
        $sent = 0;
        $failed = 0;
        
        foreach ($channels as $channel) {
            $this->log('info', "Sending notification via {$channel}...");
            
            // Simulate success/failure
            if (rand(1, 10) > 1) { // 90% success rate
                $this->log('info', "✓ Notification sent via {$channel}");
                $sent++;
            } else {
                $this->log('warning', "✗ Failed to send notification via {$channel}");
                $failed++;
            }
            
            usleep(100000); // 0.1 seconds
        }
        
        return [
            'sent' => $sent,
            'failed' => $failed,
            'status' => $failed === 0 ? 'success' : 'partial',
        ];
    }
}
