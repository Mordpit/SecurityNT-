<?php
session_start();
require_once 'config.php';

// Check if we have form data
if (!isset($_SESSION['form_data'])) {
    header('Location: register.php');
    exit();
}

$data = $_SESSION['form_data'];

$fullname = $data['fullname'];
$phone = $data['phone'];
$idcard = $data['idcard'];
$department = $data['department'];
$detail = $data['detail'];
$timein = $data['timein'];
$timeout = $data['timeout'];
$contact = $data['contact'];

// Handle file upload
$profile_image = '';
if (!empty($data['profile_image_data'])) {
    $image_data = base64_decode($data['profile_image_data']);
    $image_name = $data['image_name'];
    $filetype = pathinfo($image_name, PATHINFO_EXTENSION);
    
    // Create uploads/YYYY/MM directory if not exists
    $year  = date('Y');
    $month = date('m');
    $upload_dir = 'uploads/' . $year . '/' . $month;
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate filename: IDCARD_YYYYMMDD.extension
    $datetime = date('Ymd');
    $new_filename = $idcard . '_' . $datetime . '.' . $filetype;
    $upload_path = $upload_dir . '/' . $new_filename;
    
    if (file_put_contents($upload_path, $image_data)) {
        $profile_image = $upload_path;
    }
}

// Prepare and execute SQL statement
$stmt = $conn->prepare("INSERT INTO registrations (fullname, phone, idcard, department, detail, timein, timeout, contact, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssssss", $fullname, $phone, $idcard, $department, $detail, $timein, $timeout, $contact, $profile_image);

if ($stmt->execute()) {
    // Log the registration activity
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $log_details = json_encode([
        'phone' => $phone,
        'department' => $department,
        'timein' => $timein,
        'timeout' => $timeout
    ]);
    
    $log_stmt = $conn->prepare("INSERT INTO activity_logs (action, user_fullname, user_idcard, details, ip_address) VALUES (?, ?, ?, ?, ?)");
    $action = 'registration';
    $log_stmt->bind_param("sssss", $action, $fullname, $idcard, $log_details, $ip_address);
    $log_stmt->execute();
    $log_stmt->close();
    
    // Clear session data
    unset($_SESSION['form_data']);
    $stmt->close();
    header('Location: success.php');
    exit();
} else {
    $error = "เกิดข้อผิดพลาด: " . $stmt->error;
    $stmt->close();
    
    // Store error and redirect back
    $_SESSION['error'] = $error;
    header('Location: before_success.php');
    exit();
}
?>
