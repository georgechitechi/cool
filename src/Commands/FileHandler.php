<?php

namespace Georgechitechi\Cool\Commands;

use GuzzleHttp\Client;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

trait FileHandler
{
    /**
     * Get the path to the packages directory.
     *
     * @return string $path
     */
    public function packagesPath()
    {
        return base_path('packages');
    }

    /**
     * Get the vendor path.
     *
     * @return string $path
     */
    public function vendorPath()
    {
        return $this->packagesPath().'/'.$this->vendor();
    }

    /**
     * Get the path to store a vendor's temporary files.
     *
     * @return string $path
     */
    public function tempPath()
    {
        return $this->vendorPath().'/temp';
    }

    /**
     * Get the full package path.
     *
     * @return string $path
     */
    public function packagePath()
    {
        return $this->vendorPath().'/'.$this->package();
    }

    /**
     * Generate a random temporary filename for the package archive file.
     *
     * @param string $extension
     *
     * @return string
     */
    public function makeFilename($extension = 'zip')
    {
        return getcwd().'/package'.md5(time().uniqid()).'.'.$extension;
    }

    /**
     * Check if the package already exists.
     *
     * @return void    Throws error if package exists, aborts process
     */
    public function checkIfPackageExists()
    {
        if (is_dir($this->packagePath())) {
            throw new RuntimeException('Package already exists');
        }
    }

    /**
     * Create a directory if it doesn't exist.
     *
     * @param  string $path Path of the directory to make
     * @return bool
     */
    public function makeDir($path)
    {
        if (! is_dir($path)) {
            return mkdir($path, 0777, true);
        }

        return false;
    }

    /**
     * Remove a directory if it exists.
     *
     * @param  string $path Path of the directory to remove.
     * @return bool
     */
    public function removeDir($path)
    {
        // if ($path == 'packages' || $path == '/') {
            // return false;
        // }

        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            if (is_dir("$path/$file")) {
                $this->removeDir("$path/$file");
            } else {
                @chmod("$path/$file", 0777);
                @unlink("$path/$file");
            }
        }

        return rmdir($path);
    }

    /**
     * Remove the rules files if present.
     */
    public function cleanUpRules()
    {
        $ruleFiles = ['rules.php', 'rewriteRules.php'];

        foreach ($ruleFiles as $file) {
            if (file_exists($this->packagePath().'/'.$file)) {
                unlink($this->packagePath().'/'.$file);
            }
        }
    }
}
