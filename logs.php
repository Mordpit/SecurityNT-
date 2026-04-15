<?php
require_once 'config.php';

// Admin credentials (username => password)
$ADMIN_ACCOUNTS = [
    'admin'  => 'admin123',
    'admin2' => 'admin456',
    'admin3' => 'admin789',
];

session_start();

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if (isset($ADMIN_ACCOUNTS[$username]) && $ADMIN_ACCOUNTS[$username] === $password) {
        $_SESSION['logged_in'] = true;
    } else {
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: logs.php');
    exit();
}

// Check authentication
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>เข้าสู่ระบบ - ดู Logs</title>
        <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Sarabun', sans-serif;
                background: #f5f5f5;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                padding: 20px;
            }
            .login-box {
                background: white;
                padding: 40px;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                max-width: 400px;
                width: 100%;
            }
            h2 { text-align: center; margin-bottom: 30px; }
            input {
                width: 100%;
                padding: 12px;
                margin-bottom: 16px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-family: 'Sarabun', sans-serif;
                font-size: 15px;
            }
            button {
                width: 100%;
                padding: 12px;
                background: #ffcc00;
                border: none;
                border-radius: 4px;
                font-weight: 600;
                cursor: pointer;
                font-family: 'Sarabun', sans-serif;
                font-size: 16px;
            }
            button:hover { background: #e6b800; }
            .error { background: #fff3cd; padding: 12px; margin-bottom: 16px; color: #856404; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>เข้าสู่ระบบ</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="ชื่อผู้ใช้" required>
                <input type="password" name="password" placeholder="รหัสผ่าน" required>
                <button type="submit" name="login">เข้าสู่ระบบ</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Fetch logs
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM activity_logs";
if ($search) {
    $sql .= " WHERE user_fullname LIKE ? OR user_idcard LIKE ? OR action LIKE ?";
}
$sql .= " ORDER BY created_at DESC LIMIT 100";

$stmt = $conn->prepare($sql);
if ($search) {
    $search_param = "%{$search}%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Activity Logs</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600&display=swap" rel="stylesheet">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Sarabun', sans-serif;
    background: #f0f2f5;
    padding: 0;
    min-height: 100vh;
}

.container {
    max-width: 1400px;
    margin: 24px;
    background: white;
    padding: 28px 32px;
    border-radius: 12px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}

.table-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
    padding-bottom: 16px;
    border-bottom: 2px solid #ffcc00;
}

h1 {
    font-size: 28px;
    color: #000;
}

.top-header {
    background: #000;
    padding: 16px 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 4px solid #ffcc00;
    position: sticky;
    top: 0;
    z-index: 100;
}

.top-header .logo img {
    height: 40px;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.nav-link {
    color: #fff;
    text-decoration: none;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    transition: background 0.2s;
}

.nav-link:hover {
    background: rgba(255,255,255,0.1);
}

.nav-link.active {
    background: #ffcc00;
    color: #000;
}

.logout-btn {
    padding: 8px 16px;
    background: #d32f2f;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-weight: 500;
    font-size: 14px;
    transition: background 0.2s;
}

.logout-btn:hover {
    background: #b71c1c;
}

.search-box {
    margin-bottom: 24px;
}

.search-box input {
    padding: 12px 16px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 15px;
    font-family: 'Sarabun', sans-serif;
    width: 100%;
    max-width: 400px;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 750px;
}

th, td {
    padding: 12px 14px;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
    white-space: nowrap;
}

th {
    background: #fafafa;
    font-weight: 600;
    color: #666;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

tr:hover td {
    background: #fffef5;
}

.action-badge {
    display: inline-block;
    padding: 4px 12px;
    background: #4CAF50;
    color: white;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 500;
}

.no-data {
    text-align: center;
    padding: 40px;
    color: #999;
}

@media (max-width: 768px) {
    .container {
        margin: 12px;
        padding: 20px 16px;
        border-radius: 8px;
    }
    .top-header {
        padding: 12px 16px;
    }
    .top-header .logo img {
        height: 32px;
    }
    .header-right {
        gap: 6px;
    }
    .nav-link, .logout-btn {
        padding: 6px 10px;
        font-size: 12px;
    }
}

@media (max-width: 480px) {
    .header-right .nav-link span.desktop-text {
        display: none;
    }
}
</style>
</head>
<body>

<div class="top-header">
    <div class="logo">
        <img src="logo.png" alt="Logo">
    </div>
    <div class="header-right">
        <a href="admin.php" class="nav-link">📊 <span class="desktop-text">Dashboard</span></a>
        <a href="logs.php" class="nav-link active">📋 <span class="desktop-text">Logs</span></a>
        <a href="?logout" class="logout-btn">ออกจากระบบ</a>
    </div>
</div>

<div class="container">

    <h1 style="font-size:22px;font-weight:700;color:#1a1a1a;margin-bottom:20px;">📋 Activity Logs</h1>

    <div class="search-box">
        <form method="GET">
            <input type="text" name="search" placeholder="ค้นหา (ชื่อ, เลขบัตร, action)..." value="<?php echo htmlspecialchars($search); ?>">
        </form>
    </div>

    <?php if ($result->num_rows > 0): ?>
    <div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Action</th>
                <th>ชื่อ-สกุล</th>
                <th>เลขบัตรประชาชน</th>
                <th>รายละเอียด</th>
                <th>IP Address</th>
                <th>วันเวลา</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><span class="action-badge"><?php echo htmlspecialchars($row['action']); ?></span></td>
                <td><?php echo htmlspecialchars($row['user_fullname']); ?></td>
                <td><?php echo htmlspecialchars($row['user_idcard']); ?></td>
                <td style="white-space:normal;max-width:200px;word-break:break-word;"><?php echo htmlspecialchars(substr($row['details'], 0, 60)); ?>...</td>
                <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                <td><?php echo date('d/m/Y H:i:s', strtotime($row['created_at'])); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>
    <?php else: ?>
    <div class="no-data">ไม่พบข้อมูล</div>
    <?php endif; ?>
</div>

</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
