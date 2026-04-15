<?php
session_start();
date_default_timezone_set('Asia/Bangkok');
require_once 'config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'] ?? '';
    
    // Validate fullname: no numbers allowed
    if (preg_match('/[0-9]/', $fullname)) {
        $error = "ชื่อ-สกุล ไม่สามารถใส่ตัวเลขได้";
    }
    $phone = $_POST['phone'] ?? '';
    $idcard = $_POST['idcard'] ?? '';

    // Validate Thai National ID checksum
    if (!isset($error)) {
        if (strlen($idcard) != 13 || !ctype_digit($idcard)) {
            $error = "เลขบัตรประชาชนต้องมี 13 หลัก";
        } else {
            $sum = 0;
            for ($i = 0; $i < 12; $i++) {
                $sum += intval($idcard[$i]) * (13 - $i);
            }
            $checkDigit = (11 - ($sum % 11)) % 10;
            if ($checkDigit != intval($idcard[12])) {
                $error = "เลขบัตรประชาชนไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง";
            }
        }
    }
    $department = $_POST['department'] ?? '';
    $detail = $_POST['detail'] ?? '';
    $timein = date('H:i'); // เวลาเข้าจริง ณ ตอนที่กดบันทึก
    $timeout = $_POST['timeout'] ?? '';
    $contact = $_POST['contact'] ?? '';
    
    // Validate required profile image
    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== 0 || $_FILES['profile_image']['size'] === 0) {
        $error = "กรุณาอัพโหลดรูปถ่าย";
    }
    
    // Handle file upload
    $profile_image = '';
    if (!isset($error) && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            // Create uploads/YYYY/MM directory if not exists
            $year  = date('Y');
            $month = date('m');
            $upload_dir = 'uploads/' . $year . '/' . $month;
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate filename: YYYYMMDD_IDCARD.extension
            $datetime = date('Ymd');
            $new_filename = $datetime . '_' . $idcard . '.' . $filetype;
            $upload_path = $upload_dir . '/' . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                $profile_image = $upload_path;
            }
        } else {
            $error = "ไฟล์รูปภาพไม่ถูกต้อง กรุณาเลือกไฟล์ JPG, PNG หรือ GIF";
        }
    }
    
    if (!isset($error)) {
        // Prepare and execute SQL statement
        $stmt = $conn->prepare("INSERT INTO registrations (fullname, phone, idcard, department, detail, timein, timeout, contact, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $fullname, $phone, $idcard, $department, $detail, $timein, $timeout, $contact, $profile_image);
        
        if ($stmt->execute()) {
            // Log the registration activity
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $log_details = json_encode([
                'phone'      => $phone,
                'department' => $department,
                'timein'     => $timein,
                'timeout'    => $timeout
            ]);
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (action, user_fullname, user_idcard, details, ip_address) VALUES (?, ?, ?, ?, ?)");
            $log_action = 'registration';
            $log_stmt->bind_param("sssss", $log_action, $fullname, $idcard, $log_details, $ip_address);
            $log_stmt->execute();
            $log_stmt->close();

            // Store data in session for display on success page
            $_SESSION['form_data'] = [
                'fullname'      => $fullname,
                'phone'         => $phone,
                'idcard'        => $idcard,
                'department'    => $department,
                'detail'        => $detail,
                'timein'        => $timein,
                'timeout'       => $timeout,
                'contact'       => $contact,
                'profile_image' => $profile_image
            ];
            header('Location: before_success.php');
            exit();
        } else {
            $error = "เกิดข้อผิดพลาด: " . $stmt->error;
        }
        $stmt->close();

    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ลงทะเบียนเข้า-ออก</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600&display=swap" rel="stylesheet">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Sarabun', sans-serif;
    background: #f5f5f5;
    min-height: 100vh;
    padding: 20px;
}

@media (max-width: 640px) {
    body {
        padding: 0;
    }
}

.container {
    max-width: 600px;
    margin: 0 auto;
    background: #ffffff;
    border-radius: 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

@media (max-width: 640px) {
    .container {
        box-shadow: none;
        min-height: 100vh;
    }
}

.header {
    background: #000000;
    padding: 24px 32px;
    border-bottom: 4px solid #ffcc00;
}

.logo img {
    height: 45px;
}

.header-title {
    background: #ffcc00;
    padding: 20px 32px;
    border-bottom: 1px solid #e0e0e0;
}

.header-title h1 {
    font-size: 22px;
    font-weight: 600;
    color: #000000;
    margin: 0;
}

.form-content {
    padding: 32px;
}

.profile-section {
    text-align: center;
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 1px solid #e0e0e0;
}

.profile-upload {
    position: relative;
    width: 140px;
    height: 160px;
    margin: 0 auto;
    border: 2px dashed #d0d0d0;
    background: #fafafa;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.profile-upload:hover {
    border-color: #ffcc00;
    background: #fffef5;
}

.profile-upload.has-image {
    border-style: solid;
    border-color: #ffcc00;
}

.remove-image-btn {
    position: absolute;
    top: -10px;
    right: -10px;
    width: 28px;
    height: 28px;
    background: #d32f2f;
    color: #fff;
    border: 2px solid #fff;
    border-radius: 50%;
    font-size: 16px;
    line-height: 24px;
    text-align: center;
    cursor: pointer;
    z-index: 10;
    display: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    transition: background 0.2s;
    padding: 0;
    font-family: sans-serif;
}

.remove-image-btn:hover {
    background: #b71c1c;
}

.profile-upload.has-image .remove-image-btn {
    display: block;
}

.profile-upload.required-error {
    border-color: #d32f2f;
    background: #fff5f5;
}

.profile-upload.required-error .upload-icon {
    color: #d32f2f;
}

.upload-label .required {
    color: #d32f2f;
    margin-left: 2px;
}

.image-error-msg {
    color: #d32f2f;
    font-size: 13px;
    margin-top: 6px;
    display: none;
}

.profile-upload img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: none;
}

.profile-upload.has-image img {
    display: block;
}

.upload-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 48px;
    color: #ccc;
    pointer-events: none;
    transition: color 0.3s;
}

.profile-upload:hover .upload-icon {
    color: #ffcc00;
}

.profile-upload.has-image .upload-icon {
    display: none;
}

.profile-upload input[type="file"] {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    opacity: 0;
    cursor: pointer;
}

.upload-label {
    margin-top: 12px;
    font-size: 14px;
    color: #666;
}

.upload-status {
    margin-top: 8px;
    font-size: 14px;
    display: none;
}

.upload-status.loading {
    display: block;
    color: #666;
}

.upload-status.success {
    display: block;
    color: #4CAF50;
    font-weight: 500;
}

.upload-status.error {
    display: block;
    color: #f44336;
    font-weight: 500;
}

.loading-spinner {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #ffcc00;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 6px;
    vertical-align: middle;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

label {
    display: block;
    margin-bottom: 8px;
    font-size: 15px;
    font-weight: 500;
    color: #333;
}

label .required {
    color: #d32f2f;
    margin-left: 2px;
}

input[type="text"],
input[type="tel"],
input[type="time"],
input[type="date"],
textarea {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #d0d0d0;
    background: #ffffff;
    font-size: 15px;
    font-family: 'Sarabun', sans-serif;
    color: #333;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
}

input[type="text"]:focus,
input[type="tel"]:focus,
input[type="time"]:focus,
input[type="date"]:focus,
textarea:focus {
    outline: none;
    border-color: #ffcc00;
    box-shadow: 0 0 0 3px rgba(255, 204, 0, 0.1);
}

textarea {
    height: 90px;
    resize: vertical;
    min-height: 70px;
}

.button-group {
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid #e0e0e0;
}

button {
    width: 100%;
    padding: 14px;
    border: none;
    background: #ffcc00;
    color: #000000;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    font-family: 'Sarabun', sans-serif;
    transition: background 0.2s;
    -webkit-tap-highlight-color: transparent;
}

@media (max-width: 640px) {
    button {
        padding: 16px;
        font-size: 17px;
    }
}

button:hover {
    background: #e6b800;
}

button:active {
    background: #ccaa00;
}

.error {
    background: #fff3cd;
    border: 1px solid #ffc107;
    color: #856404;
    padding: 14px 16px;
    margin-bottom: 24px;
    font-size: 15px;
}

input::placeholder,
textarea::placeholder {
    color: #999;
}

.idcard-status {
    font-size: 13px;
    margin-top: 6px;
    font-weight: 500;
    min-height: 18px;
    transition: color 0.2s;
}

.idcard-status.valid {
    color: #2e7d32;
}

.idcard-status.invalid {
    color: #d32f2f;
}

@media (max-width: 640px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-content {
        padding: 20px 16px;
    }
    
    .header,
    .header-title {
        padding: 16px;
    }
    
    .header-title h1 {
        font-size: 20px;
    }
    
    .logo img {
        height: 38px;
    }
    
    input[type="text"],
    input[type="tel"],
    input[type="time"],
    input[type="date"],
    textarea {
        font-size: 16px;
        padding: 14px;
    }
    
    label {
        font-size: 14px;
    }
    
    .upload-label {
        font-size: 13px;
    }
    
    .profile-section {
        margin-bottom: 24px;
        padding-bottom: 20px;
    }
}
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="logo">
            <img src="logo.png" alt="Logo">
        </div>
    </div>

    <div class="header-title">
        <h1>แบบฟอร์มลงทะเบียนเข้า-ออก</h1>
    </div>

    <?php if (isset($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php" enctype="multipart/form-data">
        <div class="form-content">
            <div class="profile-section">
                <div class="profile-upload" id="profileUpload">
                    <button type="button" class="remove-image-btn" id="removeImageBtn" onclick="removeImage()" title="ลบรูป">&times;</button>
                    <img id="preview" src="" alt="รูปถ่าย">
                    <div class="upload-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 3V15M12 15L8 11M12 15L16 11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M4 17V18C4 19.1046 4.89543 20 6 20H18C19.1046 20 20 19.1046 20 18V17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <input type="file" name="profile_image" accept="image/jpeg,image/jpg,image/png,image/gif" onchange="handleImageUpload(event)">
                </div>
                <div class="upload-label">คลิกเพื่ออัพโหลดรูปถ่าย<span class="required">*</span></div>
                <div class="upload-status" id="uploadStatus"></div>
                <div class="image-error-msg" id="imageError">กรุณาอัพโหลดรูปถ่าย</div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>ชื่อ-สกุล<span class="required"></span></label>
                    <input type="text" name="fullname" placeholder="กรอกชื่อ-นามสกุล" required oninput="this.value = this.value.replace(/[0-9]/g, '')" title="ชื่อ-สกุล ไม่สามารถใส่ตัวเลขได้">
                </div>

                <div class="form-group">
                    <label>เบอร์โทรศัพท์<span class="required"></span></label>
                    <input type="tel" name="phone" placeholder="กรอกเบอร์โทรศัพท์ 9-10 หลัก" pattern="[0-9]{9,10}" maxlength="10" title="กรุณากรอกเบอร์โทรศัพท์ 9-10 หลัก" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>เลขบัตรประชาชน<span class="required"></span></label>
                    <input type="tel" name="idcard" id="idcard" placeholder="กรอกเลขบัตรประชาชน" pattern="[0-9]{13}" maxlength="13" title="กรุณากรอกเลขบัตรประชาชน 13 หลัก" oninput="this.value = this.value.replace(/[^0-9]/g, ''); checkIDStatus(this.value)" required>
                    <div id="idcard-status" class="idcard-status"></div>
                </div>

                <div class="form-group">
                    <label>หน่วยงาน/บริษัท<span class="required"></span></label>
                    <input type="text" name="department" placeholder="ระบุสังกัด" required>
                </div>
            </div>

            <div class="form-group">
                <label>รายละเอียดงาน<span class="required"></span></label>
                <textarea name="detail" placeholder="ระบุวัตถุประสงค์ หรือรายละเอียดงาน "  oninvalid="this.setCustomValidity('กรุณากรอกรายละเอียดงาน')" oninput="this.setCustomValidity('')" required></textarea>
            </div>

            <!-- <div class="form-group" style="margin-bottom: 8px;">
                <div style="font-size: 16px; color: #666; padding: 4px 0;">
                    <strong></strong> <span id="currentDate" style="color: #000; font-weight: 500;"></span>
                </div>
            </div> -->

            <!-- <div class="form-row">
                <div class="form-group">
                    <label>เวลาเข้างาน<span class="required">*</span></label>
                    <input type="time" name="timein" id="timein" required>
                </div>
            </div> -->

            <div class="form-group">
                <label>เวลาที่ปฏิบัติงานเสร็จ<span class="required"></span></label>
                <input type="time" name="timeout" id="timeout" required oninvalid="this.setCustomValidity('กรุณาระบุเวลาออก')" oninput="this.setCustomValidity('')">
            </div>

            <div class="form-group">
                <label>เบอร์เจ้าหน้าที่ NT<span class="required"></span></label>
                <input type="tel" name="contact" placeholder="กรอกเบอร์ติดต่อ" pattern="[0-9]{9,10}" maxlength="10" title="กรุณากรอกเบอร์ติดต่อ 9-10 หลัก" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
            </div>

            <div class="button-group">
                <button type="submit" onclick="return validateForm()">บันทึกข้อมูล</button>
            </div>
        </div>
    </form>
</div>

<script>
// Set current date in Thai format
function setCurrentDate() {
    const now = new Date();
    const days = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
    const months = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 
                    'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    
    const dayName = days[now.getDay()];
    const day = now.getDate();
    const month = months[now.getMonth()];
    const year = now.getFullYear() + 543; // พ.ศ.
    
    const dateStr = `วัน${dayName}ที่ ${day} ${month} ${year}`;
    document.getElementById('currentDate').textContent = dateStr;
}

// Set minimum time for timeout field (cannot be before current time)
function setMinTimeout() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const currentTime = hours + ':' + minutes;
    const timeoutField = document.getElementById('timeout');
    if (timeoutField) {
        timeoutField.min = currentTime;
    }
}

// Initialize
setCurrentDate();
setMinTimeout();

// Update min time every minute
setInterval(setMinTimeout, 60000);

// Handle image upload with validation and status
function handleImageUpload(event) {
    const file = event.target.files[0];
    const statusDiv = document.getElementById('uploadStatus');
    const uploadDiv = document.getElementById('profileUpload');
    const preview = document.getElementById('preview');
    
    if (!file) {
        return;
    }
    
    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        statusDiv.className = 'upload-status error';
        statusDiv.innerHTML = '❌ ไฟล์ไม่ถูกต้อง! กรุณาเลือกไฟล์รูปภาพ (JPG, PNG, GIF)';
        event.target.value = ''; // Clear the input
        return;
    }
    
    // Validate file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
        statusDiv.className = 'upload-status error';
        statusDiv.innerHTML = '❌ ไฟล์ใหญ่เกินไป! กรุณาเลือกไฟล์ที่มีขนาดไม่เกิน 5MB';
        event.target.value = '';
        return;
    }
    
    // Show loading state
    statusDiv.className = 'upload-status loading';
    statusDiv.innerHTML = '<span class="loading-spinner"></span>กำลังโหลดรูปภาพ...';
    
    // Read the file
    const reader = new FileReader();
    
    reader.onload = function(e) {
        // Simulate loading delay for better UX
        setTimeout(function() {
            preview.src = e.target.result;
            uploadDiv.classList.add('has-image');
            
            // Show success message
            statusDiv.className = 'upload-status success';
            statusDiv.innerHTML = '✓ อัพโหลดรูปภาพสำเร็จ';
            
            // Hide success message after 3 seconds
            setTimeout(function() {
                statusDiv.style.display = 'none';
            }, 3000);
        }, 500);
    };
    
    reader.onerror = function() {
        statusDiv.className = 'upload-status error';
        statusDiv.innerHTML = '❌ เกิดข้อผิดพลาดในการอ่านไฟล์';
    };
    
    reader.readAsDataURL(file);
}

// Remove uploaded image
function removeImage() {
    const fileInput = document.querySelector('input[name="profile_image"]');
    const uploadDiv = document.getElementById('profileUpload');
    const preview = document.getElementById('preview');
    const statusDiv = document.getElementById('uploadStatus');
    
    // Clear file input
    fileInput.value = '';
    
    // Reset preview
    preview.src = '';
    uploadDiv.classList.remove('has-image');
    uploadDiv.classList.remove('required-error');
    
    // Reset status
    statusDiv.className = 'upload-status';
    statusDiv.innerHTML = '';
    statusDiv.style.display = '';
    
    // Hide error message if shown
    document.getElementById('imageError').style.display = 'none';
}
// Validate Thai National ID checksum
function checkThaiID(id) {
    if (id.length != 13) return false;
    var sum = 0;
    for (var i = 0; i < 12; i++) {
        sum += parseFloat(id.charAt(i)) * (13 - i);
    }
    var checkDigit = (11 - (sum % 11)) % 10;
    return checkDigit == id.charAt(12);
}

// Show real-time status under the idcard field
function checkIDStatus(val) {
    var statusEl = document.getElementById('idcard-status');
    if (!statusEl) return;
    if (val.length < 13) {
        statusEl.textContent = '';
        statusEl.className = 'idcard-status';
        return;
    }
    if (checkThaiID(val)) {
        statusEl.textContent = '✓ บัตรประชาชนถูกต้อง';
        statusEl.className = 'idcard-status valid';
    } else {
        statusEl.textContent = '✗ เลขบัตรประชาชนไม่ถูกต้อง';
        statusEl.className = 'idcard-status invalid';
    }
}

function validateForm() {
    const fileInput = document.querySelector('input[name="profile_image"]');
    const uploadDiv = document.getElementById('profileUpload');
    const errorMsg = document.getElementById('imageError');
    
    // Validate Thai ID
    const idcardInput = document.querySelector('input[name="idcard"]');
    const idcardVal = idcardInput ? idcardInput.value : '';
    if (!checkThaiID(idcardVal)) {
        alert('เลขบัตรประชาชนไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง');
        if (idcardInput) idcardInput.focus();
        return false;
    }

    if (!fileInput.files || fileInput.files.length === 0) {
        uploadDiv.classList.add('required-error');
        errorMsg.style.display = 'block';
        uploadDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
    }
    
    uploadDiv.classList.remove('required-error');
    errorMsg.style.display = 'none';
    return true;
}
</script>

</body>
</html>
