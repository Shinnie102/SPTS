<!DOCTYPE html>
<html lang="Vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PointC - Login</title>
    <link rel="stylesheet" href="{{ asset('css/auth/login.css') }}">
</head>
<body>
    <div class="container">
        <div class="login-box">
            <div class="login-info">
                <div class="logo-title">
                    <span class="logo">
                        <img src="{{ asset('images/logo.svg') }}" 
                            alt="Logo"
                            width="40"
                            height="37">
                    </span>
                    <span class="title">PointC</span>
                </div>
                <div class="subtitle">Theo dõi – Đánh giá – Cải thiện</div>
                <h1 class="login-header">Đăng nhập</h1>
            </div>

            <form class="login-form" id="loginForm" method="POST" action="{{ route('login') }}">
                @csrf
                
                <!-- Display validation errors -->
                @if ($errors->any())
                    <div class="alert alert-error">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <!-- Display session messages -->
                @if (session('error'))
                    <div id="login-error" class="alert alert-error">
                        {{ session('error') }}
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="input-group">
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="{{ old('email') }}"
                           placeholder="Nhập tài khoản email" 
                           class="input-field"
                           required 
                           autofocus>
                </div>
                <div class="input-group">
                    <input type="password" 
                           id="password" 
                           name="password"
                           placeholder="Nhập mật khẩu"
                           class="input-field password-input" 
                           required>
                    <span class="toggle-password" tabindex="0">
                        <svg class="eye-icon eye-off" width="22" height="22"
                            viewBox="0 0 24 24" fill="none">
                            <path
                                d="M1 12C1 12 5 5 12 5C19 5 23 12 23 12C23 12 19 19 12 19C5 19 1 12 1 12Z"
                                stroke="#222" stroke-width="2" />
                            <path d="M4 4L20 20" stroke="#222"
                                stroke-width="2" stroke-linecap="round" />
                            <circle cx="12" cy="12" r="3" stroke="#222"
                                stroke-width="2" />
                        </svg>
                        <svg class="eye-icon eye-on" width="22" height="22"
                            viewBox="0 0 24 24" fill="none">
                            <path
                                d="M1 12C1 12 5 5 12 5C19 5 23 12 23 12C23 12 19 19 12 19C5 19 1 12 1 12Z"
                                stroke="#222" stroke-width="2" />
                            <circle cx="12" cy="12" r="3" stroke="#222"
                                stroke-width="2" />
                        </svg>
                    </span>
                </div>

                <button type="submit" class="login-btn" id="loginBtn">Đăng nhập</button>
                
                <div class="forgot">
                    Quên <a href="#" class="password-link">mật khẩu</a> của bạn?
                </div>
            </form>
        </div>

        <div class="footer">
            <div class="language">
                Ngôn ngữ
                <span class="dropdown">
                    Tiếng Việt
                    <svg width="12" height="12" viewBox="0 0 24 24"
                        fill="none">
                        <path d="M7 10l5 5 5-5" stroke="#888"
                            stroke-width="2" fill="none" />
                    </svg>
                </span>
            </div>
            <div class="support">Hỗ trợ</div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgot-modal-overlay" class="modal-overlay"></div>
    <div id="forgot-modal" class="modal">
        <div class="modal-header">
            <button id="modal-back" class="modal-back">&lt;</button>
            <span class="modal-title">Quên mật khẩu?</span>
            <button id="modal-close" class="modal-close">×</button>
        </div>
        <div class="modal-body">
            <p class="modal-desc">Vui lòng điền thông tin tài khoản của bạn.</p>
            <form class="forgot-form" id="forgotPasswordForm">
                @csrf
                <div class="modal-input-group">
                    <input type="email" 
                           id="forgotEmail" 
                           name="forgotEmail"
                           class="modal-input" 
                           placeholder="Nhập email của bạn"
                           required>
                    <button type="button" class="send-code-btn" id="sendCodeBtn">Lấy mã</button>
                </div>
                <div class="modal-input-group" id="codeGroup" style="display: none;">
                    <input type="text" 
                           id="verificationCode"
                           name="verificationCode" 
                           class="modal-input"
                           placeholder="Nhập mã xác nhận">
                </div>
                <div class="modal-input-group" id="newPasswordGroup" style="display: none;">
                    <input type="password" 
                           id="newPassword"
                           name="newPassword" 
                           class="modal-input"
                           placeholder="Nhập mật khẩu mới">
                </div>
                <button type="submit" 
                        class="modal-submit-btn"
                        id="forgotSubmitBtn" 
                        style="display: none;">Đặt lại mật khẩu</button>
                <div id="forgotErrorMessage" 
                     class="error-message"
                     style="display: none;"></div>
            </form>
            <div class="modal-note">
                Nếu bạn không nhận được mã, vui lòng liên hệ với chúng tôi để được hỗ trợ.
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.querySelector('.toggle-password');
        const passwordInput = document.querySelector('.password-input');
        const eyeOff = document.querySelector('.eye-off');
        const eyeOn = document.querySelector('.eye-on');

        if (togglePassword) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                eyeOff.style.display = type === 'password' ? 'block' : 'none';
                eyeOn.style.display = type === 'password' ? 'none' : 'block';
            });
        }

        // Modal functionality
        const forgotLink = document.querySelector('.password-link');
        const modal = document.getElementById('forgot-modal');
        const overlay = document.getElementById('forgot-modal-overlay');
        const closeBtn = document.getElementById('modal-close');
        const backBtn = document.getElementById('modal-back');

        function openModal() {
            modal.style.display = 'block';
            overlay.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.style.display = 'none';
            overlay.style.display = 'none';
            document.body.style.overflow = '';
            // Reset form state
            document.getElementById('forgotPasswordForm').reset();
            document.getElementById('codeGroup').style.display = 'none';
            document.getElementById('newPasswordGroup').style.display = 'none';
            document.getElementById('forgotSubmitBtn').style.display = 'none';
            document.getElementById('sendCodeBtn').style.display = 'inline-block';
            document.getElementById('forgotErrorMessage').style.display = 'none';
            document.getElementById('verificationCode').required = false;
            document.getElementById('newPassword').required = false;
        }

        if (forgotLink) {
            forgotLink.addEventListener('click', function(e) {
                e.preventDefault();
                openModal();
            });
        }

        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (backBtn) backBtn.addEventListener('click', closeModal);
        if (overlay) overlay.addEventListener('click', closeModal);

        // Get CSRF token
        function getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        }

        // Forgot password - Request code
        document.getElementById('sendCodeBtn')?.addEventListener('click', async function() {
            const email = document.getElementById('forgotEmail').value;
            const sendCodeBtn = document.getElementById('sendCodeBtn');
            const errorMessage = document.getElementById('forgotErrorMessage');
            const codeGroup = document.getElementById('codeGroup');

            if (!email) {
                errorMessage.textContent = 'Vui lòng nhập địa chỉ email của bạn.';
                errorMessage.style.display = 'block';
                return;
            }

            errorMessage.style.display = 'none';
            sendCodeBtn.textContent = 'Đang gửi...';
            sendCodeBtn.disabled = true;

            try {
                const response = await fetch('{{ route("password.request.code") }}', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: JSON.stringify({ email })
                });

                const data = await response.json();

                if (data.success) {
                    alert(`Mã xác nhận đã được gửi đến email của bạn!`);
                    codeGroup.style.display = 'block';
                    sendCodeBtn.style.display = 'none';
                    document.getElementById('forgotSubmitBtn').style.display = 'block';
                    document.getElementById('verificationCode').required = true;
                } else {
                    errorMessage.textContent = data.message || 'Không thể gửi mã. Vui lòng thử lại.';
                    errorMessage.style.display = 'block';
                }
            } catch (error) {
                console.error('Error requesting code:', error);
                errorMessage.textContent = 'Lỗi mạng. Vui lòng thử lại.';
                errorMessage.style.display = 'block';
            } finally {
                sendCodeBtn.textContent = 'Lấy mã';
                sendCodeBtn.disabled = false;
            }
        });

        // Forgot password - Submit form
        document.getElementById('forgotPasswordForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();

            const email = document.getElementById('forgotEmail').value;
            const verificationCode = document.getElementById('verificationCode').value;
            const newPassword = document.getElementById('newPassword').value;
            const submitBtn = document.getElementById('forgotSubmitBtn');
            const errorMessage = document.getElementById('forgotErrorMessage');
            const newPasswordGroup = document.getElementById('newPasswordGroup');

            errorMessage.style.display = 'none';
            submitBtn.textContent = 'Đang xử lý...';
            submitBtn.disabled = true;

            try {
                // Step 1: Verify code
                const verifyResponse = await fetch('{{ route("password.verify.code") }}', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: JSON.stringify({ email, code: verificationCode })
                });

                const verifyData = await verifyResponse.json();

                if (!verifyData.success) {
                    errorMessage.textContent = verifyData.message || 'Mã xác nhận không hợp lệ.';
                    errorMessage.style.display = 'block';
                    submitBtn.textContent = 'Đặt lại mật khẩu';
                    submitBtn.disabled = false;
                    return;
                }

                // Step 2: Show new password field if code is correct
                if (!newPasswordGroup.style.display || newPasswordGroup.style.display === 'none') {
                    newPasswordGroup.style.display = 'block';
                    submitBtn.textContent = 'Xác nhận đặt lại';
                    submitBtn.disabled = false;
                    document.getElementById('newPassword').required = true;
                    return;
                }

                // Step 3: Reset password
                const resetResponse = await fetch('{{ route("password.reset") }}', {
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
                        location.reload();
                    }, 1000);
                } else {
                    errorMessage.textContent = resetData.message || 'Không thể đặt lại mật khẩu.';
                    errorMessage.style.display = 'block';
                }
            } catch (error) {
                console.error('Error resetting password:', error);
                errorMessage.textContent = 'Lỗi mạng. Vui lòng thử lại.';
                errorMessage.style.display = 'block';
            } finally {
                if (submitBtn.textContent !== 'Thành công! Đang chuyển hướng...') {
                    submitBtn.textContent = newPasswordGroup.style.display === 'block' ? 'Xác nhận đặt lại' : 'Đặt lại mật khẩu';
                    submitBtn.disabled = false;
                }
            }
        });
    </script>
</body>
</html>
