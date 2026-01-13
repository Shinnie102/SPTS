// Login page functionality for PointC
// Handles password visibility toggle and forgot password modal

// Display error if URL contains ?error=true
(function () {
    if (window.location.search.includes('error=true')) {
        const errorElement = document.getElementById('login-error');
        if (errorElement) {
            errorElement.style.display = 'block';
        }
    }
})();

// Toggle password visibility
const togglePassword = document.querySelector('.toggle-password');
const passwordInput = document.querySelector('.password-input');
const eyeOff = document.querySelector('.eye-off');
const eyeOn = document.querySelector('.eye-on');

if (togglePassword && passwordInput) {
    togglePassword.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        if (eyeOff) eyeOff.style.display = type === 'password' ? 'block' : 'none';
        if (eyeOn) eyeOn.style.display = type === 'password' ? 'none' : 'block';
    });
}

// Modal functionality
const forgotLink = document.querySelector('.password-link');
const modal = document.getElementById('forgot-modal');
const overlay = document.getElementById('forgot-modal-overlay');
const closeBtn = document.getElementById('modal-close');
const backBtn = document.getElementById('modal-back');

function openModal() {
    if (modal && overlay) {
        modal.style.display = 'block';
        overlay.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal() {
    if (modal && overlay) {
        modal.style.display = 'none';
        overlay.style.display = 'none';
        document.body.style.overflow = '';
        
        // Reset form state
        const form = document.getElementById('forgotPasswordForm');
        if (form) form.reset();
        
        const elements = {
            codeGroup: document.getElementById('codeGroup'),
            newPasswordGroup: document.getElementById('newPasswordGroup'),
            submitBtn: document.getElementById('forgotSubmitBtn'),
            sendCodeBtn: document.getElementById('sendCodeBtn'),
            errorMessage: document.getElementById('forgotErrorMessage'),
            verificationCode: document.getElementById('verificationCode'),
            newPassword: document.getElementById('newPassword')
        };
        
        if (elements.codeGroup) elements.codeGroup.style.display = 'none';
        if (elements.newPasswordGroup) elements.newPasswordGroup.style.display = 'none';
        if (elements.submitBtn) elements.submitBtn.style.display = 'none';
        if (elements.sendCodeBtn) elements.sendCodeBtn.style.display = 'inline-block';
        if (elements.errorMessage) elements.errorMessage.style.display = 'none';
        if (elements.verificationCode) elements.verificationCode.required = false;
        if (elements.newPassword) elements.newPassword.required = false;
    }
}

if (forgotLink) {
    forgotLink.addEventListener('click', function (e) {
        e.preventDefault();
        openModal();
    });
}

if (closeBtn) closeBtn.addEventListener('click', closeModal);
if (backBtn) backBtn.addEventListener('click', closeModal);
if (overlay) overlay.addEventListener('click', closeModal);

// Get CSRF token from meta tag
function getCsrfToken() {
    const token = document.querySelector('meta[name="csrf-token"]');
    return token ? token.getAttribute('content') : '';
}

// Forgot password - Request verification code
const sendCodeBtn = document.getElementById('sendCodeBtn');
if (sendCodeBtn) {
    sendCodeBtn.addEventListener('click', async function () {
        const email = document.getElementById('forgotEmail').value;
        const errorMessage = document.getElementById('forgotErrorMessage');
        const codeGroup = document.getElementById('codeGroup');

        if (!email) {
            if (errorMessage) {
                errorMessage.textContent = 'Vui lòng nhập địa chỉ email.';
                errorMessage.style.display = 'block';
            }
            return;
        }

        if (errorMessage) errorMessage.style.display = 'none';
        sendCodeBtn.textContent = 'Đang gửi...';
        sendCodeBtn.disabled = true;

        try {
            // Note: This route is placeholder - implement in routes/web.php
            const response = await fetch('/password/request-code', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({ email })
            });

            const data = await response.json();

            if (data.success) {
                alert('Mã xác nhận đã được gửi đến email của bạn!');
                if (codeGroup) codeGroup.style.display = 'block';
                sendCodeBtn.style.display = 'none';
                const submitBtn = document.getElementById('forgotSubmitBtn');
                if (submitBtn) submitBtn.style.display = 'block';
                const verificationCode = document.getElementById('verificationCode');
                if (verificationCode) verificationCode.required = true;
            } else {
                if (errorMessage) {
                    errorMessage.textContent = data.message || 'Không thể gửi mã xác nhận.';
                    errorMessage.style.display = 'block';
                }
            }
        } catch (error) {
            console.error('Error requesting verification code:', error);
            if (errorMessage) {
                errorMessage.textContent = 'Chức năng này chưa được triển khai.';
                errorMessage.style.display = 'block';
            }
        } finally {
            sendCodeBtn.textContent = 'Lấy mã';
            sendCodeBtn.disabled = false;
        }
    });
}

// Forgot password - Submit form
const forgotForm = document.getElementById('forgotPasswordForm');
if (forgotForm) {
    forgotForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const email = document.getElementById('forgotEmail').value;
        const verificationCode = document.getElementById('verificationCode').value;
        const newPassword = document.getElementById('newPassword').value;
        const submitBtn = document.getElementById('forgotSubmitBtn');
        const errorMessage = document.getElementById('forgotErrorMessage');
        const newPasswordGroup = document.getElementById('newPasswordGroup');

        if (!submitBtn) return;

        if (errorMessage) errorMessage.style.display = 'none';
        submitBtn.textContent = 'Đang xử lý...';
        submitBtn.disabled = true;

        try {
            // Step 1: Verify code
            const verifyResponse = await fetch('/password/verify-code', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({ email, code: verificationCode })
            });

            const verifyData = await verifyResponse.json();

            if (!verifyData.success) {
                if (errorMessage) {
                    errorMessage.textContent = verifyData.message || 'Mã xác nhận không hợp lệ.';
                    errorMessage.style.display = 'block';
                }
                submitBtn.textContent = 'Đặt lại mật khẩu';
                submitBtn.disabled = false;
                return;
            }

            // Step 2: Show new password field if code is valid
            if (newPasswordGroup && (!newPasswordGroup.style.display || newPasswordGroup.style.display === 'none')) {
                newPasswordGroup.style.display = 'block';
                submitBtn.textContent = 'Xác nhận đặt lại';
                submitBtn.disabled = false;
                const newPasswordInput = document.getElementById('newPassword');
                if (newPasswordInput) newPasswordInput.required = true;
                return;
            }

            // Step 3: Reset password
            const resetResponse = await fetch('/password/reset', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({ email, password: newPassword })
            });

            const resetData = await resetResponse.json();

            if (resetData.success) {
                submitBtn.textContent = 'Thành công! Đang chuyển hướng...';
                submitBtn.style.backgroundColor = '#28a745';
                setTimeout(() => {
                    closeModal();
                    window.location.reload();
                }, 1000);
            } else {
                if (errorMessage) {
                    errorMessage.textContent = resetData.message || 'Không thể đặt lại mật khẩu.';
                    errorMessage.style.display = 'block';
                }
            }
        } catch (error) {
            console.error('Error resetting password:', error);
            if (errorMessage) {
                errorMessage.textContent = 'Chức năng này chưa được triển khai.';
                errorMessage.style.display = 'block';
            }
        } finally {
            if (submitBtn.textContent !== 'Thành công! Đang chuyển hướng...') {
                const isPasswordShown = newPasswordGroup && newPasswordGroup.style.display === 'block';
                submitBtn.textContent = isPasswordShown ? 'Xác nhận đặt lại' : 'Đặt lại mật khẩu';
                submitBtn.disabled = false;
            }
        }
    });
}
