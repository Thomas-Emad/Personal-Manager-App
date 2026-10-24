<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\UserSetting;
use App\Services\NotificationService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function index(): Response
    {
        $timezone = UserSetting::get('timezone', config('app.timezone', 'UTC'));
        $defaultStudyMinutes = UserSetting::get('default_study_minutes', 45);
        $defaultBreakMinutes = UserSetting::get('default_break_minutes', 15);
        $notificationsEnabled = UserSetting::get('notifications_enabled', true);
        $locale = session('locale', config('app.locale', 'en'));

        $permissionStatus = $this->notificationService->checkPermission();

        return Inertia::render('Settings/Index', [
            'settings' => [
                'timezone' => $timezone,
                'default_study_minutes' => (int) $defaultStudyMinutes,
                'default_break_minutes' => (int) $defaultBreakMinutes,
                'notifications_enabled' => (bool) $notificationsEnabled,
                'locale' => $locale,
                'native_bridge_active' => $this->notificationService->isNativeAvailable(),
                'permission_status' => $permissionStatus,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'timezone' => ['required', 'string'],
            'default_study_minutes' => ['required', 'integer', 'min:15', 'max:180'],
            'default_break_minutes' => ['required', 'integer', 'min:5', 'max:60'],
            'notifications_enabled' => ['required', 'boolean'],
        ]);

        UserSetting::set('timezone', $validated['timezone']);
        UserSetting::set('default_study_minutes', $validated['default_study_minutes']);
        UserSetting::set('default_break_minutes', $validated['default_break_minutes']);
        UserSetting::set('notifications_enabled', $validated['notifications_enabled']);

        return redirect()->back()->with('success', 'Preferences saved.');
    }

    public function toggleLocale(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'in:en,ar'],
        ]);

        session(['locale' => $validated['locale']]);
        app()->setLocale($validated['locale']);

        UserSetting::set('locale', $validated['locale']);

        $msg = $validated['locale'] === 'ar' ? 'تم تفعيل اللغة العربية بالكامل (RTL)' : 'Switched to English (LTR)';

        return redirect()->back()->with('success', $msg);
    }

    public function testNotification(): RedirectResponse
    {
        try {
            $result = $this->notificationService->schedule(
                id: 'settings_test_'.time(),
                title: 'Notification System Online',
                body: 'Your local productivity reminder system is operating nominally.',
                delaySeconds: 5
            );

            return redirect()->back()->with([
                'success' => 'Test notification scheduled for 5 seconds from now!',
                'notification_result' => $result,
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
