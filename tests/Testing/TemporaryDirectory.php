<?php

declare(strict_types=1);

namespace OasFake\Testing;

/**
 * Owns an isolated temporary directory for one test scenario.
 */
final class TemporaryDirectory
{
    private string $path;

    /**
     * Create the directory beneath the system temporary location.
     */
    public function __construct(string $prefix)
    {
        $this->path = sys_get_temp_dir() . '/' . $prefix . '-' . spl_object_id($this);
        mkdir($this->path, 0777, true);
    }

    /**
     * Remove regular files and the owned directory.
     */
    public function __destruct()
    {
        $files = glob($this->path . '/*');
        if ($files !== false) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        if (is_dir($this->path)) {
            rmdir($this->path);
        }
    }

    /**
     * Return the owned directory path.
     */
    public function path(): string
    {
        return $this->path;
    }
}
