<?php

namespace Installer\Service;

use Installer\Config;
use Installer\Util;

class DiagnosticService
{
    private Util $util;

    public function __construct(Util $util)
    {
        $this->util = $util;
    }

    public function run(): array
    {
        $results = [
            'success' => true,
            'issues' => [],
            'system_info' => []
        ];

        // PHP version
        if (version_compare(PHP_VERSION, Config::MIN_PHP_VERSION, '<')) {
            $results['success'] = false;
            $results['issues'][] = "PHP version too low: " . PHP_VERSION . " (required: " . Config::MIN_PHP_VERSION . "+)";
        }

        // PHP max execution time
        $maxExecutionTime = ini_get('max_execution_time');
        if ($maxExecutionTime > 0 && $maxExecutionTime < 300) {
            $results['issues'][] = "PHP max_execution_time is set to {$maxExecutionTime}s. Recommended: 300s or higher for installation.";
        }

        // PHP memory limit
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->util->returnBytes($memoryLimit);
        if ($memoryLimitBytes > 0 && $memoryLimitBytes < 512 * 1024 * 1024) { // 512MB
            $results['issues'][] = "PHP memory_limit is set to {$memoryLimit}. Recommended: 512M or higher.";
        }

        // Git
        exec('git --version 2>&1', $gitOutput, $gitReturn);
        $gitVersion = ($gitReturn === 0) ? implode("\n", $gitOutput) : null;
        if ($gitReturn !== 0) {
            $results['success'] = false;
            $results['issues'][] = "Git is not installed or not accessible. Git is required for cloning the repository.";
        }

        // Composer
        exec('composer --version 2>&1', $composerOutput, $composerReturn);
        $composerVersion = ($composerReturn === 0) ? implode("\n", $composerOutput) : null;
        if ($composerReturn !== 0) {
            $results['success'] = false;
            $results['issues'][] = "Composer is not installed or not accessible. Composer is required for installing dependencies.";
        }

        // Directory permissions (project root)
        $projectRoot = Config::getProjectRoot();
        if (!is_writable($projectRoot)) {
            $results['success'] = false;
            $results['issues'][] = "The project root directory ({$projectRoot}) is not writable. Please check permissions.";
        }

        $results['system_info'] = [
            'php_version' => PHP_VERSION,
            'os' => PHP_OS,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'git_version' => $gitVersion,
            'composer_version' => $composerVersion,
            'max_execution_time' => $maxExecutionTime,
            'memory_limit' => $memoryLimit
        ];

        return $results;
    }
}
