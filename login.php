<?php
require_once 'includes/config.php';
require_once 'includes/connection.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: pages/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    try {
        if (empty($username) || empty($password)) {
            throw new Exception('Please enter both username and password.');
        }
        
        // Rate limiting
        $clientIP = getClientIP();
        if (!checkRateLimit($clientIP, 5, 900)) { // 5 attempts per 15 minutes
            throw new Exception('Too many failed login attempts. Please try again later.');
        }
        
        // Find user by username or email
        $user = $db->fetchOne(
            'SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1',
            [$username, $username]
        );
        
        if (!$user || !password_verify($password, $user['password'])) {
            logActivity('Failed Login Attempt', "Username: $username, IP: $clientIP");
            throw new Exception('Invalid username or password.');
        }
        
        // Successful login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['authenticated'] = true;
        
        // Set remember me cookie
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true); // 30 days, httponly
            // In production, store this token in database with user association
        }
        
        // Update last login
        $db->update('users', ['updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
        
        logActivity('Successful Login', "User: {$user['username']}, Role: {$user['role']}");
        
        // Redirect to dashboard
        header('Location: pages/dashboard.php');
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - School Admin System</title>
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin="">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?display=swap&family=Noto+Sans:wght@400;500;700;900&family=Public+Sans:wght@400;500;700;900">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="icon" type="image/x-icon" href="data:image/x-icon;base64,">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card fade-in">
            <div class="login-header">
                <div class="login-logo">
                    <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g clip-path="url(#clip0_6_543)">
                            <path d="M42.1739 20.1739L27.8261 5.82609C29.1366 7.13663 28.3989 10.1876 26.2002 13.7654C24.8538 15.9564 22.9595 18.3449 20.6522 20.6522C18.3449 22.9595 15.9564 24.8538 13.7654 26.2002C10.1876 28.3989 7.13663 29.1366 5.82609 27.8261L20.1739 42.1739C21.4845 43.4845 24.5355 42.7467 28.1133 40.548C30.3042 39.2016 32.6927 37.3073 35 35C37.3073 32.6927 39.2016 30.3042 40.548 28.1133C42.7467 24.5355 43.4845 21.4845 42.1739 20.1739Z" fill="currentColor"></path>
                        </g>
                        <defs>
                            <clipPath id="clip0_6_543"><rect width="48" height="48" fill="white"></rect></clipPath>
                        </defs>
                    </svg>
                </div>
                <h1 class="login-title">Welcome Back</h1>
                <p class="login-subtitle">Sign in to your School Admin account</p>
            </div>
            
            <?php if ($error): ?>
            <div class="error-message" id="errorMessage">
                <svg class="error-icon" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="success-message" id="successMessage">
                <svg class="error-icon" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>
            
            <form class="login-form" method="POST" action="" id="loginForm">
                <div class="login-form-group">
                    <label for="username" class="login-label">Username or Email</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="login-input" 
                        placeholder="Enter your username or email"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        required
                        autocomplete="username"
                    >
                </div>
                
                <div class="login-form-group">
                    <label for="password" class="login-label">Password</label>
                    <div class="password-field">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="login-input" 
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <svg id="eyeIcon" width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="login-options">
                    <label class="remember-me" onclick="toggleRemember()">
                        <div class="remember-checkbox" id="rememberCheckbox"></div>
                        <span>Remember me</span>
                        <input type="checkbox" name="remember" id="rememberInput" style="display: none;">
                    </label>
                    <a href="#" class="forgot-link">Forgot password?</a>
                </div>
                
                <button type="submit" class="login-button" id="loginButton">
                    <span id="buttonText">Sign In</span>
                </button>
            </form>
            
            <div class="login-footer">
                <p>© 2024 School Admin System. All rights reserved.</p>
                <p><strong>Demo Credentials:</strong></p>
                <p>Admin: admin / admin123</p>
                <p>Staff: staff1 / admin123</p>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide messages
        const errorMessage = document.getElementById('errorMessage');
        const successMessage = document.getElementById('successMessage');
        
        if (errorMessage) {
            setTimeout(() => {
                errorMessage.style.opacity = '0';
                setTimeout(() => errorMessage.remove(), 300);
            }, 5000);
        }
        
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.opacity = '0';
                setTimeout(() => successMessage.remove(), 300);
            }, 3000);
        }
        
        // Password toggle
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `
                    <path d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 11-4.243-4.243m4.242 4.242L9.88 9.88"></path>
                `;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `
                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                `;
            }
        }
        
        // Remember me toggle
        function toggleRemember() {
            const checkbox = document.getElementById('rememberCheckbox');
            const input = document.getElementById('rememberInput');
            
            if (checkbox.classList.contains('checked')) {
                checkbox.classList.remove('checked');
                input.checked = false;
            } else {
                checkbox.classList.add('checked');
                input.checked = true;
            }
        }
        
        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const button = document.getElementById('loginButton');
            const buttonText = document.getElementById('buttonText');
            
            button.disabled = true;
            buttonText.innerHTML = '<span class="loading-spinner"></span>Signing In...';
            
            // Remove any existing error styling
            document.querySelectorAll('.login-input.error').forEach(input => {
                input.classList.remove('error');
            });
            
            // Basic validation
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                
                if (!username) {
                    document.getElementById('username').classList.add('error');
                }
                if (!password) {
                    document.getElementById('password').classList.add('error');
                }
                
                button.disabled = false;
                buttonText.innerHTML = 'Sign In';
                
                showError('Please fill in all required fields.');
                return;
            }
        });
        
        // Show error message
        function showError(message) {
            const existingError = document.getElementById('errorMessage');
            if (existingError) {
                existingError.remove();
            }
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.id = 'errorMessage';
            errorDiv.innerHTML = `
                <svg class="error-icon" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                ${message}
            `;
            
            document.querySelector('.login-form').insertBefore(errorDiv, document.querySelector('.login-form-group'));
            
            setTimeout(() => {
                errorDiv.style.opacity = '0';
                setTimeout(() => errorDiv.remove(), 300);
            }, 5000);
        }
        
        // Clear error styling on input
        document.querySelectorAll('.login-input').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('error');
            });
        });
        
        // Auto-focus first input
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
        
        // Handle Enter key in form
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
                const form = document.getElementById('loginForm');
                const inputs = Array.from(form.querySelectorAll('input[required]'));
                const currentIndex = inputs.indexOf(e.target);
                
                if (currentIndex < inputs.length - 1) {
                    e.preventDefault();
                    inputs[currentIndex + 1].focus();
                }
            }
        });
    </script>
</body>
</html>