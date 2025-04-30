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
    require_once 'includes/functions.php';
    
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
        case 'create_installer':
            createInstallerAndStartServer();
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

// Load the UI
require_once 'includes/header.php';
require_once 'includes/steps/' . STEPS[$step]['file'] . '.php';
require_once 'includes/footer.php';

/**
 * Create a zip file and start the server
 */
function createInstallerAndStartServer()
{
    // Create a new zip archive
    $zip = new ZipArchive();
    $zipFileName = 'installer.zip';
    
    // Open the archive for writing
    if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        // Add the assets directory recursively
        addDirectoryToZip($zip, 'assets', 'assets');
        
        // Add the includes directory recursively
        addDirectoryToZip($zip, 'includes', 'includes');
        
        // Add install.php file
        $zip->addFile('install.php', 'install.php');
        
        // Close the archive
        $zip->close();
        
        // Start the server (Windows-friendly command)
        if (stripos(PHP_OS, 'WIN') === 0) {
            // Windows - using start command to run in background
            pclose(popen('start /B php artisan serve', 'r'));
        } else {
            // Unix/Linux
            exec('php artisan serve > /dev/null 2>&1 &');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Installer package created successfully!',
            'zipFile' => $zipFileName,
            'serverUrl' => 'http://localhost:8000'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create installer package.'
        ]);
    }
    exit;
}

/**
 * Add a directory recursively to the zip file
 */
function addDirectoryToZip($zip, $directory, $zipDirectory)
{
    if (!is_dir($directory)) {
        return false;
    }
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen(dirname($directory)) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }
    
    return true;
}
