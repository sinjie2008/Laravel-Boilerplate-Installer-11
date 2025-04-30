<h4 class="step-title">Database Configuration</h4>

<div class="step-description">
    Please provide your database connection details. These will be used to configure your Laravel application.
    <div class="alert alert-info mt-3">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Important:</strong> Make sure your database already exists or the user has permissions to create it.
    </div>
</div>

<form id="database-form">
    <div class="alert alert-danger d-none mb-4" id="database-error"></div>
    
    <div class="form-group mb-3">
        <label for="db_host">Database Host <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
        <div class="invalid-feedback">Database host is required</div>
        <small class="form-text text-muted">Usually "localhost" or "127.0.0.1" for local installations</small>
    </div>
    
    <div class="form-group mb-3">
        <label for="db_port">Database Port</label>
        <input type="text" class="form-control" id="db_port" name="db_port" value="3306">
        <small class="form-text text-muted">Default is 3306 for MySQL</small>
    </div>
    
    <div class="form-group mb-3">
        <label for="db_name">Database Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="db_name" name="db_name" required>
        <div class="invalid-feedback">Database name is required</div>
        <small class="form-text text-muted">The database should already exist or the user should have permissions to create it</small>
    </div>
    
    <div class="form-group mb-3">
        <label for="db_user">Database Username <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="db_user" name="db_user" required>
        <div class="invalid-feedback">Database username is required</div>
    </div>
    
    <div class="form-group mb-3">
        <label for="db_password">Database Password</label>
        <input type="password" class="form-control" id="db_password" name="db_password">
        <small class="form-text text-muted">Leave blank if no password is required</small>
    </div>
    
    <div class="text-end mt-4">
        <a href="install.php?step=3" class="btn btn-secondary btn-action me-2">
            <i class="fas fa-arrow-left me-2"></i> Back
        </a>
        <button type="submit" class="btn btn-primary btn-action">
            Next <i class="fas fa-arrow-right ms-2"></i>
        </button>
    </div>
</form> 