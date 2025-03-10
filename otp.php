<?php
require_once __DIR__ . '/vendor/autoload.php';

require 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    die(json_encode(["status" => "false", "message" => "Database connection failed"]));
}

// Function to verify user authentication
function authenticateUser($username, $password, $mysqli)
{
    $stmt = $mysqli->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashedPassword);
        $stmt->fetch();
        $stmt->close();
        return password_verify($password, $hashedPassword);
    }
    $stmt->close();
    return false;
}

// Function to generate OTP
function generateOTP($length = 6)
{
    return str_pad(mt_rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

// Function to store OTP in database
function storeOTP($email, $otp, $mysqli)
{
    $expiry_time = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    $stmt = $mysqli->prepare("INSERT INTO otp_codes (email, otp_code, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $otp, $expiry_time);
    $stmt->execute();
    $stmt->close();
}

// Function to send email
function sendEmail($to, $otp, $companyName, $companyEmail, $senderEmail)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom($senderEmail);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = 'Your One-Time Passcode (OTP) from ' . $companyName;

        $mail->Body = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>OTP Verification</title>
    <style>
        body {
            background-color: #f4f4f4;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            background-color: #ffffff;
            max-width: 600px;
            margin: 30px auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #dddddd;
        }
        .header h1 {
            margin: 0;
            color: #333333;
        }
        .content {
            margin-top: 20px;
            text-align: center;
        }
        .content p {
            font-size: 16px;
            color: #555555;
        }
        .otp-code {
            font-size: 28px;
            font-weight: bold;
            color: #007bff;
            padding: 15px 25px;
            border: 2px solid #007bff;
            border-radius: 6px;
            display: inline-block;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #888888;
        }
        .footer a {
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>' . $companyName . '</h1>
        </div>
        <div class="content">
            <p>Hello ' . $to . ',</p>
            <p>You have requested a One-Time Passcode (OTP) for accessing our services. Please use the OTP below to complete your verification:</p>
            <div class="otp-code">' . $otp . '</div>
            <p>This OTP is valid for the next 10 minutes.</p>
            <p>If you did not request this, please contact us immediately at <a href="mailto:' . $companyEmail . '">' . $companyEmail . '</a>.</p>
        </div>
        <div class="footer">
            <p>This is an automatically generated email. Please do not reply to this message.</p>
        </div>
    </div>
</body>
</html>';


        return $mail->send() ? ["status" => "true", "message" => "Email sent successfully"] : ["status" => "false", "message" => "Failed to send email"];
    } catch (Exception $e) {
        return ["status" => "false", "message" => "Email error: " . $mail->ErrorInfo];
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['username'], $data['password'], $data['To'], $data['Company_name'], $data['Company_email'], $data['OTP_Length'])) {
        $username = $data['username'];
        $password = $data['password'];

        // Authenticate user
        if (!authenticateUser($username, $password, $mysqli)) {
            http_response_code(401);
            echo json_encode(["status" => "false", "message" => "Unauthorized "]);
            exit;
        }

        // Process OTP request
        $to = $data['To'];
        $companyName = $data['Company_name'];
        $companyEmail = $data['Company_email'];
        $otpLength = (int) $data['OTP_Length'];
        $otp = generateOTP($otpLength);

        storeOTP($to, $otp, $mysqli);
        $response = sendEmail($to, $otp, $companyName, $companyEmail, SMTP_USER);
        echo json_encode($response);
    } else {
        http_response_code(400);
        echo json_encode(["status" => "false", "message" => "Missing parameters"]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "false", "message" => "Method Not Allowed"]);
}

$mysqli->close();
?>