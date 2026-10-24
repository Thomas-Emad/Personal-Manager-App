<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Ikromjon\LocalNotifications\Data\NotificationOptions;
use Ikromjon\LocalNotifications\Facades\LocalNotifications;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Get active driver: 'native', 'mock', or 'auto'.
     */
    public function getDriver(): string
    {
        return (string) config('services.notifications.driver', env('NOTIFICATION_DRIVER', 'auto'));
    }

    /**
     * Check if the NativePHP mobile bridge is active.
     */
    public function isNativeAvailable(): bool
    {
        return function_exists('nativephp_call');
    }

    /**
     * Schedule a local notification.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function schedule(
        string $id,
        string $title,
        string $body,
        int $delaySeconds = 10,
        array $data = []
    ): array {
        $driver = $this->getDriver();

        // Production guardrail: Mock driver must never be used in a production environment
        if ((app()->environment('production') || config('app.env') === 'production') && $driver === 'mock') {
            throw new Exception(
                'Production Security Violation: Mock notification driver cannot silently be used in production.'
            );
        }

        // Enforce deterministic hash-safe ID format to prevent collisions in Android PendingIntent
        $safeId = $this->generateDeterministicId($id);

        if ($driver !== 'mock' && $this->isNativeAvailable()) {
            $options = new NotificationOptions(
                id: $safeId,
                title: $title,
                body: $body,
                delay: $delaySeconds,
                sound: true,
                data: array_merge($data, [
                    'original_id' => $id,
                    'scheduled_at' => now()->toISOString(),
                    'delay_seconds' => $delaySeconds,
                ])
            );

            $result = LocalNotifications::schedule($options);

            // Check if native bridge returned NO_DEVICE
            $hasDeviceError = isset($result['status']) && $result['status'] === 'error' && ($result['code'] ?? '') === 'NO_DEVICE';

            if (! $hasDeviceError || $driver === 'native') {
                Log::info("Native notification scheduled: {$safeId}", [
                    'result' => $result,
                    'delay' => $delaySeconds,
                ]);

                return [
                    'success' => ! $hasDeviceError,
                    'driver' => 'native',
                    'notification_id' => $safeId,
                    'delay_seconds' => $delaySeconds,
                    'target_time' => now()->addSeconds($delaySeconds)->toDateTimeString(),
                    'native_result' => $result,
                ];
            }
        }

        // Dev / Browser preview fallback when driver is 'mock' or 'auto' without connected device
        Log::info("Dev/Test mock notification scheduled: {$safeId}", [
            'id' => $safeId,
            'title' => $title,
            'body' => $body,
            'delay' => $delaySeconds,
        ]);

        return [
            'success' => true,
            'driver' => 'mock',
            'notification_id' => $safeId,
            'delay_seconds' => $delaySeconds,
            'target_time' => now()->addSeconds($delaySeconds)->toDateTimeString(),
            'message' => 'Notification recorded via local development driver (browser preview). In native runtime with emulator/device, this triggers Android AlarmManager.',
        ];
    }

    /**
     * Generate a collision-resistant deterministic notification ID.
     */
    public function generateDeterministicId(string $baseId): string
    {
        $crc = sprintf('%08x', crc32($baseId));

        return "pma_{$crc}_{$baseId}";
    }

    /**
     * Cancel a scheduled notification.
     *
     * @return array<string, mixed>
     */
    public function cancel(string $id): array
    {
        $safeId = $this->generateDeterministicId($id);

        if ($this->getDriver() !== 'mock' && $this->isNativeAvailable()) {
            return LocalNotifications::cancel($safeId);
        }

        Log::info("Dev/Test mock notification cancelled: {$safeId}");

        return ['success' => true, 'driver' => 'mock', 'cancelled_id' => $safeId];
    }

    /**
     * Request notification permissions.
     *
     * @return array<string, mixed>
     */
    public function requestPermission(): array
    {
        if ($this->getDriver() !== 'mock' && $this->isNativeAvailable()) {
            return LocalNotifications::requestPermission();
        }

        return ['status' => 'granted', 'driver' => 'mock'];
    }

    /**
     * Check notification permission status.
     *
     * @return array<string, mixed>
     */
    public function checkPermission(): array
    {
        if ($this->getDriver() !== 'mock' && $this->isNativeAvailable()) {
            return LocalNotifications::checkPermission();
        }

        return ['status' => 'granted', 'driver' => 'mock'];
    }
}
