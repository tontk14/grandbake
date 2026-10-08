<?php
session_start();

require_once "db.php";

/* ตรวจสอบว่าล็อกอินหรือยัง */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

/* ตรวจสอบและสร้างตาราง orders / order_items หากยังไม่มี */
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

/* อัปเดตข้อมูลส่วนตัว (เบอร์โทรและที่อยู่) */
$alertMessage = "";
$alertType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "update_profile") {
    $newPhone = trim($_POST["phone"] ?? "");
    $newAddress = trim($_POST["address"] ?? "");

    $upStmt = $conn->prepare("UPDATE users SET phone = ?, address = ? WHERE id = ?");
    $upStmt->bind_param("ssi", $newPhone, $newAddress, $user_id);
    if ($upStmt->execute()) {
        $alertMessage = "อัปเดตข้อมูลส่วนตัวเรียบร้อยแล้ว";
        $alertType = "success";
    } else {
        $alertMessage = "เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาลองใหม่อีกครั้ง";
        $alertType = "error";
    }
    $upStmt->close();
}

/* ดึงข้อมูลสมาชิกจาก Database */
$stmt = $conn->prepare(
    "SELECT id, first_name, last_name, email, phone, address, created_at
     FROM users
     WHERE id = ?"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

/* ดึงประวัติคำสั่งซื้อของสมาชิก (ค้นหาจาก user_id หรือ email ที่ตรงกัน) */
$userEmail = $user["email"];
$orderStmt = $conn->prepare("
    SELECT id, order_number, customer_name, customer_email, customer_phone, customer_address, 
           payment_method, total_amount, total_items, status, created_at
    FROM orders
    WHERE user_id = ? OR (customer_email = ? AND customer_email != '')
    ORDER BY id DESC
");
$orderStmt->bind_param("is", $user_id, $userEmail);
$orderStmt->execute();
$ordersResult = $orderStmt->get_result();
$orders = $ordersResult->fetch_all(MYSQLI_ASSOC);
$orderStmt->close();

/* ดึงรายการสินค้าของแต่ละออเดอร์ */
$itemsByOrderId = [];
if (!empty($orders)) {
    $orderIds = array_column($orders, "id");
    $placeholders = implode(",", array_fill(0, count($orderIds), "?"));
    $types = str_repeat("i", count($orderIds));

    $itemQuery = "
        SELECT order_id, product_name, price, quantity, subtotal, image
        FROM order_items
        WHERE order_id IN ($placeholders)
        ORDER BY id ASC
    ";
    $itemStmt = $conn->prepare($itemQuery);
    $itemStmt->bind_param($types, ...$orderIds);
    $itemStmt->execute();
    $itemsResult = $itemStmt->get_result();
    while ($row = $itemsResult->fetch_assoc()) {
        $itemsByOrderId[$row["order_id"]][] = $row;
    }
    $itemStmt->close();
}

/* คำนวณยอดสถิติ */
$totalOrderCount = count($orders);
$totalSpent = 0;
foreach ($orders as $ord) {
    $totalSpent += (float)$ord["total_amount"];
}

/* ฟังก์ชันแปลงวันที่ภาษาไทย */
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

/* ฟังก์ชันป้ายสถานะออเดอร์ */
function getOrderStatusBadge($status) {
    $s = strtolower(trim($status ?? ''));
    switch ($s) {
        case 'confirmed':
            return ['text' => 'ยืนยันคำสั่งซื้อแล้ว', 'bg' => '#e8f4fd', 'color' => '#1565c0', 'icon' => '✓'];
        case 'preparing':
            return ['text' => 'กำลังจัดเตรียมเค้ก', 'bg' => '#fff4e5', 'color' => '#e65100', 'icon' => '🧁'];
        case 'delivering':
            return ['text' => 'กำลังจัดส่ง', 'bg' => '#f3e8fd', 'color' => '#6a1b9a', 'icon' => '🛵'];
        case 'completed':
            return ['text' => 'จัดส่งสำเร็จ', 'bg' => '#e8f8f0', 'color' => '#2e7d32', 'icon' => '🎉'];
        case 'cancelled':
            return ['text' => 'ยกเลิกแล้ว', 'bg' => '#fdeeed', 'color' => '#c62828', 'icon' => '✕'];
        case 'pending':
        default:
            return ['text' => 'รอดำเนินการ', 'bg' => '#fff9e6', 'color' => '#b78103', 'icon' => '⏳'];
    }
}

/* ฟังก์ชันช่องทางชำระเงิน */
function getPaymentMethodText($method) {
    $m = strtolower(trim($method ?? ''));
    switch ($m) {
        case 'bank':
        case 'transfer':
            return 'โอนเงินผ่านธนาคาร';
        case 'cash':
        case 'cod':
            return 'เก็บเงินปลายทาง';
        case 'credit':
        case 'card':
            return 'บัตรเครดิต / เดบิต';
        default:
            return htmlspecialchars($method ?: 'เก็บเงินปลายทาง');
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บัญชีของฉัน & ประวัติการสั่งซื้อ | Grand Bake</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Thai:wght@400;500;600;700&family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Prompt", sans-serif;
            background: #fbf6f1;
            color: #4f3b2d;
            line-height: 1.6;
            min-height: 100vh;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button, input, textarea {
            font-family: inherit;
        }

        /* =====================================
           NAVBAR
        ===================================== */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            height: 78px;
            padding: 0 7%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #eee3d8;
            box-shadow: 0 4px 20px rgba(70, 50, 35, 0.05);
        }

        .logo img {
            width: 120px;
            height: auto;
            display: block;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 26px;
        }

        .nav-menu a {
            color: #695648;
            font-size: 15px;
            transition: 0.2s;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            color: #9a5b3c;
            font-weight: 500;
        }

        .cart-link {
            font-size: 19px !important;
            margin-left: 6px;
        }

        .logout-btn {
            padding: 7px 16px;
            border-radius: 20px;
            border: 1px solid #d9c8b8;
            color: #75543d !important;
            font-size: 13px !important;
            transition: 0.2s;
        }

        .logout-btn:hover {
            background: #f5ece4;
        }

        /* =====================================
           MAIN CONTAINER
        ===================================== */
        .profile-page {
            max-width: 1140px;
            margin: 45px auto 80px;
            padding: 0 25px;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .profile-header span {
            font-size: 12px;
            letter-spacing: 3.5px;
            color: #b08264;
            font-weight: 600;
        }

        .profile-header h1 {
            margin: 6px 0 6px;
            font-family: "Noto Serif Thai", serif;
            font-size: 36px;
            color: #4d3326;
        }

        .profile-header p {
            font-size: 14px;
            color: #8c776b;
        }

        /* ALERT NOTIFICATION */
        .alert-box {
            padding: 13px 20px;
            border-radius: 14px;
            margin-bottom: 25px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeIn 0.3s ease;
        }

        .alert-success {
            background: #eaf6ee;
            border: 1px solid #bce2c7;
            color: #276b3a;
        }

        .alert-error {
            background: #fdeeee;
            border: 1px solid #f6c8c8;
            color: #aa2c2c;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* =====================================
           GRID LAYOUT
        ===================================== */
        .profile-grid {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 30px;
            align-items: start;
        }

        /* =====================================
           LEFT CARD: USER PROFILE SUMMARY
        ===================================== */
        .profile-card-left {
            background: white;
            border-radius: 24px;
            padding: 35px 25px;
            text-align: center;
            border: 1px solid #ede1d5;
            box-shadow: 0 12px 35px rgba(75, 52, 36, 0.06);
            position: sticky;
            top: 105px;
        }

        .profile-avatar {
            width: 95px;
            height: 95px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fceee3, #f5dcc8);
            color: #7b4b32;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            margin: 0 auto 16px;
            border: 4px solid #fffaf5;
            box-shadow: 0 6px 18px rgba(110, 75, 50, 0.12);
        }

        .profile-card-left h2 {
            font-family: "Noto Serif Thai", serif;
            font-size: 21px;
            color: #4d3326;
            margin-bottom: 4px;
        }

        .member-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            background: #f5ebe1;
            color: #8c5d42;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 22px;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            padding: 16px 0;
            border-top: 1px solid #f0e6dd;
            border-bottom: 1px solid #f0e6dd;
            margin-bottom: 24px;
            text-align: center;
        }

        .stat-item .stat-num {
            font-size: 20px;
            font-weight: 600;
            color: #7e4b31;
            display: block;
        }

        .stat-item .stat-lbl {
            font-size: 12px;
            color: #968376;
            margin-top: 2px;
        }

        .left-menu-links {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .side-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px;
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 500;
            transition: 0.2s;
        }

        .side-btn-primary {
            background: #754b35;
            color: white;
        }

        .side-btn-primary:hover {
            background: #5e3b29;
            transform: translateY(-1px);
        }

        .side-btn-secondary {
            background: #faf4ed;
            color: #6d4e3b;
            border: 1px solid #e8dbce;
        }

        .side-btn-secondary:hover {
            background: #f3e8dc;
        }

        /* =====================================
           RIGHT AREA: TABS & CONTENT
        ===================================== */
        .right-container {
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .tab-nav {
            display: flex;
            background: #ede2d7;
            padding: 6px;
            border-radius: 16px;
            gap: 6px;
        }

        .tab-btn {
            flex: 1;
            padding: 12px 18px;
            border: none;
            background: transparent;
            color: #6f5443;
            font-size: 14.5px;
            font-weight: 500;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.25s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .tab-btn:hover {
            color: #4a3224;
        }

        .tab-btn.active {
            background: white;
            color: #633a25;
            font-weight: 600;
            box-shadow: 0 4px 14px rgba(70, 45, 30, 0.08);
        }

        .tab-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 22px;
            height: 22px;
            padding: 0 6px;
            border-radius: 12px;
            background: #7d4d34;
            color: white;
            font-size: 11px;
            font-weight: 600;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.25s ease;
        }

        /* =====================================
           ORDER HISTORY CARDS
        ===================================== */
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .order-card {
            background: white;
            border-radius: 20px;
            border: 1px solid #ebdcd0;
            box-shadow: 0 10px 30px rgba(70, 50, 35, 0.05);
            overflow: hidden;
            transition: 0.2s ease;
        }

        .order-card:hover {
            box-shadow: 0 14px 38px rgba(70, 50, 35, 0.08);
            border-color: #dfcdbe;
        }

        .order-card-header {
            padding: 18px 24px;
            background: #fdfaf7;
            border-bottom: 1px solid #efe4db;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .order-meta-left {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .order-num-tag {
            font-weight: 600;
            font-size: 15px;
            color: #553526;
            letter-spacing: 0.5px;
        }

        .order-date-tag {
            font-size: 12.5px;
            color: #968376;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 12.5px;
            font-weight: 600;
        }

        .order-items-box {
            padding: 18px 24px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .order-single-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding-bottom: 14px;
            border-bottom: 1px dashed #f0e6dd;
        }

        .order-single-item:last-child {
            padding-bottom: 0;
            border-bottom: none;
        }

        .item-main-info {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .item-thumb {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            object-fit: cover;
            background: #f6ede5;
            border: 1px solid #ebdcd0;
            flex-shrink: 0;
        }

        .item-thumb-placeholder {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            background: #f6ede5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .item-details h4 {
            font-size: 14.5px;
            color: #4b3022;
            font-weight: 500;
            margin-bottom: 2px;
        }

        .item-details span {
            font-size: 12.5px;
            color: #927c70;
        }

        .item-subtotal {
            font-size: 14.5px;
            font-weight: 600;
            color: #6c4430;
            white-space: nowrap;
        }

        .order-card-footer {
            padding: 16px 24px;
            background: #fffdfb;
            border-top: 1px solid #efe4db;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
        }

        .footer-payment-info {
            font-size: 13px;
            color: #8c7669;
        }

        .footer-total-box {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .total-amount-label {
            font-size: 13px;
            color: #796153;
        }

        .total-amount-val {
            font-size: 19px;
            font-weight: 700;
            color: #87492c;
        }

        .btn-view-order {
            padding: 8px 18px;
            border-radius: 20px;
            background: #f7ede3;
            color: #724933;
            border: 1px solid #e5d3c4;
            font-size: 13px;
            font-weight: 500;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-view-order:hover {
            background: #734932;
            color: white;
            border-color: #734932;
        }

        /* EMPTY STATE */
        .empty-orders {
            background: white;
            border-radius: 22px;
            padding: 65px 30px;
            text-align: center;
            border: 1px solid #ede1d5;
            box-shadow: 0 10px 30px rgba(75, 52, 36, 0.05);
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
            display: block;
        }

        .empty-orders h3 {
            font-family: "Noto Serif Thai", serif;
            font-size: 24px;
            color: #55392b;
            margin-bottom: 8px;
        }

        .empty-orders p {
            color: #927c70;
            font-size: 14px;
            max-width: 420px;
            margin: 0 auto 24px;
        }

        .btn-shop-now {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 13px 28px;
            border-radius: 30px;
            background: #794a32;
            color: white;
            font-size: 14.5px;
            font-weight: 500;
            transition: 0.25s;
            box-shadow: 0 8px 20px rgba(121, 74, 50, 0.2);
        }

        .btn-shop-now:hover {
            background: #603822;
            transform: translateY(-2px);
        }

        /* =====================================
           ACCOUNT DETAILS & EDIT FORM
        ===================================== */
        .profile-card-right {
            background: white;
            border-radius: 22px;
            padding: 35px;
            border: 1px solid #ebdcd0;
            box-shadow: 0 10px 30px rgba(75, 52, 36, 0.05);
        }

        .card-header-flex {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
            padding-bottom: 14px;
            border-bottom: 1px solid #efe4db;
        }

        .card-header-flex h2 {
            font-family: "Noto Serif Thai", serif;
            font-size: 22px;
            color: #4e3325;
            margin: 0;
        }

        .info-row {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid #f2e9e2;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            width: 140px;
            color: #968376;
            font-size: 13.5px;
            flex-shrink: 0;
        }

        .info-value {
            flex: 1;
            color: #4b3427;
            font-size: 14.5px;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            color: #796153;
            font-weight: 500;
            margin-bottom: 7px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid #dfcfc2;
            background: #fffcf9;
            color: #4d3326;
            font-size: 14px;
            transition: 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: #8c5d42;
            background: white;
            box-shadow: 0 0 0 3px rgba(140, 93, 66, 0.12);
        }

        textarea.form-input {
            resize: vertical;
            min-height: 90px;
        }

        .btn-save-profile {
            padding: 12px 28px;
            border-radius: 12px;
            background: #774c35;
            color: white;
            border: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-save-profile:hover {
            background: #5f3a25;
            transform: translateY(-1px);
        }

        /* =====================================
           RESPONSIVE
        ===================================== */
        @media (max-width: 860px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }

            .profile-card-left {
                position: static;
            }

            .order-card-header,
            .order-card-footer {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .footer-total-box {
                width: 100%;
                justify-content: space-between;
            }

            .navbar {
                padding: 0 20px;
            }

            .nav-menu a:not(.logout-btn):not(.cart-link) {
                display: none;
            }
        }
    </style>
</head>

<body>

    <!-- ================= NAVBAR ================= -->
    <nav class="navbar">
        <div class="logo">
            <a href="index.php">
                <img src="logo.jpg" alt="Grand Bake Logo">
            </a>
        </div>

        <div class="nav-menu">
            <a href="index.php">หน้าแรก</a>
            <a href="products.php">สินค้า</a>
            <a href="profile.php" class="active">บัญชีของฉัน</a>
            <a href="cart.html" class="cart-link" aria-label="ตะกร้าสินค้า">🛒</a>
            <a href="logout.php" class="logout-btn">ออกจากระบบ</a>
        </div>
    </nav>

    <!-- ================= MAIN ================= -->
    <main class="profile-page">

        <div class="profile-header">
            <span>GRAND BAKE MEMBER</span>
            <h1>บัญชีของฉัน & ประวัติการสั่งซื้อ</h1>
            <p>ติดตามสถานะคำสั่งซื้อเค้กและจัดการข้อมูลส่วนตัวของคุณ</p>
        </div>

        <?php if (!empty($alertMessage)): ?>
            <div class="alert-box alert-<?= htmlspecialchars($alertType) ?>">
                <span><?= $alertType === 'success' ? '✓' : '⚠️' ?></span>
                <span><?= htmlspecialchars($alertMessage) ?></span>
            </div>
        <?php endif; ?>

        <div class="profile-grid">

            <!-- LEFT COLUMN: MEMBER SUMMARY -->
            <aside class="profile-card-left">
                <div class="profile-avatar">
                    👤
                </div>

                <h2>
                    <?= htmlspecialchars($user["first_name"] . " " . $user["last_name"]) ?>
                </h2>

                <div class="member-badge">
                    สมาชิก Grand Bake
                </div>

                <div class="stat-grid">
                    <div class="stat-item">
                        <span class="stat-num"><?= number_format($totalOrderCount) ?></span>
                        <span class="stat-lbl">คำสั่งซื้อทั้งหมด</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-num">฿<?= number_format($totalSpent) ?></span>
                        <span class="stat-lbl">ยอดสั่งซื้อสะสม</span>
                    </div>
                </div>

                <div class="left-menu-links">
                    <a href="products.php" class="side-btn side-btn-primary">
                        🛍️ เลือกซื้อเค้กเพิ่ม
                    </a>
                    <a href="logout.php" class="side-btn side-btn-secondary">
                        🚪 ออกจากระบบ
                    </a>
                </div>
            </aside>

            <!-- RIGHT COLUMN: TABS (ORDERS / ACCOUNT) -->
            <section class="right-container">

                <!-- TAB SWITCHER -->
                <div class="tab-nav">
                    <button type="button" class="tab-btn active" onclick="switchTab('orders', event)">
                        📦 ประวัติการสั่งซื้อ
                        <span class="tab-count"><?= $totalOrderCount ?></span>
                    </button>
                    <button type="button" class="tab-btn" onclick="switchTab('account', event)">
                        👤 ข้อมูลสมาชิก & ที่อยู่จัดส่ง
                    </button>
                </div>

                <!-- TAB 1: ORDER HISTORY -->
                <div id="tab-orders" class="tab-content active">
                    <?php if (empty($orders)): ?>
                        <div class="empty-orders">
                            <span class="empty-icon">🍰</span>
                            <h3>ยังไม่มีประวัติการสั่งซื้อ</h3>
                            <p>คุณยังไม่ได้สั่งซื้อเค้กกับ Grand Bake ให้ทุกช่วงเวลาพิเศษของคุณหวานละมุนด้วยเค้กสไตล์เกาหลีระดับพรีเมียมจากเรา</p>
                            <a href="products.php" class="btn-shop-now">
                                เลือกซื้อเค้กแสนอร่อย <span>→</span>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="orders-list">
                            <?php foreach ($orders as $ord): 
                                $badge = getOrderStatusBadge($ord["status"] ?? "");
                                $orderItems = $itemsByOrderId[$ord["id"]] ?? [];
                            ?>
                                <article class="order-card">
                                    <div class="order-card-header">
                                        <div class="order-meta-left">
                                            <span class="order-num-tag">
                                                🧾 เลขที่คำสั่งซื้อ: <strong><?= htmlspecialchars($ord["order_number"]) ?></strong>
                                            </span>
                                            <span class="order-date-tag">
                                                สั่งซื้อเมื่อ: <?= formatThaiDate($ord["created_at"]) ?>
                                            </span>
                                        </div>

                                        <div class="status-badge" style="background: <?= $badge['bg'] ?>; color: <?= $badge['color'] ?>;">
                                            <span><?= $badge['icon'] ?></span>
                                            <span><?= $badge['text'] ?></span>
                                        </div>
                                    </div>

                                    <div class="order-items-box">
                                        <?php if (!empty($orderItems)): ?>
                                            <?php foreach ($orderItems as $item): ?>
                                                <div class="order-single-item">
                                                    <div class="item-main-info">
                                                        <?php if (!empty($item["image"])): ?>
                                                            <img src="<?= htmlspecialchars($item["image"]) ?>" alt="<?= htmlspecialchars($item["product_name"]) ?>" class="item-thumb">
                                                        <?php else: ?>
                                                            <div class="item-thumb-placeholder">🎂</div>
                                                        <?php endif; ?>

                                                        <div class="item-details">
                                                            <h4><?= htmlspecialchars($item["product_name"]) ?></h4>
                                                            <span>฿<?= number_format($item["price"]) ?> × <?= (int)$item["quantity"] ?> ชิ้น</span>
                                                        </div>
                                                    </div>

                                                    <div class="item-subtotal">
                                                        ฿<?= number_format($item["subtotal"]) ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="order-single-item">
                                                <div class="item-main-info">
                                                    <div class="item-thumb-placeholder">🎂</div>
                                                    <div class="item-details">
                                                        <h4>เค้ก Grand Bake</h4>
                                                        <span>จำนวน <?= (int)$ord["total_items"] ?> ชิ้น</span>
                                                    </div>
                                                </div>
                                                <div class="item-subtotal">
                                                    ฿<?= number_format($ord["total_amount"]) ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="order-card-footer">
                                        <div class="footer-payment-info">
                                            💳 ช่องทางชำระเงิน: <strong><?= getPaymentMethodText($ord["payment_method"] ?? "") ?></strong>
                                        </div>

                                        <div class="footer-total-box">
                                            <div>
                                                <span class="total-amount-label">รวมทั้งหมด (<?= (int)$ord["total_items"] ?> ชิ้น):</span>
                                                <span class="total-amount-val">฿<?= number_format($ord["total_amount"]) ?></span>
                                            </div>

                                            <a href="order_success.php?order=<?= urlencode($ord["order_number"]) ?>" class="btn-view-order">
                                                ดูใบเสร็จ <span>→</span>
                                            </a>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- TAB 2: ACCOUNT DETAILS & ADDRESS EDIT -->
                <div id="tab-account" class="tab-content">
                    <div class="profile-card-right">
                        <div class="card-header-flex">
                            <h2>ข้อมูลสมาชิก</h2>
                        </div>

                        <div class="info-row">
                            <div class="info-label">ชื่อ - นามสกุล</div>
                            <div class="info-value">
                                <?= htmlspecialchars($user["first_name"] . " " . $user["last_name"]) ?>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">อีเมล</div>
                            <div class="info-value">
                                <?= htmlspecialchars($user["email"]) ?>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">วันที่สมัครสมาชิก</div>
                            <div class="info-value">
                                <?= formatThaiDate($user["created_at"] ?? "") ?>
                            </div>
                        </div>

                        <div class="card-header-flex" style="margin-top: 28px;">
                            <h2>แก้ไขข้อมูลการติดต่อ & ที่อยู่จัดส่ง</h2>
                        </div>

                        <form method="POST" action="profile.php">
                            <input type="hidden" name="action" value="update_profile">

                            <div class="form-group">
                                <label for="phoneInput">เบอร์โทรศัพท์ติดต่อ</label>
                                <input type="text" id="phoneInput" name="phone" class="form-input" 
                                       value="<?= htmlspecialchars($user["phone"] ?? "") ?>" 
                                       placeholder="เช่น 0812345678" required>
                            </div>

                            <div class="form-group">
                                <label for="addressInput">ที่อยู่สำหรับจัดส่งเค้ก</label>
                                <textarea id="addressInput" name="address" class="form-input" 
                                          placeholder="บ้านเลขที่, ซอย, ถนน, ตำบล/แขวง, อำเภอ/เขต, จังหวัด, รหัสไปรษณีย์" 
                                          rows="4" required><?= htmlspecialchars($user["address"] ?? "") ?></textarea>
                            </div>

                            <button type="submit" class="btn-save-profile">
                                💾 บันทึกการเปลี่ยนแปลง
                            </button>
                        </form>
                    </div>
                </div>

            </section>

        </div>

    </main>

    <script>
        function switchTab(tabName, event) {
            if (event) event.preventDefault();

            // Reset tab buttons
            const buttons = document.querySelectorAll(".tab-btn");
            buttons.forEach(btn => btn.classList.remove("active"));

            // Reset tab contents
            const contents = document.querySelectorAll(".tab-content");
            contents.forEach(content => content.classList.remove("active"));

            if (tabName === "account") {
                document.getElementById("tab-account").classList.add("active");
                buttons[1].classList.add("active");
                window.location.hash = "account";
            } else {
                document.getElementById("tab-orders").classList.add("active");
                buttons[0].classList.add("active");
                window.location.hash = "orders";
            }
        }

        // Check URL Hash on page load
        window.addEventListener("DOMContentLoaded", () => {
            if (window.location.hash === "#account") {
                switchTab("account");
            } else {
                switchTab("orders");
            }
        });
    </script>
    <script src="cart-badge.js"></script>
</body>

</html>