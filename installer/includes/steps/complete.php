<h4 class="step-title">Installation Complete</h4>

<div class="step-description">
    Congratulations! Laravel Boilerplate has been successfully installed.
</div>

<div class="alert alert-success mb-4">
    <i class="fas fa-check-circle mr-2"></i>
    Your Laravel Boilerplate is now installed and ready to use.
</div>

<?php
// If installation was successful, redirect to home
if (isset($_SESSION['installed']) && $_SESSION['installed']) {
    // Clear session
    session_destroy();
    
    // Get current URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/');
    $baseUrl = $protocol . '://' . $host . $uri;
    $homeUrl = str_replace('/install.php', '', $baseUrl);
    
    // Auto redirect after 5 seconds
    echo '<meta http-equiv="refresh" content="5;url=' . $homeUrl . '">';
    echo '<div class="alert alert-info mb-4">';
    echo '<i class="fas fa-spinner fa-spin mr-2"></i>';
    echo 'You will be redirected to the homepage in 5 seconds...';
    echo '</div>';
}
?>

<div class="text-center mt-4">
    <a href="<?php echo $homeUrl ?? './'; ?>" class="btn btn-primary btn-action">
        <i class="fas fa-home mr-2"></i> Go to Homepage
    </a>
    
    <button id="create-installer-btn" class="btn btn-success btn-action ms-2">
        <i class="fas fa-download mr-2"></i> Complete Installation
    </button>
</div>

<div id="installer-result" class="mt-4 d-none">
    <div class="alert alert-info">
        <div id="installer-message"></div>
        <div id="installer-download" class="mt-2 d-none">
            <a id="download-link" href="#" class="btn btn-sm btn-primary">
                <i class="fas fa-download mr-1"></i> Download Installer
            </a>
        </div>
    </div>
</div> 