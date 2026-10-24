<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Assessment;
use App\Models\AvailabilityRule;
use App\Models\Lesson;
use App\Models\Phase;
use App\Models\Resource;
use App\Models\ResourceAttachment;
use App\Models\Roadmap;
use App\Models\ScheduledEvent;
use App\Models\SessionInterval;
use App\Models\SpacedRepetitionItem;
use App\Models\StudySession;
use App\Models\Task;
use App\Models\TaskPrerequisite;
use App\Models\Topic;
use App\Models\UserSetting;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ZipArchive;

class BackupService
{
    public const SCHEMA_VERSION = '1.0.0';

    public const APP_VERSION = '1.0.0';

    /**
     * Export all user data into a ZIP archive (.pma).
     *
     * @return string Path to the created backup archive
     *
     * @throws Exception
     */
    public function exportBackup(?string $password = null): string
    {
        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);

        $timestamp = now()->format('Ymd_His');
        $archivePath = "{$backupDir}/backup_{$timestamp}.pma";

        $data = [
            'roadmaps' => Roadmap::all()->toArray(),
            'phases' => Phase::all()->toArray(),
            'topics' => Topic::all()->toArray(),
            'lessons' => Lesson::all()->toArray(),
            'tasks' => Task::all()->toArray(),
            'task_prerequisites' => TaskPrerequisite::all()->toArray(),
            'assessments' => Assessment::all()->toArray(),
            'availability_rules' => AvailabilityRule::all()->toArray(),
            'scheduled_events' => ScheduledEvent::all()->toArray(),
            'study_sessions' => StudySession::all()->toArray(),
            'session_intervals' => SessionInterval::all()->toArray(),
            'resources' => Resource::all()->toArray(),
            'resource_attachments' => ResourceAttachment::all()->toArray(),
            'spaced_repetition_items' => SpacedRepetitionItem::all()->toArray(),
            'user_settings' => UserSetting::all()->toArray(),
        ];

        $jsonPayload = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($jsonPayload === false) {
            throw new Exception('Failed to serialize data to JSON.');
        }

        $checksum = hash('sha256', $jsonPayload);

        $manifest = [
            'app_version' => self::APP_VERSION,
            'schema_version' => self::SCHEMA_VERSION,
            'exported_at' => now()->toIso8601String(),
            'checksum_sha256' => $checksum,
            'is_encrypted' => false,
            'counts' => [
                'roadmaps' => count($data['roadmaps']),
                'tasks' => count($data['tasks']),
                'scheduled_events' => count($data['scheduled_events']),
                'study_sessions' => count($data['study_sessions']),
                'resources' => count($data['resources']),
            ],
        ];

        // Encrypt data.json if password requested
        $dataFileContent = $jsonPayload;
        if (! empty($password)) {
            $salt = random_bytes(32);
            $key = hash_pbkdf2('sha256', $password, $salt, 600000, 32, true);
            $iv = random_bytes(12);
            $tag = '';
            $encrypted = openssl_encrypt($jsonPayload, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);

            if ($encrypted === false) {
                throw new Exception('Encryption failed.');
            }

            $dataFileContent = $encrypted;
            $manifest['is_encrypted'] = true;
            $manifest['encryption'] = [
                'cipher' => 'aes-256-gcm',
                'kdf' => 'pbkdf2-sha256',
                'iterations' => 600000,
                'salt' => bin2hex($salt),
                'iv' => bin2hex($iv),
                'tag' => bin2hex($tag),
            ];
        }

        // Build ZIP
        $zip = new ZipArchive;
        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Could not create ZIP archive.');
        }

        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
        $zip->addFromString($manifest['is_encrypted'] ? 'data.json.enc' : 'data.json', $dataFileContent);

        // Include attachments
        $attachmentsDir = storage_path('app/attachments');
        if (File::isDirectory($attachmentsDir)) {
            foreach (File::allFiles($attachmentsDir) as $file) {
                $zip->addFile($file->getRealPath(), 'attachments/'.$file->getFilename());
            }
        }

        $zip->close();

        return $archivePath;
    }

    /**
     * Restore data from a backup archive.
     *
     * @param  string  $mode  'replace' or 'merge'
     *
     * @throws Exception
     */
    public function importBackup(string $archivePath, string $mode = 'replace', ?string $password = null): array
    {
        if (! File::exists($archivePath)) {
            throw new Exception('Backup archive not found.');
        }

        $zip = new ZipArchive;
        if ($zip->open($archivePath) !== true) {
            throw new Exception('Failed to open backup ZIP archive.');
        }

        $manifestContent = $zip->getFromName('manifest.json');
        if ($manifestContent === false) {
            $zip->close();
            throw new Exception('Invalid archive: manifest.json is missing.');
        }

        $manifest = json_decode($manifestContent, true);
        if (! is_array($manifest) || ! isset($manifest['schema_version'])) {
            $zip->close();
            throw new Exception('Invalid manifest structure.');
        }

        $isEncrypted = (bool) ($manifest['is_encrypted'] ?? false);
        $dataName = $isEncrypted ? 'data.json.enc' : 'data.json';
        $rawPayload = $zip->getFromName($dataName);

        if ($rawPayload === false) {
            $zip->close();
            throw new Exception("Archive missing payload file {$dataName}.");
        }

        // Decrypt if necessary
        $jsonString = $rawPayload;
        if ($isEncrypted) {
            if (empty($password)) {
                $zip->close();
                throw new Exception('Backup is password protected. Please provide the decryption password.');
            }

            $enc = $manifest['encryption'] ?? [];
            $salt = hex2bin((string) ($enc['salt'] ?? ''));
            $iv = hex2bin((string) ($enc['iv'] ?? ''));
            $tag = hex2bin((string) ($enc['tag'] ?? ''));
            $iterations = (int) ($enc['iterations'] ?? 600000);

            $key = hash_pbkdf2('sha256', $password, $salt, $iterations, 32, true);
            $decrypted = openssl_decrypt($rawPayload, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

            if ($decrypted === false) {
                $zip->close();
                throw new Exception('Decryption failed! Incorrect password or corrupted archive.');
            }

            $jsonString = $decrypted;
        }

        // Validate SHA-256
        if (! $isEncrypted) {
            $actualChecksum = hash('sha256', $jsonString);
            if ($actualChecksum !== ($manifest['checksum_sha256'] ?? '')) {
                $zip->close();
                throw new Exception('Integrity check failed: checksum mismatch in data.json.');
            }
        }

        $data = json_decode($jsonString, true);
        if (! is_array($data)) {
            $zip->close();
            throw new Exception('Failed to decode data.json structure.');
        }

        // Atomic DB restore
        DB::beginTransaction();
        try {
            if ($mode === 'replace') {
                // Clear existing user data
                SessionInterval::truncate();
                StudySession::truncate();
                ScheduledEvent::truncate();
                AvailabilityRule::truncate();
                Assessment::truncate();
                TaskPrerequisite::truncate();
                Task::truncate();
                Lesson::truncate();
                Topic::truncate();
                Phase::truncate();
                Roadmap::truncate();
                ResourceAttachment::truncate();
                Resource::truncate();
                SpacedRepetitionItem::truncate();
            }

            // Insert records
            foreach ($data['roadmaps'] ?? [] as $row) {
                Roadmap::updateOrCreate(['id' => $row['id']], $row);
            }
            foreach ($data['phases'] ?? [] as $row) {
                Phase::updateOrCreate(['id' => $row['id']], $row);
            }
            foreach ($data['topics'] ?? [] as $row) {
                Topic::updateOrCreate(['id' => $row['id']], $row);
            }
            foreach ($data['lessons'] ?? [] as $row) {
                Lesson::updateOrCreate(['id' => $row['id']], $row);
            }
            foreach ($data['tasks'] ?? [] as $row) {
                Task::updateOrCreate(['id' => $row['id']], $row);
            }
            foreach ($data['task_prerequisites'] ?? [] as $row) {
                TaskPrerequisite::updateOrCreate(['id' => $row['id']], $row);
            }
            foreach ($data['assessments'] ?? [] as $row) {
                Assessment::updateOrCreate(['id' => $row['id']], $row);
            }
            foreach ($data['availability_rules'] ?? [] as $row) {
                AvailabilityRule::updateOrCreate(['id' => $row['id']], $row);
            }
            foreach ($data['scheduled_events'] ?? [] as $row) {
                ScheduledEvent::updateOrCreate(['id' => $row['id']], $row);
            }
            foreach ($data['study_sessions'] ?? [] as $row) {
                StudySession::updateOrCreate(['id' => $row['id']], $row);
            }
            foreach ($data['session_intervals'] ?? [] as $row) {
                SessionInterval::updateOrCreate(['id' => $row['id']], $row);
            }
            foreach ($data['resources'] ?? [] as $row) {
                Resource::updateOrCreate(['id' => $row['id']], $row);
            }
            foreach ($data['spaced_repetition_items'] ?? [] as $row) {
                SpacedRepetitionItem::updateOrCreate(['id' => $row['id']], $row);
            }
            foreach ($data['user_settings'] ?? [] as $row) {
                UserSetting::updateOrCreate(['key' => $row['key']], $row);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $zip->close();
            throw new Exception('Database restore failed and was rolled back: '.$e->getMessage());
        }

        $zip->close();

        return [
            'success' => true,
            'mode' => $mode,
            'counts' => $manifest['counts'] ?? [],
        ];
    }
}
