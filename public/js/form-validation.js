jQuery(document).ready(function($) {

    'use strict';

    var FormValidation = {

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Form submit
            $('#nxtrunn-submit-form').on('submit', function(e) {
                e.preventDefault();
                FormValidation.handleSubmit(e);
                return false;
            });

            // Inline validation on blur
            $('#nxtrunn-submit-form').on('blur', 'input[required], select[required], textarea[required]', function() {
                FormValidation.validateField($(this));
            });

            // Clear error on focus
            $('#nxtrunn-submit-form').on('focus', 'input, select, textarea', function() {
                var $field = $(this).closest('.nxtrunn-form-field');
                if ($field.length) {
                    $field.removeClass('has-error');
                }
            });

            // Email validation on blur
            $('#nxtrunn-submit-form').on('blur', 'input[type="email"]', function() {
                var $field = $(this).closest('.nxtrunn-form-field');
                var val = $(this).val().trim();
                if (val && !FormValidation.isValidEmail(val)) {
                    $field.addClass('has-error').removeClass('is-valid');
                    FormValidation.setFieldError($field, 'Please enter a valid email address');
                } else if (val) {
                    $field.removeClass('has-error').addClass('is-valid');
                }
            });

            // URL validation on blur
            $('#nxtrunn-submit-form').on('blur', 'input[type="url"]', function() {
                var $field = $(this).closest('.nxtrunn-form-field');
                var val = $(this).val().trim();
                if (val && !FormValidation.isValidUrl(val)) {
                    $field.addClass('has-error').removeClass('is-valid');
                    FormValidation.setFieldError($field, 'URL must start with http:// or https://');
                } else if (val) {
                    $field.removeClass('has-error').addClass('is-valid');
                }
            });

            // Show/hide admin note field based on badge selection
            $('#is_woman_run, #is_bipoc_owned').on('change', this.toggleAdminNote);

            // Logo preview
            $('#club_logo').on('change', this.previewLogo);

            // Show/hide "Other" sponsor field
            $('#club_sponsor').on('change', this.toggleOtherSponsor);
        },

        // Validate a single field
        validateField: function($input) {
            var $field = $input.closest('.nxtrunn-form-field');
            if (!$field.length) return true;

            var val = $input.val().trim();
            var label = $field.find('label').text().replace('*', '').trim();

            if ($input.prop('required') && !val) {
                $field.addClass('has-error').removeClass('is-valid');
                FormValidation.setFieldError($field, label + ' is required');
                return false;
            } else if (val) {
                $field.removeClass('has-error').addClass('is-valid');
                return true;
            }

            return true;
        },

        // Set inline error message
        setFieldError: function($field, message) {
            var $error = $field.find('.nxtrunn-field-error');
            if ($error.length) {
                $error.find('span').text(message);
            } else {
                // Create error element if it doesn't exist
                var errorHtml = '<div class="nxtrunn-field-error" role="alert" aria-live="polite">' +
                    '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:12px;height:12px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>' +
                    '<span>' + message + '</span></div>';
                $field.append(errorHtml);
            }
        },

        toggleAdminNote: function() {
            if ($('#is_woman_run').is(':checked') || $('#is_bipoc_owned').is(':checked')) {
                $('#admin-note-field').slideDown();
            } else {
                $('#admin-note-field').slideUp();
            }
        },

        toggleOtherSponsor: function() {
            if ($(this).val() === 'other') {
                $('#other-sponsor-field').slideDown();
            } else {
                $('#other-sponsor-field').slideUp();
                $('#club_sponsor_other').val('');
            }
        },

        previewLogo: function(e) {
            var file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = function(event) {
                    $('#logo-preview img').attr('src', event.target.result);
                    $('#logo-preview').fadeIn();
                };
                reader.readAsDataURL(file);
            }
        },

        handleSubmit: function(e) {
            var $form = $('#nxtrunn-submit-form');
            var $submitBtn = $form.find('.nxtrunn-submit-btn');
            var $message = $form.find('.nxtrunn-form-message');

            // Validate all required fields
            if (!FormValidation.validateForm($form)) {
                return false;
            }

            // Loading state
            $submitBtn.addClass('is-loading').prop('disabled', true);
            $message.hide();

            // Create FormData for file upload
            var formData = new FormData($form[0]);

            $.ajax({
                url: nxtrunn_form_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $submitBtn.removeClass('is-loading').prop('disabled', false);

                    if (response.success) {
                        $message
                            .removeClass('error')
                            .addClass('success')
                            .html(
                                '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' +
                                '<div><strong>Club submitted successfully!</strong><br>' + (response.data.message || 'Our team will review and publish within 48 hours.') + '</div>'
                            )
                            .fadeIn();

                        // Reset form
                        $form[0].reset();
                        $('#admin-note-field').hide();
                        $('#other-sponsor-field').hide();
                        $('#logo-preview').hide();
                        $form.find('.nxtrunn-form-field').removeClass('has-error is-valid');

                        // Scroll to message
                        $('html, body').animate({
                            scrollTop: $message.offset().top - 100
                        }, 500);
                    } else {
                        $message
                            .removeClass('success')
                            .addClass('error')
                            .html(
                                '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>' +
                                '<div><strong>Submission failed.</strong><br>' + (response.data.message || 'Please try again.') + '</div>'
                            )
                            .fadeIn();
                    }
                },
                error: function() {
                    $submitBtn.removeClass('is-loading').prop('disabled', false);

                    $message
                        .removeClass('success')
                        .addClass('error')
                        .html(
                            '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>' +
                            '<div><strong>Connection error.</strong><br>Please check your internet and try again.</div>'
                        )
                        .fadeIn();
                }
            });

            return false;
        },

        validateForm: function($form) {
            var isValid = true;
            var $firstError = null;

            // Validate all required fields
            $form.find('[required]').each(function() {
                var result = FormValidation.validateField($(this));
                if (!result && !$firstError) {
                    $firstError = $(this);
                }
                if (!result) {
                    isValid = false;
                }
            });

            // Validate email format
            $form.find('input[type="email"]').each(function() {
                var val = $(this).val().trim();
                if (val && !FormValidation.isValidEmail(val)) {
                    isValid = false;
                    var $field = $(this).closest('.nxtrunn-form-field');
                    $field.addClass('has-error').removeClass('is-valid');
                    FormValidation.setFieldError($field, 'Please enter a valid email address');
                    if (!$firstError) $firstError = $(this);
                }
            });

            // Validate URL format
            $form.find('input[type="url"]').each(function() {
                var val = $(this).val().trim();
                if (val && !FormValidation.isValidUrl(val)) {
                    isValid = false;
                    var $field = $(this).closest('.nxtrunn-form-field');
                    $field.addClass('has-error').removeClass('is-valid');
                    FormValidation.setFieldError($field, 'URL must start with http:// or https://');
                    if (!$firstError) $firstError = $(this);
                }
            });

            // Scroll to first error
            if (!isValid && $firstError) {
                $('html, body').animate({
                    scrollTop: $firstError.offset().top - 120
                }, 400);
                $firstError.focus();
            }

            return isValid;
        },

        isValidEmail: function(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        isValidUrl: function(url) {
            return /^https?:\/\/.+/i.test(url);
        }
    };

    // Initialize
    if ($('#nxtrunn-submit-form').length) {
        FormValidation.init();
    }

});
