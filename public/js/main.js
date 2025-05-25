$(document).ready(function() {
    // --- Registration Form Validation ---
    $('#registerForm').submit(function(e) {
        let errors = [];
        const username = $('#username').val().trim();
        const email = $('#email').val().trim();
        const password = $('#password').val();
        const confirm_password = $('#confirm_password').val();
        const full_name = $('#full_name').val().trim();

        if (username.length < 3) errors.push("Username must be at least 3 characters long.");
        if (!isValidEmail(email)) errors.push("Invalid email address.");
        if (password.length < 6) errors.push("Password must be at least 6 characters long.");
        if (password !== confirm_password) errors.push("Passwords do not match.");
        if (full_name === "") errors.push("Full name is required.");

        if (errors.length > 0) {
            e.preventDefault();
            displayValidationErrors(errors, $('#registerForm .form-messages'));
            return false;
        }
        $('#registerForm .form-messages').empty().hide();
        return true;
    });

    // --- Login Form Validation ---
    $('#loginForm').submit(function(e) {
        let errors = [];
        const username = $('#login_username').val().trim();
        const password = $('#login_password').val();

        if (username === "") errors.push("Username is required.");
        if (password === "") errors.push("Password is required.");

        if (errors.length > 0) {
            e.preventDefault();
            displayValidationErrors(errors, $('#loginForm .form-messages'));
            return false;
        }
        $('#loginForm .form-messages').empty().hide();
        return true;
    });
    
    // --- Profile Update Form Validation ---
    $('#profileForm').submit(function(e) {
        let errors = [];
        const email = $('#profile_email').val().trim();
        const full_name = $('#profile_full_name').val().trim();

        if (!isValidEmail(email)) errors.push("Invalid email address.");
        if (full_name === "") errors.push("Full name is required.");

        if (errors.length > 0) {
            e.preventDefault();
            displayValidationErrors(errors, $('#profileForm .form-messages'));
            return false;
        }
        $('#profileForm .form-messages').empty().hide();
        return true;
    });

    // --- Change Password Form Validation ---
    $('#changePasswordForm').submit(function(e) {
        let errors = [];
        const current_password = $('#current_password').val();
        const new_password = $('#new_password').val();
        const confirm_new_password = $('#confirm_new_password').val();

        if (current_password === "") errors.push("Current password is required.");
        if (new_password.length < 6) errors.push("New password must be at least 6 characters long.");
        if (new_password !== confirm_new_password) errors.push("New passwords do not match.");

        if (errors.length > 0) {
            e.preventDefault();
            displayValidationErrors(errors, $('#changePasswordForm .form-messages'));
            return false;
        }
        $('#changePasswordForm .form-messages').empty().hide();
        return true;
    });
    
    // --- Password Reset Request Form Validation ---
    $('#requestPasswordResetForm').submit(function(e) {
        let errors = [];
        const email = $('#reset_email').val().trim();
        if (!isValidEmail(email)) errors.push("Invalid email address.");
        if (errors.length > 0) {
            e.preventDefault();
            displayValidationErrors(errors, $('#requestPasswordResetForm .form-messages'));
            return false;
        }
        $('#requestPasswordResetForm .form-messages').empty().hide();
        return true;
    });

    // --- Actual Password Reset Form Validation ---
    $('#resetPasswordForm').submit(function(e) {
        let errors = [];
        const new_password = $('#reset_new_password').val();
        const confirm_password = $('#reset_confirm_password').val();

        if (new_password.length < 6) errors.push("New password must be at least 6 characters long.");
        if (new_password !== confirm_password) errors.push("Passwords do not match.");
        
        if (errors.length > 0) {
            e.preventDefault();
            displayValidationErrors(errors, $('#resetPasswordForm .form-messages'));
            return false;
        }
         $('#resetPasswordForm .form-messages').empty().hide();
        return true;
    });


    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function displayValidationErrors(errors, container) {
        if (!container) container = $('.form-messages'); // Default if not specified
        container.html(''); // Clear previous errors
        if (errors.length > 0) {
            let errorHtml = '<div class="alert alert-danger"><ul>';
            errors.forEach(function(error) {
                errorHtml += '<li>' + escapeHtml(error) + '</li>';
            });
            errorHtml += '</ul></div>';
            container.html(errorHtml).show();
        } else {
            container.empty().hide();
        }
    }
    
    function escapeHtml(unsafe) {
        return unsafe
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    // --- Claim Submission Form Validation ---
    $('#submitClaimForm').submit(function(e) {
        let errors = [];
        const claim_date = $('#claim_date').val();
        const description = $('#description').val().trim();
        const total_amount = $('#total_amount').val().trim();
        // Basic document validation (at least one file, if required by form)
        // const document_count = $('#documents')[0].files.length;

        if (claim_date === "") errors.push("Claim date is required.");
        // Add more specific date validation if needed (e.g., not in future)
        
        if (description.length < 10) errors.push("Description must be at least 10 characters long.");
        if (total_amount === "" || isNaN(parseFloat(total_amount)) || parseFloat(total_amount) <= 0) {
            errors.push("Total amount must be a positive number.");
        }
        // if (document_count === 0 && $('#documents').prop('required')) { // If documents field is marked as required
        //    errors.push("At least one supporting document is required.");
        // }


        if (errors.length > 0) {
            e.preventDefault();
            displayValidationErrors(errors, $('#submitClaimForm .form-messages'));
            return false;
        }
        $('#submitClaimForm .form-messages').empty().hide();
        return true;
    });

    // --- HR Claim Update Form Validation (Example for status update) ---
    $('.hrUpdateClaimForm').submit(function(e) { // Using a class for multiple forms
        let errors = [];
        const status = $(this).find('select[name="status"]').val();
        const comments = $(this).find('textarea[name="comments"]').val().trim();

        if (status === "") errors.push("Status is required.");
        if ((status === 'Rejected' || status === 'Needs Information') && comments === "") {
            errors.push("Comments are required for 'Rejected' or 'Needs Information' status.");
        }

        if (errors.length > 0) {
            e.preventDefault();
            // Display errors specifically for this form
            displayValidationErrors(errors, $(this).find('.form-messages'));
            return false;
        }
        $(this).find('.form-messages').empty().hide();
        return true;
    });
    
    // Confirm before deleting a claim
    $('.delete-claim-link').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this claim? This action cannot be undone.')) {
            e.preventDefault();
        }
    });

    // --- Admin Edit User Form Validation ---
    $('#adminEditUserForm').submit(function(e) {
        let errors = [];
        const username = $('#username').val().trim();
        const email = $('#email').val().trim();
        const full_name = $('#full_name').val().trim();
        const role_id = $('#role_id').val();

        if (username.length < 3) errors.push("Username must be at least 3 characters long.");
        if (!isValidEmail(email)) errors.push("Invalid email address."); // Assumes isValidEmail function exists
        if (full_name === "") errors.push("Full name is required.");
        if (role_id === "" || role_id === null) errors.push("A role must be selected.");

        if (errors.length > 0) {
            e.preventDefault();
            displayValidationErrors(errors, $('#adminEditUserForm .form-messages')); // Assumes displayValidationErrors function exists
            return false;
        }
        $('#adminEditUserForm .form-messages').empty().hide();
        return true;
    });
});
