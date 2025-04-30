<h4 class="step-title">Administrator Account</h4>

<div class="step-description">
    Create an administrator account for your Laravel Boilerplate installation.
</div>

<form id="admin-form">
    <div class="alert alert-danger d-none mb-4" id="admin-error"></div>
    
    <div class="form-group">
        <label for="admin_name">Full Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="admin_name" name="admin_name" required>
        <div class="invalid-feedback">Full name is required</div>
    </div>
    
    <div class="form-group">
        <label for="admin_email">Email Address <span class="text-danger">*</span></label>
        <input type="email" class="form-control" id="admin_email" name="admin_email" required>
        <div class="invalid-feedback">Valid email address is required</div>
    </div>
    
    <div class="form-group">
        <label for="admin_password">Password <span class="text-danger">*</span></label>
        <input type="password" class="form-control" id="admin_password" name="admin_password" required>
        <div class="invalid-feedback">Password must be at least 8 characters long</div>
        <small class="form-text text-muted">Password must be at least 8 characters long</small>
    </div>
    
    <div class="form-group">
        <label for="admin_password_confirm">Confirm Password <span class="text-danger">*</span></label>
        <input type="password" class="form-control" id="admin_password_confirm" name="admin_password_confirm" required>
        <div class="invalid-feedback">Passwords do not match</div>
    </div>
    
    <div class="text-right mt-4">
        <a href="install.php?step=4" class="btn btn-secondary btn-action mr-2">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
        <button type="submit" class="btn btn-primary btn-action">
            Next <i class="fas fa-arrow-right ml-2"></i>
        </button>
    </div>
</form> 