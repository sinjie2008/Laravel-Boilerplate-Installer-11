<?php

namespace Installer;

use Installer\Checker\RequirementChecker;
use Installer\Checker\PermissionChecker;
use Installer\Validator\DatabaseValidator;
use Installer\Validator\AdminValidator;
use Installer\Service\InstallService;
use Installer\Service\DiagnosticService;
use Installer\Service\ArchiveService;
use Installer\Util;

class Controller
{
    private int $currentStep;

    public function __construct()
    {
        $this->currentStep = isset($_GET['step']) ? (int)$_GET['step'] : 1;
        if ($this->currentStep < 1 || $this->currentStep > count(Config::STEPS)) {
            $this->currentStep = 1;
        }
        $_SESSION['current_step'] = $this->currentStep;
    }

    public function getCurrentStep(): int
    {
        return $this->currentStep;
    }
    
    public function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/');
        return $protocol . '://' . $host . $uri;
    }

    public function getHomeUrl(): string
    {
        return str_replace('/install.php', '', $this->getBaseUrl());
    }

    public function handleRequest(): void
    {
        if (isset($_POST['action'])) {
            $this->handleAjaxRequest($_POST['action'], $_POST);
        } else {
            $this->renderPage();
        }
    }

    private function handleAjaxRequest(string $action, array $data): void
    {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Invalid action'];

        switch ($action) {
            case 'check_requirements':
                $checker = new RequirementChecker();
                $response = $checker->check();
                break;
            case 'check_permissions':
                $checker = new PermissionChecker();
                $response = $checker->check();
                break;
            case 'validate_database':
                $validator = new DatabaseValidator();
                $response = $validator->validate($data);
                break;
            case 'validate_admin':
                $validator = new AdminValidator();
                $response = $validator->validate($data);
                break;
            case 'install':
                $installService = new InstallService(new ArchiveService(), new Util());
                $response = $installService->execute(
                    $_SESSION['database'] ?? [],
                    $_SESSION['admin'] ?? []
                );
                if ($response['success']) {
                    $_SESSION['installed'] = true;
                }
                break;
            case 'run_diagnostics':
                $diagnosticService = new DiagnosticService(new Util());
                $response = $diagnosticService->run();
                break;
        }
        echo json_encode($response);
        exit;
    }

    private function renderPage(): void
    {
        if ($this->currentStep === 7 && isset($_SESSION['installed']) && $_SESSION['installed']) {
            $this->redirectToApp();
        }

        $this->loadView('header');
        $this->loadStepView(Config::STEPS[$this->currentStep]['file']);
        $this->loadView('footer');
    }

    private function loadView(string $viewName): void
    {
        $viewFile = INSTALLER_PATH . '/views/partials/' . $viewName . '.php';
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            die("View file not found: {$viewFile}");
        }
    }

    private function loadStepView(string $stepFileName): void
    {
        $viewFile = INSTALLER_PATH . '/views/steps/' . $stepFileName . '.php';
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            die("Step view file not found: {$viewFile}");
        }
    }

    public function redirectToApp(): void
    {
        // Clear sensitive session data before redirecting, but keep 'installed' if needed for one last check.
        // unset($_SESSION['database']); // Already used
        // unset($_SESSION['admin']); // Already used
        
        // Remove the temporary installation folder
        $tempInstallationPath = INSTALLER_PATH . '/temp_installation';
        if (file_exists($tempInstallationPath)) {
            $util = new Util();
            $util->rrmdir($tempInstallationPath);
        }

        // Redirect to Laravel application
        $homeUrl = 'http://127.0.0.1:8000'; // Default Laravel serve URL
        
        // Clean session completely for a fresh start on the app
        session_destroy();

        header('Location: ' . $homeUrl);
        exit;
    }
}
