<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/app.php';

class Mailer {

    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = MAIL_ENCRYPTION === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = MAIL_PORT;

            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = self::wrapTemplate($subject, $htmlBody);
            $mail->AltBody = strip_tags($htmlBody);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mailer error to $toEmail: " . $mail->ErrorInfo);
            return false;
        }
    }

    public static function sendOtp(string $toEmail, string $toName, string $otp): bool {
        $body = "
            <p>Hi <strong>{$toName}</strong>,</p>
            <p>You requested a password reset for your Auditorium Booking account.</p>
            <p>Your one-time password is:</p>
            <div style='font-size:36px;font-weight:700;letter-spacing:10px;color:#1E3A5F;
                        background:#EBF3FA;padding:20px 32px;border-radius:8px;
                        display:inline-block;margin:16px 0;'>{$otp}</div>
            <p>This OTP expires in <strong>" . OTP_EXPIRY_MINUTES . " minutes</strong>.</p>
            <p>If you did not request this, please ignore this email.</p>
        ";
        return self::send($toEmail, $toName, 'Your Password Reset OTP — ' . APP_NAME, $body);
    }

    private static function wrapTemplate(string $subject, string $body): string {
        return "<!DOCTYPE html>
<html>
<head>
  <meta charset='utf-8'>
  <meta name='viewport' content='width=device-width,initial-scale=1'>
  <title>{$subject}</title>
</head>
<body style='margin:0;padding:0;background:#f4f6f9;font-family:Arial,sans-serif;'>
  <table width='100%' cellpadding='0' cellspacing='0' style='padding:40px 20px;'>
    <tr><td align='center'>
      <table width='600' cellpadding='0' cellspacing='0' style='max-width:600px;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);'>
        <!-- Header -->
        <tr><td style='background:#1E3A5F;padding:28px 40px;'>
          <p style='margin:0;color:#fff;font-size:20px;font-weight:700;'>" . APP_NAME . "</p>
          <p style='margin:4px 0 0;color:#BDD7EE;font-size:13px;'>College Auditorium Management</p>
        </td></tr>
        <!-- Body -->
        <tr><td style='padding:36px 40px;color:#333;font-size:15px;line-height:1.7;'>
          {$body}
        </td></tr>
        <!-- Footer -->
        <tr><td style='background:#f4f6f9;padding:20px 40px;border-top:1px solid #e8edf2;'>
          <p style='margin:0;color:#888;font-size:12px;'>This is an automated message from " . APP_NAME . ". Please do not reply.</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>";
    }
}
