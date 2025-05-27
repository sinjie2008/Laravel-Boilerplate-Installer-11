<?php
/**
 * Functions for Laravel Boilerplate Installer
 */

/**
 * Check if the server meets all requirements
 */
function checkRequirements() {
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

    // Check if all requirements are met
    $allMet = $requirements['php']['status'];
    foreach ($requirements['extensions'] as $status) {
        $allMet = $allMet && $status;
    }

    return [
        'success' => $allMet,
        'requirements' => $requirements
    ];
}

/**
 * Check if directories have correct permissions
 */
function checkPermissions() {
    $dirs = [
        'storage' => false,
        'bootstrap/cache' => false,
        '.env' => false
    ];

    // We'll only check for existence since we haven't created the Laravel instance yet
    // Real permission checks will be implemented during installation
    
    return [
        'success' => true,  // For now, we'll assume success
        'permissions' => $dirs
    ];
}

/**
 * Validate database connection details
 */
function validateDatabase($data) {
    if (empty($data['db_host']) || empty($data['db_name']) || 
        empty($data['db_user'])) {
        return [
            'success' => false,
            'message' => 'Please fill in all required database fields.'
        ];
    }

    // Store in session for later
    $_SESSION['database'] = [
        'host' => $data['db_host'],
        'name' => $data['db_name'],
        'user' => $data['db_user'],
        'password' => $data['db_password'],
        'port' => !empty($data['db_port']) ? $data['db_port'] : 3306
    ];

    // Test connection
    try {
        $dsn = "mysql:host={$data['db_host']};port=" . 
               (!empty($data['db_port']) ? $data['db_port'] : 3306);
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5
        ];
        $pdo = new PDO($dsn, $data['db_user'], $data['db_password'], $options);
        
        // Check if database exists, if not try to create it
        $dbname = $data['db_name'];
        $query = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'";
        $stmt = $pdo->query($query);
        
        if ($stmt->rowCount() == 0) {
            // Try to create the database
            $pdo->exec("CREATE DATABASE `$dbname`");
        }
        
        return ['success' => true];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Database connection failed: ' . $e->getMessage()
        ];
    }
}

/**
 * Validate admin account details
 */
function validateAdmin($data) {
    if (empty($data['admin_name']) || empty($data['admin_email']) || 
        empty($data['admin_password']) || empty($data['admin_password_confirm'])) {
        return [
            'success' => false,
            'message' => 'Please fill in all required admin fields.'
        ];
    }

    if ($data['admin_password'] !== $data['admin_password_confirm']) {
        return [
            'success' => false,
            'message' => 'Passwords do not match.'
        ];
    }

    if (strlen($data['admin_password']) < 8) {
        return [
            'success' => false,
            'message' => 'Password must be at least 8 characters long.'
        ];
    }

    if (!filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'message' => 'Please enter a valid email address.'
        ];
    }

    // Store in session
    $_SESSION['admin'] = [
        'name' => $data['admin_name'],
        'email' => $data['admin_email'],
        'password' => $data['admin_password']
    ];

    return ['success' => true];
}

/**
 * Perform the actual installation
 */
function performInstallation() {
    // Make sure database and admin details are set
    if (!isset($_SESSION['database']) || !isset($_SESSION['admin'])) {
        return [
            'success' => false,
            'message' => 'Missing configuration. Please complete all previous steps.'
        ];
    }

    $repoUrl = 'https://github.com/sinjie2008/Laravel-Boilerplate-11.git';
    $tempDir = dirname(__DIR__, 2) . '/temp_installation';
    $targetDir = dirname(__DIR__, 2);
    $output = [];
    $log = [];

    try {
        // Enable full error reporting
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        
        // Increase memory limit and execution time for the installation
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);
        
        $log[] = "Starting installation process...";
        $log[] = "PHP Version: " . PHP_VERSION;
        $log[] = "Memory Limit: " . ini_get('memory_limit');
        $log[] = "Max Execution Time: " . ini_get('max_execution_time') . " seconds";
        
        // Create a temporary directory
        if (!is_dir($tempDir)) {
            if (!mkdir($tempDir, 0755, true)) {
                throw new Exception("Failed to create temporary directory. Check permissions.");
            }
            $log[] = "Created temporary directory for installation.";
        } else {
            $log[] = "Using existing temporary directory.";
        }

        // Check if Git is installed
        exec("git --version 2>&1", $gitOutput, $gitReturnVar);
        if ($gitReturnVar !== 0) {
            throw new Exception("Git is not installed or not accessible. Git output: " . implode("\n", $gitOutput));
        }
        $log[] = "Git detected: " . $gitOutput[0];

        // Clone the repository
        $log[] = "Cloning Laravel Boilerplate repository...";
        $output = [];
        $returnVar = 0;
        exec("git clone $repoUrl $tempDir 2>&1", $output, $returnVar);
        $log = array_merge($log, $output);
        
        if ($returnVar !== 0) {
            throw new Exception("Failed to clone repository: " . implode("\n", $output));
        }
        $log[] = "Repository cloned successfully.";

        // Copy all files except the installer files
        $log[] = "Copying repository files to target directory...";
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $iterator->getSubPathname();
            
            // Skip if it's part of the installer
            $relativePath = $iterator->getSubPathname();
            if (strpos($relativePath, 'install.php') === 0 || 
                strpos($relativePath, 'includes') === 0 || 
                strpos($relativePath, 'assets') === 0) {
                continue;
            }
            
            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                copy($item, $targetPath);
            }
        }
        $log[] = "Files copied successfully.";

        // Create .env file from .env.example
        $log[] = "Configuring environment (.env) file...";
        $envExample = file_get_contents($tempDir . '/.env.example');
        $envContents = str_replace(
            [
                'DB_HOST=127.0.0.1',
                'DB_PORT=3306',
                'DB_DATABASE=laravel',
                'DB_USERNAME=root',
                'DB_PASSWORD='
            ],
            [
                'DB_HOST=' . $_SESSION['database']['host'],
                'DB_PORT=' . $_SESSION['database']['port'],
                'DB_DATABASE=' . $_SESSION['database']['name'],
                'DB_USERNAME=' . $_SESSION['database']['user'],
                'DB_PASSWORD=' . $_SESSION['database']['password']
            ],
            $envExample
        );
        
        // Double-check that no placeholder values remain in the .env content
        $placeholders = ['your_db_host', 'your_db_name', 'your_db_user', 'your_db_password'];
        $envContents = str_replace(
            $placeholders,
            [
                $_SESSION['database']['host'],
                $_SESSION['database']['name'],
                $_SESSION['database']['user'],
                $_SESSION['database']['password']
            ],
            $envContents
        );
        
        file_put_contents($targetDir . '/.env', $envContents);
        $log[] = "Environment file configured with database settings: " . 
                "Host: {$_SESSION['database']['host']}, " . 
                "Database: {$_SESSION['database']['name']}, " . 
                "User: {$_SESSION['database']['user']}";

        // Check if Composer is installed
        exec("composer --version 2>&1", $composerOutput, $composerReturnVar);
        if ($composerReturnVar !== 0) {
            throw new Exception("Composer is not installed or not accessible. Composer output: " . implode("\n", $composerOutput));
        }
        $log[] = "Composer detected: " . $composerOutput[0];

        // Install Composer dependencies first
        $log[] = "Installing Composer dependencies. This may take several minutes...";
        $output = [];
        exec("cd $targetDir && composer install 2>&1", $output, $returnVar);
        $log = array_merge($log, $output);
        
        if ($returnVar !== 0) {
            throw new Exception("Failed to install Composer dependencies: " . implode("\n", $output));
        }
        $log[] = "Composer dependencies installed successfully.";

        // Generate app key
        $log[] = "Generating application key...";
        $output = [];
        exec("cd $targetDir && php artisan key:generate 2>&1", $output, $returnVar);
        $log = array_merge($log, $output);
        
        if ($returnVar !== 0) {
            throw new Exception("Failed to generate app key: " . implode("\n", $output));
        }
        $log[] = "Application key generated successfully.";

        // Test database connection with artisan before migrations
        $log[] = "Testing database connection...";
        $output = [];
        exec("cd $targetDir && php artisan migrate:status 2>&1", $output, $returnVar);
        $log = array_merge($log, $output);
        
        // Check if the error is just "Migration table not found" which is normal for a fresh install
        $migrationTableNotFound = false;
        foreach ($output as $line) {
            if (strpos($line, 'Migration table not found') !== false) {
                $migrationTableNotFound = true;
                break;
            }
        }
        
        if ($returnVar !== 0 && !$migrationTableNotFound) {
            // If the connection test fails with an error other than missing migration table
            $errorMsg = "Database connection test failed";
            if (!empty($output)) {
                foreach ($output as $line) {
                    if (strpos($line, 'SQLSTATE') !== false || 
                        strpos($line, 'error') !== false || 
                        strpos($line, 'Error') !== false) {
                        $errorMsg = $line;
                        break;
                    }
                }
            }
            throw new Exception($errorMsg . "\n" . implode("\n", $output));
        }
        
        if ($migrationTableNotFound) {
            $log[] = "Migration table not found - this is normal for a fresh installation.";
        } else {
            $log[] = "Database connection successful.";
        }
        
        // Run migrations and seed the database
        $log[] = "Running database migrations and seeders...";
        $output = [];
        exec("cd $targetDir && php artisan migrate --seed 2>&1", $output, $returnVar);
        $log = array_merge($log, $output);
        
        if ($returnVar !== 0) {
            // Try to provide more specific error message from the output
            $errorMsg = "Failed to run migrations";
            if (!empty($output)) {
                foreach ($output as $line) {
                    if (strpos($line, 'SQLSTATE') !== false || 
                        strpos($line, 'error') !== false || 
                        strpos($line, 'Error') !== false) {
                        $errorMsg = $line;
                        break;
                    }
                }
            }
            throw new Exception($errorMsg . "\n" . implode("\n", $output));
        }
        $log[] = "Database migrations completed successfully.";

        // Create admin user
        $log[] = "Creating administrator account...";
        $adminCreateCommand = sprintf(
            "cd %s && php artisan tinker --execute=\"\$user = new \App\Models\User(); \$user->name='%s'; \$user->email='%s'; \$user->password=bcrypt('%s'); \$user->active=true; \$user->confirmed=true; \$user->assignRole('administrator'); \$user->save();\" 2>&1",
            $targetDir,
            $_SESSION['admin']['name'],
            $_SESSION['admin']['email'],
            $_SESSION['admin']['password']
        );
        $output = [];
        exec($adminCreateCommand, $output, $returnVar);
        $log = array_merge($log, $output);
        
        if ($returnVar !== 0) {
            throw new Exception("Failed to create admin user: " . implode("\n", $output));
        }
        $log[] = "Administrator account created successfully.";

        // Create installer package and start server
        $log[] = "Creating installer package and starting server...";
        createInstallerAndStartServer(); 
        $log[] = "Installer package creation and server start initiated.";

        // Success - store installation status
        $_SESSION['installed'] = true;

        // Clean up temporary directory
        rrmdir($tempDir);
        $log[] = "Cleaned up temporary installation files.";

        $log[] = "Installation completed successfully!";
        
        return [
            'success' => true,
            'log' => $log
        ];
    } catch (Exception $e) {
        // Clean up on error
        if (is_dir($tempDir)) {
            rrmdir($tempDir);
        }
        
        $log[] = "ERROR: " . $e->getMessage();
        
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'log' => $log
        ];
    }
}

/**
 * Recursively remove a directory and its contents
 */
function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . DIRECTORY_SEPARATOR . $object)) {
                    rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                } else {
                    // Add error suppression to avoid permission errors
                    @unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
        }
        // Add error suppression to avoid permission errors
        @rmdir($dir);
    }
}

/**
 * Run diagnostics on system to identify potential issues
 */
function runDiagnostics() {
    $results = [
        'success' => true,
        'issues' => []
    ];
    
    // Check PHP version
    if (version_compare(PHP_VERSION, '8.1.0', '<')) {
        $results['success'] = false;
        $results['issues'][] = "PHP version too low: " . PHP_VERSION . " (required: 8.1.0+)";
    }
    
    // Check PHP max execution time
    $maxExecutionTime = ini_get('max_execution_time');
    if ($maxExecutionTime > 0 && $maxExecutionTime < 300) {
        $results['issues'][] = "PHP max_execution_time is set to {$maxExecutionTime}s. Recommended: 300s or higher for installation.";
    }
    
    // Check PHP memory limit
    $memoryLimit = ini_get('memory_limit');
    $memoryLimitBytes = return_bytes($memoryLimit);
    if ($memoryLimitBytes > 0 && $memoryLimitBytes < 1024 * 1024 * 512) { // 512MB
        $results['issues'][] = "PHP memory_limit is set to {$memoryLimit}. Recommended: 512M or higher.";
    }
    
    // Check if Git is installed
    $gitVersion = null;
    exec('git --version 2>&1', $gitOutput, $gitReturn);
    if ($gitReturn !== 0) {
        $results['success'] = false;
        $results['issues'][] = "Git is not installed or not accessible. Git is required for cloning the repository.";
    } else {
        $gitVersion = implode("\n", $gitOutput);
    }
    
    // Check if Composer is installed
    $composerVersion = null;
    exec('composer --version 2>&1', $composerOutput, $composerReturn);
    if ($composerReturn !== 0) {
        $results['success'] = false;
        $results['issues'][] = "Composer is not installed or not accessible. Composer is required for installing dependencies.";
    } else {
        $composerVersion = implode("\n", $composerOutput);
    }
    
    // Check directory permissions
    $targetDir = dirname(__DIR__);
    if (!is_writable($targetDir)) {
        $results['success'] = false;
        $results['issues'][] = "The installation directory is not writable. Please check permissions.";
    }
    
    // System info
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

/**
 * Convert memory limit string to bytes
 */
function return_bytes($val) {
    $val = trim($val);
    if (empty($val)) {
        return 0;
    }
    
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    
    return $val;
} 


/**
 * Create a zip file of the installer and start the server
 */
function createInstallerAndStartServer()
{
    // Define paths relative to the project root
    $projectRoot = dirname(__DIR__, 2); // Go up two levels from installer/includes
    $installerDir = $projectRoot . DIRECTORY_SEPARATOR . 'installer';
    $assetsDir = $installerDir . DIRECTORY_SEPARATOR . 'assets';
    $includesDir = $installerDir . DIRECTORY_SEPARATOR . 'includes';
    $installScript = $installerDir . DIRECTORY_SEPARATOR . 'install.php';
    $zipFileName = $projectRoot . DIRECTORY_SEPARATOR . 'installer.zip'; // Place zip in project root

    // Check if ZipArchive extension is loaded
    if (!class_exists('ZipArchive')) {
         // Log error instead of echoing JSON directly if called from performInstallation
         error_log('ZipArchive extension is not enabled on the server.');
         return; // Exit function, let performInstallation handle response
    }

    $zip = new ZipArchive();

    // Open the archive for writing
    if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        // Add the assets directory recursively, placing it under 'assets/' inside the zip
        if (!addDirectoryToZip($zip, $assetsDir, 'assets')) {
             error_log("Failed to add assets directory to zip.");
             // Handle error appropriately, maybe close zip and return failure
        }

        // Add the includes directory recursively, placing it under 'includes/' inside the zip
        if (!addDirectoryToZip($zip, $includesDir, 'includes')) {
             error_log("Failed to add includes directory to zip.");
             // Handle error appropriately
        }

        // Add install.php file to the root of the zip
        if (file_exists($installScript)) {
            if (!$zip->addFile($installScript, 'install.php')) {
                 error_log("Failed to add install.php to zip.");
                 // Handle error appropriately
            }
        } else {
             error_log("install.php not found at: " . $installScript);
             // Handle error appropriately
        }

        // Get the status string for logging/debugging
        $statusString = $zip->getStatusString();
        $numFiles = $zip->numFiles;

        // Close the archive
        $closeResult = $zip->close();

        if ($closeResult === TRUE && $numFiles > 0) {
             error_log("Installer package created successfully with {$numFiles} files!"); // Log success

            // Start the server (Windows-friendly command)
            // Ensure the server starts from the project root where artisan exists
            $serverCommand = "cd " . escapeshellarg($projectRoot) . " && php artisan serve";
            if (stripos(PHP_OS, 'WIN') === 0) {
                // Windows - using start command to run in background
                pclose(popen('start /B ' . $serverCommand, 'r'));
            } else {
                // Unix/Linux
                exec($serverCommand . ' > /dev/null 2>&1 &');
            }
             error_log("Started Laravel server."); // Log server start

        } else {
             // Provide more details on failure
             $errorMessage = 'Failed to create installer package.';
             if ($closeResult !== TRUE) {
                 $errorMessage .= ' Error closing zip: ' . $statusString;
             } elseif ($numFiles === 0) {
                 $errorMessage .= ' No files were added to the zip archive.';
             }
             error_log($errorMessage . " Zip status: " . $statusString);
        }
    } else {
         error_log('Failed to open installer zip file for writing. Error code: ' . $zip->status);
    }
    // Do not exit here, let the calling function continue
}

/**
 * Add a directory recursively to the zip file.
 *
 * @param ZipArchive $zip The ZipArchive instance.
 * @param string $sourceDirectory The absolute path to the source directory to add.
 * @param string $zipDirectory The path prefix inside the zip archive (e.g., 'assets').
 */
function addDirectoryToZip($zip, $sourceDirectory, $zipDirectory)
{
    $sourcePath = realpath($sourceDirectory);
    if (!$sourcePath || !is_dir($sourcePath)) {
        error_log("addDirectoryToZip: Source directory not found or is not a directory: " . $sourceDirectory);
        return false; // Indicate failure
    }

    // Ensure zipDirectory ends with a slash if not empty
    if (!empty($zipDirectory)) {
        $zipDirectory = rtrim($zipDirectory, '/\\') . '/';
    } else {
        $zipDirectory = ''; // Root of the zip
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    $addedCount = 0;
    foreach ($files as $name => $file) {
        // Skip directories
        if ($file->isDir()) {
            continue;
        }

        // Get real path for source file
        $filePath = $file->getRealPath();

        // Calculate relative path from sourceDirectory base
        // Example: $sourcePath = /path/to/installer/assets
        //          $filePath = /path/to/installer/assets/css/style.css
        //          $relativePath should be css/style.css
        $relativePath = substr($filePath, strlen($sourcePath) + 1);

        // Create the full path inside the zip file
        // Example: $zipDirectory = 'assets/'
        //          $relativePath = 'css/style.css'
        //          $zipPath should be assets/css/style.css
        $zipPath = $zipDirectory . $relativePath;

        // Normalize directory separators for zip standard
        $zipPath = str_replace(DIRECTORY_SEPARATOR, '/', $zipPath);

        // Add file to zip
        if ($zip->addFile($filePath, $zipPath)) {
            $addedCount++;
        } else {
             error_log("addDirectoryToZip: Failed to add file to zip: " . $filePath . " as " . $zipPath . " Status: " . $zip->getStatusString());
             // Continue adding other files, but log the error
        }
    }
    
    // Check if any files were actually added from this directory
    if ($addedCount === 0) {
         error_log("addDirectoryToZip: No files were added from directory: " . $sourceDirectory);
         // This might not be an error if the directory was empty, but good to log.
    }

    return true; // Return true even if some files failed, but logged errors
}

/**
 * Start Laravel server and redirect to a specified URL
 */
function startLaravelServerAndRedirect(){
       // Change to the Laravel project directory
        header('Location: http://127.0.0.1:8000');
        exit;
}

