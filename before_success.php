<?php
session_start();

// Check if form data exists in session
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
$profile_image = $data['profile_image'];

// Clear session data after reading
unset($_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ตรวจสอบข้อมูล</title>
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

.content {
    padding: 32px;
}

.profile-preview {
    text-align: center;
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 1px solid #e0e0e0;
}

.profile-preview img {
    width: 140px;
    height: 160px;
    object-fit: cover;
    border: 2px solid #ffcc00;
}

.info-group {
    margin-bottom: 20px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.info-item {
    margin-bottom: 16px;
}

.info-item.full-width {
    grid-column: 1 / -1;
}

.info-label {
    font-size: 14px;
    color: #666;
    margin-bottom: 4px;
}

.info-value {
    font-size: 16px;
    font-weight: 500;
    color: #000;
}

.button-group {
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid #e0e0e0;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

button, .btn {
    padding: 14px;
    border: none;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    font-family: 'Sarabun', sans-serif;
    transition: background 0.2s;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    -webkit-tap-highlight-color: transparent;
}

@media (max-width: 640px) {
    button, .btn {
        padding: 16px;
        font-size: 17px;
    }
}

.btn-edit {
    background: #ffffff;
    color: #000000;
    border: 2px solid #d0d0d0;
}

.btn-edit:hover {
    background: #f5f5f5;
}

.btn-confirm {
    background: #ffcc00;
    color: #000000;
}

.btn-confirm:hover {
    background: #e6b800;
}

@media (max-width: 640px) {
    .info-group {
        grid-template-columns: 1fr;
    }
    
    .button-group {
        grid-template-columns: 1fr;
    }
    
    .content {
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
    
    .info-label {
        font-size: 13px;
    }
    
    .info-value {
        font-size: 15px;
    }
    
}

/* Success Section */
.success-section {
    text-align: center;
    margin-top: 32px;
    padding-top: 32px;
    border-top: 1px solid #e0e0e0;
}

.success-icon {
    width: 80px;
    height: 80px;
    background: #ffcc00;
    border-radius: 50%;
    margin: 0 auto 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 44px;
    color: #2e7d32;
}

.success-message {
    color: #555;
    font-size: 16px;
    line-height: 1.6;
    margin-bottom: 28px;
}

.btn-back {
    display: inline-block;
    padding: 14px 40px;
    background: #ffcc00;
    color: #000000;
    text-decoration: none;
    font-size: 16px;
    font-weight: 600;
    font-family: 'Sarabun', sans-serif;
    transition: background 0.2s;
    -webkit-tap-highlight-color: transparent;
}

.btn-back:hover {
    background: #e6b800;
}

.btn-back:active {
    background: #ccaa00;
}

@media (max-width: 640px) {
    .success-message {
        font-size: 15px;
    }
    
    .btn-back {
        padding: 16px 40px;
        font-size: 17px;
        width: 100%;
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
        <h1>บันทึกข้อมูลสำเร็จ</h1>
    </div>

    <div class="content">
        <?php if (!empty($profile_image)): ?>
        <div class="profile-preview">
            <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="รูปถ่าย">
        </div>
        <?php endif; ?>

        <div class="info-group">
            <div class="info-item">
                <div class="info-label">ชื่อ-สกุล</div>
                <div class="info-value"><?php echo htmlspecialchars($fullname); ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">เบอร์โทรศัพท์</div>
                <div class="info-value"><?php echo htmlspecialchars($phone); ?></div>
            </div>
        </div>

        <div class="info-group">
            <div class="info-item">
                <div class="info-label">เลขบัตรประชาชน</div>
                <div class="info-value"><?php echo htmlspecialchars($idcard); ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">หน่วยงาน/บริษัท</div>
                <div class="info-value"><?php echo htmlspecialchars($department); ?></div>
            </div>
        </div>

        <div class="info-item full-width">
            <div class="info-label">รายละเอียดงาน</div>
            <div class="info-value"><?php echo nl2br(htmlspecialchars($detail)); ?></div>
        </div>

        <div class="info-group">
            <div class="info-item">
                <div class="info-label">เวลาเข้างาน</div>
                <div class="info-value"><?php echo htmlspecialchars($timein); ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">เวลาที่ปฏิบัติงานเสร็จ</div>
                <div class="info-value"><?php echo htmlspecialchars($timeout); ?></div>
            </div>
        </div>

        <div class="info-item">
            <div class="info-label">เบอร์เจ้าหน้าที่ NT</div>
            <div class="info-value"><?php echo htmlspecialchars($contact); ?></div>
        </div>

        <!-- <div class="button-group">
            <a href="register.php" class="btn btn-edit">← แก้ไขข้อมูล</a>
            <form method="POST" action="save_data.php" style="margin: 0; display: block;">
                <button type="submit" class="btn-confirm" style="width: 100%;">✓ ยืนยันและบันทึก</button>
            </form>
        </div> -->

        <div class="success-section">
            <div class="success-icon">✓</div>
            <p class="success-message">ข้อมูลการลงทะเบียนของคุณถูกบันทึกเรียบร้อยแล้ว<br>ขอบคุณสำหรับความร่วมมือ</p>
            <a href="register.php" class="btn-back">กลับไปหน้าลงทะเบียน</a>
        </div>
    </div>
</div>

</body>
</html>
