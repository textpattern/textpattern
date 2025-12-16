<?php
/**
 * Class definition file for SLIRGarbageCollector
 *
 * This file is part of SLIR (Smart Lencioni Image Resizer).
 *
 * Copyright (c) 2014 Joe Lencioni <joe.lencioni@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @copyright Copyright © 2014, Joe Lencioni
 * @license MIT
 * @since 2.0
 * @package SLIR
 */

/**
 * SLIR garbage collector class
 *
 * @since 2.0
 * @author Joe Lencioni <joe@shiftingpixel.com>
 * @package SLIR
 */
namespace lencioni\SLIR;

class SLIRGarbageCollector
{

    /**
     * Setting for the garbage collector to sleep for a second after looking at this many files
     *
     * @since 2.0
     * @var integer
     */
    const BREATHE_EVERY = 5000;

    /**
     * Garbage collector
     *
     * Clears out old files from the cache
     *
     * @since 2.0
     * @param array $directories
     * @return void
     */
    public function __construct(array $directories)
    {
        // This code needs to be in a try/catch block to prevent the epically unhelpful
        // "PHP Fatal error:  Exception thrown without a stack frame in Unknown on line
        // 0" from showing up in the error log.
        try {
            if ($this->isRunning()) {
                return;
            }
            $this->start();
            foreach ($directories as $directory => $useAccessedTime) {
                $this->deleteStaleFilesFromDirectory($directory, $useAccessedTime);
            }
            $this->finish();
        } catch (\Exception $e) {
            error_log(sprintf("\n[%s] %s thrown within the SLIR garbage collector. Message: %s in %s on line %d", gmdate('D M d H:i:s Y'), get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()), 3, SLIRConfig::$pathToErrorLog);
            error_log("\nException trace stack: " . print_r($e->getTrace(), true), 3, SLIRConfig::$pathToErrorLog);
            $this->finish(false);
        }
    }

    /**
     * Deletes stale files from a directory.
     *
     * Used by the garbage collector to keep the cache directories from overflowing.
     *
     * @param string $path Directory to delete stale files from
     */
    private function deleteStaleFilesFromDirectory($path, $useAccessedTime = true)
    {
        $now  = time();
        $dirs = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($dirs, \RecursiveIteratorIterator::CHILD_FIRST);

        $fileCount = 0;

        if ($useAccessedTime === true) {
            $function = 'getATime';
        } else {
            $function = 'getCTime';
        }

        foreach ($iterator as $file) {
            // Every x files, stop for a second to help let other things on the server happen
            if ($fileCount++ % self::BREATHE_EVERY == 0) {
                sleep(1);
            }

            $filepath = $file->getRealPath();

            // If the file is a link and not readable, the file it was pointing at has probably
            // been deleted, so we need to delete the link.
            // Otherwise, if the file is older than the max lifetime specified in the config, it is
            // stale and should be deleted.
            if ($file->isFile() && strpos(mime_content_type($filepath), 'image/') === 0 && (($file->isLink() && !$file->isReadable()) || ($now - $file->$function()) > SLIRConfig::$garbageCollectFileCacheMaxLifetime)) {
                unlink($filepath);
            }

            // Remove empty directories too.
            if ($file->isDir() && iterator_count($iterator->getChildren()) === 0) {
                rmdir($filepath);
            }

        }

        unset($dirs, $iterator);
    }

    /**
     * Checks to see if the garbage collector is currently running.
     *
     * @since 2.0
     * @return boolean
     */
    private function isRunning()
    {
        if (file_exists(SLIRConfig::$pathToCacheDir . '/garbageCollector.tmp') && filemtime(SLIRConfig::$pathToCacheDir . '/garbageCollector.tmp') > time() - 86400) {
            // If the file is more than 1 day old, something probably went wrong and we should run again anyway
            return true;
        } else {
            return false;
        }
    }

    /**
     * Writes a file to the cache to use as a signal that the garbage collector is currently running.
     *
     * @since 2.0
     * @return void
     */
    private function start()
    {
        error_log(sprintf("\n[%s] Garbage collection started", gmdate('D M d H:i:s Y')), 3, SLIRConfig::$pathToErrorLog);

        // Create the file that tells SLIR that the garbage collector is currently running and doesn't need to run again right now.
        touch(SLIRConfig::$pathToCacheDir . '/garbageCollector.tmp');
    }

    /**
     * Removes the file that signifies that the garbage collector is currently running.
     *
     * @since 2.0
     * @param boolean $successful
     * @return void
     */
    private function finish($successful = true)
    {
        // Delete the file that tells SLIR that the garbage collector is running
        unlink(SLIRConfig::$pathToCacheDir . '/garbageCollector.tmp');

        if ($successful) {
            error_log(sprintf("\n[%s] Garbage collection completed", gmdate('D M d H:i:s Y')), 3, SLIRConfig::$pathToErrorLog);
        }
    }
}
