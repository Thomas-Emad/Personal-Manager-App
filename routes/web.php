<?php

declare(strict_types=1);

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\PrototypeController;
use App\Http\Controllers\RoadmapController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StudyTrackerController;
use Illuminate\Support\Facades\Route;

// Today Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Calendar & Adaptive Scheduling
Route::prefix('calendar')->name('calendar.')->group(function () {
    Route::get('/', [CalendarController::class, 'index'])->name('index');
    Route::post('/events', [CalendarController::class, 'storeEvent'])->name('events.store');
    Route::put('/events/{id}', [CalendarController::class, 'reschedule'])->name('events.reschedule');
    Route::delete('/events/{id}', [CalendarController::class, 'destroy'])->name('events.destroy');
    Route::post('/availability', [CalendarController::class, 'updateAvailability'])->name('availability.update');
});

// Dynamic Roadmap Builder
Route::prefix('roadmaps')->name('roadmaps.')->group(function () {
    Route::get('/', [RoadmapController::class, 'index'])->name('index');
    Route::post('/', [RoadmapController::class, 'store'])->name('store');
    Route::get('/{id}', [RoadmapController::class, 'show'])->name('show');
    Route::put('/{id}', [RoadmapController::class, 'update'])->name('update');
    Route::delete('/{id}', [RoadmapController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/phases', [RoadmapController::class, 'storePhase'])->name('phases.store');
    Route::post('/phases/{id}/topics', [RoadmapController::class, 'storeTopic'])->name('topics.store');
    Route::post('/topics/{id}/lessons', [RoadmapController::class, 'storeLesson'])->name('lessons.store');
    Route::post('/lessons/{id}/tasks', [RoadmapController::class, 'storeTask'])->name('tasks.store');
    Route::post('/tasks/prerequisites', [RoadmapController::class, 'addPrerequisite'])->name('prerequisites.store');
    Route::post('/tasks/{id}/schedule', [RoadmapController::class, 'scheduleTask'])->name('tasks.schedule');
});

// Study Session Tracker
Route::prefix('tracker')->name('tracker.')->group(function () {
    Route::get('/', [StudyTrackerController::class, 'index'])->name('index');
    Route::post('/start', [StudyTrackerController::class, 'start'])->name('start');
    Route::post('/{id}/pause', [StudyTrackerController::class, 'pause'])->name('pause');
    Route::post('/{id}/resume', [StudyTrackerController::class, 'resume'])->name('resume');
    Route::post('/{id}/finish', [StudyTrackerController::class, 'finish'])->name('finish');
});

// Study Library
Route::prefix('library')->name('library.')->group(function () {
    Route::get('/', [LibraryController::class, 'index'])->name('index');
    Route::post('/', [LibraryController::class, 'store'])->name('store');
    Route::delete('/{id}', [LibraryController::class, 'destroy'])->name('destroy');
});

// Progress & Analytics
Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');

// Settings & Backup
Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('index');
    Route::post('/', [SettingsController::class, 'update'])->name('update');
    Route::post('/locale', [SettingsController::class, 'toggleLocale'])->name('locale');
    Route::post('/notify-test', [SettingsController::class, 'testNotification'])->name('notify-test');

    // Backup & Restore
    Route::get('/backup/export', [BackupController::class, 'export'])->name('backup.export');
    Route::post('/backup/import', [BackupController::class, 'import'])->name('backup.import');
    Route::post('/roadmap/import', [BackupController::class, 'importRoadmapJson'])->name('roadmap.import');
});

// Backward-compatible Prototype Endpoints
Route::prefix('prototype')->name('prototype.')->group(function () {
    Route::get('/', [PrototypeController::class, 'index'])->name('index');
    Route::post('/items', [PrototypeController::class, 'storeItem'])->name('items.store');
    Route::delete('/items/{id}', [PrototypeController::class, 'deleteItem'])->name('items.destroy');
    Route::post('/notify', [PrototypeController::class, 'scheduleNotification'])->name('notify');
    Route::post('/locale', [PrototypeController::class, 'setLocale'])->name('locale');
});
