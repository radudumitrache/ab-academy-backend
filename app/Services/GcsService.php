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
        $keyFile = config('filesystems.disks.gcs.key_file');

        // Resolve relative paths from the Laravel project root
        if ($keyFile && !str_starts_with($keyFile, '/') && !preg_match('/^[A-Za-z]:/', $keyFile)) {
            $keyFile = base_path($keyFile);
        }

        $config = [
            'projectId' => config('filesystems.disks.gcs.project_id'),
        ];

        if ($keyFile && file_exists($keyFile)) {
            $config['keyFilePath'] = $keyFile;
        }

        // Suppress open_basedir warnings from the GCS SDK's environment detection
        // (it tries to read /sys/class/dmi/id/product_name which is blocked on shared hosting)
        $previousLevel = error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
        $this->client  = new StorageClient($config);
        error_reporting($previousLevel);

        $bucket = config('filesystems.disks.gcs.bucket');
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
     * Check whether an object exists in GCS.
     */
    public function objectExists(string $path): bool
    {
        return $this->client->bucket($this->bucketName)->object($path)->exists();
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
     * Create the standard folder structure for an admin.
     * Structure: admin/profile/.keep
     *            admin/files/.keep
     */
    public function createAdminFolders(): array
    {
        $bucket  = $this->client->bucket($this->bucketName);
        $folders = [
            'admin/profile/.keep',
            'admin/files/.keep',
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
     * Create the standard folder structure for a student.
     * Structure: students/{username}/profile/.keep
     */
    public function createStudentFolders(string $username): array
    {
        $bucket  = $this->client->bucket($this->bucketName);
        $folders = [
            "students/{$username}/profile/.keep",
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
     * List immediate subfolders under a prefix using GCS delimiter-based listing.
     * Returns folder names (without the leading prefix).
     * This finds ALL virtual folders — even those with no .keep placeholder.
     */
    public function listSubfolders(string $prefix): array
    {
        $prefix  = $prefix === '' ? '' : rtrim($prefix, '/') . '/';
        $bucket  = $this->client->bucket($this->bucketName);

        // Use the delimiter API to get virtual directory prefixes
        $options = ['delimiter' => '/'];
        if ($prefix !== '') {
            $options['prefix'] = $prefix;
        }

        $objects  = $bucket->objects($options);
        $folders  = [];

        // Iterate to exhaust the result (needed to populate prefixes())
        foreach ($objects as $object) {}

        foreach ($objects->prefixes() as $commonPrefix) {
            // Strip the leading prefix and trailing slash to get just the folder name
            $name = rtrim(substr($commonPrefix, strlen($prefix)), '/');
            if ($name !== '') {
                $folders[] = $name;
            }
        }

        // Also detect folders via .keep objects (for backwards compatibility)
        $keepObjects = $bucket->objects(['prefix' => $prefix ?: '']);
        foreach ($keepObjects as $object) {
            $relative = substr($object->name(), strlen($prefix));
            $parts    = explode('/', rtrim($relative, '/'));
            if (count($parts) === 2 && end($parts) === '.keep') {
                $folders[] = $parts[0];
            }
        }

        return array_values(array_unique($folders));
    }

    /**
     * List the contents of a prefix as a structured tree:
     * Returns both immediate subfolders (virtual directories) and direct files.
     */
    public function listContents(string $prefix): array
    {
        $prefix  = $prefix === '' ? '' : rtrim($prefix, '/') . '/';
        $bucket  = $this->client->bucket($this->bucketName);

        $options = ['delimiter' => '/'];
        if ($prefix !== '') {
            $options['prefix'] = $prefix;
        }

        $objects = $bucket->objects($options);
        $files   = [];

        foreach ($objects as $object) {
            $name = $object->name();
            // Skip .keep placeholders and the prefix itself
            if (str_ends_with($name, '/.keep') || str_ends_with($name, '.keep') || $name === $prefix) {
                continue;
            }
            $files[] = $name;
        }

        $folders = [];
        foreach ($objects->prefixes() as $commonPrefix) {
            $name = rtrim(substr($commonPrefix, strlen($prefix)), '/');
            if ($name !== '') {
                $folders[] = $commonPrefix; // full path with trailing slash
            }
        }

        return [
            'folders' => $folders,
            'files'   => $files,
        ];
    }
}
