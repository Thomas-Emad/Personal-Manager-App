<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\BackupService;
use App\Services\RoadmapImportService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(
        protected BackupService $backupService,
        protected RoadmapImportService $roadmapImportService
    ) {}

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        $password = $request->get('password') ? (string) $request->get('password') : null;

        try {
            $archivePath = $this->backupService->exportBackup($password);

            return response()->download($archivePath, basename($archivePath), [
                'Content-Type' => 'application/zip',
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Export failed: '.$e->getMessage());
        }
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'backup_file' => ['required', 'file'],
            'mode' => ['required', 'in:replace,merge'],
            'password' => ['nullable', 'string'],
        ]);

        $file = $request->file('backup_file');
        $tempPath = $file->getRealPath();

        try {
            $result = $this->backupService->importBackup(
                $tempPath,
                $validated['mode'],
                $validated['password'] ?? null
            );

            return redirect()->back()->with('success', "Backup restored successfully in {$validated['mode']} mode!");
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Restore failed: '.$e->getMessage());
        }
    }

    public function importRoadmapJson(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'json_file' => ['required', 'file'],
        ]);

        $file = $request->file('json_file');
        $content = File::get($file->getRealPath());

        try {
            $roadmap = $this->roadmapImportService->import($content);

            return redirect()->route('roadmaps.show', $roadmap->id)->with('success', "Roadmap '{$roadmap->title}' imported successfully!");
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Roadmap import failed: '.$e->getMessage());
        }
    }
}
