<h4 class="step-title">Folder Permissions</h4>

<div class="step-description">
    The installer will check if the necessary folders and files have the correct permissions.
</div>

<button id="check-permissions-btn" class="btn btn-info mb-4">
    <i class="fas fa-folder-open mr-2"></i> Check Permissions
</button>

<div class="table-responsive">
    <table class="table requirements-table">
        <thead>
            <tr>
                <th>Directory/File</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody id="permissions-results">
            <!-- Permissions results will be loaded here -->
        </tbody>
    </table>
</div>

<div id="permissions-error" class="alert alert-danger d-none mb-4">
    <i class="fas fa-exclamation-circle mr-2"></i>
    Some directories do not have correct permissions. Please fix the issues before continuing.
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle mr-2"></i>
    The actual permission check will be performed during installation. This is just a preliminary check.
</div>

<div class="text-right mt-4">
    <a href="install.php?step=2" class="btn btn-secondary btn-action mr-2">
        <i class="fas fa-arrow-left mr-2"></i> Back
    </a>
    <a id="permissions-next-btn" href="install.php?step=4" class="btn btn-primary btn-action d-none">
        Next <i class="fas fa-arrow-right ml-2"></i>
    </a>
</div> 