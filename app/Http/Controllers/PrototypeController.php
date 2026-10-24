<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PrototypeItem;
use App\Services\NotificationService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PrototypeController extends Controller
{
    /**
     * Display the minimal prototype dashboard.
     */
    public function index(NotificationService $notificationService): Response
    {
        $items = PrototypeItem::query()->latest()->get();

        $journalMode = 'unknown';
        try {
            $modeResult = DB::select('PRAGMA journal_mode;');
            if (! empty($modeResult) && isset($modeResult[0]->journal_mode)) {
                $journalMode = (string) $modeResult[0]->journal_mode;
            }
        } catch (Exception) {
            // Keep default
        }

        $sqliteDbPath = config('database.connections.sqlite.database');

        $systemInfo = [
            'php_version' => PHP_VERSION,
            'os' => PHP_OS,
            'os_family' => PHP_OS_FAMILY,
            'sqlite_path' => $sqliteDbPath,
            'sqlite_journal_mode' => $journalMode,
            'native_bridge_active' => $notificationService->isNativeAvailable(),
            'total_items_persisted' => $items->count(),
            'locale' => session('locale', config('app.locale', 'en')),
            'direction' => session('locale', config('app.locale', 'en')) === 'ar' ? 'rtl' : 'ltr',
            'app_id' => config('nativephp.app_id', env('NATIVEPHP_APP_ID', 'com.personalmanager.app')),
            'server_time' => now()->toIso8601String(),
        ];

        return Inertia::render('Prototype', [
            'items' => $items,
            'systemInfo' => $systemInfo,
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
                'notification' => session('notification_result'),
            ],
        ]);
    }

    /**
     * Store a new prototype item in SQLite.
     */
    public function storeItem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $item = PrototypeItem::create([
            'title' => $validated['title'],
            'details' => $validated['details'] ?? null,
        ]);

        return redirect()->back()->with('success', "Item '{$item->title}' persisted successfully to SQLite database!");
    }

    /**
     * Delete an item from SQLite.
     */
    public function deleteItem(string $id): RedirectResponse
    {
        $item = PrototypeItem::findOrFail($id);
        $title = $item->title;
        $item->delete();

        return redirect()->back()->with('success', "Item '{$title}' removed from database.");
    }

    /**
     * Trigger a local notification test.
     */
    public function scheduleNotification(Request $request, NotificationService $notificationService): RedirectResponse
    {
        $validated = $request->validate([
            'seconds' => ['nullable', 'integer', 'min:3', 'max:300'],
            'title' => ['nullable', 'string', 'max:100'],
            'body' => ['nullable', 'string', 'max:255'],
        ]);

        $delay = (int) ($validated['seconds'] ?? 10);
        $title = $validated['title'] ?? 'Personal Productivity Reminder';
        $body = $validated['body'] ?? "Study session scheduled to begin in {$delay} seconds!";

        try {
            $result = $notificationService->schedule(
                id: 'proto_'.time(),
                title: $title,
                body: $body,
                delaySeconds: $delay,
                data: ['source' => 'prototype_screen']
            );

            return redirect()->back()->with([
                'success' => "Notification scheduled successfully for {$delay} seconds from now!",
                'notification_result' => $result,
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Notification dispatch failed: '.$e->getMessage());
        }
    }

    /**
     * Switch language between English (LTR) and Arabic (RTL).
     */
    public function setLocale(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'in:en,ar'],
        ]);

        session(['locale' => $validated['locale']]);
        app()->setLocale($validated['locale']);

        return redirect()->back()->with('success', $validated['locale'] === 'ar' ? 'تم تحويل الواجهة إلى اللغة العربية (RTL)' : 'Switched interface to English (LTR)');
    }
}
