<?php
session_start();
require_once "db.php";

// รหัสผ่านเข้าหลังบ้านแอดมิน (ตรงกับ admin_orders.php)
define('ADMIN_PASSWORD', 'admin1234');

// ตรวจสอบการ Login ของแอดมิน
$loginError = "";
if (isset($_POST["admin_login"])) {
    $enteredPassword = $_POST["password"] ?? "";
    if ($enteredPassword === ADMIN_PASSWORD) {
        $_SESSION["admin_logged"] = true;
        header("Location: admin_users.php");
        exit;
    } else {
        $loginError = "รหัสผ่านแอดมินไม่ถูกต้อง";
    }
}

// ตรวจสอบการ Logout
if (isset($_GET["action"]) && $_GET["action"] === "logout") {
    unset($_SESSION["admin_logged"]);
    header("Location: admin_users.php");
    exit;
}

$isAdmin = !empty($_SESSION["admin_logged"]);

// ตรวจสอบและสร้างตาราง users หากยังไม่มี
$conn->query("
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        phone VARCHAR(50) NOT NULL,
        address TEXT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ตรวจสอบและเพิ่มคอลัมน์ user_id ใน orders หากยังไม่มี
$colCheck = $conn->query("SHOW COLUMNS FROM orders LIKE 'user_id'");
if ($colCheck && $colCheck->num_rows === 0) {
    @$conn->query("ALTER TABLE orders ADD COLUMN user_id INT NULL AFTER order_number");
}

// ซิงค์ Collation คอลัมน์ email ให้ตรงกันเพื่อป้องกันข้อผิดพลาด Illegal mix of collations
@$conn->query("ALTER TABLE orders MODIFY customer_email VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL");
@$conn->query("ALTER TABLE users MODIFY email VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL");

$alertMsg = "";
$alertType = "";

if (isset($_GET["msg"])) {
    switch ($_GET["msg"]) {
        case "added":
            $alertMsg = "เพิ่มสมาชิกลูกค้าใหม่สำเร็จเรียบร้อยแล้ว";
            $alertType = "success";
            break;
        case "updated":
            $alertMsg = "อัปเดตข้อมูลสมาชิกสำเร็จเรียบร้อยแล้ว";
            $alertType = "success";
            break;
        case "deleted":
            $alertMsg = "ลบสมาชิกออกจากระบบเรียบร้อยแล้ว";
            $alertType = "success";
            break;
        case "error_email":
            $alertMsg = "อีเมลนี้มีอยู่ในระบบแล้ว กรุณาใช้อีเมลอื่น";
            $alertType = "error";
            break;
    }
}

// จัดการ AJAX ขอประวัติคำสั่งซื้อของลูกค้า
if ($isAdmin && isset($_GET["action"]) && $_GET["action"] === "get_user_orders") {
    header('Content-Type: application/json; charset=utf-8');
    $userId = (int)($_GET["user_id"] ?? 0);
    $userEmail = trim($_GET["user_email"] ?? "");

    $ordersList = [];
    if ($userId > 0 || !empty($userEmail)) {
        $stmt = $conn->prepare("
            SELECT id, order_number, total_amount, total_items, status, payment_method, created_at 
            FROM orders 
            WHERE user_id = ? OR customer_email = ? 
            ORDER BY id DESC
        ");
        $stmt->bind_param("is", $userId, $userEmail);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $ordersList[] = [
                'id' => $row['id'],
                'order_number' => $row['order_number'],
                'total_amount' => number_format((float)$row['total_amount']),
                'total_items' => (int)$row['total_items'],
                'status' => strtolower($row['status'] ?? 'pending'),
                'payment_method' => $row['payment_method'],
                'created_at' => formatThaiDate($row['created_at'])
            ];
        }
        $stmt->close();
    }
    echo json_encode(['success' => true, 'orders' => $ordersList]);
    exit;
}

// จัดการ POST: เพิ่มสมาชิกใหม่
if ($isAdmin && $_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "add_user") {
    $firstName = trim($_POST["first_name"] ?? "");
    $lastName = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($firstName === "" || $lastName === "" || $email === "" || $phone === "" || $password === "") {
        header("Location: admin_users.php?msg=empty_fields");
        exit;
    }

    // ตรวจสอบอีเมลซ้ำ
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        $checkStmt->close();
        header("Location: admin_users.php?msg=error_email");
        exit;
    }
    $checkStmt->close();

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $insStmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, address, password) VALUES (?, ?, ?, ?, ?, ?)");
    $insStmt->bind_param("ssssss", $firstName, $lastName, $email, $phone, $address, $hashedPassword);
    $insStmt->execute();
    $insStmt->close();

    header("Location: admin_users.php?msg=added");
    exit;
}

// จัดการ POST: แก้ไขข้อมูลสมาชิก
if ($isAdmin && $_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "edit_user") {
    $editId = (int)($_POST["user_id"] ?? 0);
    $firstName = trim($_POST["first_name"] ?? "");
    $lastName = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $newPassword = $_POST["new_password"] ?? "";

    if ($editId > 0 && $firstName !== "" && $lastName !== "" && $email !== "") {
        // ตรวจสอบอีเมลซ้ำกับคนอื่น
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkStmt->bind_param("si", $email, $editId);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            $checkStmt->close();
            header("Location: admin_users.php?msg=error_email");
            exit;
        }
        $checkStmt->close();

        if (!empty($newPassword)) {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $upStmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, password = ? WHERE id = ?");
            $upStmt->bind_param("ssssssi", $firstName, $lastName, $email, $phone, $address, $hashed, $editId);
        } else {
            $upStmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
            $upStmt->bind_param("sssssi", $firstName, $lastName, $email, $phone, $address, $editId);
        }
        $upStmt->execute();
        $upStmt->close();

        header("Location: admin_users.php?msg=updated");
        exit;
    }
}

// จัดการ GET: ลบสมาชิก
if ($isAdmin && isset($_GET["action"]) && $_GET["action"] === "delete" && isset($_GET["id"])) {
    $delId = (int)$_GET["id"];
    if ($delId > 0) {
        // ไม่ลบออเดอร์ทิ้งเพื่อรักษาบัญชีการเงิน แต่เซ็ต user_id ให้เป็น NULL
        $conn->query("UPDATE orders SET user_id = NULL WHERE user_id = $delId");
        $conn->query("DELETE FROM users WHERE id = $delId");
        header("Location: admin_users.php?msg=deleted");
        exit;
    }
}

// สถิติรวมและรายการสมาชิก
$users = [];
$totalUsers = 0;
$activeBuyers = 0;
$totalMemberSpent = 0;
$newUsersThisMonth = 0;

if ($isAdmin) {
    $search = trim($_GET["search"] ?? "");
    $filterOrder = trim($_GET["filter"] ?? "all");
    $sortBy = trim($_GET["sort"] ?? "newest");

    // คำนวณตัวเลขสถิติภาพรวม
    $statsTotalRes = $conn->query("SELECT COUNT(*) as cnt FROM users");
    if ($statsTotalRes) {
        $totalUsers = (int)($statsTotalRes->fetch_assoc()['cnt'] ?? 0);
    }

    $firstDayThisMonth = date('Y-m-01 00:00:00');
    $statsNewRes = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE created_at >= '$firstDayThisMonth'");
    if ($statsNewRes) {
        $newUsersThisMonth = (int)($statsNewRes->fetch_assoc()['cnt'] ?? 0);
    }

    // สร้างคำสั่ง SQL สำหรับดึงข้อมูลลูกค้าพร้อมสถิติคำสั่งซื้อ
    // กำหนด COLLATE utf8mb4_general_ci เพื่อป้องกัน Illegal mix of collations ข้ามตาราง
    $sql = "
        SELECT 
            u.id, 
            u.first_name, 
            u.last_name, 
            u.email, 
            u.phone, 
            u.address, 
            u.created_at,
            COUNT(DISTINCT o.id) AS total_orders,
            COALESCE(SUM(CASE WHEN o.status != 'cancelled' THEN o.total_amount ELSE 0 END), 0) AS total_spent,
            MAX(o.created_at) AS last_order_date
        FROM users u
        LEFT JOIN orders o ON (o.user_id = u.id OR o.customer_email COLLATE utf8mb4_general_ci = u.email COLLATE utf8mb4_general_ci)
        WHERE 1=1
    ";

    $params = [];
    $types = "";

    if (!empty($search)) {
        $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
        $sParam = "%" . $search . "%";
        $params[] = $sParam;
        $params[] = $sParam;
        $params[] = $sParam;
        $params[] = $sParam;
        $types .= "ssss";
    }

    $sql .= " GROUP BY u.id, u.first_name, u.last_name, u.email, u.phone, u.address, u.created_at";

    // กรองตามประวัติคำสั่งซื้อ
    if ($filterOrder === "has_orders") {
        $sql .= " HAVING total_orders > 0";
    } elseif ($filterOrder === "no_orders") {
        $sql .= " HAVING total_orders = 0";
    }

    // เรียงลำดับ
    switch ($sortBy) {
        case "oldest":
            $sql .= " ORDER BY u.id ASC";
            break;
        case "most_orders":
            $sql .= " ORDER BY total_orders DESC, u.id DESC";
            break;
        case "most_spent":
            $sql .= " ORDER BY total_spent DESC, u.id DESC";
            break;
        case "name":
            $sql .= " ORDER BY u.first_name ASC, u.last_name ASC";
            break;
        case "newest":
        default:
            $sql .= " ORDER BY u.id DESC";
            break;
    }

    try {
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $users[] = $row;
            if ((int)$row["total_orders"] > 0) {
                $activeBuyers++;
            }
            $totalMemberSpent += (float)$row["total_spent"];
        }
        $stmt->close();
    } catch (\Throwable $e) {
        $alertMsg = "เกิดข้อผิดพลาดในการโหลดข้อมูลสมาชิก: " . htmlspecialchars($e->getMessage());
        $alertType = "error";
        error_log("admin_users SQL error: " . $e->getMessage());
    }
}

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
    <title>จัดการสมาชิก (Admin) | Grand Bake</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Thai:wght@500;600;700&family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        button, input, select, textarea { font-family: inherit; }

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
        .admin-logo-text { min-width: 0; }
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
        }
        .btn-view-store:hover { background: #fbf4ee; border-color: #cbbaa9; }
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
        }
        .btn-logout:hover { background: #f9dede; }

        /* LOGIN FORM CARD */
        .login-wrap { max-width: 440px; margin: 60px auto 40px; padding: 0 20px; }
        .login-card {
            background: white;
            border-radius: 24px;
            padding: 36px 28px;
            border: 1px solid var(--border-color);
            box-shadow: 0 16px 45px rgba(80, 50, 30, 0.08);
            text-align: center;
        }
        .login-icon { font-size: 48px; margin-bottom: 12px; display: block; }
        .login-card h2 {
            font-family: "Noto Serif Thai", serif;
            font-size: 22px;
            color: var(--brand-title);
            margin-bottom: 6px;
        }
        .login-card p { font-size: 13.5px; color: var(--text-muted); margin-bottom: 24px; }
        .input-group { margin-bottom: 18px; text-align: left; }
        .input-group label { display: block; font-size: 13px; color: #694d3c; margin-bottom: 6px; font-weight: 500; }
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
        }
        .btn-submit-login:hover { background: var(--brand-primary-hover); }

        /* MAIN CONTAINER */
        .container {
            max-width: 1280px;
            margin: 30px auto 60px;
            padding: 0 20px;
        }

        /* ALERT NOTIFICATION */
        .toast-alert {
            padding: 14px 18px;
            border-radius: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 14px;
            font-weight: 500;
            animation: fadeIn 0.3s ease;
        }
        .toast-success { background: #eaf7ed; border: 1px solid #c0e8c8; color: #1e702e; }
        .toast-error { background: #fdeeee; border: 1px solid #f8c8c8; color: #a72727; }

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
        .stat-icon-3 { background: #eefbee; color: #2e7d32; }
        .stat-icon-4 { background: #fbf0ff; color: #8e24aa; }

        .stat-info { min-width: 0; }
        .stat-info h3 {
            font-size: 21px;
            font-weight: 700;
            color: #4b3223;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .stat-info span { font-size: 12.5px; color: var(--text-muted); white-space: nowrap; }

        /* TOOLBAR & SEARCH */
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
            flex-wrap: wrap;
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
        .search-box input:focus { background: white; border-color: #8c5d42; }
        .search-box-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9c8374;
            font-size: 15px;
            pointer-events: none;
        }
        .custom-select {
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
        }
        .btn-filter:hover { background: var(--brand-primary-hover); }
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
        }
        .btn-reset:hover { background: #e6dacd; }
        .btn-add-user {
            padding: 10px 20px;
            min-height: 44px;
            border-radius: 12px;
            background: #2e7d32;
            color: white;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(46, 125, 50, 0.2);
        }
        .btn-add-user:hover { background: #236327; }

        /* MEMBERS LIST / TABLE */
        .members-card-wrap {
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
        .table-header span { font-size: 13px; color: var(--text-muted); }

        .user-row-item {
            padding: 20px 22px;
            border-bottom: 1px solid #f0e6dd;
            display: grid;
            grid-template-columns: 2.2fr 1.6fr 1.8fr 1.4fr;
            align-items: center;
            gap: 16px;
            transition: background 0.2s;
        }
        .user-row-item:last-child { border-bottom: none; }
        .user-row-item:hover { background: #fdfbf9; }

        .user-profile-col {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }
        .user-avatar-badge {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            background: #f4e8dd;
            color: #6b442e;
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .user-meta { min-width: 0; }
        .user-name-title {
            font-size: 15px;
            font-weight: 600;
            color: #4b3223;
            line-height: 1.3;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .user-id-tag {
            font-size: 11px;
            color: #927764;
            background: #f1e6dc;
            padding: 2px 7px;
            border-radius: 10px;
            font-weight: normal;
        }
        .user-date-sub {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 3px;
        }

        .user-contact-col {
            display: flex;
            flex-direction: column;
            gap: 4px;
            font-size: 13px;
        }
        .contact-link {
            color: #684937;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .contact-link:hover { color: #8e4a27; }

        .user-orders-col {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .order-badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12.5px;
            font-weight: 600;
            width: fit-content;
        }
        .badge-has-orders { background: #e8f4fd; color: #1565c0; }
        .badge-zero-orders { background: #f5f0ec; color: #9a8374; }
        .order-spend-val {
            font-size: 14.5px;
            font-weight: 700;
            color: #79442a;
        }

        .user-actions-col {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn-user-act {
            padding: 8px 12px;
            min-height: 38px;
            border-radius: 10px;
            font-size: 12.5px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            touch-action: manipulation;
        }
        .btn-view-hist { background: #fff; border: 1px solid #d8c7b8; color: #6d4b35; }
        .btn-view-hist:hover { background: #f7ede4; }
        .btn-edit-user { background: #fbf3ec; border: 1px solid #e2cbba; color: #78482f; }
        .btn-edit-user:hover { background: #f3e4d6; }
        .btn-del-user { background: #fff; border: 1px solid #f2c7c7; color: #ba2828; }
        .btn-del-user:hover { background: #fdeeed; }

        /* EMPTY STATE */
        .empty-box {
            padding: 60px 20px;
            text-align: center;
            color: var(--text-muted);
        }
        .empty-box span { font-size: 50px; display: block; margin-bottom: 12px; }

        /* MODAL STYLES */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(45, 25, 15, 0.5);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 16px;
        }
        .modal-overlay.active { display: flex; animation: fadeIn 0.2s ease; }
        .modal-box {
            background: white;
            border-radius: 24px;
            max-width: 560px;
            width: 100%;
            padding: 28px;
            box-shadow: 0 20px 50px rgba(70, 45, 30, 0.2);
            border: 1px solid var(--border-color);
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 14px;
            border-bottom: 1px solid #eee2d7;
        }
        .modal-header h3 {
            font-family: "Noto Serif Thai", serif;
            font-size: 20px;
            color: var(--brand-title);
        }
        .modal-close {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: #f5ebe0;
            color: #68432f;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-close:hover { background: #eadad0; }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 16px;
        }
        .form-full { grid-column: 1 / -1; }
        .form-label {
            display: block;
            font-size: 13px;
            color: #654938;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .modal-input {
            width: 100%;
            padding: 10px 14px;
            min-height: 44px;
            border-radius: 12px;
            border: 1px solid #dccdc0;
            background: #faf6f2;
            font-size: 14.5px;
            outline: none;
            transition: 0.2s;
        }
        .modal-input:focus {
            background: white;
            border-color: #8c5d42;
            box-shadow: 0 0 0 3px rgba(140, 93, 66, 0.12);
        }
        .modal-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #eee2d7;
        }
        .btn-modal-cancel {
            padding: 10px 18px;
            border-radius: 12px;
            background: #f1e7df;
            color: #724c36;
            border: none;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-modal-submit {
            padding: 10px 22px;
            border-radius: 12px;
            background: var(--brand-primary);
            color: white;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-modal-submit:hover { background: var(--brand-primary-hover); }

        /* ORDER HISTORY MODAL LIST */
        .order-hist-item {
            padding: 12px 14px;
            background: #faf6f2;
            border: 1px solid #eee3d9;
            border-radius: 14px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .order-hist-num { font-weight: 700; color: #633924; font-size: 14px; }
        .order-hist-date { font-size: 12px; color: #927867; margin-top: 2px; }
        .order-hist-amount { font-size: 15px; font-weight: 700; color: #79442a; text-align: right; }

        /* RESPONSIVE DESIGN */
        @media (max-width: 992px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .user-row-item {
                grid-template-columns: 1fr 1fr;
                gap: 16px;
            }
            .user-actions-col {
                grid-column: 1 / -1;
                justify-content: flex-start;
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
            .toolbar-card { flex-direction: column; align-items: stretch; }
            .filter-form { flex-direction: column; align-items: stretch; }
            .btn-add-user { width: 100%; justify-content: center; }
        }

        @media (max-width: 600px) {
            .container { margin: 16px auto 40px; padding: 0 14px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .stat-card { padding: 14px 12px; border-radius: 14px; }
            .stat-icon { width: 40px; height: 40px; font-size: 20px; }
            .stat-info h3 { font-size: 18px; }
            .stat-info span { font-size: 11px; }

            .user-row-item {
                grid-template-columns: 1fr;
                gap: 12px;
                padding: 16px 14px;
            }
            .user-actions-col {
                display: grid;
                grid-template-columns: 1fr 1fr 1fr;
                gap: 6px;
                width: 100%;
            }
            .btn-user-act {
                min-height: 44px;
                width: 100%;
                font-size: 12px;
                padding: 6px;
            }
            .form-grid { grid-template-columns: 1fr; }
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
                <a href="admin_orders.php" class="nav-tab">📦 จัดการคำสั่งซื้อ</a>
                <a href="admin_users.php" class="nav-tab active">👥 จัดการสมาชิกลูกค้า</a>
            </nav>
        <?php endif; ?>

        <div class="nav-actions">
            <a href="index.php" class="btn-view-store" target="_blank">🌐 เปิดดูหน้าร้าน</a>
            <?php if ($isAdmin): ?>
                <a href="admin_users.php?action=logout" class="btn-logout">🚪 ออกจากระบบ</a>
            <?php endif; ?>
        </div>
    </header>

    <?php if (!$isAdmin): ?>
        <!-- LOGIN CARD -->
        <main class="login-wrap">
            <div class="login-card">
                <span class="login-icon">🔐</span>
                <h2>เข้าสู่ระบบผู้ดูแลร้าน</h2>
                <p>กรุณากรอกรหัสผ่านเพื่อเข้าจัดการข้อมูลสมาชิกลูกค้า</p>

                <?php if (!empty($loginError)): ?>
                    <div class="toast-alert toast-error">
                        ⚠️ <?= htmlspecialchars($loginError) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="admin_users.php">
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
        <!-- MAIN DASHBOARD CONTENT -->
        <main class="container">

            <!-- TOAST ALERT -->
            <?php if (!empty($alertMsg)): ?>
                <div class="toast-alert toast-<?= $alertType ?>">
                    <span><?= $alertType === 'success' ? '✓' : '⚠️' ?> <?= htmlspecialchars($alertMsg) ?></span>
                    <button type="button" onclick="this.parentElement.style.display='none'" style="border:none;background:none;cursor:pointer;font-size:16px;">✕</button>
                </div>
            <?php endif; ?>

            <!-- STATS CARDS -->
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-1">👥</div>
                    <div class="stat-info">
                        <h3><?= number_format($totalUsers) ?> คน</h3>
                        <span>สมาชิกลูกค้าทั้งหมด</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-icon-2">🛍️</div>
                    <div class="stat-info">
                        <h3><?= number_format($activeBuyers) ?> คน</h3>
                        <span>สมาชิกที่เคยสั่งซื้อ</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-icon-3">💰</div>
                    <div class="stat-info">
                        <h3>฿<?= number_format($totalMemberSpent) ?></h3>
                        <span>ยอดซื้อรวมจากสมาชิก</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-icon-4">✨</div>
                    <div class="stat-info">
                        <h3>+<?= number_format($newUsersThisMonth) ?> คน</h3>
                        <span>สมาชิกใหม่เดือนนี้</span>
                    </div>
                </div>
            </section>

            <!-- TOOLBAR / FILTER -->
            <section class="toolbar-card">
                <form method="GET" action="admin_users.php" class="filter-form">
                    <div class="search-box">
                        <span class="search-box-icon">🔍</span>
                        <input type="text" name="search" placeholder="ค้นหาชื่อ, นามสกุล, อีเมล, เบอร์โทร..." value="<?= htmlspecialchars($_GET["search"] ?? "") ?>">
                    </div>

                    <select name="filter" class="custom-select">
                        <option value="all" <?= (($_GET["filter"] ?? "") === "all") ? "selected" : "" ?>>สมาชิกทั้งหมด</option>
                        <option value="has_orders" <?= (($_GET["filter"] ?? "") === "has_orders") ? "selected" : "" ?>>🛒 เคยสั่งซื้อแล้ว</option>
                        <option value="no_orders" <?= (($_GET["filter"] ?? "") === "no_orders") ? "selected" : "" ?>>⏳ ยังไม่เคยสั่งซื้อ</option>
                    </select>

                    <select name="sort" class="custom-select">
                        <option value="newest" <?= (($_GET["sort"] ?? "") === "newest") ? "selected" : "" ?>>สมัครล่าสุด (ใหม่-เก่า)</option>
                        <option value="oldest" <?= (($_GET["sort"] ?? "") === "oldest") ? "selected" : "" ?>>สมัครแรกสุด (เก่า-ใหม่)</option>
                        <option value="most_orders" <?= (($_GET["sort"] ?? "") === "most_orders") ? "selected" : "" ?>>ออเดอร์มากสุด</option>
                        <option value="most_spent" <?= (($_GET["sort"] ?? "") === "most_spent") ? "selected" : "" ?>>ยอดซื้อมากสุด</option>
                        <option value="name" <?= (($_GET["sort"] ?? "") === "name") ? "selected" : "" ?>>เรียงตามชื่อ ก-ฮ</option>
                    </select>

                    <button type="submit" class="btn-filter">กรองข้อมูล</button>
                    <?php if (!empty($_GET["search"]) || !empty($_GET["filter"]) || !empty($_GET["sort"])): ?>
                        <a href="admin_users.php" class="btn-reset">ล้างตัวกรอง</a>
                    <?php endif; ?>
                </form>

                <div>
                    <button type="button" onclick="openAddUserModal()" class="btn-add-user">
                        ➕ เพิ่มสมาชิกใหม่
                    </button>
                </div>
            </section>

            <!-- MEMBERS LIST -->
            <section class="members-card-wrap">
                <div class="table-header">
                    <h2>รายชื่อสมาชิกลูกค้าทั้งหมด</h2>
                    <span>พบทั้งหมด <?= count($users) ?> รายการ</span>
                </div>

                <?php if (empty($users)): ?>
                    <div class="empty-box">
                        <span>👥</span>
                        <h3>ไม่พบข้อมูลสมาชิกลูกค้า</h3>
                        <p>ไม่มีรายชื่อสมาชิกที่ตรงกับคำค้นหาหรือเงื่อนไขในขณะนี้</p>
                    </div>
                <?php else: ?>
                    <div class="users-list">
                        <?php foreach ($users as $usr): 
                            $uId = (int)$usr["id"];
                            $initial = mb_substr($usr["first_name"] ?? "U", 0, 1, "UTF-8");
                            $orderCnt = (int)$usr["total_orders"];
                            $spent = (float)$usr["total_spent"];
                        ?>
                            <article class="user-row-item" id="user-row-<?= $uId ?>">
                                <!-- โปรไฟล์ & ชื่อ -->
                                <div class="user-profile-col">
                                    <div class="user-avatar-badge"><?= htmlspecialchars($initial) ?></div>
                                    <div class="user-meta">
                                        <div class="user-name-title">
                                            <?= htmlspecialchars($usr["first_name"] . " " . $usr["last_name"]) ?>
                                            <span class="user-id-tag">#<?= $uId ?></span>
                                        </div>
                                        <div class="user-date-sub">
                                            สมัครเมื่อ: <?= formatThaiDate($usr["created_at"]) ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- ข้อมูลติดต่อ -->
                                <div class="user-contact-col">
                                    <a href="mailto:<?= htmlspecialchars($usr["email"]) ?>" class="contact-link" title="ส่งอีเมล">
                                        ✉️ <?= htmlspecialchars($usr["email"]) ?>
                                    </a>
                                    <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $usr["phone"])) ?>" class="contact-link" title="โทรออก">
                                        📞 <?= htmlspecialchars($usr["phone"]) ?>
                                    </a>
                                    <?php if (!empty($usr["address"])): ?>
                                        <div style="font-size: 11.5px; color: #8e7464; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($usr["address"]) ?>">
                                            📍 <?= htmlspecialchars($usr["address"]) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- สถิติการซื้อ -->
                                <div class="user-orders-col">
                                    <div class="order-badge-pill <?= $orderCnt > 0 ? 'badge-has-orders' : 'badge-zero-orders' ?>">
                                        <?= $orderCnt > 0 ? "🛍️ สั่งซื้อแล้ว $orderCnt ครั้ง" : "⏳ ยังไม่มีออเดอร์" ?>
                                    </div>
                                    <div class="order-spend-val">
                                        ฿<?= number_format($spent) ?>
                                    </div>
                                </div>

                                <!-- ปุ่มจัดการ -->
                                <div class="user-actions-col">
                                    <button type="button" onclick="viewUserOrders(<?= $uId ?>, '<?= htmlspecialchars(addslashes($usr["first_name"] . " " . $usr["last_name"])) ?>', '<?= htmlspecialchars(addslashes($usr["email"])) ?>')" class="btn-user-act btn-view-hist" title="ดูประวัติการสั่งซื้อ">
                                        📦 ออเดอร์
                                    </button>
                                    <button type="button" onclick="openEditUserModal(<?= htmlspecialchars(json_encode($usr)) ?>)" class="btn-user-act btn-edit-user" title="แก้ไขข้อมูล">
                                        ✏️ แก้ไข
                                    </button>
                                    <button type="button" onclick="confirmDeleteUser(<?= $uId ?>, '<?= htmlspecialchars(addslashes($usr["first_name"] . " " . $usr["last_name"])) ?>')" class="btn-user-act btn-del-user" title="ลบสมาชิก">
                                        🗑️ ลบ
                                    </button>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>

        <!-- MODAL 1: เพิ่มสมาชิกใหม่ -->
        <div class="modal-overlay" id="addUserModal">
            <div class="modal-box">
                <div class="modal-header">
                    <h3>➕ เพิ่มสมาชิกลูกค้าใหม่</h3>
                    <button type="button" class="modal-close" onclick="closeModal('addUserModal')">✕</button>
                </div>
                <form method="POST" action="admin_users.php">
                    <input type="hidden" name="action" value="add_user">
                    <div class="form-grid">
                        <div>
                            <label class="form-label">ชื่อจริง *</label>
                            <input type="text" name="first_name" class="modal-input" placeholder="เช่น มินตรา" required>
                        </div>
                        <div>
                            <label class="form-label">นามสกุล *</label>
                            <input type="text" name="last_name" class="modal-input" placeholder="เช่น สุขใจ" required>
                        </div>
                        <div>
                            <label class="form-label">อีเมล *</label>
                            <input type="email" name="email" class="modal-input" placeholder="example@email.com" required>
                        </div>
                        <div>
                            <label class="form-label">เบอร์โทรศัพท์ *</label>
                            <input type="tel" name="phone" class="modal-input" placeholder="08X-XXX-XXXX" required>
                        </div>
                        <div class="form-full">
                            <label class="form-label">รหัสผ่านสำหรับเข้าสู่ระบบ *</label>
                            <input type="password" name="password" class="modal-input" placeholder="ตั้งรหัสผ่านอย่างน้อย 8 ตัวอักษร" minlength="8" required>
                        </div>
                        <div class="form-full">
                            <label class="form-label">ที่อยู่สำหรับจัดส่งเค้ก</label>
                            <textarea name="address" class="modal-input" rows="2" placeholder="บ้านเลขที่, ถนน, แขวง/ตำบล, เขต/อำเภอ, จังหวัด, รหัสไปรษณีย์"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-modal-cancel" onclick="closeModal('addUserModal')">ยกเลิก</button>
                        <button type="submit" class="btn-modal-submit">บันทึกสมาชิก</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 2: แก้ไขข้อมูลสมาชิก -->
        <div class="modal-overlay" id="editUserModal">
            <div class="modal-box">
                <div class="modal-header">
                    <h3>✏️ แก้ไขข้อมูลสมาชิก</h3>
                    <button type="button" class="modal-close" onclick="closeModal('editUserModal')">✕</button>
                </div>
                <form method="POST" action="admin_users.php">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="form-grid">
                        <div>
                            <label class="form-label">ชื่อจริง *</label>
                            <input type="text" name="first_name" id="edit_first_name" class="modal-input" required>
                        </div>
                        <div>
                            <label class="form-label">นามสกุล *</label>
                            <input type="text" name="last_name" id="edit_last_name" class="modal-input" required>
                        </div>
                        <div>
                            <label class="form-label">อีเมล *</label>
                            <input type="email" name="email" id="edit_email" class="modal-input" required>
                        </div>
                        <div>
                            <label class="form-label">เบอร์โทรศัพท์ *</label>
                            <input type="tel" name="phone" id="edit_phone" class="modal-input" required>
                        </div>
                        <div class="form-full">
                            <label class="form-label">เปลี่ยนรหัสผ่านใหม่ (เว้นว่างไว้หากไม่ต้องการเปลี่ยน)</label>
                            <input type="password" name="new_password" class="modal-input" placeholder="ใส่รหัสใหม่ถ้าต้องการเปลี่ยน..." minlength="8">
                        </div>
                        <div class="form-full">
                            <label class="form-label">ที่อยู่สำหรับจัดส่งเค้ก</label>
                            <textarea name="address" id="edit_address" class="modal-input" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-modal-cancel" onclick="closeModal('editUserModal')">ยกเลิก</button>
                        <button type="submit" class="btn-modal-submit">อัปเดตข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 3: ดูประวัติคำสั่งซื้อของสมาชิก -->
        <div class="modal-overlay" id="userOrdersModal">
            <div class="modal-box">
                <div class="modal-header">
                    <div>
                        <h3 id="orderModalTitle">📦 ประวัติคำสั่งซื้อของลูกค้า</h3>
                        <small id="orderModalSubtitle" style="color: #927764; font-size: 12.5px;"></small>
                    </div>
                    <button type="button" class="modal-close" onclick="closeModal('userOrdersModal')">✕</button>
                </div>
                <div id="orderModalContent">
                    <div style="text-align: center; padding: 30px; color: #8e7464;">กำลังโหลดประวัติคำสั่งซื้อ...</div>
                </div>
                <div class="modal-footer">
                    <a id="btnFilterUserOrders" href="#" class="btn-modal-submit" style="text-decoration:none;">
                        🔎 ดูในหน้าจัดการออเดอร์
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function openAddUserModal() {
            document.getElementById('addUserModal').classList.add('active');
        }

        function openEditUserModal(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_first_name').value = user.first_name || '';
            document.getElementById('edit_last_name').value = user.last_name || '';
            document.getElementById('edit_email').value = user.email || '';
            document.getElementById('edit_phone').value = user.phone || '';
            document.getElementById('edit_address').value = user.address || '';
            document.getElementById('editUserModal').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // ปิด modal เมื่อคลิกด้านนอก
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });

        // ดึงประวัติคำสั่งซื้อของสมาชิกมาแสดง
        function viewUserOrders(userId, userName, userEmail) {
            const modal = document.getElementById('userOrdersModal');
            const title = document.getElementById('orderModalTitle');
            const sub = document.getElementById('orderModalSubtitle');
            const content = document.getElementById('orderModalContent');
            const btnLink = document.getElementById('btnFilterUserOrders');

            title.textContent = `📦 ออเดอร์ของ: ${userName}`;
            sub.textContent = `อีเมล: ${userEmail}`;
            btnLink.href = `admin_orders.php?search=${encodeURIComponent(userEmail)}`;
            content.innerHTML = '<div style="text-align: center; padding: 30px; color: #8e7464;">กำลังโหลดประวัติคำสั่งซื้อ...</div>';
            modal.classList.add('active');

            fetch(`admin_users.php?action=get_user_orders&user_id=${userId}&user_email=${encodeURIComponent(userEmail)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.orders && data.orders.length > 0) {
                        let html = '';
                        data.orders.forEach(ord => {
                            const stClass = 'st-' + ord.status;
                            html += `
                                <div class="order-hist-item">
                                    <div>
                                        <div class="order-hist-num">🧾 #${ord.order_number}</div>
                                        <div class="order-hist-date">${ord.created_at}</div>
                                        <div style="font-size: 11.5px; color: #8c7263; margin-top: 2px;">
                                            จำนวน: ${ord.total_items} ชิ้น • ${ord.payment_method}
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div class="order-hist-amount">฿${ord.total_amount}</div>
                                        <span style="display:inline-block; margin-top:4px; font-size:11px; padding:2px 8px; border-radius:10px; font-weight:600; background:#f0e5dc; color:#6d4833;">
                                            ${ord.status}
                                        </span>
                                    </div>
                                </div>
                            `;
                        });
                        content.innerHTML = html;
                    } else {
                        content.innerHTML = `
                            <div style="text-align: center; padding: 40px 20px; color: #9c8374;">
                                <span style="font-size: 38px; display: block; margin-bottom: 8px;">🧁</span>
                                <strong>ยังไม่มีประวัติการสั่งซื้อ</strong>
                                <p style="font-size: 13px; margin-top: 4px;">ลูกค้ารายนี้ยังไม่เคยทำรายการสั่งซื้อเค้ก</p>
                            </div>
                        `;
                    }
                })
                .catch(err => {
                    content.innerHTML = '<div style="text-align: center; padding: 20px; color: #a72727;">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
                });
        }

        function confirmDeleteUser(userId, userName) {
            if (confirm(`คุณต้องการลบสมาชิกลูกค้า "${userName}" หรือไม่?\n(คำสั่งซื้อเดิมของลูกค้าจะไม่ถูกลบ)`)) {
                window.location.href = `admin_users.php?action=delete&id=${userId}`;
            }
        }
    </script>
</body>
</html>
