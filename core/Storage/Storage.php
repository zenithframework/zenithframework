<?php

declare(strict_types=1);

namespace Zen\Storage;

use ZipArchive;

class Storage
{
    protected ?StorageDriver $driver = null;
    protected string $defaultDisk = 'local';

    public function __construct()
    {
        $this->initDriver();
    }

    protected function initDriver(): void
    {
        $config = config('storage') ?? ['default' => 'local', 'disks' => []];
        $diskName = $config['default'] ?? 'local';
        $diskConfig = $config['disks'][$diskName] ?? [];

        $this->driver = match ($diskName) {
            's3' => new S3Driver($diskConfig),
            'ftp' => new FtpDriver($diskConfig),
            default => new LocalDriver($diskConfig),
        };
    }

    public function put(string $path, string $contents): bool
    {
        return $this->driver->put($path, $contents);
    }

    public function get(string $path): ?string
    {
        return $this->driver->get($path);
    }

    public function exists(string $path): bool
    {
        return $this->driver->exists($path);
    }

    public function delete(string $path): bool
    {
        return $this->driver->delete($path);
    }

    public function copy(string $from, string $to): bool
    {
        $contents = $this->get($from);
        if ($contents === null) {
            return false;
        }
        return $this->put($to, $contents);
    }

    public function move(string $from, string $to): bool
    {
        if (!$this->copy($from, $to)) {
            return false;
        }
        return $this->delete($from);
    }

    public function size(string $path): int
    {
        return $this->driver->size($path);
    }

    public function lastModified(string $path): int
    {
        return $this->driver->lastModified($path);
    }

    public function mimeType(string $path): ?string
    {
        return $this->driver->mimeType($path);
    }

    public function files(string $directory = ''): array
    {
        return $this->driver->files($directory);
    }

    public function directories(string $directory = ''): array
    {
        return $this->driver->directories($directory);
    }

    public function makeDirectory(string $path): bool
    {
        return $this->driver->makeDirectory($path);
    }

    public function deleteDirectory(string $path): bool
    {
        return $this->driver->deleteDirectory($path);
    }

    public function url(string $path): string
    {
        return $this->driver->url($path);
    }

    public function path(string $path): string
    {
        return $this->driver->path($path);
    }

    public function download(string $path, ?string $name = null): \Zen\Http\Response
    {
        $contents = $this->get($path);
        if ($contents === null) {
            abort(404, 'File not found');
        }

        $name = $name ?? basename($path);

        return response($contents, 200, [
            'Content-Type' => $this->mimeType($path) ?? 'application/octet-stream',
            'Content-Disposition' => "attachment; filename=\"{$name}\"",
            'Content-Length' => strlen($contents),
        ]);
    }

    public function zip(string $outputPath, array $files): bool
    {
        $zip = new ZipArchive();
        if ($zip->open($outputPath, ZipArchive::CREATE) !== true) {
            return false;
        }

        foreach ($files as $file) {
            $contents = $this->get($file);
            if ($contents !== null) {
                $zip->addFromString($file, $contents);
            }
        }

        return $zip->close();
    }

    public function extract(string $path, string $destination): bool
    {
        $zip = new ZipArchive();
        $contents = $this->get($path);

        if ($contents === null) {
            return false;
        }

        $tempFile = sys_get_temp_dir() . '/zen_extract_' . uniqid() . '.zip';
        file_put_contents($tempFile, $contents);

        if ($zip->open($tempFile) !== true) {
            unlink($tempFile);
            return false;
        }

        $result = $zip->extractTo($destination);
        $zip->close();
        unlink($tempFile);

        return $result;
    }

    public function disk(string $name): static
    {
        $config = config('storage') ?? ['disks' => []];
        $diskConfig = $config['disks'][$name] ?? [];

        $this->driver = match ($name) {
            's3' => new S3Driver($diskConfig),
            'ftp' => new FtpDriver($diskConfig),
            default => new LocalDriver($diskConfig),
        };

        return $this;
    }
}

interface StorageDriver
{
    public function put(string $path, string $contents): bool;
    public function get(string $path): ?string;
    public function exists(string $path): bool;
    public function delete(string $path): bool;
    public function size(string $path): int;
    public function lastModified(string $path): int;
    public function mimeType(string $path): ?string;
    public function files(string $directory = ''): array;
    public function directories(string $directory = ''): array;
    public function makeDirectory(string $path): bool;
    public function deleteDirectory(string $path): bool;
    public function url(string $path): string;
    public function path(string $path): string;
}

class LocalDriver implements StorageDriver
{
    protected string $root;

    public function __construct(array $config = [])
    {
        $this->root = $config['root'] ?? dirname(__DIR__, 2) . '/storage';
        if (!is_dir($this->root)) {
            mkdir($this->root, 0755, true);
        }
    }

    public function put(string $path, string $contents): bool
    {
        $fullPath = $this->path($path);
        $dir = dirname($fullPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return file_put_contents($fullPath, $contents) !== false;
    }

    public function get(string $path): ?string
    {
        $fullPath = $this->path($path);
        return file_exists($fullPath) ? file_get_contents($fullPath) : null;
    }

    public function exists(string $path): bool
    {
        return file_exists($this->path($path));
    }

    public function delete(string $path): bool
    {
        $fullPath = $this->path($path);
        if (is_dir($fullPath)) {
            return $this->deleteDirectory($path);
        }
        return file_exists($fullPath) ? unlink($fullPath) : false;
    }

    public function size(string $path): int
    {
        $fullPath = $this->path($path);
        return file_exists($fullPath) ? (int) filesize($fullPath) : 0;
    }

    public function lastModified(string $path): int
    {
        $fullPath = $this->path($path);
        return file_exists($fullPath) ? (int) filemtime($fullPath) : 0;
    }

    public function mimeType(string $path): ?string
    {
        $fullPath = $this->path($path);
        return file_exists($fullPath) ? mime_content_type($fullPath) : null;
    }

    public function files(string $directory = ''): array
    {
        $fullPath = $this->path($directory);
        if (!is_dir($fullPath)) {
            return [];
        }

        $files = [];
        foreach (scandir($fullPath) as $file) {
            if ($file[0] !== '.' && is_file($fullPath . '/' . $file)) {
                $files[] = ($directory ? $directory . '/' : '') . $file;
            }
        }

        return $files;
    }

    public function directories(string $directory = ''): array
    {
        $fullPath = $this->path($directory);
        if (!is_dir($fullPath)) {
            return [];
        }

        $dirs = [];
        foreach (scandir($fullPath) as $dir) {
            if ($dir[0] !== '.' && is_dir($fullPath . '/' . $dir)) {
                $dirs[] = ($directory ? $directory . '/' : '') . $dir;
            }
        }

        return $dirs;
    }

    public function makeDirectory(string $path): bool
    {
        return is_dir($this->path($path)) || mkdir($this->path($path), 0755, true);
    }

    public function deleteDirectory(string $path): bool
    {
        $fullPath = $this->path($path);
        if (!is_dir($fullPath)) {
            return false;
        }

        foreach ($this->files($path) as $file) {
            $this->delete($path . '/' . $file);
        }

        foreach ($this->directories($path) as $dir) {
            $this->deleteDirectory($path . '/' . $dir);
        }

        return rmdir($fullPath);
    }

    public function url(string $path): string
    {
        return '/storage/' . ltrim($path, '/');
    }

    public function path(string $path): string
    {
        return $this->root . '/' . ltrim($path, '/');
    }
}

class S3Driver implements StorageDriver
{
    protected array $config;
    protected object|null $client = null;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'key' => '',
            'secret' => '',
            'region' => 'us-east-1',
            'bucket' => '',
            'endpoint' => '',
        ], $config);

        $this->initClient();
    }

    protected function initClient(): void
    {
        $s3Class = 'Aws\S3\S3Client';
        if (class_exists($s3Class)) {
            $this->client = new $s3Class([
                'version' => 'latest',
                'region' => $this->config['region'],
                'credentials' => [
                    'key' => $this->config['key'],
                    'secret' => $this->config['secret'],
                ],
            ]);
        }
    }

    public function put(string $path, string $contents): bool
    {
        if ($this->client === null) {
            return false;
        }

        try {
            $this->client->putObject([
                'Bucket' => $this->config['bucket'],
                'Key' => $path,
                'Body' => $contents,
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function get(string $path): ?string
    {
        if ($this->client === null) {
            return null;
        }

        try {
            $result = $this->client->getObject([
                'Bucket' => $this->config['bucket'],
                'Key' => $path,
            ]);
            return (string) $result['Body'];
        } catch (\Exception $e) {
            return null;
        }
    }

    public function exists(string $path): bool
    {
        if ($this->client === null) {
            return false;
        }

        return $this->client->doesObjectExist($this->config['bucket'], $path);
    }

    public function delete(string $path): bool
    {
        if ($this->client === null) {
            return false;
        }

        try {
            $this->client->deleteObject([
                'Bucket' => $this->config['bucket'],
                'Key' => $path,
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function size(string $path): int
    {
        $result = $this->getMetadata($path);
        return $result['ContentLength'] ?? 0;
    }

    public function lastModified(string $path): int
    {
        $result = $this->getMetadata($path);
        return isset($result['LastModified']) ? strtotime($result['LastModified']) : 0;
    }

    public function mimeType(string $path): ?string
    {
        $result = $this->getMetadata($path);
        return $result['ContentType'] ?? null;
    }

    protected function getMetadata(string $path): array
    {
        if ($this->client === null) {
            return [];
        }

        try {
            return $this->client->headObject([
                'Bucket' => $this->config['bucket'],
                'Key' => $path,
            ])->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function files(string $directory = ''): array
    {
        return [];
    }

    public function directories(string $directory = ''): array
    {
        return [];
    }

    public function makeDirectory(string $path): bool
    {
        return true;
    }

    public function deleteDirectory(string $path): bool
    {
        if ($this->client === null) {
            return false;
        }

        $objects = $this->client->listObjects([
            'Bucket' => $this->config['bucket'],
            'Prefix' => $path . '/',
        ]);

        foreach ($objects['Contents'] ?? [] as $object) {
            $this->delete($object['Key']);
        }

        return true;
    }

    public function url(string $path): string
    {
        $endpoint = $this->config['endpoint'] ?? "https://{$this->config['bucket']}.s3.amazonaws.com";
        return $endpoint . '/' . ltrim($path, '/');
    }

    public function path(string $path): string
    {
        return $this->url($path);
    }
}

class FtpDriver implements StorageDriver
{
    protected array $config;
    protected $connection = null;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'host' => 'localhost',
            'port' => 21,
            'username' => 'anonymous',
            'password' => '',
            'root' => '/',
        ], $config);
    }

    public function connect(): bool
    {
        if ($this->connection !== null) {
            return true;
        }

        $this->connection = ftp_connect($this->config['host'], $this->config['port']);
        if ($this->connection === false) {
            return false;
        }

        $login = ftp_login($this->connection, $this->config['username'], $this->config['password']);
        ftp_pasv($this->connection, true);

        return $login;
    }

    public function put(string $path, string $contents): bool
    {
        if (!$this->connect()) {
            return false;
        }

        $temp = tempnam(sys_get_temp_dir(), 'zen_');
        file_put_contents($temp, $contents);

        $result = ftp_put($this->connection, $this->fullPath($path), $temp, FTP_BINARY);
        unlink($temp);

        return $result;
    }

    public function get(string $path): ?string
    {
        if (!$this->connect()) {
            return null;
        }

        $temp = tempnam(sys_get_temp_dir(), 'zen_');
        if (!ftp_get($this->connection, $temp, $this->fullPath($path), FTP_BINARY)) {
            unlink($temp);
            return null;
        }

        $contents = file_get_contents($temp);
        unlink($temp);

        return $contents;
    }

    public function exists(string $path): bool
    {
        if (!$this->connect()) {
            return false;
        }

        return ftp_size($this->connection, $this->fullPath($path)) !== -1;
    }

    public function delete(string $path): bool
    {
        if (!$this->connect()) {
            return false;
        }

        return ftp_delete($this->connection, $this->fullPath($path));
    }

    public function size(string $path): int
    {
        if (!$this->connect()) {
            return 0;
        }

        $size = ftp_size($this->connection, $this->fullPath($path));
        return $size !== -1 ? $size : 0;
    }

    public function lastModified(string $path): int
    {
        return 0;
    }

    public function mimeType(string $path): ?string
    {
        return null;
    }

    public function files(string $directory = ''): array
    {
        if (!$this->connect()) {
            return [];
        }

        $files = ftp_nlist($this->connection, $this->fullPath($directory));
        return array_filter($files, fn($f) => !is_dir($f));
    }

    public function directories(string $directory = ''): array
    {
        if (!$this->connect()) {
            return [];
        }

        $dirs = ftp_nlist($this->connection, $this->fullPath($directory));
        return array_filter($dirs, fn($d) => is_dir($d));
    }

    public function makeDirectory(string $path): bool
    {
        if (!$this->connect()) {
            return false;
        }

        return ftp_mkdir($this->connection, $this->fullPath($path)) !== false;
    }

    public function deleteDirectory(string $path): bool
    {
        if (!$this->connect()) {
            return false;
        }

        return ftp_rmdir($this->connection, $this->fullPath($path));
    }

    public function url(string $path): string
    {
        return "ftp://{$this->config['host']}/" . ltrim($path, '/');
    }

    public function path(string $path): string
    {
        return $this->fullPath($path);
    }

    protected function fullPath(string $path): string
    {
        return rtrim($this->config['root'], '/') . '/' . ltrim($path, '/');
    }

    public function __destruct()
    {
        if ($this->connection !== null) {
            ftp_close($this->connection);
        }
    }
}