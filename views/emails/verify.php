<?php
/**
 * Email Template: Account Verification
 * Variables: $name, $link
 */
$appName = 'HotelOS';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Account</title>
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background: #f5f5f5;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 12px 12px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 28px;"><?= $appName ?></h1>
        <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0;">Hotel Management System</p>
    </div>
    
    <div style="background: white; padding: 40px; border: 1px solid #e5e7eb; border-top: none;">
        <h2 style="color: #1f2937; margin-top: 0;">Hi <?= htmlspecialchars($name ?? 'there') ?>,</h2>
        
        <p style="color: #4b5563; font-size: 16px;">
            Welcome to <?= $appName ?>! Please verify your email address by clicking the button below.
        </p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="<?= htmlspecialchars($link ?? '#') ?>" 
               style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                      color: white; 
                      padding: 14px 32px; 
                      text-decoration: none; 
                      border-radius: 8px; 
                      display: inline-block; 
                      font-weight: 600;
                      font-size: 16px;
                      box-shadow: 0 4px 14px rgba(102, 126, 234, 0.4);">
                ✓ Verify Email Address
            </a>
        </div>
        
        <p style="color: #6b7280; font-size: 14px;">
            If the button doesn't work, copy and paste this link into your browser:
        </p>
        <p style="color: #3b82f6; font-size: 12px; word-break: break-all; background: #f3f4f6; padding: 10px; border-radius: 4px;">
            <?= htmlspecialchars($link ?? '#') ?>
        </p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        
        <p style="color: #9ca3af; font-size: 13px; margin: 0;">
            If you didn't create an account with <?= $appName ?>, you can safely ignore this email.
        </p>
    </div>
    
    <div style="background: #1f2937; padding: 20px; text-align: center; border-radius: 0 0 12px 12px;">
        <p style="color: #9ca3af; margin: 0; font-size: 12px;">
            © <?= date('Y') ?> <?= $appName ?>. All rights reserved.
        </p>
    </div>
</body>
</html>
