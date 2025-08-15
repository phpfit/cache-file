<?php

namespace PhpFit\CacheFile;

use Psr\SimpleCache\CacheInterface;
use PhpFit\File\FileSystem;
use PhpFit\SourceGenerator\Generator;

class File implements CacheInterface
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->getCacheFile($key);
        if (!is_file($file)) {
            return $default;
        }

        $cache = include $file;
        if ($cache->expiration < time()) {
            unlink($file);
            return $default;
        }

        return $cache->content;
    }

    public function getCacheFile(string $key): string
    {
        return $this->path . '/' . $key . '.php';
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        if (!$ttl) {
            $ttl = strtotime('+2000 years');
        }

        if ($ttl instanceof \DateInterval) {
            $ref = new \DateTimeImmutable();
            $et = $ref->add($ttl);
            $ttl = $et->getTimestamp() - $ref->getTimestamp();
        }

        $ttl += time();

        $cache = [
            'expiration' => $ttl,
            'content' => $value
        ];

        $cache_source = Generator::array($cache);
        $file = $this->getCacheFile($key);

        $nl = PHP_EOL;

        $ctn = '<?php' . $nl;
        $ctn .= 'return (object)' . $cache_source . ';';

        $f = fopen($file, 'w');
        fwrite($f, $ctn);
        fclose($f);

        return true;
    }

    public function delete(string $key): bool
    {
        $file = $this->getCacheFile($key);
        if (!is_file($file)) {
            return true;
        }

        return unlink($file);
    }

    public function clear(): bool
    {
        $files = FileSystem::scan($this->path);

        foreach ($files as $file) {
            if ($file === '.gitkeep') {
                continue;
            }

            $file_abs = $this->path . '/' . $file;
            if (!is_file($file_abs)) {
                continue;
            }

            unlink($file_abs);
        }

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        $file = $this->getCacheFile($key);
        return is_file($file);
    }
}
