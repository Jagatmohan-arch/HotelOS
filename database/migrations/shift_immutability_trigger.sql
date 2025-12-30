-- ==========================================
-- Phase D: Shift Immutability Trigger
-- Prevents modification of closed shifts
-- ==========================================

DELIMITER $$

DROP TRIGGER IF EXISTS prevent_closed_shift_modification$$

CREATE TRIGGER prevent_closed_shift_modification
BEFORE UPDATE ON shifts
FOR EACH ROW
BEGIN
    -- If shift is already CLOSED, prevent any modifications
    IF OLD.status = 'CLOSED' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot modify a closed shift. Shifts are immutable after closure for financial integrity.';
    END IF;
END$$

DELIMITER ;

-- ==========================================
-- Test the trigger (optional - comment out in production)
-- ==========================================
-- To test, try updating a closed shift:
-- UPDATE shifts SET closing_cash = 5000 WHERE id = 1 AND status = 'CLOSED';
-- Should fail with error message

SELECT '✅ Shift immutability trigger created' AS Status;
