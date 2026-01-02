<?php
/**
 * HotelOS - Web Auth Routes
 * 
 * Extracted from public/index.php
 * These routes handle web page authentication
 * 
 * Routes:
 * - GET/POST /login
 * - GET/POST /register
 * - GET /logout
 * - GET /verify-email
 * - GET/POST /forgot-password
 * - GET/POST /reset-password
 */

declare(strict_types=1);

use HotelOS\Core\Auth;
use HotelOS\Core\Database;

/**
 * Register web auth routes
 * 
 * @param string $requestUri Current request URI
 * @param string $requestMethod HTTP method
 * @param Auth $auth Auth instance
 * @return bool True if route was handled
 */
function handleWebAuthRoutes(string $requestUri, string $requestMethod, Auth $auth): bool
{
    switch ($requestUri) {
        case '/':
        case '/login':
            if ($auth->check()) {
                header('Location: /dashboard');
                exit;
            }
            if ($requestMethod === 'POST') {
                webAuthLoginForm($auth);
            } else {
                webAuthLoginPage($auth);
            }
            return true;
            
        case '/register':
            if ($auth->check()) {
                header('Location: /dashboard');
                exit;
            }
            if ($requestMethod === 'POST') {
                webAuthRegisterForm($auth);
            } else {
                webAuthRegisterPage($auth);
            }
            return true;
            
        case '/logout':
            webAuthLogout($auth);
            return true;
            
        case '/verify-email':
            webAuthVerifyEmail();
            return true;
            
        case '/forgot-password':
            if ($requestMethod === 'POST') {
                webAuthForgotPasswordForm($auth);
            } else {
                webAuthForgotPasswordPage($auth);
            }
            return true;
            
        case '/reset-password':
            $token = $_GET['token'] ?? $_POST['token'] ?? '';
            if ($requestMethod === 'POST') {
                webAuthResetPasswordForm($auth, $token);
            } else {
                webAuthResetPasswordPage($auth, $token);
            }
            return true;
    }
    
    return false;
}

/**
 * Render login page
 */
function webAuthLoginPage(Auth $auth): void
{
    $error = null;
    $csrfToken = $auth->csrfToken();
    
    if (isset($_GET['error'])) {
        $error = match($_GET['error']) {
            'invalid' => 'Invalid email or password.',
            'locked' => 'Your account is temporarily locked.',
            'inactive' => 'Your account has been deactivated.',
            'rate_limited' => 'Too many attempts. Please wait.',
            default => 'An error occurred. Please try again.'
        };
    }
    
    $title = 'Login';
    $bodyClass = 'page-login';
    
    ob_start();
    include VIEWS_PATH . '/auth/login.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/base.php';
}

/**
 * Handle login form submission
 */
function webAuthLoginForm(Auth $auth): void
{
    // Rate limiting check
    $rateLimiter = new \HotelOS\Core\RateLimiter();
    $clientIP = \HotelOS\Core\RateLimiter::getClientIP();
    
    if ($rateLimiter->isRateLimited($clientIP, 'login')) {
        header('Location: /login?error=rate_limited');
        exit;
    }
    
    $loginType = $_POST['login_type'] ?? 'owner';
    
    // Staff PIN Login
    if ($loginType === 'staff' && !empty($_POST['pin'])) {
        $pin = $_POST['pin'];
        $result = $auth->attemptPIN($pin);
        
        if ($result['success']) {
            header('Location: /dashboard');
        } else {
            header('Location: /login?error=invalid&tab=staff');
        }
        exit;
    }
    
    // Regular email/password login
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        header('Location: /login?error=invalid');
        exit;
    }
    
    $result = $auth->attempt($email, $password);
    
    if ($result['success']) {
        $rateLimiter->clearAttempts($clientIP, 'login');
        header('Location: /dashboard');
    } else {
        $rateLimiter->logAttempt($clientIP, 'login');
        header('Location: /login?error=invalid');
    }
    exit;
}

/**
 * Render register page
 */
function webAuthRegisterPage(Auth $auth): void
{
    $csrfToken = $auth->csrfToken();
    $title = 'Register';
    $bodyClass = 'page-register';
    $error = $_GET['error'] ?? null;
    
    ob_start();
    include VIEWS_PATH . '/auth/register.php';
    $content = ob_get_clean();
    
    include VIEWS_PATH . '/layouts/base.php';
}

/**
 * Handle register form - delegates to existing handler
 */
function webAuthRegisterForm(Auth $auth): void
{
    // This delegates to the existing handleRegisterForm in index.php
    // Will be fully extracted in Phase 2
    handleRegisterForm($auth);
}

/**
 * Handle logout with shift check
 */
function webAuthLogout(Auth $auth): void
{
    $user = $auth->user();
    if ($user && isset($user['id'])) {
        $shiftHandler = new \HotelOS\Handlers\ShiftHandler();
        $openShift = $shiftHandler->getCurrentShift((int)$user['id']);
        
        if ($openShift) {
            $_SESSION['logout_blocked'] = true;
            $_SESSION['logout_blocked_reason'] = 'open_shift';
            header('Location: /shifts?error=close_shift_first');
            exit;
        }
    }
    
    $auth->logout();
    header('Location: /');
    exit;
}

/**
 * Handle email verification
 */
function webAuthVerifyEmail(): void
{
    $token = $_GET['token'] ?? '';
    if (empty($token)) {
        die('Invalid verification link.');
    }
    
    $db = Database::getInstance();
    $user = $db->queryOne(
        "SELECT * FROM users WHERE email_verification_token = :token", 
        ['token' => $token], 
        enforceTenant: false
    );
    
    if (!$user) {
        die('Invalid or expired verification link.');
    }
    
    $db->execute(
        "UPDATE users SET email_verified_at = NOW(), email_verification_token = NULL WHERE id = :id", 
        ['id' => $user['id']], 
        enforceTenant: false
    );
    
    Auth::loginUser($user);
    header("Location: /dashboard?verified=1");
    exit;
}

/**
 * Show forgot password page
 */
function webAuthForgotPasswordPage(Auth $auth): void
{
    $csrfToken = $auth->csrfToken();
    $mode = 'request';
    $error = '';
    $success = '';
    $resetLink = '';
    include VIEWS_PATH . '/auth/forgot-password.php';
}

/**
 * Handle forgot password form - delegates to existing handler
 */
function webAuthForgotPasswordForm(Auth $auth): void
{
    handleForgotPasswordForm($auth);
}

/**
 * Show reset password page
 */
function webAuthResetPasswordPage(Auth $auth, string $token): void
{
    if (empty($token)) {
        header('Location: /forgot-password');
        exit;
    }
    
    $validUser = $auth->validateResetToken($token);
    if (!$validUser) {
        $error = 'Invalid or expired reset token. Please request a new one.';
        $success = '';
        $csrfToken = $auth->csrfToken();
        $mode = 'request';
        $resetLink = '';
        include VIEWS_PATH . '/auth/forgot-password.php';
        exit;
    }
    
    $mode = 'reset';
    $error = '';
    $success = '';
    $csrfToken = $auth->csrfToken();
    include VIEWS_PATH . '/auth/forgot-password.php';
}

/**
 * Handle reset password form - delegates to existing handler
 */
function webAuthResetPasswordForm(Auth $auth, string $token): void
{
    handleResetPasswordForm($auth, $token);
}
