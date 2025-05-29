<h4 class="step-title">Server Requirements</h4>

<div class="step-description">
    The installer will check if your server meets all the requirements for running Laravel Boilerplate.
</div>

<button id="check-requirements-btn" class="btn btn-info mb-4">
    <i class="fas fa-server mr-2"></i> Check Requirements
</button>

<div class="table-responsive">
    <table class="table requirements-table">
        <thead>
            <tr>
                <th>Requirement</th>
                <th>Current</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody id="requirements-results">
            <!-- Requirements results will be loaded here -->
        </tbody>
    </table>
</div>

<div id="requirements-error" class="alert alert-danger d-none mb-4">
    <i class="fas fa-exclamation-circle mr-2"></i>
    Your server does not meet all requirements. Please fix the issues before continuing.
</div>

<div class="text-right mt-4">
    <a href="install.php?step=1" class="btn btn-secondary btn-action mr-2">
        <i class="fas fa-arrow-left mr-2"></i> Back
    </a>
    <a id="requirements-next-btn" href="install.php?step=3" class="btn btn-primary btn-action d-none">
        Next <i class="fas fa-arrow-right ml-2"></i>
    </a>
</div> 