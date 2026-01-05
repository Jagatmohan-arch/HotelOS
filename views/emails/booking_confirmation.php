<?php
/**
 * Email Template: Booking Confirmation
 * Variables: $booking_number, $guest_name, $hotel_name, $room_number, $room_type, 
 *            $check_in_date, $check_out_date, $nights, $grand_total
 */
$appName = 'HotelOS';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background: #f5f5f5;">
    <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 30px; text-align: center; border-radius: 12px 12px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 28px;"><?= htmlspecialchars($hotel_name ?? 'Hotel') ?></h1>
        <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0;">Booking Confirmation</p>
    </div>
    
    <div style="background: white; padding: 40px; border: 1px solid #e5e7eb; border-top: none;">
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="display: inline-block; background: #ecfdf5; color: #059669; padding: 10px 20px; border-radius: 50px; font-weight: 600;">
                ✓ Booking Confirmed
            </div>
        </div>
        
        <h2 style="color: #1f2937; margin-top: 0;">Dear <?= htmlspecialchars($guest_name ?? 'Guest') ?>,</h2>
        
        <p style="color: #4b5563; font-size: 16px;">
            Thank you for your reservation. Your booking has been confirmed with the following details:
        </p>
        
        <div style="background: #f9fafb; border-radius: 8px; padding: 20px; margin: 20px 0;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; color: #6b7280;">Booking Number:</td>
                    <td style="padding: 8px 0; color: #1f2937; font-weight: 600; text-align: right;">
                        <?= htmlspecialchars($booking_number ?? 'N/A') ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #6b7280;">Room:</td>
                    <td style="padding: 8px 0; color: #1f2937; font-weight: 600; text-align: right;">
                        <?= htmlspecialchars($room_number ?? '') ?> (<?= htmlspecialchars($room_type ?? '') ?>)
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #6b7280;">Check-in:</td>
                    <td style="padding: 8px 0; color: #1f2937; font-weight: 600; text-align: right;">
                        <?= htmlspecialchars($check_in_date ?? 'N/A') ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #6b7280;">Check-out:</td>
                    <td style="padding: 8px 0; color: #1f2937; font-weight: 600; text-align: right;">
                        <?= htmlspecialchars($check_out_date ?? 'N/A') ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #6b7280;">Duration:</td>
                    <td style="padding: 8px 0; color: #1f2937; font-weight: 600; text-align: right;">
                        <?= htmlspecialchars($nights ?? '1') ?> Night(s)
                    </td>
                </tr>
                <tr style="border-top: 1px solid #e5e7eb;">
                    <td style="padding: 12px 0 8px 0; color: #1f2937; font-weight: 600;">Total Amount:</td>
                    <td style="padding: 12px 0 8px 0; color: #059669; font-weight: 700; text-align: right; font-size: 18px;">
                        ₹<?= number_format((float)($grand_total ?? 0), 2) ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <div style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0; border-radius: 0 8px 8px 0;">
            <p style="color: #1e40af; margin: 0; font-size: 14px;">
                <strong>ℹ️ Check-in Time:</strong> 2:00 PM onwards<br>
                <strong>ℹ️ Check-out Time:</strong> Before 11:00 AM
            </p>
        </div>
        
        <p style="color: #4b5563; font-size: 14px;">
            Please carry a valid ID proof at the time of check-in. For any queries, contact the hotel reception.
        </p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        
        <p style="color: #9ca3af; font-size: 13px; margin: 0;">
            We look forward to welcoming you!
        </p>
    </div>
    
    <div style="background: #1f2937; padding: 20px; text-align: center; border-radius: 0 0 12px 12px;">
        <p style="color: #9ca3af; margin: 0; font-size: 12px;">
            Powered by <?= $appName ?> | © <?= date('Y') ?> All rights reserved.
        </p>
    </div>
</body>
</html>
