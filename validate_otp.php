<?php
require 'config.php';

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
  die(json_encode(["status" => "false", "message" => "Database connection failed"]));
}

// Function to validate OTP
function validateOTP($email, $otp, $mysqli)
{
  // Get the current timestamp
  $current_time = date('Y-m-d H:i:s');

  // Check if the OTP exists and is still valid
  $stmt = $mysqli->prepare("SELECT id FROM otp_codes WHERE email = ? AND otp_code = ? AND expires_at > ?");
  $stmt->bind_param("sss", $email, $otp, $current_time);
  $stmt->execute();

  // Bind result variables
  $otp_id = null; // Initialize variable
  $stmt->bind_result($otp_id);

  if ($stmt->fetch()) { // Check if an OTP exists
    $stmt->close();

    // If OTP is valid, delete it from the database
    $deleteStmt = $mysqli->prepare("DELETE FROM otp_codes WHERE id = ?");
    $deleteStmt->bind_param("i", $otp_id);
    $deleteStmt->execute();
    $deleteStmt->close();

    return ["status" => "true", "message" => "OTP verified successfully"];
  } else {
    $stmt->close();
    return ["status" => "false", "message" => "Invalid or expired OTP"];
  }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = json_decode(file_get_contents('php://input'), true);

  if (isset($data['email'], $data['otp'])) {
    $email = $data['email'];
    $otp = $data['otp'];

    $response = validateOTP($email, $otp, $mysqli);
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