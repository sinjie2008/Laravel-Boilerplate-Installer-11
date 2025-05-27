<?php
/**
 * Laravel Boilerplate Installer
 * 
 * A step-by-step installation wizard for setting up Laravel Boilerplate
 */

session_start();

require_once 'includes/functions.php';

// Check if PHP version is sufficient
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    die('PHP version 8.1.0 or higher is required to run this installer.');
}

// Define constants
define('INSTALLER_PATH', __DIR__);
define('STEPS', [
    1 => ['name' => 'Welcome', 'file' => 'welcome'],
    2 => ['name' => 'Requirements', 'file' => 'requirements'],
    3 => ['name' => 'Permissions', 'file' => 'permissions'],
    4 => ['name' => 'Database', 'file' => 'database'],
    5 => ['name' => 'Admin', 'file' => 'admin'],
    6 => ['name' => 'Installation', 'file' => 'installation'],
    7 => ['name' => 'Complete', 'file' => 'complete']
]);

// Process AJAX requests
if (isset($_POST['action'])) {
   
    $action = $_POST['action'];
    switch ($action) {
        case 'check_requirements':
            echo json_encode(checkRequirements());
            break;
        case 'check_permissions':
            echo json_encode(checkPermissions());
            break;
        case 'validate_database':
            echo json_encode(validateDatabase($_POST));
            break;
        case 'validate_admin':
            echo json_encode(validateAdmin($_POST));
            break;
        case 'install':
            echo json_encode(performInstallation());
            break;
        case 'run_diagnostics':
            echo json_encode(runDiagnostics());
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

// Set current step
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($step < 1 || $step > count(STEPS)) {
    $step = 1;
}

// Store step in session
$_SESSION['current_step'] = $step;

// If installation was successful and we are on the complete step, redirect
if ($step === 7 && isset($_SESSION['installed']) && $_SESSION['installed']) {
    // Get current URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/');
    $baseUrl = $protocol . '://' . $host . $uri;
    $homeUrl = str_replace('/install.php', '', $baseUrl);
    
    // Call the function to start Laravel server and redirect
    startLaravelServerAndRedirect();
}

// Load the UI
require_once 'includes/header.php';
require_once 'includes/steps/' . STEPS[$step]['file'] . '.php';
require_once 'includes/footer.php';
