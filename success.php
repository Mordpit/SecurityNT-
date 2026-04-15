<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>บันทึกสำเร็จ</title>
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
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

@media (max-width: 640px) {
    body {
        padding: 0;
        align-items: flex-start;
    }
}

.container {
    max-width: 500px;
    width: 100%;
    background: #ffffff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    text-align: center;
}

@media (max-width: 640px) {
    .container {
        box-shadow: none;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
}

.header {
    background: #000000;
    padding: 24px;
    border-bottom: 4px solid #ffcc00;
}

.success-content {
    padding: 60px 40px;
}

.success-icon {
    width: 90px;
    height: 90px;
    background: #ffcc00;
    border-radius: 50%;
    margin: 0 auto 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 50px;
    color: #000000;
}

h1 {
    color: #000000;
    margin: 0 0 16px;
    font-size: 26px;
    font-weight: 600;
}

p {
    color: #555;
    margin: 0 0 40px;
    font-size: 16px;
    line-height: 1.6;
}

.button-group {
    margin-top: 32px;
}

a {
    display: inline-block;
    padding: 14px 40px;
    background: #ffcc00;
    color: #000000;
    text-decoration: none;
    font-size: 16px;
    font-weight: 600;
    transition: background 0.2s;
}

a:hover {
    background: #e6b800;
}

a:active {
    background: #ccaa00;
}

@media (max-width: 640px) {
    .success-content {
        padding: 40px 20px;
    }
    
    .header {
        padding: 16px;
    }
    
    .header img {
        height: 38px !important;
    }
    
    h1 {
        font-size: 22px;
    }
    
    p {
        font-size: 15px;
    }
    
    a {
        padding: 16px 40px;
        font-size: 17px;
        width: 100%;
        -webkit-tap-highlight-color: transparent;
    }
}
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <img src="logo.png" alt="Logo" style="height: 45px;">
    </div>
    
    <div class="success-content">
        <div class="success-icon">✓</div>
        <h1>บันทึกข้อมูลสำเร็จ</h1>
        <p>ข้อมูลการลงทะเบียนของคุณถูกบันทึกเรียบร้อยแล้ว<br>ขอบคุณสำหรับความร่วมมือ</p>
        
        <div class="button-group">
            <a href="register.php">กลับไปหน้าลงทะเบียน</a>
        </div>
    </div>
</div>

</body>
</html>
