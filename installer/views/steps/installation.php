<h4 class="step-title">Installation</h4>

<div class="step-description">
    The installer will now:
    <ul>
        <li>Clone the latest Laravel Boilerplate from GitHub</li>
        <li>Configure the application with your database settings</li>
        <li>Run migrations and seed the database</li>
        <li>Create the administrator account</li>
    </ul>
</div>

<div class="alert alert-warning mb-4">
    <i class="fas fa-exclamation-triangle me-2"></i>
    This process may take a few minutes. Please do not close your browser.
</div>

<div class="alert alert-danger d-none mb-4" id="installation-error"></div>

<div class="d-flex mb-4">
    <button id="run-diagnostics-btn" class="btn btn-secondary me-2">
        <i class="fas fa-stethoscope me-2"></i> Run Diagnostics
    </button>
    
    <button id="start-installation-btn" class="btn btn-primary">
        <i class="fas fa-cogs me-2"></i> Start Installation
    </button>
</div>

<pre id="installation-log" class="installation-log"></pre>

<div class="text-end mt-4">
    <a href="install.php?step=5" class="btn btn-secondary btn-action me-2">
        <i class="fas fa-arrow-left me-2"></i> Back
    </a>
    <a id="installation-next-btn" href="install.php?step=7" class="btn btn-success btn-action d-none">
        Complete <i class="fas fa-check ms-2"></i>
    </a>
</div> 