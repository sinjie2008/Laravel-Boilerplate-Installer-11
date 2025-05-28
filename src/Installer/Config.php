<?php

namespace Installer;

class Config
{
    public const MIN_PHP_VERSION = '8.1.0';

    public const STEPS = [
        1 => ['name' => 'Welcome', 'file' => 'welcome'],
        2 => ['name' => 'Requirements', 'file' => 'requirements'],
        3 => ['name' => 'Permissions', 'file' => 'permissions'],
        4 => ['name' => 'Database', 'file' => 'database'],
        5 => ['name' => 'Admin', 'file' => 'admin'],
        6 => ['name' => 'Installation', 'file' => 'installation'],
        7 => ['name' => 'Complete', 'file' => 'complete']
    ];

    public const REPO_URL = 'https://github.com/sinjie2008/Laravel-Boilerplate-11.git';
    public const INSTALLER_DIR_NAME = 'installer';

    public static function getProjectRoot(): string {
        return dirname(INSTALLER_PATH);
    }
}
