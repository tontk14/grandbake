<?php
session_start();
require_once "db.php";

// ตั้งค่ารหัสผ่านเข้าหลังบ้านแอดมิน (เปลี่ยนได้ตามต้องการ)
define('ADMIN_PASSWORD', 'admin1234');

// ตรวจสอบการ Login ของแอดมิน
$loginError = "";
if (isset($_POST["admin_login"])) {
    $enteredPassword = $_POST["password"] ?? "";
    if ($enteredPassword === ADMIN_PASSWORD) {
        $_SESSION["admin_logged"] = true;
        header("Location: admin_orders.php");
        exit;
    } else {
        $loginError = "รหัสผ่านแอดมินไม่ถูกต้อง";
    }
}

// ตรวจสอบการ Logout
if (isset($_GET["action"]) && $_GET["action"] === "logout") {
    unset($_SESSION["admin_logged"]);
    header("Location: admin_orders.php");
    exit;
}

$isAdmin = !empty($_SESSION["admin_logged"]);

// ตรวจสอบและสร้างตาราง orders / order_items หากยังไม่มี
$conn->query("
    CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_number VARCHAR(50) NOT NULL UNIQUE,
        user_id INT NULL,
        customer_name VARCHAR(150) NOT NULL,
        customer_email VARCHAR(150) NOT NULL,
        customer_phone VARCHAR(50) NOT NULL,
        customer_address TEXT NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        total_items INT NOT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ตรวจสอบและเพิ่มคอลัมน์ user_id ใน orders หากยังไม่มี
$colCheck = $conn->query("SHOW COLUMNS FROM orders LIKE 'user_id'");
if ($colCheck && $colCheck->num_rows === 0) {
    @$conn->query("ALTER TABLE orders ADD COLUMN user_id INT NULL AFTER order_number");
}
@$conn->query("ALTER TABLE orders MODIFY customer_email VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL");

$conn->query("
    CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NULL,
        product_name VARCHAR(150) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        quantity INT NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        image VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// จัดการ AJAX อัปเดตสถานะคำสั่งซื้อ
if ($isAdmin && $_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_status"])) {
    header('Content-Type: application/json; charset=utf-8');
    $orderId = (int)($_POST["order_id"] ?? 0);
    $newStatus = trim($_POST["status"] ?? "");

    $allowedStatus = ['pending', 'confirmed', 'preparing', 'delivering', 'completed', 'cancelled'];
    if ($orderId > 0 && in_array($newStatus, $allowedStatus)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $orderId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'อัปเดตสถานะสำเร็จ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ไม่สามารถอัปเดตสถานะได้']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
    }
    exit;
}

// จัดการลบคำสั่งซื้อ (Delete Order)
if ($isAdmin && isset($_GET["action"]) && $_GET["action"] === "delete" && isset($_GET["id"])) {
    $delId = (int)$_GET["id"];
    if ($delId > 0) {
        $conn->query("DELETE FROM order_items WHERE order_id = $delId");
        $conn->query("DELETE FROM orders WHERE id = $delId");
        header("Location: admin_orders.php?msg=deleted");
        exit;
    }
}

// ดึงข้อมูลสำหรับหน้า Dashboard เมื่อเข้าสู่ระบบแล้ว
$orders = [];
$totalRevenue = 0;
$countTotal = 0;
$countPending = 0;
$countPreparing = 0;
$countDelivering = 0;
$countCompleted = 0;

if ($isAdmin) {
    // ฟิลเตอร์การค้นหาและสถานะ
    $search = trim($_GET["search"] ?? "");
    $filterStatus = trim($_GET["status"] ?? "");

    $query = "SELECT * FROM orders WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $query .= " AND (order_number LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ? OR customer_email LIKE ?)";
        $searchParam = "%" . $search . "%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ssss";
    }

    if (!empty($filterStatus) && $filterStatus !== "all") {
        $query .= " AND status = ?";
        $params[] = $filterStatus;
        $types .= "s";
    }

    $query .= " ORDER BY id DESC";

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // ดึง items ของทุกออเดอร์
    $orderIds = array_column($orders, "id");
    $itemsByOrderId = [];
    if (!empty($orderIds)) {
        $placeholders = implode(",", array_fill(0, count($orderIds), "?"));
        $typesOrder = str_repeat("i", count($orderIds));
        $itemStmt = $conn->prepare("SELECT * FROM order_items WHERE order_id IN ($placeholders) ORDER BY id ASC");
        $itemStmt->bind_param($typesOrder, ...$orderIds);
        $itemStmt->execute();
        $res = $itemStmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $itemsByOrderId[$row["order_id"]][] = $row;
        }
        $itemStmt->close();
    }

    // คำนวณตัวเลขสถิติรวมทั้งหมด (ทุกออเดอร์)
    $statRes = $conn->query("SELECT status, total_amount FROM orders");
    while ($row = $statRes->fetch_assoc()) {
        $countTotal++;
        $st = strtolower($row["status"] ?? "pending");
        if ($st !== 'cancelled') {
            $totalRevenue += (float)$row["total_amount"];
        }
        if ($st === 'pending' || $st === 'confirmed') $countPending++;
        elseif ($st === 'preparing') $countPreparing++;
        elseif ($st === 'delivering') $countDelivering++;
        elseif ($st === 'completed') $countCompleted++;
    }
}

// ฟังก์ชันแปลงวันที่ภาษาไทย
function formatThaiDate($datetime) {
    if (!$datetime) return "-";
    $time = strtotime($datetime);
    $thai_months = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
        5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
        9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
    ];
    $day = date('j', $time);
    $month = $thai_months[(int)date('n', $time)] ?? '';
    $year = date('Y', $time) + 543;
    $clock = date('H:i', $time);
    return "$day $month $year • $clock น.";
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>ระบบจัดการคำสั่งซื้อ (Admin) | Grand Bake</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Thai:wght@500;600;700&family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --brand-primary: #774932;
            --brand-primary-hover: #5d3622;
            --brand-dark: #4b3629;
            --brand-title: #513322;
            --bg-page: #fbf7f3;
            --bg-card: #ffffff;
            --bg-subtle: #faf6f2;
            --border-color: #ebdcd0;
            --border-subtle: #eee4dc;
            --text-muted: #8c7669;
            --radius-card: 20px;
            --radius-btn: 12px;
            --safe-bottom: env(safe-area-inset-bottom, 0px);
            --safe-top: env(safe-area-inset-top, 0px);
        }

        body {
            font-family: "Prompt", sans-serif;
            background: var(--bg-page);
            color: var(--brand-dark);
            min-height: 100vh;
            padding-bottom: max(32px, calc(var(--safe-bottom) + 20px));
            -webkit-tap-highlight-color: transparent;
        }

        a { text-decoration: none; color: inherit; }
        button, input, select { font-family: inherit; }

        /* HEADER / NAVBAR */
        .admin-nav {
            background: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            padding: 10px max(5%, 16px);
            min-height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 12px rgba(80, 50, 30, 0.04);
            gap: 12px;
        }
        .admin-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }
        .admin-logo img {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }
        .admin-logo-text {
            min-width: 0;
        }
        .admin-logo-text h1 {
            font-family: "Noto Serif Thai", serif;
            font-size: 18px;
            color: var(--brand-title);
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .admin-logo-text span {
            font-size: 11px;
            letter-spacing: 2px;
            color: #9c775d;
            font-weight: 600;
            display: block;
        }
        /* NAVIGATION TABS */
        .admin-nav-tabs {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #f7efe7;
            padding: 5px;
            border-radius: 25px;
            border: 1px solid #ebdcd0;
        }
        .nav-tab {
            padding: 7px 18px;
            border-radius: 20px;
            font-size: 13.5px;
            font-weight: 500;
            color: #724c36;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .nav-tab:hover {
            color: #553422;
            background: rgba(255, 255, 255, 0.6);
        }
        .nav-tab.active {
            background: #774932;
            color: white;
            box-shadow: 0 3px 10px rgba(119, 73, 50, 0.25);
            font-weight: 600;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        .btn-view-store {
            font-size: 13.5px;
            color: #795540;
            padding: 8px 16px;
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            border: 1px solid #dfcfc2;
            transition: 0.2s;
            touch-action: manipulation;
        }
        .btn-view-store:hover {
            background: #fbf4ee;
            border-color: #cbbaa9;
        }
        .btn-logout {
            font-size: 13.5px;
            color: #a43a3a;
            padding: 8px 16px;
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            background: #fdf0f0;
            border: 1px solid #f6cfcf;
            transition: 0.2s;
            touch-action: manipulation;
        }
        .btn-logout:hover {
            background: #f9dede;
        }
        .btn-view-store:active, .btn-logout:active {
            transform: scale(0.97);
        }

        /* LOGIN FORM CARD */
        .login-wrap {
            max-width: 440px;
            margin: 60px auto 40px;
            padding: 0 20px;
        }
        .login-card {
            background: white;
            border-radius: 24px;
            padding: 36px 28px;
            border: 1px solid var(--border-color);
            box-shadow: 0 16px 45px rgba(80, 50, 30, 0.08);
            text-align: center;
        }
        .login-icon {
            font-size: 48px;
            margin-bottom: 12px;
            display: block;
        }
        .login-card h2 {
            font-family: "Noto Serif Thai", serif;
            font-size: 22px;
            color: var(--brand-title);
            margin-bottom: 6px;
        }
        .login-card p {
            font-size: 13.5px;
            color: var(--text-muted);
            margin-bottom: 24px;
        }
        .input-group {
            margin-bottom: 18px;
            text-align: left;
        }
        .input-group label {
            display: block;
            font-size: 13px;
            color: #694d3c;
            margin-bottom: 6px;
            font-weight: 500;
        }
        .admin-input {
            width: 100%;
            padding: 12px 16px;
            min-height: 48px;
            border-radius: 12px;
            border: 1px solid #dccdc0;
            font-size: 15px;
            outline: none;
            transition: 0.2s;
            background: #fcf9f6;
        }
        .admin-input:focus {
            border-color: #8c5d42;
            background: white;
            box-shadow: 0 0 0 3px rgba(140, 93, 66, 0.12);
        }
        .btn-submit-login {
            width: 100%;
            min-height: 48px;
            padding: 13px;
            border-radius: 14px;
            background: var(--brand-primary);
            color: white;
            border: none;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
            box-shadow: 0 6px 18px rgba(119, 73, 50, 0.2);
            touch-action: manipulation;
        }
        .btn-submit-login:hover {
            background: var(--brand-primary-hover);
        }
        .btn-submit-login:active {
            transform: scale(0.98);
        }
        .error-alert {
            background: #fdeeee;
            border: 1px solid #f8c8c8;
            color: #a72727;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 18px;
        }

        /* MAIN CONTAINER */
        .container {
            max-width: 1280px;
            margin: 30px auto 60px;
            padding: 0 20px;
        }

        /* STATS CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            border-radius: 18px;
            padding: 20px;
            border: 1px solid var(--border-color);
            box-shadow: 0 6px 20px rgba(70, 50, 30, 0.04);
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }
        .stat-icon-1 { background: #fdf3e7; color: #b26829; }
        .stat-icon-2 { background: #eef7ff; color: #1e88e5; }
        .stat-icon-3 { background: #fbf0ff; color: #8e24aa; }
        .stat-icon-4 { background: #eefbee; color: #2e7d32; }

        .stat-info {
            min-width: 0;
        }
        .stat-info h3 {
            font-size: 21px;
            font-weight: 700;
            color: #4b3223;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .stat-info span {
            font-size: 12.5px;
            color: var(--text-muted);
            white-space: nowrap;
        }

        /* FILTER & TOOLBAR */
        .toolbar-card {
            background: white;
            border-radius: 18px;
            padding: 18px 20px;
            border: 1px solid var(--border-color);
            box-shadow: 0 6px 20px rgba(70, 50, 30, 0.04);
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }
        .filter-form {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            flex: 1;
        }
        .search-box {
            position: relative;
            min-width: 260px;
            flex: 1;
        }
        .search-box input {
            width: 100%;
            padding: 11px 16px 11px 38px;
            min-height: 44px;
            border-radius: 12px;
            border: 1px solid #dccdc0;
            background: #faf6f2;
            font-size: 14.5px;
            outline: none;
            transition: 0.2s;
        }
        .search-box input:focus {
            background: white;
            border-color: #8c5d42;
        }
        .search-box-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9c8374;
            font-size: 15px;
            pointer-events: none;
        }
        .status-select {
            padding: 10px 16px;
            min-height: 44px;
            border-radius: 12px;
            border: 1px solid #dccdc0;
            background: #faf6f2;
            color: #553b2c;
            font-size: 14px;
            cursor: pointer;
            outline: none;
        }
        .btn-filter {
            padding: 10px 20px;
            min-height: 44px;
            border-radius: 12px;
            background: var(--brand-primary);
            color: white;
            border: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            touch-action: manipulation;
        }
        .btn-filter:hover {
            background: var(--brand-primary-hover);
        }
        .btn-reset {
            padding: 10px 16px;
            min-height: 44px;
            border-radius: 12px;
            background: #f1e7df;
            color: #724c36;
            font-size: 13.5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
            transition: 0.2s;
            touch-action: manipulation;
        }
        .btn-reset:hover {
            background: #e6dacd;
        }
        .btn-filter:active, .btn-reset:active {
            transform: scale(0.97);
        }

        /* ORDERS LIST / CARDS */
        .orders-table-wrap {
            background: white;
            border-radius: 20px;
            border: 1px solid var(--border-color);
            box-shadow: 0 8px 24px rgba(70, 50, 30, 0.05);
            overflow: hidden;
        }
        .table-header {
            padding: 18px 22px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .table-header h2 {
            font-family: "Noto Serif Thai", serif;
            font-size: 19px;
            color: #4d3324;
        }
        .table-header span {
            font-size: 13px;
            color: var(--text-muted);
        }

        .order-row-item {
            padding: 22px;
            border-bottom: 1px solid #f0e6dd;
            display: flex;
            flex-direction: column;
            gap: 16px;
            transition: background 0.2s;
        }
        .order-row-item:last-child {
            border-bottom: none;
        }
        .order-row-item:hover {
            background: #fdfbf9;
        }

        .row-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .order-id-badge {
            font-weight: 700;
            font-size: 16px;
            color: #633924;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        .order-time {
            font-size: 13px;
            color: #968376;
            font-weight: normal;
        }

        /* STATUS BADGES & SELECT */
        .status-changer {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .status-dropdown {
            padding: 8px 14px;
            min-height: 38px;
            border-radius: 20px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            outline: none;
            border: 1px solid transparent;
            transition: 0.2s;
            touch-action: manipulation;
        }
        .st-confirmed { background: #e8f4fd; color: #1565c0; border-color: #bedef7; }
        .st-pending   { background: #fff9e6; color: #b78103; border-color: #f7e6a7; }
        .st-preparing { background: #fff4e5; color: #e65100; border-color: #ffdcb3; }
        .st-delivering{ background: #f3e8fd; color: #6a1b9a; border-color: #e2c1fa; }
        .st-completed { background: #e8f8f0; color: #2e7d32; border-color: #b9e9cb; }
        .st-cancelled { background: #fdeeed; color: #c62828; border-color: #f7c5c2; }

        /* DETAILS GRID */
        .row-body {
            display: grid;
            grid-template-columns: 1.15fr 1.25fr 0.9fr;
            gap: 18px;
            background: var(--bg-subtle);
            padding: 16px 18px;
            border-radius: 16px;
            border: 1px solid var(--border-subtle);
        }

        .cust-info h4, .items-info h4, .payment-info h4 {
            font-size: 12px;
            color: #927b6f;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .cust-info p {
            font-size: 13.5px;
            color: #4b3427;
            line-height: 1.6;
        }
        .cust-phone-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            color: #794a32;
            padding: 2px 6px;
            margin: 2px 0;
            background: #f4eae1;
            border-radius: 6px;
            transition: 0.2s;
        }
        .cust-phone-link:hover {
            background: #ebdcd0;
        }
        .phone-call-badge {
            font-size: 11px;
            background: #794a32;
            color: #fff;
            padding: 1px 6px;
            border-radius: 10px;
            font-weight: normal;
        }

        .items-mini-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .item-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
            color: #4e3526;
            gap: 8px;
        }
        .item-line span:first-child {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .item-line span:last-child {
            font-weight: 600;
            flex-shrink: 0;
        }

        .payment-info .pay-amount {
            font-size: 22px;
            font-weight: 700;
            color: #814629;
            margin-top: 2px;
        }
        .payment-info .pay-method {
            font-size: 12.5px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* ACTIONS */
        .row-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 2px;
            flex-wrap: wrap;
        }
        .btn-act {
            padding: 8px 16px;
            min-height: 40px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            touch-action: manipulation;
        }
        .btn-act-receipt {
            background: #fff;
            border: 1px solid #d9c8b8;
            color: #6d4b35;
        }
        .btn-act-receipt:hover {
            background: #f7ece2;
        }
        .btn-act-print {
            background: #734832;
            border: 1px solid #734832;
            color: white;
        }
        .btn-act-print:hover {
            background: #5a3522;
        }
        .btn-act-del {
            background: #fff;
            border: 1px solid #f2c7c7;
            color: #ba2828;
        }
        .btn-act-del:hover {
            background: #fdeeed;
        }
        .btn-act:active {
            transform: scale(0.97);
        }

        /* EMPTY STATE */
        .empty-box {
            padding: 60px 20px;
            text-align: center;
            color: var(--text-muted);
        }
        .empty-box span {
            font-size: 50px;
            display: block;
            margin-bottom: 12px;
        }

        /* RESPONSIVE ADAPTATION (TABLET & MOBILE) */
        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            .row-body {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .toolbar-card {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-form .search-box,
            .filter-form .status-select,
            .filter-form .btn-filter,
            .filter-form .btn-reset {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .admin-nav {
                flex-wrap: wrap;
                padding: 10px 16px;
            }
            .admin-nav-tabs {
                order: 3;
                width: 100%;
                margin-top: 8px;
            }
            .nav-tab {
                flex: 1;
                justify-content: center;
                text-align: center;
                padding: 9px 8px;
                min-height: 42px;
            }
        }

        @media (max-width: 680px) {
            .admin-nav {
                min-height: 62px;
            }
            .admin-logo img {
                width: 38px;
                height: 38px;
            }
            .admin-logo-text h1 {
                font-size: 15px;
            }
            .admin-logo-text span {
                font-size: 9.5px;
                letter-spacing: 1px;
            }
            .btn-view-store, .btn-logout {
                padding: 6px 12px;
                font-size: 12.5px;
                min-height: 36px;
            }

            .container {
                margin: 16px auto 40px;
                padding: 0 14px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                margin-bottom: 16px;
            }
            .stat-card {
                padding: 14px 12px;
                border-radius: 14px;
                gap: 10px;
            }
            .stat-icon {
                width: 42px;
                height: 42px;
                font-size: 20px;
                border-radius: 12px;
            }
            .stat-info h3 {
                font-size: 18px;
            }
            .stat-info span {
                font-size: 11px;
            }

            .toolbar-card {
                padding: 14px;
                border-radius: 16px;
                margin-bottom: 16px;
            }
            .table-header {
                padding: 14px 16px;
            }
            .table-header h2 {
                font-size: 17px;
            }

            .order-row-item {
                padding: 16px 14px;
                gap: 14px;
            }
            .row-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .order-id-badge {
                font-size: 15px;
            }
            .status-changer {
                width: 100%;
                justify-content: space-between;
                background: #f7ede3;
                padding: 6px 12px;
                border-radius: 12px;
            }
            .status-dropdown {
                min-height: 44px;
                font-size: 14px;
                flex: 1;
                max-width: 220px;
                text-align: center;
            }

            .row-body {
                padding: 12px 14px;
                border-radius: 14px;
            }
            .payment-info .pay-amount {
                font-size: 20px;
            }

            /* Touch-ergonomic action bar on mobile */
            .row-actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
                width: 100%;
            }
            .btn-act {
                min-height: 44px;
                padding: 10px 12px;
                font-size: 13px;
                width: 100%;
            }
            /* Make print slip and receipt span full or half cleanly */
            .row-actions a, .row-actions button {
                width: 100%;
            }
        }

        /* Prevent auto-zoom on iOS inputs and ensure comfortable touch targets */
        @media (pointer: coarse) {
            .admin-input, .search-box input, .status-select, .status-dropdown {
                font-size: 16px;
            }
            .btn-act, .btn-filter, .btn-reset, .btn-view-store, .btn-logout, .btn-submit-login {
                min-height: 44px;
            }
        }

        /* PRINT STYLES */
        @media print {
            .admin-nav, .stats-grid, .toolbar-card, .row-actions, .status-changer {
                display: none !important;
            }
            body { background: white !important; padding: 0 !important; }
            .container { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
            .order-row-item { page-break-inside: avoid; border: 1px solid #ccc !important; margin-bottom: 20px !important; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header class="admin-nav">
        <div class="admin-logo">
            <img src="logo.jpg" alt="Grand Bake Logo">
            <div class="admin-logo-text">
                <h1>ระบบหลังบ้าน Grand Bake</h1>
                <span>ADMIN DASHBOARD</span>
            </div>
        </div>

        <?php if ($isAdmin): ?>
            <!-- ADMIN NAVIGATION TABS -->
            <nav class="admin-nav-tabs" aria-label="เมนูระบบหลังบ้าน">
                <a href="admin_orders.php" class="nav-tab active">📦 จัดการคำสั่งซื้อ</a>
                <a href="admin_users.php" class="nav-tab">👥 จัดการสมาชิกลูกค้า</a>
            </nav>
        <?php endif; ?>

        <div class="nav-actions">
            <a href="index.php" class="btn-view-store" target="_blank">🌐 เปิดดูหน้าร้าน</a>
            <?php if ($isAdmin): ?>
                <a href="admin_orders.php?action=logout" class="btn-logout">🚪 ออกจากระบบ</a>
            <?php endif; ?>
        </div>
    </header>

    <?php if (!$isAdmin): ?>
        <!-- LOGIN CARD -->
        <main class="login-wrap">
            <div class="login-card">
                <span class="login-icon">🔐</span>
                <h2>เข้าสู่ระบบผู้ดูแลร้าน</h2>
                <p>กรุณากรอกรหัสผ่านเพื่อเข้าจัดการออเดอร์และดูสถิติ</p>

                <?php if (!empty($loginError)): ?>
                    <div class="error-alert">
                        ⚠️ <?= htmlspecialchars($loginError) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="admin_orders.php">
                    <input type="hidden" name="admin_login" value="1">
                    <div class="input-group">
                        <label for="password">รหัสผ่านแอดมิน (Admin Password)</label>
                        <input type="password" id="password" name="password" class="admin-input" placeholder="ใส่รหัสผ่านแอดมิน..." required autofocus>
                    </div>
                    <button type="submit" class="btn-submit-login">
                        เข้าสู่ระบบหลังบ้าน ➔
                    </button>
                </form>
                <div style="margin-top: 18px; font-size: 12px; color: #9c8374;">
                    (รหัสเริ่มต้น: <strong>admin1234</strong>)
                </div>
            </div>
        </main>
    <?php else: ?>
        <!-- DASHBOARD -->
        <main class="container">

            <!-- STATS -->
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-1">💰</div>
                    <div class="stat-info">
                        <h3>฿<?= number_format($totalRevenue) ?></h3>
                        <span>ยอดขายรวมสุทธิ</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-icon-2">📦</div>
                    <div class="stat-info">
                        <h3><?= number_format($countTotal) ?> ออเดอร์</h3>
                        <span>คำสั่งซื้อทั้งหมด</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-icon-3">🧁</div>
                    <div class="stat-info">
                        <h3><?= number_format($countPending + $countPreparing) ?></h3>
                        <span>กำลังจัดเตรียม / อบเค้ก</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-icon-4">🎉</div>
                    <div class="stat-info">
                        <h3><?= number_format($countCompleted) ?></h3>
                        <span>จัดส่งสำเร็จแล้ว</span>
                    </div>
                </div>
            </section>

            <!-- TOOLBAR / FILTER -->
            <section class="toolbar-card">
                <form method="GET" action="admin_orders.php" class="filter-form">
                    <div class="search-box">
                        <span class="search-box-icon">🔍</span>
                        <input type="text" name="search" placeholder="ค้นหาเลขที่ออเดอร์, ชื่อลูกค้า, เบอร์โทร..." value="<?= htmlspecialchars($_GET["search"] ?? "") ?>">
                    </div>

                    <select name="status" class="status-select">
                        <option value="all">สถานะทั้งหมด</option>
                        <option value="confirmed" <?= (($_GET["status"] ?? "") === "confirmed") ? "selected" : "" ?>>✓ ยืนยันแล้ว</option>
                        <option value="pending" <?= (($_GET["status"] ?? "") === "pending") ? "selected" : "" ?>>⏳ รอดำเนินการ</option>
                        <option value="preparing" <?= (($_GET["status"] ?? "") === "preparing") ? "selected" : "" ?>>🧁 กำลังเตรียมเค้ก</option>
                        <option value="delivering" <?= (($_GET["status"] ?? "") === "delivering") ? "selected" : "" ?>>🛵 กำลังจัดส่ง</option>
                        <option value="completed" <?= (($_GET["status"] ?? "") === "completed") ? "selected" : "" ?>>🎉 จัดส่งสำเร็จ</option>
                        <option value="cancelled" <?= (($_GET["status"] ?? "") === "cancelled") ? "selected" : "" ?>>✕ ยกเลิกแล้ว</option>
                    </select>

                    <button type="submit" class="btn-filter">กรองข้อมูล</button>
                    <?php if (!empty($_GET["search"]) || !empty($_GET["status"])): ?>
                        <a href="admin_orders.php" class="btn-reset">ล้างตัวกรอง</a>
                    <?php endif; ?>
                </form>

                <div>
                    <button type="button" onclick="window.print()" class="btn-reset" style="cursor: pointer;">
                        🖨️ พิมพ์รายการออเดอร์
                    </button>
                </div>
            </section>

            <!-- ORDERS LIST -->
            <section class="orders-table-wrap">
                <div class="table-header">
                    <h2>รายการคำสั่งซื้อทั้งหมด</h2>
                    <span>พบทั้งหมด <?= count($orders) ?> รายการ</span>
                </div>

                <?php if (empty($orders)): ?>
                    <div class="empty-box">
                        <span>🧁</span>
                        <h3>ไม่พบรายการคำสั่งซื้อตามที่ค้นหา</h3>
                        <p>ไม่มีคำสั่งซื้อที่ตรงกับเงื่อนไขในขณะนี้</p>
                    </div>
                <?php else: ?>
                    <div class="orders-table">
                        <?php foreach ($orders as $ord): 
                            $ordId = $ord["id"];
                            $st = strtolower($ord["status"] ?? "pending");
                            $items = $itemsByOrderId[$ordId] ?? [];
                        ?>
                            <article class="order-row-item" id="order-row-<?= $ordId ?>">
                                <div class="row-top">
                                    <div class="order-id-badge">
                                        🧾 <?= htmlspecialchars($ord["order_number"]) ?>
                                        <span class="order-time">
                                            (<?= formatThaiDate($ord["created_at"]) ?>)
                                        </span>
                                    </div>

                                    <div class="status-changer">
                                        <label for="status-<?= $ordId ?>" style="font-size: 13px; color: #8c7669;">สถานะ:</label>
                                        <select id="status-<?= $ordId ?>" class="status-dropdown st-<?= $st ?>" onchange="updateOrderStatus(<?= $ordId ?>, this.value, this)">
                                            <option value="confirmed" <?= $st === 'confirmed' ? 'selected' : '' ?>>✓ ยืนยันแล้ว</option>
                                            <option value="pending" <?= $st === 'pending' ? 'selected' : '' ?>>⏳ รอดำเนินการ</option>
                                            <option value="preparing" <?= $st === 'preparing' ? 'selected' : '' ?>>🧁 กำลังจัดเตรียมเค้ก</option>
                                            <option value="delivering" <?= $st === 'delivering' ? 'selected' : '' ?>>🛵 กำลังจัดส่ง</option>
                                            <option value="completed" <?= $st === 'completed' ? 'selected' : '' ?>>🎉 จัดส่งสำเร็จ</option>
                                            <option value="cancelled" <?= $st === 'cancelled' ? 'selected' : '' ?>>✕ ยกเลิก</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row-body">
                                    <!-- ลูกค้า & จัดส่ง -->
                                    <div class="cust-info">
                                        <h4>ข้อมูลลูกค้า & จัดส่ง</h4>
                                        <p>
                                            <strong>👤 <?= htmlspecialchars($ord["customer_name"]) ?></strong><br>
                                            📞 <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $ord["customer_phone"])) ?>" class="cust-phone-link">
                                                <span><?= htmlspecialchars($ord["customer_phone"]) ?></span>
                                                <span class="phone-call-badge">โทรออก</span>
                                            </a><br>
                                            ✉️ <?= htmlspecialchars($ord["customer_email"]) ?><br>
                                            📍 <?= nl2br(htmlspecialchars($ord["customer_address"])) ?>
                                        </p>
                                        <?php if (!empty($ord["delivery_date"])): ?>
                                            <div style="margin-top: 8px; padding: 6px 10px; background: #fff5ee; border-radius: 8px; border: 1px solid #f3dac9; font-size: 12.5px; color: #844728;">
                                                📅 <strong>เวลานัดรับ:</strong> <?= htmlspecialchars($ord["delivery_date"]) ?> (<?= htmlspecialchars($ord["delivery_time"] ?? "-") ?>)
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($ord["cake_message"])): ?>
                                            <div style="margin-top: 6px; padding: 6px 10px; background: #fbf0ff; border-radius: 8px; border: 1px solid #edd3f7; font-size: 12.5px; color: #6a2491;">
                                                ✍️ <strong>เขียนหน้าเค้ก:</strong> "<?= htmlspecialchars($ord["cake_message"]) ?>"
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- รายการสินค้า -->
                                    <div class="items-info">
                                        <h4>รายการเค้กที่สั่ง (<?= (int)$ord["total_items"] ?> ชิ้น)</h4>
                                        <div class="items-mini-list">
                                            <?php if (!empty($items)): ?>
                                                <?php foreach ($items as $it): ?>
                                                    <div class="item-line">
                                                        <span>🍰 <?= htmlspecialchars($it["product_name"]) ?> × <?= (int)$it["quantity"] ?></span>
                                                        <span>฿<?= number_format($it["subtotal"]) ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="item-line">
                                                    <span>เค้ก Grand Bake (<?= (int)$ord["total_items"] ?> ชิ้น)</span>
                                                    <span>฿<?= number_format($ord["total_amount"]) ?></span>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($ord["discount_amount"]) && (float)$ord["discount_amount"] > 0): ?>
                                                <div class="item-line" style="color: #b73824; border-top: 1px dashed #eed5c7; padding-top: 4px; margin-top: 4px;">
                                                    <span>🎟️ ส่วนลด (<?= htmlspecialchars($ord["discount_code"] ?? "") ?>)</span>
                                                    <span>-฿<?= number_format($ord["discount_amount"]) ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- ยอดเงิน & การชำระ -->
                                    <div class="payment-info">
                                        <h4>ยอดชำระเงิน</h4>
                                        <div class="pay-amount">
                                            ฿<?= number_format($ord["total_amount"]) ?>
                                        </div>
                                        <div class="pay-method">
                                            💳 ชำระด้วย: <?= htmlspecialchars($ord["payment_method"] ?? "เก็บเงินปลายทาง") ?>
                                            <?php if (!empty($ord["slip_image"])): ?>
                                                <div style="margin-top: 6px;">
                                                    <span style="font-size: 11.5px; color: #2e7d32; background: #eaf7ed; padding: 2px 8px; border-radius: 12px; font-weight: 600;">✓ แนบสลิปแล้ว</span>
                                                </div>
                                            <?php elseif (($ord["payment_method"] ?? "") === "transfer" || ($ord["payment_method"] ?? "") === "bank"): ?>
                                                <div style="margin-top: 6px;">
                                                    <span style="font-size: 11.5px; color: #b78103; background: #fff9e6; padding: 2px 8px; border-radius: 12px;">⏳ รอแนบสลิป</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="row-actions">
                                    <?php if (!empty($ord["slip_image"])): ?>
                                        <a href="<?= htmlspecialchars($ord["slip_image"]) ?>" target="_blank" class="btn-act" style="background: #eaf8ed; color: #2e7d32; border: 1px solid #bce6c6;">
                                            📎 ดูสลิปโอนเงิน
                                        </a>
                                    <?php endif; ?>
                                    <a href="order_success.php?order=<?= urlencode($ord["order_number"]) ?>" target="_blank" class="btn-act btn-act-receipt">
                                        👁️ ดูใบเสร็จลูกค้า
                                    </a>
                                    <button type="button" onclick="printOrderSlip('<?= htmlspecialchars($ord["order_number"]) ?>')" class="btn-act btn-act-print">
                                        🏷️ ปริ้นท์ใบปะหน้าเค้ก
                                    </button>
                                    <button type="button" onclick="confirmDeleteOrder(<?= $ordId ?>, '<?= htmlspecialchars($ord["order_number"]) ?>')" class="btn-act btn-act-del">
                                        🗑️ ลบ
                                    </button>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        </main>
    <?php endif; ?>

    <script>
        function updateOrderStatus(orderId, newStatus, selectElem) {
            const formData = new FormData();
            formData.append('update_status', '1');
            formData.append('order_id', orderId);
            formData.append('status', newStatus);

            selectElem.disabled = true;
            selectElem.style.opacity = '0.6';

            fetch('admin_orders.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                selectElem.disabled = false;
                selectElem.style.opacity = '1';
                if (data.success) {
                    // Update class color
                    selectElem.className = 'status-dropdown st-' + newStatus;
                } else {
                    alert('เกิดข้อผิดพลาด: ' + (data.message || 'ไม่สามารถอัปเดตได้'));
                }
            })
            .catch(err => {
                selectElem.disabled = false;
                selectElem.style.opacity = '1';
                console.error(err);
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            });
        }

        function confirmDeleteOrder(orderId, orderNumber) {
            if (confirm(`คุณต้องการลบคำสั่งซื้อ #${orderNumber} หรือไม่?\n(การกระทำนี้ไม่สามารถย้อนกลับได้)`)) {
                window.location.href = `admin_orders.php?action=delete&id=${orderId}`;
            }
        }

        function printOrderSlip(orderNumber) {
            window.open(`order_success.php?order=${encodeURIComponent(orderNumber)}`, '_blank');
        }
    </script>
</body>
</html>
