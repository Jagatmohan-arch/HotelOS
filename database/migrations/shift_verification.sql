-- Phase F3: Manager Verification & Variance Flags
-- Allows managers to audit and verify closed shifts

ALTER TABLE `shifts`
ADD COLUMN `verified_by` INT UNSIGNED NULL COMMENT 'Manager who audited this shift',
ADD COLUMN `verified_at` TIMESTAMP NULL,
ADD COLUMN `manager_note` VARCHAR(255) NULL COMMENT 'Remarks on variance or issues',
ADD KEY `fk_shifts_verifier` (`verified_by`),
ADD CONSTRAINT `fk_shifts_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`) ON DELETE SET NULL;
