<?php
/**
 * Laravel Boilerplate Installer
 * 
 * A step-by-step installation wizard for setting up Laravel Boilerplate
 */

session_start();

// Check if PHP version is sufficient
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    die('PHP version 8.1.0 or higher is required to run this installer.');
}

// Define constants
if (!defined('INSTALLER_PATH')) {
    define('INSTALLER_PATH', __DIR__);
}

// Autoloader for Installer classes
spl_autoload_register(function ($class) {
    $prefix = 'Installer\\';
    $base_dir = dirname(INSTALLER_PATH) . '/src/Installer/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

$controller = new Installer\Controller();
$controller->handleRequest();

// The rest of the file is now handled by $controller->handleRequest()
// which will include header, step file, and footer as needed.
