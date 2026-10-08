<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/send_order_email.php';

// รับเฉพาะ POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method Not Allowed'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// รับข้อมูลจาก JSON payload หรือ $_POST
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data) || empty($data)) {
    $data = $_POST;
}

$name           = trim($data['name'] ?? '');
$email          = trim($data['email'] ?? '');
$phone          = trim($data['phone'] ?? '');
$address        = trim($data['address'] ?? '');
$payment        = trim($data['payment'] ?? 'cash');
$deliveryDate   = trim($data['delivery_date'] ?? date('Y-m-d'));
$deliveryTime   = trim($data['delivery_time'] ?? '15:00 - 18:00 น.');
$cakeMessage    = trim($data['cake_message'] ?? '');
$discountCode   = strtoupper(trim($data['discount_code'] ?? ''));
$cart           = $data['cart'] ?? [];

// ตรวจสอบความถูกต้องของข้อมูล
if (empty($name) || empty($email) || empty($phone) || empty($address)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'กรุณากรอกข้อมูลสำหรับจัดส่งให้ครบถ้วน'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'รูปแบบอีเมลไม่ถูกต้อง'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($cart) || !is_array($cart)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ไม่มีสินค้าในตะกร้า'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// คำนวณยอดเงินและรายการ
$subtotalAmount = 0;
$totalItems     = 0;
$processedItems = [];

foreach ($cart as $item) {
    $itemQty = max(1, (int)($item['quantity'] ?? 1));
    $itemPrice = (float)($item['price'] ?? 0);
    $itemSubtotal = $itemQty * $itemPrice;

    $subtotalAmount += $itemSubtotal;
    $totalItems     += $itemQty;

    $processedItems[] = [
        'id'       => $item['id'] ?? null,
        'name'     => $item['name'] ?? 'เค้ก Grand Bake',
        'price'    => $itemPrice,
        'quantity' => $itemQty,
        'subtotal' => $itemSubtotal,
        'image'    => $item['image'] ?? ''
    ];
}

if ($totalItems <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ไม่มีจำนวนสินค้าที่ถูกต้อง'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// คำนวณส่วนลดจากโค้ดโปรโมชั่น
$discountAmount = 0;
if (!empty($discountCode)) {
    switch ($discountCode) {
        case 'GBNEW10':
            $discountAmount = round($subtotalAmount * 0.10); // ลด 10%
            break;
        case 'CAKE50':
            $discountAmount = 50.00; // ลด 50 บาท
            break;
        case 'GRAND20':
            $discountAmount = round($subtotalAmount * 0.20); // ลด 20%
            break;
        default:
            $discountCode = '';
            $discountAmount = 0;
            break;
    }
}

$totalAmount = max(0, $subtotalAmount - $discountAmount);

// สร้างรหัสคำสั่งซื้อที่ไม่ซ้ำ (เช่น GB-20261001-4A8F)
$orderNumber = 'GB-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
$userId = $_SESSION['user_id'] ?? null;
$orderDate = date('d/m/Y H:i น.');

// ตรวจสอบและสร้างตาราง orders / order_items
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
        status VARCHAR(50) DEFAULT 'confirmed',
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

// อัปเดตคอลัมน์เพิ่มเติมในตาราง orders หากยังไม่มี
$columnsToAdd = [
    'delivery_date'   => "VARCHAR(50) NULL AFTER customer_address",
    'delivery_time'   => "VARCHAR(50) NULL AFTER delivery_date",
    'cake_message'    => "TEXT NULL AFTER delivery_time",
    'discount_code'   => "VARCHAR(50) NULL AFTER total_items",
    'discount_amount' => "DECIMAL(10,2) DEFAULT 0.00 AFTER discount_code",
    'slip_image'      => "VARCHAR(255) NULL AFTER payment_method"
];
foreach ($columnsToAdd as $col => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM orders LIKE '$col'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE orders ADD COLUMN $col $definition");
    }
}

// บันทึก Order ลงฐานข้อมูล
$stmt = $conn->prepare("
    INSERT INTO orders 
    (order_number, user_id, customer_name, customer_email, customer_phone, customer_address, 
     delivery_date, delivery_time, cake_message, payment_method, total_amount, total_items, discount_code, discount_amount, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')
");

$stmt->bind_param(
    "sissssssssdisd",
    $orderNumber,
    $userId,
    $name,
    $email,
    $phone,
    $address,
    $deliveryDate,
    $deliveryTime,
    $cakeMessage,
    $payment,
    $totalAmount,
    $totalItems,
    $discountCode,
    $discountAmount
);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการบันทึกคำสั่งซื้อ: ' . $stmt->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$dbOrderId = $stmt->insert_id;
$stmt->close();

// บันทึกรายการสินค้าใน Order Items
$itemStmt = $conn->prepare("
    INSERT INTO order_items 
    (order_id, product_id, product_name, price, quantity, subtotal, image)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

foreach ($processedItems as $pItem) {
    $prodId   = $pItem['id'];
    $prodName = $pItem['name'];
    $prodPrice= $pItem['price'];
    $prodQty  = $pItem['quantity'];
    $prodSub  = $pItem['subtotal'];
    $prodImg  = $pItem['image'];

    $itemStmt->bind_param(
        "iisddis",
        $dbOrderId,
        $prodId,
        $prodName,
        $prodPrice,
        $prodQty,
        $prodSub,
        $prodImg
    );
    $itemStmt->execute();
}
$itemStmt->close();

// ส่งอีเมลยืนยันคำสั่งซื้อ
$emailSent = false;
$emailError = null;

try {
    $emailData = [
        'order_number'    => $orderNumber,
        'order_date'      => $orderDate,
        'customer_name'   => $name,
        'customer_email'  => $email,
        'customer_phone'  => $phone,
        'customer_address'=> $address,
        'delivery_date'   => $deliveryDate,
        'delivery_time'   => $deliveryTime,
        'cake_message'    => $cakeMessage,
        'payment_method'  => $payment,
        'items'           => $processedItems,
        'total_items'     => $totalItems,
        'total_amount'    => $totalAmount,
        'discount_code'   => $discountCode,
        'discount_amount' => $discountAmount
    ];

    $emailSent = sendOrderConfirmationEmail($emailData);
} catch (Throwable $e) {
    $emailError = $e->getMessage();
}

// ส่งผลลัพธ์กลับไปยัง JavaScript
echo json_encode([
    'success'         => true,
    'message'         => 'คำสั่งซื้อได้รับการบันทึกเรียบร้อยแล้ว',
    'order_number'    => $orderNumber,
    'total_amount'    => $totalAmount,
    'discount_amount' => $discountAmount,
    'email_sent'      => $emailSent,
    'email_error'     => $emailError
], JSON_UNESCAPED_UNICODE);
