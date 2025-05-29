<?php

namespace Installer\Service;

use Installer\Config;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class ArchiveService
{
    public function createInstallerPackage(): void
    {
        $projectRoot = Config::getProjectRoot();
        $installerDir = $projectRoot . DIRECTORY_SEPARATOR . Config::INSTALLER_DIR_NAME;
        $assetsDir = $installerDir . DIRECTORY_SEPARATOR . 'assets';
        $srcDir = $installerDir . DIRECTORY_SEPARATOR . 'src';
        $viewsDir = $installerDir . DIRECTORY_SEPARATOR . 'views';
        $installScript = $installerDir . DIRECTORY_SEPARATOR . 'install.php';
        $zipFileName = $projectRoot . DIRECTORY_SEPARATOR . 'installer.zip';

        if (!class_exists('ZipArchive')) {
            error_log('ZipArchive extension is not enabled on the server.');
            return;
        }

        $zip = new ZipArchive();

        if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            if (is_dir($assetsDir)) {
                $this->addDirectoryToZip($zip, $assetsDir, 'installer/assets');
            }
            if (is_dir($srcDir)) {
                $this->addDirectoryToZip($zip, $srcDir, 'installer/src');
            }
            if (is_dir($viewsDir)) {
                $this->addDirectoryToZip($zip, $viewsDir, 'installer/views');
            }

            if (file_exists($installScript)) {
                if (!$zip->addFile($installScript, 'installer/install.php')) {
                    error_log("Failed to add install.php to zip. Status: " . $zip->getStatusString());
                }
            } else {
                error_log("install.php not found at: " . $installScript);
            }

            $statusString = $zip->getStatusString();
            $numFiles = $zip->numFiles;
            $closeResult = $zip->close();

            if ($closeResult === TRUE && $numFiles > 0) {
                error_log("Installer package created successfully with {$numFiles} files at {$zipFileName}!");
            } else {
                $errorMessage = 'Failed to create installer package.';
                if ($closeResult !== TRUE) {
                    $errorMessage .= ' Error closing zip: ' . $statusString;
                } elseif ($numFiles === 0) {
                    $errorMessage .= ' No files were added to the zip archive. Ensure paths are correct (assets, src, views, install.php).';
                    error_log("Checked paths: assetsDir={$assetsDir}, srcDir={$srcDir}, viewsDir={$viewsDir}, installScript={$installScript}");
                }
                error_log($errorMessage . " Zip status: " . $statusString);
            }
        } else {
            error_log('Failed to open installer zip file for writing. Error code: ' . $zip->status . ' (' . $zip->getStatusString() . ') at ' . $zipFileName);
        }
    }

    private function addDirectoryToZip(ZipArchive $zip, string $sourceDirectory, string $zipDirectory): bool
    {
        $sourcePath = realpath($sourceDirectory);
        if (!$sourcePath || !is_dir($sourcePath)) {
            error_log("addDirectoryToZip: Source directory not found or is not a directory: " . $sourceDirectory);
            return false;
        }

        $zipDirectory = rtrim($zipDirectory, '/\\') . '/';

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $addedCount = 0;
        foreach ($files as $name => $file) {
            if ($file->isDir()) {
                continue;
            }

            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($sourcePath) + 1);
            $zipPath = $zipDirectory . $relativePath;
            $zipPath = str_replace(DIRECTORY_SEPARATOR, '/', $zipPath);

            if ($zip->addFile($filePath, $zipPath)) {
                $addedCount++;
            } else {
                error_log("addDirectoryToZip: Failed to add file to zip: " . $filePath . " as " . $zipPath . " Status: " . $zip->getStatusString());
            }
        }

        if ($addedCount === 0 && iterator_count($files) > 0) {
             error_log("addDirectoryToZip: No files were added from non-empty directory: " . $sourceDirectory . ". Zip path prefix: " . $zipDirectory);
        } elseif ($addedCount > 0) {
             error_log("addDirectoryToZip: Added {$addedCount} files from {$sourceDirectory} to {$zipDirectory}");
        } else {
             error_log("addDirectoryToZip: Directory {$sourceDirectory} was empty or all files failed to add.");
        }

        return true;
    }
}
