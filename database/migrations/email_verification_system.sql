-- Add verification token column to users table
ALTER TABLE `users` ADD COLUMN `email_verification_token` VARCHAR(64) NULL AFTER `email_verified_at`;
ALTER TABLE `users` ADD INDEX `idx_user_verify_token` (`email_verification_token`);

-- Reset verification status for demo users (optional, but good for testing)
-- UPDATE `users` SET `email_verified_at` = NULL, `email_verification_token` = NULL WHERE `role` = 'owner' AND `email` LIKE '%@example.com';
