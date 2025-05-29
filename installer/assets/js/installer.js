/* Laravel Boilerplate Installer JavaScript */
 
 $(document).ready(function() {
    const InstallerApp = {
        // Helper methods for button state
        setButtonLoading: function($btn, loadingText) {
            $btn.html(loadingText);
            $btn.prop('disabled', true);
        },

        restoreButtonState: function($btn, originalText) {
            $btn.html(originalText);
            $btn.prop('disabled', false);
        },
    
        // Event Handlers
        checkRequirements: function(event) {
            const $btn = $(event.currentTarget);
            const originalBtnText = $btn.html();
            this.setButtonLoading($btn, '<span class="spinner-border spinner-border-sm me-2"></span> Checking...');
            
            const app = this;
            $.ajax({
                url: 'install.php',
                type: 'POST',
                dataType: 'json',
                data: { action: 'check_requirements' },
                success: function(response) {
                    const $requirementsTable = $('#requirements-results');
                    $requirementsTable.empty();
                    
                    const phpReq = response.requirements.php;
                    $requirementsTable.append(`
                        <tr>
                            <td>PHP ${phpReq.version}+</td>
                            <td>${phpReq.current}</td>
                            <td class="text-center">
                                ${phpReq.status 
                                    ? '<i class="fas fa-check-circle text-success status-icon"></i>' 
                                    : '<i class="fas fa-times-circle text-danger status-icon"></i>'}
                            </td>
                        </tr>
                    `);
                    
                    for (const [extension, status] of Object.entries(response.requirements.extensions)) {
                        $requirementsTable.append(`
                            <tr>
                                <td>${extension} Extension</td>
                                <td>${status ? 'Installed' : 'Not Installed'}</td>
                                <td class="text-center">
                                    ${status 
                                        ? '<i class="fas fa-check-circle text-success status-icon"></i>' 
                                        : '<i class="fas fa-times-circle text-danger status-icon"></i>'}
                                </td>
                            </tr>
                        `);
                    }
                    
                    if (response.success) {
                        $('#requirements-next-btn').removeClass('d-none');
                        $('#requirements-error').addClass('d-none');
                    } else {
                        $('#requirements-next-btn').addClass('d-none');
                        $('#requirements-error').removeClass('d-none');
                    }
                    app.restoreButtonState($btn, originalBtnText);
                },
                error: function() {
                    alert('An error occurred while checking requirements');
                    app.restoreButtonState($btn, originalBtnText);
                }
            });
        },
    
        checkPermissions: function(event) {
            const $btn = $(event.currentTarget);
            const originalBtnText = $btn.html();
            this.setButtonLoading($btn, '<span class="spinner-border spinner-border-sm me-2"></span> Checking...');

            const app = this;
            $.ajax({
                url: 'install.php',
                type: 'POST',
                dataType: 'json',
                data: { action: 'check_permissions' },
                success: function(response) {
                    const $permissionsTable = $('#permissions-results');
                    $permissionsTable.empty();
                    
                    for (const [directory, status] of Object.entries(response.permissions)) {
                        $permissionsTable.append(`
                            <tr>
                                <td>${directory}</td>
                                <td class="text-center">
                                    ${status 
                                        ? '<i class="fas fa-check-circle text-success status-icon"></i>' 
                                        : '<i class="fas fa-times-circle text-danger status-icon"></i>'}
                                </td>
                            </tr>
                        `);
                    }
                    
                    if (response.success) {
                        $('#permissions-next-btn').removeClass('d-none');
                        $('#permissions-error').addClass('d-none');
                    } else {
                        $('#permissions-next-btn').addClass('d-none');
                        $('#permissions-error').removeClass('d-none');
                    }
                    app.restoreButtonState($btn, originalBtnText);
                },
                error: function() {
                    alert('An error occurred while checking permissions');
                    app.restoreButtonState($btn, originalBtnText);
                }
            });
        },
    
        submitDatabaseForm: function(event) {
            event.preventDefault();
            const $form = $(event.currentTarget);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalBtnText = $submitBtn.html();
            
            $form.find('.is-invalid').removeClass('is-invalid');
            
            let isValid = true;
            $form.find('[required]').each(function() {
                if (!$(this).val()) {
                    $(this).addClass('is-invalid');
                    isValid = false;
                }
            });
            
            if (!isValid) return;
            
            this.setButtonLoading($submitBtn, '<span class="spinner-border spinner-border-sm me-2"></span> Testing Connection...');
            
            const app = this;
            $.ajax({
                url: 'install.php',
                type: 'POST',
                dataType: 'json',
                data: $form.serialize() + '&action=validate_database',
                success: function(response) {
                    if (response.success) {
                        window.location.href = 'install.php?step=5';
                    } else {
                        $('#database-error')
                            .text(response.message)
                            .removeClass('d-none');
                        app.restoreButtonState($submitBtn, originalBtnText);
                    }
                },
                error: function() {
                    $('#database-error')
                        .text('An error occurred while checking the database connection')
                        .removeClass('d-none');
                    app.restoreButtonState($submitBtn, originalBtnText);
                }
            });
        },
    
        submitAdminForm: function(event) {
            event.preventDefault();
            const $form = $(event.currentTarget);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalBtnText = $submitBtn.html();
            
            $form.find('.is-invalid').removeClass('is-invalid');
            $('#admin-error').addClass('d-none');
            
            let isValid = true;
            $form.find('[required]').each(function() {
                if (!$(this).val()) {
                    $(this).addClass('is-invalid');
                    isValid = false;
                }
            });
            
            const email = $('#admin_email').val();
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                $('#admin_email').addClass('is-invalid');
                isValid = false;
            }
            
            const password = $('#admin_password').val();
            const confirmPassword = $('#admin_password_confirm').val();
            if (password && confirmPassword && password !== confirmPassword) {
                $('#admin_password_confirm').addClass('is-invalid');
                isValid = false;
            }
            
            if (password && password.length < 8) {
                $('#admin_password').addClass('is-invalid');
                isValid = false;
            }
            
            if (!isValid) return;
            
            this.setButtonLoading($submitBtn, '<span class="spinner-border spinner-border-sm me-2"></span> Validating...');
            
            const app = this;
            $.ajax({
                url: 'install.php',
                type: 'POST',
                dataType: 'json',
                data: $form.serialize() + '&action=validate_admin',
                success: function(response) {
                    if (response.success) {
                        window.location.href = 'install.php?step=6';
                    } else {
                        $('#admin-error')
                            .text(response.message)
                            .removeClass('d-none');
                        app.restoreButtonState($submitBtn, originalBtnText);
                    }
                },
                error: function() {
                    $('#admin-error')
                        .text('An error occurred while validating admin details')
                        .removeClass('d-none');
                    app.restoreButtonState($submitBtn, originalBtnText);
                }
            });
        },
    
        startInstallation: function(event) {
            const $btn = $(event.currentTarget);
            const originalBtnText = $btn.html();
            const $log = $('#installation-log');
            
            this.setButtonLoading($btn, '<span class="spinner-border spinner-border-sm me-2"></span> Installing...');
            $('#installation-error').addClass('d-none');
            $log.empty().append('Starting installation process...\n');
            
            const app = this;
            $.ajax({
                url: 'install.php',
                type: 'POST',
                dataType: 'json',
                data: { action: 'install' },
                timeout: 600000,
                success: function(response) {
                    if (response.success) {
                        if (response.log && Array.isArray(response.log)) {
                            $log.empty();
                            response.log.forEach(line => {
                                $log.append(line + '\n');
                            });
                        } else {
                            $log.append('Installation completed successfully!\n');
                        }
                        $log.append('Redirecting to the Laravel Boilerplate homepage...\n');
                        $('#installation-next-btn').removeClass('d-none');
                        $btn.addClass('d-none');
                    } else {
                        if (response.log && Array.isArray(response.log)) {
                            $log.empty();
                            response.log.forEach(line => {
                                $log.append(line + '\n');
                            });
                        } else {
                            $log.append('Installation failed: ' + response.message + '\n');
                        }
                        $('#installation-error')
                            .text(response.message)
                            .removeClass('d-none');
                        app.restoreButtonState($btn, originalBtnText);
                        
                        $log.append('\n\nPossible solutions:\n');
                        $log.append('1. Check your database connection details\n');
                        $log.append('2. Ensure your database server is running\n');
                        $log.append('3. Verify that the database user has create privileges\n');
                        $log.append('4. Try again with the corrected settings\n');
                    }
                },
                error: function(xhr, status, error) {
                    $log.append('An error occurred during the installation process\n');
                    if (status === 'timeout') {
                        $log.append('The installation process timed out. This may be because:\n');
                        $log.append('- Your server has limited execution time for PHP scripts\n');
                        $log.append('- The repository clone or composer install is taking too long\n');
                        $('#installation-error').text('Installation timed out. The process might be still running on the server.');
                    } else if (xhr.responseText) {
                        try {
                            const errorData = JSON.parse(xhr.responseText);
                            if (errorData.message) {
                                $log.append('Error details: ' + errorData.message + '\n');
                            }
                        } catch (e) {
                            $log.append('Server response: \n' + xhr.responseText + '\n');
                            $('#installation-error').html('Installation process error. See detailed log below.');
                        }
                    } else {
                        $('#installation-error').text('An error occurred during the installation process: ' + error);
                    }
                    app.restoreButtonState($btn, originalBtnText);
                    
                    $log.append('\n\nTroubleshooting steps:\n');
                    $log.append('1. Check PHP error logs for more details\n');
                    $log.append('2. Make sure Git is installed and accessible from command line\n');
                    $log.append('3. Verify that Composer is installed and accessible\n');
                    $log.append('4. Check file permissions in your web directory\n');
                    $log.append('5. Increase PHP memory_limit and max_execution_time in php.ini\n');
                }
            });
        },
    
        runDiagnostics: function(event) {
            const $btn = $(event.currentTarget);
            const originalBtnText = $btn.html();
            const $log = $('#installation-log');
            
            this.setButtonLoading($btn, '<span class="spinner-border spinner-border-sm me-2"></span> Running Diagnostics...');
            $('#installation-error').addClass('d-none');
            $log.empty().append('Running system diagnostics...\n');
            
            const app = this;
            $.ajax({
                url: 'install.php',
                type: 'POST',
                dataType: 'json',
                data: { action: 'run_diagnostics' },
                success: function(response) {
                    $log.append('Diagnostics completed.\n\n');
                    $log.append('=== SYSTEM INFORMATION ===\n');
                    if (response.system_info) {
                        const sysInfo = response.system_info;
                        $log.append(`PHP Version: ${sysInfo.php_version}\n`);
                        $log.append(`Operating System: ${sysInfo.os}\n`);
                        $log.append(`Server Software: ${sysInfo.server_software || 'Unknown'}\n`);
                        $log.append(`Git Version: ${sysInfo.git_version || 'Not detected'}\n`);
                        $log.append(`Composer Version: ${sysInfo.composer_version || 'Not detected'}\n`);
                        $log.append(`PHP Max Execution Time: ${sysInfo.max_execution_time}s\n`);
                        $log.append(`PHP Memory Limit: ${sysInfo.memory_limit}\n\n`);
                    }
                    
                    if (response.issues && response.issues.length > 0) {
                        $log.append('=== POTENTIAL ISSUES ===\n');
                        response.issues.forEach((issue, index) => {
                            $log.append(`${index + 1}. ${issue}\n`);
                        });
                        $log.append('\n');
                        
                        if (!response.success) {
                            $('#installation-error')
                                .text('System check failed. Please fix the issues before proceeding.')
                                .removeClass('d-none');
                        } else {
                            $log.append('NOTICE: Warnings found but installation can proceed.\n\n');
                        }
                    } else {
                        $log.append('=== SYSTEM CHECK PASSED ===\n');
                        $log.append('No issues detected. Your system is ready for installation.\n\n');
                    }
                    
                    $log.append('=== RECOMMENDATIONS ===\n');
                    $log.append('1. Make sure your database server is running.\n');
                    $log.append('2. Database user should have ALL PRIVILEGES on the specified database.\n');
                    $log.append('3. Ensure the web server user has write permissions for the entire directory.\n');
                    
                    app.restoreButtonState($btn, originalBtnText);
                },
                error: function(xhr, status, error) {
                    $log.append('An error occurred while running diagnostics\n');
                    if (xhr.responseText) {
                        try {
                            const errorData = JSON.parse(xhr.responseText);
                            if (errorData.message) {
                                $log.append('Error details: ' + errorData.message + '\n');
                            }
                        } catch (e) {
                            // Not JSON
                        }
                    }
                    $('#installation-error')
                        .text('An error occurred while running diagnostics: ' + error)
                        .removeClass('d-none');
                    app.restoreButtonState($btn, originalBtnText);
                }
            });
        },

        // Generic event handlers initialization
        initGenericEventHandlers: function() {
            $('input, select').on('input change', function() {
                $(this).removeClass('is-invalid');
            });
        },

        // Main initialization
        init: function() {
            $('#check-requirements-btn').on('click', $.proxy(this.checkRequirements, this));
            $('#check-permissions-btn').on('click', $.proxy(this.checkPermissions, this));
            $('#database-form').on('submit', $.proxy(this.submitDatabaseForm, this));
            $('#admin-form').on('submit', $.proxy(this.submitAdminForm, this));
            $('#start-installation-btn').on('click', $.proxy(this.startInstallation, this));
            $('#run-diagnostics-btn').on('click', $.proxy(this.runDiagnostics, this));
            
            this.initGenericEventHandlers();
        }
    };

    InstallerApp.init();
});
