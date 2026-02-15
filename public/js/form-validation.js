/**
 * Contrôle de saisie des formulaires (front & back)
 * Utilisation : ajouter la classe "js-form-validate" sur le <form>
 * Attributs sur les champs : required, data-required-msg, data-email-msg, data-minlength, data-minlength-msg, pattern, data-pattern-msg
 */
(function () {
    'use strict';

    var EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    function getMessage(el, key) {
        return el.getAttribute('data-' + key + '-msg') || messageDefaults[key] || '';
    }

    var messageDefaults = {
        required: 'Champ obligatoire.',
        email: 'Adresse email invalide.',
        minlength: 'Trop court.',
        pattern: 'Format invalide.'
    };

    function showError(input, msg) {
        var wrap = input.closest('.form-group');
        if (!wrap) return;
        var err = wrap.querySelector('.field-error');
        if (err) {
            err.textContent = msg;
            err.classList.add('is-visible');
        }
        input.setAttribute('aria-invalid', 'true');
        input.classList.add('input-error');
    }

    function clearError(input) {
        var wrap = input.closest('.form-group');
        if (!wrap) return;
        var err = wrap.querySelector('.field-error');
        if (err) {
            err.textContent = '';
            err.classList.remove('is-visible');
        }
        input.removeAttribute('aria-invalid');
        input.classList.remove('input-error');
    }

    function validateField(input, form) {
        clearError(input);
        var value = (input.value || '').trim();
        var type = (input.getAttribute('type') || '').toLowerCase();

        // Skip validation for hidden fields
        if (input.offsetParent === null) {
            return true;
        }

        if (input.hasAttribute('required') && value === '') {
            showError(input, getMessage(input, 'required'));
            return false;
        }

        if (value === '' && !input.hasAttribute('required')) {
            return true;
        }

        if (type === 'email' && value !== '') {
            if (!EMAIL_REGEX.test(value)) {
                showError(input, getMessage(input, 'email') || messageDefaults.email);
                return false;
            }
        }

        var minLen = input.getAttribute('data-minlength');
        if (minLen !== null && value.length > 0) {
            var n = parseInt(minLen, 10);
            if (!isNaN(n) && value.length < n) {
                showError(input, getMessage(input, 'minlength') || 'Minimum ' + n + ' caractères.');
                return false;
            }
        }

        var pattern = input.getAttribute('pattern');
        if (pattern && value !== '') {
            try {
                var re = new RegExp(pattern);
                if (!re.test(value)) {
                    showError(input, getMessage(input, 'pattern') || messageDefaults.pattern);
                    return false;
                }
            } catch (e) {}
        }

        return true;
    }

    function validateForm(form) {
        var valid = true;
        var firstInvalid = null;
        var inputs = form.querySelectorAll('input[required], input[type="email"], input[data-minlength], input[pattern], select[required], textarea[required]');

        inputs.forEach(function (input) {
            if (input.offsetParent === null) return; // skip hidden (e.g. role-dependent)
            if (!validateField(input, form)) {
                valid = false;
                if (!firstInvalid) firstInvalid = input;
            }
        });

        if (firstInvalid) {
            firstInvalid.focus();
        }
        return valid;
    }

    function init() {
        document.querySelectorAll('form.js-form-validate').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                console.log('Form submit intercepted');
                if (!validateForm(form)) {
                    console.log('Form validation FAILED');
                    e.preventDefault();
                    return false;
                }
                console.log('Form validation PASSED, allowing submit');
            });

            form.querySelectorAll('input, select, textarea').forEach(function (input) {
                input.addEventListener('blur', function () {
                    if (input.value.trim() !== '' || input.hasAttribute('required')) {
                        validateField(input, form);
                    } else {
                        clearError(input);
                    }
                });
                input.addEventListener('input', function () {
                    if (input.classList.contains('input-error')) {
                        validateField(input, form);
                    }
                });
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
