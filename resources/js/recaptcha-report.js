const dispatchRecaptchaInput = (value = '') => {
    const livewireField = document.getElementById('recaptcha-response');

    if (!livewireField) {
        return;
    }

    livewireField.value = value;
    livewireField.dispatchEvent(new Event('input', { bubbles: true }));
};

window.nidviteSyncRecaptchaToken = () => {
    const tokenField = document.querySelector('textarea[name="g-recaptcha-response"]');
    dispatchRecaptchaInput(tokenField ? tokenField.value : '');
};

window.onReportRecaptchaSuccess = (token) => {
    dispatchRecaptchaInput(token || '');
};

window.onReportRecaptchaExpired = () => {
    window.onReportRecaptchaSuccess('');
};
