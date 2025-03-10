<?php
require 'vendor/autoload.php'; // Include PHPMailer library

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to send email
function sendEmail($to, $otp, $companyName, $companyEmail, $senderEmail)
{
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = ''; // Your Gmail address
        $mail->Password = ''; // Your Gmail password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Sender and recipient settings
        $mail->setFrom($senderEmail);
        $mail->addAddress($to);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'One-Time Passcode (OTP) for ' . $companyName;
        $mail->Body = '
<!DOCTYPE html>
<html>
<head>
    <style>
        .email-container {
            background-color: #f4f4f4;
            padding: 20px;
            border-radius: 10px;
            font-family: Arial, sans-serif;
        }
        .otp {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            padding: 10px;
            background-color: #ffffff;
            border: 2px solid #007bff;
            border-radius: 5px;
            margin-bottom: 20px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <p>Dear ' . $to . ',</p>
        <p>We are pleased to provide you with your One-Time Passcode (OTP) for ' . $companyName . '. Please use the following code to proceed:</p>
        <div class="otp">' . $otp . '</div>
        <p>If you have any questions or need further assistance, please don\'t hesitate to contact us at <a href="mailto:' . $companyEmail . '">' . $companyEmail . '</a>.</p>
        <p>Best Regards,<br>' . $companyName . '</p>
        <p><i>This message is generated automatically. Please do not reply.</i></p>
    </div>
</body>
</html>';

        // Send email
        if ($mail->send()) {
            return json_encode(array("status" => "true", "message" => "Email sent successfully"));
        } else {
            return json_encode(array("status" => "false", "message" => "Failed to send email"));
        }
    } catch (Exception $e) {
        return json_encode(array("status" => "false", "message" => "Failed to send email"));
    }
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decode JSON data
    $data = json_decode(file_get_contents('php://input'), true);

    // Check if all required parameters are present
    if (isset($data['To'], $data['OTP_Code'], $data['Company_name'], $data['Company_email'])) {
        $to = $data['To'];
        $otp = $data['OTP_Code'];
        $companyName = $data['Company_name'];
        $companyEmail = $data['Company_email'];
        $senderEmail = 'solution.tech.nimesh@gmail.com'; // Set the sender email

        // Send email
        $response = sendEmail($to, $otp, $companyName, $companyEmail, $senderEmail);
        echo $response;
    } else {
        http_response_code(400);
        echo json_encode(array("status" => "false", "message" => "Missing parameters"));
    }
} else {
    http_response_code(405);
    echo json_encode(array("status" => "false", "message" => "Method Not Allowed"));
}
?>
