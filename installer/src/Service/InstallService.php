<?php

namespace Installer\Service;

use Installer\Config;
use Installer\Util;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Exception;

class InstallService
{
    private ArchiveService $archiveService;
    private Util $util;
    private array $log = [];

    public function __construct(ArchiveService $archiveService, Util $util)
    {
        $this->archiveService = $archiveService;
        $this->util = $util;
    }

    public function execute(array $dbConfig, array $adminConfig): array
    {
        if (empty($dbConfig) || empty($adminConfig)) {
            return [
                'success' => false,
                'message' => 'Missing configuration. Please complete all previous steps.',
                'log' => $this->log
            ];
        }

        $projectRoot = Config::getProjectRoot();
        $tempDir = $projectRoot . DIRECTORY_SEPARATOR . 'temp_installation';

        try {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
            ini_set('memory_limit', '512M');
            set_time_limit(600); // Extended execution time

            $this->logMessage("Starting installation process...");
            $this->logMessage("PHP Version: " . PHP_VERSION);
            $this->logMessage("Memory Limit: " . ini_get('memory_limit'));
            $this->logMessage("Max Execution Time: " . ini_get('max_execution_time') . " seconds");
            $this->logMessage("Project Root: " . $projectRoot);
            $this->logMessage("Temporary Directory: " . $tempDir);


            if (is_dir($tempDir)) {
                $this->logMessage("Attempting to clean up existing temporary directory using system command...");
                $command = '';
                if (stripos(PHP_OS, 'WIN') === 0) {
                    $command = 'rmdir /s /q ' . escapeshellarg($tempDir);
                } else {
                    $command = 'rm -rf ' . escapeshellarg($tempDir);
                }
                $output = [];
                $returnVar = -1;
                exec($command . ' 2>&1', $output, $returnVar);
                if ($returnVar === 0) {
                    $this->logMessage("System command cleanup successful.");
                } else {
                    $this->logMessage("System command cleanup failed. Falling back to PHP rrmdir. Output: " . implode("\n", $output));
                    $this->util->rrmdir($tempDir);
                }

                if (is_dir($tempDir)) {
                    throw new Exception("Failed to remove existing temporary directory. Check permissions for {$tempDir}.");
                }
            }
            // Only create if it doesn't exist after cleanup
            if (!is_dir($tempDir)) {
                if (!mkdir($tempDir, 0777, true)) {
                    throw new Exception("Failed to create temporary directory. Check permissions for {$projectRoot}.");
                }
                $this->logMessage("Created temporary directory for installation.");
            } else {
                $this->logMessage("Temporary directory already exists and is ready.");
            }


            $this->executeCommand("git --version", "Git check");
            $this->executeCommand("git clone " . Config::REPO_URL . " " . escapeshellarg($tempDir), "Cloning repository");

            $this->logMessage("Copying repository files to target directory...");
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                $targetPath = $projectRoot . DIRECTORY_SEPARATOR . $iterator->getSubPathname();
                $relativePath = $iterator->getSubPathname();

                // Skip 'installer' directory contents and .git directory
                if (strpos($relativePath, Config::INSTALLER_DIR_NAME . DIRECTORY_SEPARATOR) === 0 ||
                    strpos($relativePath, '.git') === 0 ) {
                    continue;
                }

                if ($item->isDir()) {
                    if (!is_dir($targetPath)) {
                        mkdir($targetPath, 0777, true);
                    }
                } else {
                    if (copy($item->getRealPath(), $targetPath) === false) {
                         throw new Exception("Failed to copy file: {$item->getRealPath()} to {$targetPath}");
                    }
                }
            }
            $this->logMessage("Files copied successfully.");

            $this->logMessage("Configuring environment (.env) file...");
            $envExamplePath = $projectRoot . '/.env.example';
            $envPath = $projectRoot . '/.env';

            if (!file_exists($envExamplePath)) {
                 // If .env.example was not copied from repo root, try to find it in tempDir
                 $envExamplePath = $tempDir . '/.env.example';
                 if (!file_exists($envExamplePath)) {
                    throw new Exception(".env.example not found in {$projectRoot} or {$tempDir}.");
                 }
            }

            $envExampleContent = file_get_contents($envExamplePath);
            if ($envExampleContent === false) throw new Exception("Could not read .env.example");

            $envContents = str_replace(
                [
                    'DB_HOST=127.0.0.1', 'DB_PORT=3306', 'DB_DATABASE=laravel', 'DB_USERNAME=root', 'DB_PASSWORD=',
                    'your_db_host', 'your_db_name', 'your_db_user', 'your_db_password' // Common placeholders
                ],
                [
                    'DB_HOST=' . $dbConfig['host'], 'DB_PORT=' . $dbConfig['port'], 'DB_DATABASE=' . $dbConfig['name'],
                    'DB_USERNAME=' . $dbConfig['user'], 'DB_PASSWORD=' . $dbConfig['password'],
                    $dbConfig['host'], $dbConfig['name'], $dbConfig['user'], $dbConfig['password']
                ],
                $envExampleContent
            );
            if (file_put_contents($envPath, $envContents) === false) throw new Exception("Could not write .env file to {$envPath}");
            $this->logMessage("Environment file configured.");

            $this->executeCommand("composer --version", "Composer check", $projectRoot);
            $this->executeCommand("composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist", "Installing Composer dependencies", $projectRoot);
            $this->executeCommand("php artisan key:generate --force", "Generating application key", $projectRoot);
            
            // Test DB connection via migrate:status
            $output = []; $returnVar = 0;
            $this->executeShellCommand("php artisan migrate:status", $projectRoot, $output, $returnVar, "Testing database connection");
            $migrationTableNotFound = false;
            foreach($output as $line) { if (strpos($line, "Migration table not found") !== false) $migrationTableNotFound = true; }
            if ($returnVar !== 0 && !$migrationTableNotFound) throw new Exception("Database connection test failed: " . implode("\n", $output));
            if ($migrationTableNotFound) $this->logMessage("Migration table not found (normal for fresh install).");
            else $this->logMessage("Database connection successful.");

            $this->executeCommand("php artisan migrate --seed --force", "Running migrations and seeders", $projectRoot);

            $this->logMessage("Creating administrator account...");
            $adminName = escapeshellarg($adminConfig['name']);
            $adminEmail = escapeshellarg($adminConfig['email']);
            $adminPassword = escapeshellarg($adminConfig['password']);
            $tinkerCommand = "User::create(['name' => $adminName, 'email' => $adminEmail, 'password' => bcrypt($adminPassword), 'active' => true, 'confirmed_at' => now()])->assignRole('administrator');";
            // Note: Tinker execute has limitations. Might need a dedicated Artisan command in the boilerplate for robust user creation.
            // For now, simplifying the command. A proper command would be:
            // $tinkerCommand = escapeshellarg("\$user = new \App\Models\User; \$user->name={$adminName}; \$user->email={$adminEmail}; \$user->password=bcrypt({$adminPassword}); \$user->active=true; \$user->email_verified_at=now(); \$user->save(); \$user->assignRole('administrator');");
            // Simplified for broad compatibility and reduced escaping issues
            $createAdminCmd = sprintf(
                "php artisan tinker --execute=\"App\Models\User::factory()->create(['name' => '%s', 'email' => '%s', 'password' => bcrypt('%s'), 'active' => true, 'email_verified_at' => now()])->assignRole('administrator');\"",
                addslashes($adminConfig['name']), addslashes($adminConfig['email']), addslashes($adminConfig['password'])
            );
            $this->executeCommand($createAdminCmd, "Creating admin user", $projectRoot);

            $this->logMessage("Creating installer package...");
            $this->archiveService->createInstallerPackage();

            $this->logMessage("Attempting to start Laravel development server...");
            $this->startLaravelServer($projectRoot);

            $this->util->rrmdir($tempDir);
            $this->logMessage("Cleaned up temporary installation files.");
            $this->logMessage("Installation completed successfully!");

            return ['success' => true, 'log' => $this->log];

        } catch (Exception $e) {
            $this->logMessage("ERROR: " . $e->getMessage() . "\nStack trace:\n" . $e->getTraceAsString());
            if (is_dir($tempDir)) {
                $this->util->rrmdir($tempDir);
            }
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'log' => $this->log
            ];
        }
    }

    private function logMessage(string $message): void
    {
        $this->log[] = "[" . date("Y-m-d H:i:s") . "] " . $message;
    }

    private function executeCommand(string $command, string $description, ?string $cwd = null): void
    {
        $output = [];
        $returnVar = -1;
        $this->executeShellCommand($command, $cwd, $output, $returnVar, $description);
        if ($returnVar !== 0) {
            throw new Exception("Failed {$description}: " . implode("\n", $output));
        }
    }

    private function executeShellCommand(string $command, ?string $cwd, array &$output, int &$returnVar, string $description): void
    {
        $this->logMessage("Executing: {$description} (Command: {$command})");
        $originalDir = getcwd();
        if ($cwd && !chdir($cwd)) {
            throw new Exception("Failed to change directory to {$cwd}");
        }

        // Ensure command output is captured properly, especially errors
        $fullCommand = $command . ' 2>&1';
        exec($fullCommand, $output, $returnVar);

        if ($cwd) {
            chdir($originalDir);
        }

        foreach($output as $line) {
            $this->logMessage("Output: " . $line);
        }
        $this->logMessage("{$description} completed with status: {$returnVar}.");
    }

    private function startLaravelServer(string $projectRoot): void
    {
        $serverCommand = "php artisan serve";
        $this->logMessage("Preparing to start server with command: {$serverCommand} in {$projectRoot}");

        $originalDir = getcwd();
        if (!chdir($projectRoot)) {
             $this->logMessage("Failed to change directory to {$projectRoot} for starting server.");
             return;
        }

        if (stripos(PHP_OS, 'WIN') === 0) {
            // Windows - using start command to run in background
            // Ensure 'start' is available and path to php is correct if not in system PATH
            pclose(popen('start /B cmd /c "' . $serverCommand . '"', 'r'));
            $this->logMessage("Attempted to start server on Windows in background.");
        } else {
            // Unix/Linux
            // nohup and & ensure it runs in background and persists after script ends
            exec("nohup {$serverCommand} > storage/logs/laravel_serve.log 2>&1 &");
            $this->logMessage("Attempted to start server on Unix/Linux in background. Log in storage/logs/laravel_serve.log");
        }
        chdir($originalDir);
        $this->logMessage("Note: Server start is asynchronous. Check http://127.0.0.1:8000 manually.");
    }
}
