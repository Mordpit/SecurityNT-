<?php
require_once 'config.php';
date_default_timezone_set('Asia/Bangkok');

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
    header('Location: admin.php');
    exit();
}

// Check authentication - show login page if not logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
            border-top: 4px solid #ffcc00;
        }
        h2 { text-align: center; margin-bottom: 8px; font-size: 24px; }
        .subtitle { text-align: center; color: #888; margin-bottom: 30px; font-size: 14px; }
        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Sarabun', sans-serif;
            font-size: 15px;
        }
        input:focus { outline: none; border-color: #ffcc00; box-shadow: 0 0 0 3px rgba(255,204,0,0.1); }
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
            transition: background 0.2s;
        }
        button:hover { background: #e6b800; }
        .error { background: #fff3cd; padding: 12px; margin-bottom: 16px; color: #856404; border-radius: 4px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>🔐 Admin Dashboard</h2>
        <p class="subtitle">ระบบดูข้อมูลผู้ลงทะเบียน</p>
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

// ============ LOGGED IN - ADMIN DASHBOARD ============

// Get selected month/year from GET params or default to current
$currentMonth = isset($_GET['month']) ? intval($_GET['month']) : intval(date('n'));
$currentYear = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Validate month/year
if ($currentMonth < 1 || $currentMonth > 12) $currentMonth = intval(date('n'));
if ($currentYear < 2020 || $currentYear > 2100) $currentYear = intval(date('Y'));

// Selected date for detail view
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Current view mode: 'calendar' (default), 'month', 'all'
$viewMode = isset($_GET['view']) ? $_GET['view'] : 'calendar';

// Prev/Next month calculation
$prevMonth = $currentMonth - 1;
$prevYear = $currentYear;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }

$nextMonth = $currentMonth + 1;
$nextYear = $currentYear;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

// Get registration counts for each day of the selected month (unique idcard per day)
$startDate = sprintf('%04d-%02d-01', $currentYear, $currentMonth);
$endDate = date('Y-m-t', strtotime($startDate));

$stmt = $conn->prepare("SELECT DATE(created_at) as reg_date, COUNT(DISTINCT idcard) as cnt FROM registrations WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at)");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$countResult = $stmt->get_result();

$dayCounts = [];
while ($row = $countResult->fetch_assoc()) {
    $dayCounts[$row['reg_date']] = intval($row['cnt']);
}
$stmt->close();

// Get registrations for the selected date
$regStmt = $conn->prepare("SELECT * FROM registrations WHERE DATE(created_at) = ? ORDER BY created_at DESC");
$regStmt->bind_param("s", $selectedDate);
$regStmt->execute();
$registrations = $regStmt->get_result();

// Count total for selected date
$totalForDate = $registrations->num_rows;

// Thai month names
$thaiMonths = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
               'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
$thaiDays = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];

// Calendar calculations
$daysInMonth = intval(date('t', strtotime($startDate)));
$firstDayOfWeek = intval(date('w', strtotime($startDate))); // 0=Sunday

// Format selected date in Thai
$selectedDateObj = new DateTime($selectedDate);
$selectedDayNum = intval($selectedDateObj->format('j'));
$selectedMonthNum = intval($selectedDateObj->format('n'));
$selectedYearBE = intval($selectedDateObj->format('Y')) + 543;
$selectedDateThai = $selectedDayNum . ' ' . $thaiMonths[$selectedMonthNum] . ' ' . $selectedYearBE;

// Total registrations for this month (all entries = นับจำนวนครั้ง)
$totalMonthStmt = $conn->prepare("SELECT COUNT(*) as total FROM registrations WHERE DATE(created_at) BETWEEN ? AND ?");
$totalMonthStmt->bind_param("ss", $startDate, $endDate);
$totalMonthStmt->execute();
$totalMonth = $totalMonthStmt->get_result()->fetch_assoc()['total'];
$totalMonthStmt->close();

// Total unique people by idcard
$totalAllStmt = $conn->prepare("SELECT COUNT(DISTINCT idcard) as total FROM registrations");
$totalAllStmt->execute();
$totalAll = $totalAllStmt->get_result()->fetch_assoc()['total'];
$totalAllStmt->close();

// Total registrations for today
$today = date('Y-m-d');
$totalTodayStmt = $conn->prepare("SELECT COUNT(*) as total FROM registrations WHERE DATE(created_at) = ?");
$totalTodayStmt->bind_param("s", $today);
$totalTodayStmt->execute();
$totalToday = $totalTodayStmt->get_result()->fetch_assoc()['total'];
$totalTodayStmt->close();

// If viewing month or all, fetch unique registrations
$viewRegistrations = null;
$viewTitle = '';
if ($viewMode === 'month') {
    $viewStmt = $conn->prepare("
        SELECT r.* FROM registrations r
        INNER JOIN (
            SELECT idcard, DATE(created_at) as reg_date, MIN(id) as first_id
            FROM registrations
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY idcard, DATE(created_at)
        ) u ON r.id = u.first_id
        ORDER BY r.created_at DESC
    ");
    $viewStmt->bind_param("ss", $startDate, $endDate);
    $viewStmt->execute();
    $viewRegistrations = $viewStmt->get_result();
    $viewTitle = 'การเข้าตึกภายในเดือน ' . $thaiMonths[$currentMonth] . ' ' . ($currentYear + 543) . ' รายคน';
} elseif ($viewMode === 'all') {
    $viewStmt = $conn->prepare("
        SELECT r.* FROM registrations r
        INNER JOIN (
            SELECT idcard, MIN(id) as first_id
            FROM registrations
            GROUP BY idcard
        ) u ON r.id = u.first_id
        ORDER BY r.fullname ASC
    ");
    $viewStmt->execute();
    $viewRegistrations = $viewStmt->get_result();
    $viewTitle = 'ลงทะเบียนทั้งหมด (ไม่ซ้ำ)';
} elseif ($viewMode === 'person' && isset($_GET['idcard'])) {
    $personIdcard = $_GET['idcard'];
    $personFrom = isset($_GET['from']) ? $_GET['from'] : 'all';
    
    // Get all registrations for this person
    $personStmt = $conn->prepare("SELECT * FROM registrations WHERE idcard = ? ORDER BY created_at DESC");
    $personStmt->bind_param("s", $personIdcard);
    $personStmt->execute();
    $personRegistrations = $personStmt->get_result();
    
    // Get person's latest info
    $personInfo = null;
    $personRows = [];
    while ($pRow = $personRegistrations->fetch_assoc()) {
        if ($personInfo === null) $personInfo = $pRow;
        $personRows[] = $pRow;
    }

    // Get the most recent phone number for this person (always latest entry)
    $latestPhoneStmt = $conn->prepare("SELECT phone FROM registrations WHERE idcard = ? ORDER BY created_at DESC LIMIT 1");
    $latestPhoneStmt->bind_param("s", $personIdcard);
    $latestPhoneStmt->execute();
    $latestPhoneRow = $latestPhoneStmt->get_result()->fetch_assoc();
    $latestPhone = $latestPhoneRow ? $latestPhoneRow['phone'] : ($personInfo['phone'] ?? '');
    $latestPhoneStmt->close();
}

// Get monthly unique registration counts for the current year (for chart)
$chartYear = $currentYear;
$chartStmt = $conn->prepare("
    SELECT MONTH(created_at) as m, COUNT(DISTINCT idcard, DATE(created_at)) as cnt
    FROM registrations
    WHERE YEAR(created_at) = ?
    GROUP BY MONTH(created_at)
    ORDER BY m
");
$chartStmt->bind_param("i", $chartYear);
$chartStmt->execute();
$chartResult = $chartStmt->get_result();
$monthlyData = array_fill(1, 12, 0);
while ($cr = $chartResult->fetch_assoc()) {
    $monthlyData[intval($cr['m'])] = intval($cr['cnt']);
}
$chartStmt->close();
$chartDataJson = json_encode(array_values($monthlyData));

$yearBE = $currentYear + 543;
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - ข้อมูลผู้ลงทะเบียน</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Sarabun', sans-serif;
    background: #f0f2f5;
    min-height: 100vh;
}

/* ===== HEADER ===== */
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

/* ===== MAIN CONTENT ===== */
.main-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px;
}

.page-title {
    font-size: 26px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 8px;
}

.page-subtitle {
    font-size: 14px;
    color: #888;
    margin-bottom: 24px;
}

/* ===== STATS CARDS ===== */
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px 24px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    display: flex;
    align-items: center;
    gap: 16px;
}

.stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.stat-icon.yellow { background: #fff8e1; }
.stat-icon.blue   { background: #e3f2fd; }
.stat-icon.green  { background: #e8f5e9; }

.stat-info h3 {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a1a;
    line-height: 1;
}

.stat-info p {
    font-size: 13px;
    color: #888;
    margin-top: 4px;
}

a.stat-card {
    text-decoration: none;
    color: inherit;
    transition: transform 0.15s, box-shadow 0.15s;
}

a.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

a.stat-card.active-view {
    border: 2px solid #ffcc00;
    box-shadow: 0 4px 12px rgba(255,204,0,0.2);
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #666;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    padding: 8px 16px;
    border-radius: 8px;
    background: #f5f5f5;
    transition: all 0.2s;
    margin-bottom: 16px;
}

.back-link:hover {
    background: #e0e0e0;
    color: #333;
}

/* ===== CLICKABLE TABLE ROWS ===== */
tr.clickable-row {
    cursor: pointer;
    transition: background 0.15s;
}

tr.clickable-row:hover td {
    background: #fff8e1;
}

tr.clickable-row td .row-arrow {
    color: #ccc;
    font-size: 18px;
    transition: color 0.15s, transform 0.15s;
}

tr.clickable-row:hover td .row-arrow {
    color: #ffcc00;
    transform: translateX(3px);
}

/* ===== PERSON INFO HEADER ===== */
.person-header {
    display: flex;
    align-items: center;
    gap: 24px;
    padding: 24px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    margin-bottom: 20px;
}

.person-avatar {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    object-fit: cover;
    border: 3px solid #ffcc00;
    flex-shrink: 0;
}

.person-avatar-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    flex-shrink: 0;
    border: 3px solid #e0e0e0;
}

.person-details h2 {
    font-size: 22px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 4px;
}

.person-details .person-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 14px;
    color: #666;
}

.person-meta span {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.history-count {
    margin-left: auto;
    text-align: center;
}

.history-count h3 {
    font-size: 32px;
    font-weight: 700;
    color: #ffcc00;
    line-height: 1;
}

.history-count p {
    font-size: 12px;
    color: #999;
    margin-top: 2px;
}

@media (max-width: 640px) {
    .person-header {
        flex-direction: column;
        text-align: center;
        gap: 16px;
        padding: 20px 16px;
    }
    .history-count {
        margin-left: 0;
    }
    .person-details .person-meta {
        justify-content: center;
    }
}

/* ===== CHART ===== */
.chart-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    padding: 24px;
    margin-top: 24px;
}

.chart-card .chart-title {
    font-size: 18px;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.chart-container {
    position: relative;
    width: 100%;
    height: 300px;
}

@media (max-width: 768px) {
    .chart-container {
        height: 220px;
    }
    .chart-card {
        padding: 16px;
    }
}

/* ===== GRID LAYOUT ===== */
.dashboard-grid {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 24px;
    align-items: start;
}

/* ===== CALENDAR ===== */
.calendar-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    overflow: hidden;
}

.calendar-header {
    background: #000;
    color: #fff;
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.calendar-header h2 {
    font-size: 18px;
    font-weight: 600;
}

.month-nav {
    display: flex;
    gap: 8px;
}

.month-nav a {
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.1);
    color: #fff;
    text-decoration: none;
    border-radius: 8px;
    font-size: 18px;
    transition: background 0.2s;
}

.month-nav a:hover {
    background: #ffcc00;
    color: #000;
}

.calendar-body {
    padding: 16px;
}

.day-names {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    text-align: center;
    margin-bottom: 8px;
}

.day-names span {
    font-size: 13px;
    font-weight: 600;
    color: #999;
    padding: 8px 0;
}

.day-names span:first-child { color: #e53935; }
.day-names span:last-child  { color: #1e88e5; }

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
}

.cal-day {
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    cursor: pointer;
    text-decoration: none;
    color: #333;
    font-size: 15px;
    font-weight: 500;
    position: relative;
    transition: all 0.15s;
}

.cal-day:hover {
    background: #f5f5f5;
}

.cal-day.empty {
    cursor: default;
}

.cal-day.today {
    background: #fffde7;
    border: 2px solid #ffcc00;
}

.cal-day.selected {
    background: #ffcc00;
    color: #000;
    font-weight: 700;
}

.cal-day.selected .cal-badge {
    background: #000;
    color: #fff;
}

.cal-day.sunday { color: #e53935; }
.cal-day.saturday { color: #1e88e5; }

.cal-badge {
    position: absolute;
    bottom: 3px;
    font-size: 10px;
    background: #ffcc00;
    color: #000;
    padding: 1px 6px;
    border-radius: 10px;
    font-weight: 700;
    line-height: 1.4;
}

/* ===== REGISTRATIONS TABLE ===== */
.table-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    overflow: hidden;
}

.table-header {
    padding: 20px 24px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.table-header h2 {
    font-size: 18px;
    font-weight: 600;
    color: #1a1a1a;
}

.table-header .date-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #fff8e1;
    border: 1px solid #ffcc00;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    color: #333;
}

.count-badge {
    background: #ffcc00;
    color: #000;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 700;
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
}

th, td {
    padding: 12px 16px;
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
    position: sticky;
    top: 0;
}

tr:hover td {
    background: #fffef5;
}

td.wrap-text {
    white-space: normal;
    max-width: 200px;
    word-break: break-word;
}

.profile-thumb {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    object-fit: cover;
    border: 2px solid #f0f0f0;
    cursor: pointer;
    transition: transform 0.2s;
}

.profile-thumb:hover {
    transform: scale(1.5);
    border-color: #ffcc00;
    z-index: 10;
    position: relative;
}

.no-image {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ccc;
    font-size: 20px;
}

.time-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
}

.time-in  { background: #e8f5e9; color: #2e7d32; }
.time-out { background: #fce4ec; color: #c62828; }

.no-data {
    text-align: center;
    padding: 60px 20px;
    color: #aaa;
}

.no-data .no-data-icon {
    font-size: 48px;
    margin-bottom: 12px;
}

.no-data p {
    font-size: 16px;
}

.no-data .hint {
    font-size: 13px;
    color: #ccc;
    margin-top: 4px;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
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

    .main-content {
        padding: 16px;
    }

    .page-title {
        font-size: 22px;
    }

    .stats-row {
        grid-template-columns: 1fr;
    }

    .stat-card {
        padding: 16px;
    }

    .stat-icon {
        width: 44px;
        height: 44px;
        font-size: 20px;
    }

    .stat-info h3 {
        font-size: 22px;
    }

    .calendar-body {
        padding: 12px 8px;
    }

    .cal-day {
        font-size: 13px;
    }

    .cal-badge {
        font-size: 9px;
        padding: 0px 4px;
    }

    .table-header {
        padding: 16px;
    }

    th, td {
        padding: 10px 12px;
        font-size: 13px;
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

<!-- ===== HEADER ===== -->
<div class="top-header">
    <div class="logo">
        <img src="logo.png" alt="Logo">
    </div>
    <div class="header-right">
        <a href="admin.php" class="nav-link active">📊 <span class="desktop-text">Dashboard</span></a>
        <a href="logs.php" class="nav-link">📋 <span class="desktop-text">Logs</span></a>
        <a href="?logout" class="logout-btn">ออกจากระบบ</a>
    </div>
</div>

<!-- ===== MAIN CONTENT ===== -->
<div class="main-content">
    <h1 class="page-title">📊 ข้อมูลผู้ลงทะเบียน</h1>
    <p class="page-subtitle">ดูข้อมูลการลงทะเบียนเข้า-ออกจากฐานข้อมูล แบ่งตามวันที่</p>

    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon blue">📝</div>
            <div class="stat-info">
                <h3><?php echo $totalForDate; ?></h3>
                <p>จำนวนการเข้าตึก <?php echo $selectedDateThai; ?><?php if ($selectedDate === date('Y-m-d')) echo ' <strong>(วันนี้❗️)</strong>'; ?></p>
            </div>
        </div>
        <a href="?month=<?php echo $currentMonth; ?>&year=<?php echo $currentYear; ?>&view=month" class="stat-card <?php echo $viewMode === 'month' ? 'active-view' : ''; ?>">
            <div class="stat-icon yellow">🗓️</div>
            <div class="stat-info">
                <h3><?php echo $totalMonth; ?></h3>
                <p>จำนวนการเข้าตึกเดือน <?php echo $thaiMonths[$currentMonth] . ' ' . $yearBE; ?></p>
            </div>
        </a>
        <a href="?month=<?php echo $currentMonth; ?>&year=<?php echo $currentYear; ?>&view=all" class="stat-card <?php echo $viewMode === 'all' ? 'active-view' : ''; ?>">
            <div class="stat-icon green">👥</div>
            <div class="stat-info">
                <h3><?php echo $totalAll; ?></h3>
                <p>จำนวนผู้คนลงทะเบียนทั้งหมด</p>
            </div>
        </a>
    </div>

    <?php if ($viewMode === 'person' && isset($personInfo)): ?>
    <!-- ===== PERSON HISTORY VIEW ===== -->
    <?php
    $backView = isset($personFrom) ? $personFrom : 'all';
    $backUrl = '?month=' . $currentMonth . '&year=' . $currentYear . '&view=' . $backView;
    ?>
    <a href="<?php echo $backUrl; ?>" class="back-link">← กลับไปรายชื่อ</a>

    <div class="person-header">
        <?php if (!empty($personInfo['profile_image']) && file_exists($personInfo['profile_image'])): ?>
            <img src="<?php echo htmlspecialchars($personInfo['profile_image']); ?>" alt="รูป" class="person-avatar">
        <?php else: ?>
            <div class="person-avatar-placeholder">👤</div>
        <?php endif; ?>
        <div class="person-details">
            <h2><?php echo htmlspecialchars($personInfo['fullname']); ?></h2>
            <div class="person-meta">
                <span>🪪 ID : <?php echo htmlspecialchars($personInfo['idcard']); ?></span>
                <span>📞 Phone : <?php echo htmlspecialchars($latestPhone); ?></span>
            </div>
        </div>
        <div class="history-count">
            <h3><?php echo count($personRows); ?></h3>
            <p>ครั้งที่เข้ามา</p>
        </div>
    </div>

    <div class="table-card">
        <div class="table-header">
            <h2>🗓 ประวัติการเข้า-ออก</h2>
            <span class="count-badge"><?php echo count($personRows); ?> รายการ</span>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>วันที่</th>
                        <th>หน่วยงาน</th>
                        <th>รายละเอียดงาน</th>
                        <th>เวลาเข้า</th>
                        <th>เวลาออก</th>
                        <th>เบอร์ผู้ติดต่อ</th>
                        <th>รูปถ่าย</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $num = 1;
                    foreach ($personRows as $row):
                    ?>
                    <tr>
                        <td><?php echo $num++; ?></td>
                        <td><strong><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['department']); ?></td>
                        <td class="wrap-text"><?php echo htmlspecialchars($row['detail']); ?></td>
                        <td><span class="time-badge time-in"><?php echo htmlspecialchars($row['timein']); ?></span></td>
                        <td><span class="time-badge time-out"><?php echo htmlspecialchars($row['timeout']); ?></span></td>
                        <td><?php echo htmlspecialchars($row['contact']); ?></td>
                        <td>
                            <?php if (!empty($row['profile_image']) && file_exists($row['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($row['profile_image']); ?>" alt="รูป" class="profile-thumb">
                            <?php else: ?>
                                <div class="no-image">👤</div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php elseif ($viewMode === 'month' || $viewMode === 'all'): ?>
    <!-- ===== UNIQUE REGISTRATIONS LIST VIEW (simplified) ===== -->
    <a href="?month=<?php echo $currentMonth; ?>&year=<?php echo $currentYear; ?>&date=<?php echo $selectedDate; ?>" class="back-link">← กลับไปหน้าปฏิทิน</a>

    <div class="table-card">
        <div class="table-header">
            <h2>📋 <?php echo $viewTitle; ?></h2>
            <span class="count-badge"><?php echo $viewRegistrations->num_rows; ?> คน</span>
        </div>

        <?php if ($viewRegistrations->num_rows > 0): ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>รูปถ่าย</th>
                        <th>ชื่อ-สกุล</th>
                        <th>เลขบัตรประชาชน</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $num = 1;
                    $currentView = $viewMode;
                    while ($row = $viewRegistrations->fetch_assoc()):
                    $personUrl = '?month=' . $currentMonth . '&year=' . $currentYear . '&view=person&idcard=' . urlencode($row['idcard']) . '&from=' . $currentView;
                    ?>
                    <tr class="clickable-row" onclick="window.location='<?php echo $personUrl; ?>'">
                        <td><?php echo $num++; ?></td>
                        <td>
                            <?php if (!empty($row['profile_image']) && file_exists($row['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($row['profile_image']); ?>" alt="รูป" class="profile-thumb">
                            <?php else: ?>
                                <div class="no-image">👤</div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($row['fullname']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['idcard']); ?></td>
                        <td><span class="row-arrow">›</span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="no-data">
            <div class="no-data-icon">📭</div>
            <p>ไม่มีข้อมูลการลงทะเบียน</p>
        </div>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- ===== CALENDAR VIEW (DEFAULT) ===== -->
    <div class="dashboard-grid">
        <!-- Calendar -->
        <div class="calendar-card">
            <div class="calendar-header">
                <div class="month-nav">
                    <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>&date=<?php echo $selectedDate; ?>">‹</a>
                </div>
                <h2><?php echo $thaiMonths[$currentMonth] . ' ' . $yearBE; ?></h2>
                <div class="month-nav">
                    <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>&date=<?php echo $selectedDate; ?>">›</a>
                </div>
            </div>
            <div class="calendar-body">
                <div class="day-names">
                    <?php foreach ($thaiDays as $d): ?>
                        <span><?php echo $d; ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="calendar-grid">
                    <?php
                    // Empty cells before first day
                    for ($i = 0; $i < $firstDayOfWeek; $i++) {
                        echo '<div class="cal-day empty"></div>';
                    }

                    // Days of month
                    for ($day = 1; $day <= $daysInMonth; $day++) {
                        $dateStr = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                        $dayOfWeek = ($firstDayOfWeek + $day - 1) % 7;
                        $isToday = ($dateStr === date('Y-m-d'));
                        $isSelected = ($dateStr === $selectedDate);
                        $count = $dayCounts[$dateStr] ?? 0;

                        $classes = 'cal-day';
                        if ($isToday) $classes .= ' today';
                        if ($isSelected) $classes .= ' selected';
                        if ($dayOfWeek === 0) $classes .= ' sunday';
                        if ($dayOfWeek === 6) $classes .= ' saturday';

                        echo '<a href="?month=' . $currentMonth . '&year=' . $currentYear . '&date=' . $dateStr . '" class="' . $classes . '">';
                        echo $day;
                        if ($count > 0) {
                            echo '<span class="cal-badge">' . $count . '</span>';
                        }
                        echo '</a>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Registrations Table -->
        <div class="table-card">
            <div class="table-header">
                <h2>📋 รายชื่อผู้ลงทะเบียน</h2>
                <div>
                    <span class="date-badge">📅 <?php echo $selectedDateThai; ?></span>
                    <span class="count-badge"><?php echo $totalForDate; ?> คน</span>
                </div>
            </div>

            <?php if ($totalForDate > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>รูปถ่าย</th>
                            <th>ชื่อ-สกุล</th>
                            <th>เบอร์โทร</th>
                            <th>เลขบัตรประชาชน</th>
                            <th>หน่วยงาน</th>
                            <th>รายละเอียด</th>
                            <th>เวลาเข้า</th>
                            <th>เวลาออก</th>
                            <th>เบอร์ผู้ติดต่อ</th>
                            <th>เวลาบันทึก</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $num = 1;
                        while ($row = $registrations->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $num++; ?></td>
                            <td>
                                <?php if (!empty($row['profile_image']) && file_exists($row['profile_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($row['profile_image']); ?>" alt="รูป" class="profile-thumb">
                                <?php else: ?>
                                    <div class="no-image">👤</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($row['fullname']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td><?php $ic = $row['idcard']; echo str_repeat('X', max(0, strlen($ic)-4)) . substr($ic, -4); ?></td>
                            <td><?php echo htmlspecialchars($row['department']); ?></td>
                            <td class="wrap-text"><?php echo htmlspecialchars($row['detail']); ?></td>
                            <td><span class="time-badge time-in"><?php echo htmlspecialchars($row['timein']); ?></span></td>
                            <td><span class="time-badge time-out"><?php echo htmlspecialchars($row['timeout']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['contact']); ?></td>
                            <td><?php echo date('H:i:s', strtotime($row['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="no-data">
                <div class="no-data-icon">📭</div>
                <p>ไม่มีข้อมูลการลงทะเบียนในวันนี้</p>
                <p class="hint">เลือกวันที่อื่นจากปฏิทินด้านซ้าย</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== MONTHLY CHART ===== -->
    <div class="chart-card">
        <div class="chart-title">📊 สถิติผู้เข้าใช้รายเดือน ปี <?php echo $chartYear + 543; ?></div>
        <div class="chart-container">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>

    <?php endif; ?>
</div>

<script>
<?php if ($viewMode === 'calendar'): ?>
// Monthly registration chart
const ctx = document.getElementById('monthlyChart');
if (ctx) {
    const monthLabels = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    const data = <?php echo $chartDataJson; ?>;
    const currentMonthIndex = <?php echo $currentMonth - 1; ?>;

    const bgColors = data.map((_, i) => i === currentMonthIndex ? '#ffcc00' : 'rgba(255, 204, 0, 0.3)');
    const borderColors = data.map((_, i) => i === currentMonthIndex ? '#e6b800' : 'rgba(255, 204, 0, 0.6)');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: monthLabels,
            datasets: [{
                label: 'จำนวนผู้ลงทะเบียน (ไม่ซ้ำ)',
                data: data,
                backgroundColor: bgColors,
                borderColor: borderColors,
                borderWidth: 2,
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#000',
                    titleFont: { family: 'Sarabun', size: 14 },
                    bodyFont: { family: 'Sarabun', size: 13 },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        title: function(items) {
                            const fullMonths = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
                            return fullMonths[items[0].dataIndex] + ' <?php echo $chartYear + 543; ?>';
                        },
                        label: function(item) {
                            return ' ' + item.raw + ' คน';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: { family: 'Sarabun', size: 12 },
                        color: '#999',
                        stepSize: 1,
                        precision: 0
                    },
                    grid: { color: 'rgba(0,0,0,0.04)' }
                },
                x: {
                    ticks: {
                        font: { family: 'Sarabun', size: 12, weight: '500' },
                        color: '#666'
                    },
                    grid: { display: false }
                }
            }
        }
    });
}
<?php endif; ?>
</script>

</body>
</html>
<?php
$regStmt->close();
if (isset($viewStmt)) $viewStmt->close();
if (isset($personStmt)) $personStmt->close();
$conn->close();
?>
