<?php
/**
 * HotelOS - Forgot Password Page
 * 
 * Password reset request and new password form
 */

$csrfToken = $csrfToken ?? '';
$error = $error ?? '';
$success = $success ?? '';
$mode = $mode ?? 'request'; // 'request' or 'reset'
$token = $token ?? '';
$resetLink = $resetLink ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | HotelOS</title>
    
    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        :root {
            --bg-cosmic: #0f172a;
            --bg-card: #1e293b;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --neon-cyan: #22d3ee;
            --neon-green: #34d399;
            --neon-red: #f87171;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-cosmic);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .auth-container {
            width: 100%;
            max-width: 420px;
        }
        
        .auth-card {
            background: var(--bg-card);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .brand {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .brand-icon {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }
        
        .brand-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .brand-tagline {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }
        
        h1 {
            color: var(--text-primary);
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-align: center;
        }
        
        .subtitle {
            color: var(--text-secondary);
            font-size: 0.875rem;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            display: block;
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            background: rgba(15, 23, 42, 0.6);
            color: var(--text-primary);
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--neon-cyan);
            box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.15);
        }
        
        .form-input::placeholder {
            color: var(--text-secondary);
        }
        
        .btn {
            width: 100%;
            padding: 0.875rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--neon-cyan), #06b6d4);
            color: var(--bg-cosmic);
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(34, 211, 238, 0.4);
        }
        
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--neon-red);
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: var(--neon-green);
        }
        
        .reset-link-box {
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(34, 211, 238, 0.1);
            border: 1px solid rgba(34, 211, 238, 0.3);
            border-radius: 0.5rem;
            word-break: break-all;
            font-size: 0.75rem;
            color: var(--neon-cyan);
        }
        
        .reset-link-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }
        
        .links {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .links a {
            color: var(--neon-cyan);
            text-decoration: none;
            font-size: 0.875rem;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        .password-requirements {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="brand">
                <div class="brand-icon">🏨</div>
                <div class="brand-name">HotelOS</div>
                <div class="brand-tagline">Next-Gen Hotel Management</div>
            </div>
            
            <?php if ($mode === 'request'): ?>
                <!-- Request Password Reset Form -->
                <h1>Forgot Password</h1>
                <p class="subtitle">Enter your email address and we'll send you instructions to reset your password.</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    
                    <?php if ($resetLink): ?>
                        <div class="reset-link-box">
                            <div class="reset-link-label">⚠️ Development Mode - Copy this link:</div>
                            <a href="<?= htmlspecialchars($resetLink) ?>"><?= htmlspecialchars($resetLink) ?></a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <form method="POST" action="/forgot-password">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input 
                                type="email" 
                                name="email" 
                                class="form-input" 
                                placeholder="your@email.com"
                                required
                                autofocus
                            >
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="mail" class="w-4 h-4"></i>
                            Send Reset Link
                        </button>
                    </form>
                <?php endif; ?>
                
            <?php elseif ($mode === 'reset'): ?>
                <!-- Reset Password Form -->
                <h1>Set New Password</h1>
                <p class="subtitle">Enter your new password below.</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php else: ?>
                    <form method="POST" action="/reset-password">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                        
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input 
                                type="password" 
                                name="password" 
                                class="form-input" 
                                placeholder="Enter new password"
                                minlength="8"
                                required
                                autofocus
                            >
                            <p class="password-requirements">Minimum 8 characters</p>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Confirm Password</label>
                            <input 
                                type="password" 
                                name="password_confirm" 
                                class="form-input" 
                                placeholder="Confirm new password"
                                minlength="8"
                                required
                            >
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="key" class="w-4 h-4"></i>
                            Reset Password
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="links">
                <a href="/login">← Back to Login</a>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
        });
    </script>
</body>
</html>
