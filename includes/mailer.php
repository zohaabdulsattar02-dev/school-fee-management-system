<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendOTPEmail($recipientEmail, $otp)
{
    // Load private configuration
    $config = require __DIR__ . '/../config.local.php';

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();

        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;

        $mail->Username   = $config['smtp_username'];
        $mail->Password   = $config['smtp_password'];

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 20;
        $mail->SMTPDebug = 0;

        $mail->setFrom(
            $config['smtp_username'],
            'Great Chancellors School'
        );

        $mail->addAddress($recipientEmail);

        $mail->isHTML(true);

        $mail->Subject = 'Password Reset OTP - Great Chancellors School';

        $mail->Body = "
            <div style='font-family:Arial;padding:20px;'>
                <h2>Great Chancellors School</h2>
                <p>Your password reset verification code is:</p>
                <h1 style='color:#E85D04;letter-spacing:5px;'>$otp</h1>
                <p>This OTP is valid for 15 minutes.</p>
                <p>If you did not request this, please ignore this email.</p>
            </div>
        ";

        $mail->AltBody = "Your password reset OTP is: $otp. Valid for 15 minutes.";

        $mail->send();

        return true;

    } catch (Exception $e) {

        error_log("Mailer Error: " . $mail->ErrorInfo);

        return false;
    }
}
?>