<?php
/**
 * Typecho update assistant.
 *
 * @package UpdateAssistant
 * @author  mrgeneral
 * @version 1.0.1
 * @link    https://www.chengxiaobai.cn
 */

class Archive extends Base
{
    protected function compress($archiveName, $targets, $workRootPath)
    {
        $archiveRealPath = rtrim($workRootPath, '/.\\') . DIRECTORY_SEPARATOR . 'archive' . DIRECTORY_SEPARATOR . 'back' . DIRECTORY_SEPARATOR . $archiveName . '.zip';

        // cache
        if (is_file($archiveRealPath)) {
            return true;
        }

        $archive = new ZipArchive();
        $archive->open($archiveRealPath, ZipArchive::CREATE);

        foreach ($targets as $realPath => $realRootPath) {
            if (!is_dir($realPath)) {
                $archive->addFile($realPath, str_replace($realRootPath, '', $realPath));
                continue;
            }

            $directoryIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($realPath), RecursiveIteratorIterator::SELF_FIRST);

            foreach ($directoryIterator as $fileInfo) {
                if (in_array($fileInfo->getFilename(), ['.', '..'])) {
                    continue;
                }

                if ($fileInfo->isDir()) {
                    $archive->addEmptyDir(str_replace($realRootPath, '', $fileInfo->getRealPath()));
                } else {
                    $archive->addFile($fileInfo->getRealPath(), str_replace($realRootPath, '', $fileInfo->getRealPath()));
                }
            }
        }

        if (!$archive->close()) {
            throw new Exception('Compress failed!');
        }

        return $archiveRealPath;
    }

    protected function decompression($archiveName, $workRootPath)
    {
        $tmpPath         = rtrim($workRootPath, '/.\\') . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;
        $archiveRealPath = rtrim($workRootPath, '/.\\') . DIRECTORY_SEPARATOR . 'archive' . DIRECTORY_SEPARATOR . $archiveName . '.zip';
        $archive         = new ZipArchive();

        if ($archive->open($archiveRealPath) !== true) {
            throw new Exception('Open archive failed!');
        }

        $this->clearPath($tmpPath);

        $archive->extractTo($tmpPath);

        if (!$archive->close()) {
            throw new Exception('Decompression failed!');
        }

        foreach (scandir($tmpPath) as $item) {
            if (!in_array($item, ['.', '..']) && is_dir($tmpPath . $item)) {
                return $tmpPath . $item . DIRECTORY_SEPARATOR;
            }
        }

        return $tmpPath;
    }

    protected function clearPath($realPath)
    {
        if (!is_dir($realPath)) {
            return unlink($realPath);
        }

        $directoryIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($realPath), RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($directoryIterator as $fileInfo) {
            if (in_array($fileInfo->getFilename(), ['.', '..'])) {
                continue;
            }

            if ($fileInfo->isDir()) {
                $this->clearPath($fileInfo->getRealPath());
                rmdir($fileInfo->getRealPath());
            } elseif (substr($fileInfo->getPath(), -3) !== 'tmp') {
                unlink($fileInfo->getRealPath());
            }
        }

        return true;
    }
}