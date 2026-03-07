<?php

namespace App\Services;

use Google\Cloud\Storage\StorageClient;
use Illuminate\Http\UploadedFile;

class GcsService
{
    private StorageClient $client;
    private string $bucketName;

    public function __construct()
    {
        $keyFile = env('GOOGLE_CLOUD_KEY_FILE');

        // Resolve relative paths from the Laravel project root
        if ($keyFile && !str_starts_with($keyFile, '/') && !preg_match('/^[A-Za-z]:/', $keyFile)) {
            $keyFile = base_path($keyFile);
        }

        $config = [
            'projectId' => env('GOOGLE_CLOUD_PROJECT_ID'),
        ];

        if ($keyFile && file_exists($keyFile)) {
            $config['keyFilePath'] = $keyFile;
        }

        // Suppress open_basedir warnings from the GCS SDK's environment detection
        // (it tries to read /sys/class/dmi/id/product_name which is blocked on shared hosting)
        $previousLevel = error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
        $this->client  = new StorageClient($config);
        error_reporting($previousLevel);

        $bucket = env('GOOGLE_CLOUD_BUCKET');
        if (!$bucket) {
            throw new \RuntimeException('GOOGLE_CLOUD_BUCKET is not set in .env');
        }
        $this->bucketName = $bucket;
    }

    /**
     * Upload a file to GCS and return the object path.
     */
    public function upload(UploadedFile $file, string $path): string
    {
        $bucket = $this->client->bucket($this->bucketName);

        $bucket->upload(
            fopen($file->getRealPath(), 'r'),
            ['name' => $path]
        );

        return $path;
    }

    /**
     * Generate a signed download URL valid for $minutes.
     */
    public function signedUrl(string $path, int $minutes = 60): string
    {
        $bucket = $this->client->bucket($this->bucketName);
        $object = $bucket->object($path);

        return $object->signedUrl(new \DateTime("+{$minutes} minutes"), [
            'version' => 'v4',
            'method'  => 'GET',
        ]);
    }

    /**
     * Delete an object from GCS.
     */
    public function delete(string $path): void
    {
        $bucket = $this->client->bucket($this->bucketName);
        $object = $bucket->object($path);

        if ($object->exists()) {
            $object->delete();
        }
    }

    /**
     * Create the standard folder structure for a teacher.
     * GCS has no real folders — we create empty placeholder objects.
     * Structure: teachers/{username}/private/.keep
     *            teachers/{username}/profile/.keep
     */
    public function createTeacherFolders(string $username): array
    {
        $bucket  = $this->client->bucket($this->bucketName);
        $folders = [
            "teachers/{$username}/private/.keep",
            "teachers/{$username}/profile/.keep",
        ];

        $created = [];
        foreach ($folders as $path) {
            $object = $bucket->object($path);
            if (!$object->exists()) {
                $bucket->upload('', ['name' => $path]);
                $created[] = $path;
            }
        }

        return $created;
    }

    /**
     * List all objects under a given prefix.
     * Returns an array of object names.
     */
    public function listFolder(string $prefix): array
    {
        $bucket  = $this->client->bucket($this->bucketName);
        $objects = $bucket->objects(['prefix' => $prefix]);

        $paths = [];
        foreach ($objects as $object) {
            $paths[] = $object->name();
        }

        return $paths;
    }

    /**
     * Create a subfolder (placeholder .keep object) under a given prefix.
     * Returns true if created, false if it already existed.
     */
    public function createFolder(string $folderPath): bool
    {
        // Normalise: ensure it ends with /
        $folderPath = rtrim($folderPath, '/') . '/';
        $keepPath   = $folderPath . '.keep';

        $bucket = $this->client->bucket($this->bucketName);
        $object = $bucket->object($keepPath);

        if ($object->exists()) {
            return false;
        }

        $bucket->upload('', ['name' => $keepPath]);

        return true;
    }

    /**
     * Delete all objects under a given folder prefix (including the .keep placeholder).
     * Returns the number of objects deleted.
     */
    public function deleteFolder(string $folderPath): int
    {
        $folderPath = rtrim($folderPath, '/') . '/';
        $bucket     = $this->client->bucket($this->bucketName);
        $objects    = $bucket->objects(['prefix' => $folderPath]);

        $count = 0;
        foreach ($objects as $object) {
            $object->delete();
            $count++;
        }

        return $count;
    }

    /**
     * List immediate subfolders under a prefix.
     * Returns folder names (without the leading prefix).
     */
    public function listSubfolders(string $prefix): array
    {
        $prefix  = rtrim($prefix, '/') . '/';
        $bucket  = $this->client->bucket($this->bucketName);
        $objects = $bucket->objects(['prefix' => $prefix]);

        $folders = [];
        foreach ($objects as $object) {
            $relative = substr($object->name(), strlen($prefix));
            // An immediate subfolder has exactly one '/' and it's at the end
            $parts = explode('/', rtrim($relative, '/'));
            if (count($parts) === 2 && end($parts) === '.keep') {
                $folders[] = $parts[0];
            }
        }

        return array_values(array_unique($folders));
    }
}
