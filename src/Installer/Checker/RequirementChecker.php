<?php

namespace Installer\Checker;

class RequirementChecker
{
    public function check(): array
    {
        $requirements = [
            'php' => [
                'version' => '8.1.0',
                'current' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '8.1.0', '>=')
            ],
            'extensions' => [
                'openssl' => extension_loaded('openssl'),
                'pdo' => extension_loaded('pdo'),
                'mbstring' => extension_loaded('mbstring'),
                'tokenizer' => extension_loaded('tokenizer'),
                'xml' => extension_loaded('xml'),
                'ctype' => extension_loaded('ctype'),
                'json' => extension_loaded('json'),
                'bcmath' => extension_loaded('bcmath'),
                'fileinfo' => extension_loaded('fileinfo'),
                'curl' => extension_loaded('curl')
            ]
        ];

        $allMet = $requirements['php']['status'];
        foreach ($requirements['extensions'] as $status) {
            $allMet = $allMet && $status;
        }

        return [
            'success' => $allMet,
            'requirements' => $requirements
        ];
    }
}
