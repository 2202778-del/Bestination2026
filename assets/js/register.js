/* ─── Registration Form Validation ───────────────────────────────────────────── */
(function () {
    'use strict';

    const form   = document.getElementById('registerForm');
    const submit = document.getElementById('submitBtn');
    if (!form) return;

    function showError(fieldId, msg) {
        const el = document.getElementById('err_' + fieldId);
        const inp = document.getElementById(fieldId);
        if (el)  { el.textContent = msg; }
        if (inp) { inp.classList.add('error'); }
    }
    function clearError(fieldId) {
        const el = document.getElementById('err_' + fieldId);
        const inp = document.getElementById(fieldId);
        if (el)  { el.textContent = ''; }
        if (inp) { inp.classList.remove('error'); }
    }
    function clearAll() {
        ['last_name','first_name','email','mobile','gender','school_name','grade_level']
            .forEach(clearError);
    }

    function validate() {
        clearAll();
        let valid = true;

        const lastName  = form.last_name.value.trim();
        const firstName = form.first_name.value.trim();
        const email     = form.email.value.trim();
        const mobile    = form.mobile.value.trim();
        const gender    = form.gender.value;
        const school    = form.school_name.value.trim();
        const grade     = form.grade_level.value;

        if (!lastName)  { showError('last_name', 'Last name is required.'); valid = false; }
        if (!firstName) { showError('first_name', 'First name is required.'); valid = false; }

        if (!email) {
            showError('email', 'Email is required.'); valid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showError('email', 'Please enter a valid email address.'); valid = false;
        }

        if (!mobile) {
            showError('mobile', 'Mobile number is required.'); valid = false;
        } else if (!/^09\d{9}$/.test(mobile)) {
            showError('mobile', 'Mobile must be in format 09XXXXXXXXX.'); valid = false;
        }

        if (!gender) { showError('gender', 'Please select a gender.'); valid = false; }
        if (!school) { showError('school_name', 'School name is required.'); valid = false; }
        if (!grade)  { showError('grade_level', 'Please select a grade level.'); valid = false; }

        return valid;
    }

    // Live validation on blur
    ['last_name','first_name','email','mobile','gender','school_name','grade_level'].forEach(function(id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('blur', validate);
    });

    // Mobile number: only allow digits
    const mobileInput = document.getElementById('mobile');
    if (mobileInput) {
        mobileInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').substring(0, 11);
        });
    }

    form.addEventListener('submit', function(e) {
        if (!validate()) {
            e.preventDefault();
            // Scroll to first error
            const firstError = form.querySelector('.error');
            if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        // Show loading state
        const btnText    = submit.querySelector('.btn-text');
        const btnLoading = submit.querySelector('.btn-loading');
        if (btnText)    btnText.style.display    = 'none';
        if (btnLoading) btnLoading.style.display = 'inline';
        submit.disabled = true;
    });
})();
