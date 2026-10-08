<?php
session_start();
require_once "db.php";

$orderNumber = trim($_GET["order"] ?? "");
$order = null;
$orderItems = [];

// ตรวจสอบและสร้างคอลัมน์ slip_image ใน orders หากยังไม่มี
$checkCol = $conn->query("SHOW COLUMNS FROM orders LIKE 'slip_image'");
if ($checkCol && $checkCol->num_rows === 0) {
    $conn->query("ALTER TABLE orders ADD COLUMN slip_image VARCHAR(255) NULL AFTER payment_method");
}

$slipMessage = "";
$slipType = "";

// จัดการการอัปโหลดสลิปโอนเงิน
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["upload_slip"]) && !empty($orderNumber)) {
    if (isset($_FILES["slip_file"]) && $_FILES["slip_file"]["error"] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES["slip_file"]["tmp_name"];
        $fileName = $_FILES["slip_file"]["name"];
        $fileSize = $_FILES["slip_file"]["size"];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            if ($fileSize <= 6 * 1024 * 1024) { // max 6MB
                $uploadDir = __DIR__ . '/uploads/slips/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $cleanOrderNum = preg_replace('/[^a-zA-Z0-9_-]/', '', $orderNumber);
                $newFileName = 'slip_' . $cleanOrderNum . '_' . time() . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $relativeSlipPath = 'uploads/slips/' . $newFileName;
                    $upStmt = $conn->prepare("UPDATE orders SET slip_image = ? WHERE order_number = ?");
                    $upStmt->bind_param("ss", $relativeSlipPath, $orderNumber);
                    $upStmt->execute();
                    $upStmt->close();

                    $slipMessage = "อัปโหลดสลิปสำเร็จเรียบร้อย! ทางร้านได้รับหลักฐานแล้วและกำลังตรวจสอบ";
                    $slipType = "success";
                } else {
                    $slipMessage = "เกิดข้อผิดพลาดในการบันทึกไฟล์ กรุณาลองใหม่อีกครั้ง";
                    $slipType = "error";
                }
            } else {
                $slipMessage = "ขนาดไฟล์รูปภาพต้องไม่เกิน 6 MB";
                $slipType = "error";
            }
        } else {
            $slipMessage = "กรุณาอัปโหลดไฟล์รูปภาพ (JPG, PNG หรือ WebP) เท่านั้น";
            $slipType = "error";
        }
    } else {
        $slipMessage = "กรุณาเลือกรูปภาพสลิปก่อนกดยืนยัน";
        $slipType = "error";
    }
}

// ดึงข้อมูลคำสั่งซื้อ
if (!empty($orderNumber)) {
    $stmt = $conn->prepare("
        SELECT id, order_number, customer_name, customer_email, customer_phone, customer_address, 
               payment_method, slip_image, total_amount, total_items, status, created_at
        FROM orders
        WHERE order_number = ?
    ");
    $stmt->bind_param("s", $orderNumber);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $order = $res->fetch_assoc();
        $orderId = $order['id'];

        $itemStmt = $conn->prepare("
            SELECT product_name, price, quantity, subtotal, image
            FROM order_items
            WHERE order_id = ?
        ");
        $itemStmt->bind_param("i", $orderId);
        $itemStmt->execute();
        $orderItems = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $itemStmt->close();
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สั่งซื้อสำเร็จ & ชำระเงิน | Grand Bake</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Thai:wght@400;500;600;700&family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: "Prompt", sans-serif; background: #fcf8f4; color: #4f3528; }
        a { text-decoration: none; color: inherit; }
        button, input { font-family: inherit; }

        .navbar {
            width: 100%; height: 78px; background: #fff;
            border-bottom: 1px solid #eadfd7;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 7%;
        }
        .logo img { width: 125px; height: auto; display: block; }
        .menu { display: flex; align-items: center; gap: 28px; }
        .menu a { font-size: 15px; color: #604638; transition: 0.2s; }
        .menu a:hover { color: #9a5b3c; }

        .container {
            width: 100%; max-width: 800px;
            margin: 40px auto 80px; padding: 0 20px;
        }
        .success-card {
            background: #ffffff; border-radius: 26px;
            border: 1px solid #ede3dc;
            box-shadow: 0 14px 45px rgba(85, 55, 38, 0.08);
            padding: 42px 35px; text-align: center;
        }
        .badge-icon {
            width: 80px; height: 80px; margin: 0 auto 18px;
            background: #eef8ee; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 40px; color: #2e7d32;
            border: 2px solid #ccebcc;
        }
        h1 {
            font-family: "Noto Serif Thai", serif;
            font-size: 32px; color: #4b3022; margin-bottom: 8px;
        }
        .subtitle {
            font-size: 15px; color: #856b5b; line-height: 1.7; margin-bottom: 24px;
        }
        .email-notice {
            background: #faf2eb; border: 1px dashed #d5baaa;
            border-radius: 14px; padding: 13px 20px;
            font-size: 14px; color: #694a37; margin-bottom: 28px;
            display: inline-block; max-width: 95%;
        }

        /* ALERT */
        .slip-alert {
            padding: 12px 18px; border-radius: 14px; font-size: 14px;
            margin-bottom: 22px; text-align: left;
            display: flex; align-items: center; gap: 10px;
        }
        .slip-alert-success { background: #eaf6ee; border: 1px solid #bce2c7; color: #276b3a; }
        .slip-alert-error { background: #fdeeee; border: 1px solid #f6c8c8; color: #aa2c2c; }

        /* PAYMENT & QR SECTION */
        .payment-box {
            background: #fffcf9;
            border: 2px solid #ebdcd0;
            border-radius: 20px;
            padding: 26px;
            margin-bottom: 28px;
            text-align: center;
        }
        .payment-box-title {
            font-family: "Noto Serif Thai", serif;
            font-size: 20px;
            color: #553421;
            margin-bottom: 6px;
        }
        .payment-box-desc {
            font-size: 13.5px;
            color: #8c7668;
            margin-bottom: 20px;
        }

        .qr-wrapper {
            background: white;
            padding: 16px;
            border-radius: 18px;
            display: inline-block;
            box-shadow: 0 6px 20px rgba(80, 50, 30, 0.08);
            border: 1px solid #eee4db;
            margin-bottom: 18px;
        }
        .qr-wrapper img {
            width: 220px;
            height: 220px;
            display: block;
            border-radius: 10px;
        }
        .qr-amount {
            font-size: 24px;
            font-weight: 700;
            color: #84472b;
            margin: 6px 0 16px;
        }

        .bank-details-card {
            background: #fbf5ee;
            border-radius: 14px;
            padding: 16px 20px;
            max-width: 440px;
            margin: 0 auto 24px;
            text-align: left;
            border: 1px solid #ead8c8;
        }
        .bank-row {
            display: flex;
            justify-content: space-between;
            font-size: 13.5px;
            padding: 4px 0;
            color: #553a2a;
        }
        .bank-row strong {
            color: #794a32;
        }

        /* SLIP UPLOAD FORM */
        .slip-upload-area {
            border-top: 1px dashed #e5d4c5;
            padding-top: 22px;
            margin-top: 15px;
        }
        .slip-upload-area h3 {
            font-size: 16px;
            color: #543727;
            margin-bottom: 8px;
        }
        .file-choose-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 22px;
            background: #fff;
            border: 1.5px dashed #cbb6a5;
            border-radius: 14px;
            cursor: pointer;
            font-size: 14px;
            color: #704732;
            transition: 0.2s;
            margin-bottom: 14px;
        }
        .file-choose-label:hover {
            border-color: #7c4c33;
            background: #fdfaf7;
        }
        .btn-upload-submit {
            padding: 12px 28px;
            border-radius: 25px;
            background: #754730;
            color: white;
            border: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
            box-shadow: 0 4px 14px rgba(117, 71, 48, 0.2);
        }
        .btn-upload-submit:hover {
            background: #5d3420;
            transform: translateY(-1px);
        }

        .slip-preview-box {
            margin-top: 15px;
        }
        .slip-preview-img {
            max-width: 220px;
            max-height: 280px;
            border-radius: 12px;
            border: 2px solid #ebdcd0;
            box-shadow: 0 6px 18px rgba(70, 50, 30, 0.08);
            object-fit: cover;
            display: inline-block;
            margin-top: 8px;
        }
        .slip-success-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: 20px;
            background: #eaf7ed;
            color: #2e7d32;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        /* ORDER DETAILS BOX */
        .order-box {
            text-align: left; background: #fdfbf9;
            border: 1px solid #eee4dd; border-radius: 18px;
            padding: 24px 26px; margin-bottom: 25px;
        }
        .order-header-line {
            display: flex; justify-content: space-between; align-items: center;
            padding-bottom: 14px; border-bottom: 1px solid #ebdcd0;
            margin-bottom: 16px;
        }
        .order-num { font-size: 18px; font-weight: 700; color: #674b38; }
        .order-date { font-size: 13px; color: #9c8373; }
        .info-row {
            display: flex; margin-bottom: 8px; font-size: 14px; line-height: 1.6;
        }
        .info-label { width: 120px; color: #977d6c; flex-shrink: 0; }
        .info-val { color: #513627; font-weight: 500; }
        .items-table {
            width: 100%; border-collapse: collapse; margin-top: 15px;
        }
        .items-table th, .items-table td {
            padding: 11px 8px; font-size: 14px; border-bottom: 1px solid #f4eae1;
        }
        .items-table th { text-align: left; color: #977d6c; font-weight: 500; }
        .total-highlight {
            font-size: 21px; font-weight: 700; color: #8a4425;
        }
        .actions {
            display: flex; justify-content: center; gap: 15px; margin-top: 30px;
            flex-wrap: wrap;
        }
        .btn-home {
            background: #674b38; color: white; padding: 13px 30px;
            border-radius: 30px; font-size: 14.5px; font-weight: 500;
            transition: 0.2s;
        }
        .btn-home:hover { background: #50382b; }
        .btn-shop {
            background: #f4ece4; color: #674b38; padding: 13px 28px;
            border-radius: 30px; font-size: 14.5px; font-weight: 500;
            transition: 0.2s;
        }
        .btn-shop:hover { background: #ebdcd0; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">
        <a href="index.php">
            <img src="logo.jpg" alt="Grand Bake">
        </a>
    </div>
    <div class="menu">
        <a href="index.php">หน้าแรก</a>
        <a href="products.php">สินค้า</a>
        <a href="profile.php">บัญชีของฉัน</a>
        <a href="cart.html">🛒 ตะกร้า</a>
    </div>
</nav>

<div class="container">
    <div class="success-card">
        <div class="badge-icon">✓</div>
        <h1>สั่งซื้อสินค้าสำเร็จ!</h1>
        <p class="subtitle">
            ขอบคุณที่เลือกความอร่อยจาก Grand Bake เราได้รับคำสั่งซื้อเรียบร้อยแล้วและกำลังเตรียมส่งมอบความหวานละมุนถึงคุณ
        </p>

        <?php if (!empty($slipMessage)): ?>
            <div class="slip-alert slip-alert-<?= htmlspecialchars($slipType) ?>">
                <span><?= $slipType === 'success' ? '✓' : '⚠️' ?></span>
                <span><?= htmlspecialchars($slipMessage) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($order): 
            $isTransfer = (strtolower($order['payment_method'] ?? '') === 'transfer' || strtolower($order['payment_method'] ?? '') === 'bank');
        ?>
            <div class="email-notice">
                ✉️ เราได้ส่งอีเมลยืนยันคำสั่งซื้อไปยัง <strong><?= htmlspecialchars($order['customer_email']) ?></strong> เรียบร้อยแล้ว
            </div>

            <!-- PROMPTPAY & SLIP UPLOAD (ถ้าเลือกโอนเงิน) -->
            <?php if ($isTransfer): ?>
                <div class="payment-box">
                    <h2 class="payment-box-title">ชำระเงินผ่าน QR พร้อมเพย์ / บัญชีธนาคาร</h2>
                    <p class="payment-box-desc">สแกน QR Code เพื่อชำระเงิน หรือโอนผ่านเลขบัญชีด้านล่าง แล้วแนบสลิปยืนยัน</p>

                    <div class="qr-wrapper">
                        <!-- QR Code Generator ด้วยเบอร์พร้อมเพย์ของร้าน -->
                        <img src="https://promptpay.io/0891234567/<?= number_format($order['total_amount'], 2, '.', '') ?>.png" 
                             alt="PromptPay QR Code" 
                             onerror="this.src='https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=0891234567'">
                    </div>

                    <div class="qr-amount">
                        ยอดที่ต้องชำระ: ฿<?= number_format($order['total_amount'], 2) ?>
                    </div>

                    <div class="bank-details-card">
                        <div class="bank-row">
                            <span>ธนาคาร:</span>
                            <strong>กสิกรไทย (KBANK)</strong>
                        </div>
                        <div class="bank-row">
                            <span>เลขที่บัญชี:</span>
                            <strong>123-2-34567-8</strong>
                        </div>
                        <div class="bank-row">
                            <span>ชื่อบัญชี:</span>
                            <strong>ร้านแกรนด์ เบค (Grand Bake)</strong>
                        </div>
                        <div class="bank-row">
                            <span>พร้อมเพย์ (PromptPay):</span>
                            <strong>089-123-4567</strong>
                        </div>
                    </div>

                    <!-- ฟอร์มแนบสลิป -->
                    <div class="slip-upload-area">
                        <?php if (!empty($order['slip_image'])): ?>
                            <div class="slip-success-badge">
                                ✓ ได้รับสลิปเรียบร้อยแล้ว (ทางร้านกำลังตรวจสอบ)
                            </div>
                            <div class="slip-preview-box">
                                <a href="<?= htmlspecialchars($order['slip_image']) ?>" target="_blank" title="คลิกเพื่อดูรูปขนาดเต็ม">
                                    <img src="<?= htmlspecialchars($order['slip_image']) ?>" alt="สลิปการโอนเงิน" class="slip-preview-img">
                                </a>
                                <p style="font-size: 12.5px; color: #8c7668; margin-top: 8px;">
                                    (หากต้องการเปลี่ยนสลิป สามารถอัปโหลดใหม่ด้านล่างได้ครับ)
                                </p>
                            </div>
                        <?php else: ?>
                            <h3>📎 แนบสลิปหลักฐานการโอนเงิน</h3>
                            <p style="font-size: 13px; color: #8c7668; margin-bottom: 12px;">
                                เพื่อให้เจ้าหน้าที่เร่งตรวจสอบและดำเนินการจัดทำเค้กให้รวดเร็วยิ่งขึ้น
                            </p>
                        <?php endif; ?>

                        <form method="POST" action="order_success.php?order=<?= urlencode($orderNumber) ?>" enctype="multipart/form-data" style="margin-top: 12px;">
                            <input type="hidden" name="upload_slip" value="1">
                            
                            <label class="file-choose-label">
                                <span>📷 เลือกรูปสลิป</span>
                                <input type="file" name="slip_file" accept="image/*" required style="display:none;" onchange="previewSlip(this)">
                            </label>
                            
                            <div id="chosenFileName" style="font-size: 13px; color: #79523c; margin-bottom: 12px; display: none;"></div>

                            <button type="submit" class="btn-upload-submit">
                                📤 อัปโหลดสลิปยืนยันการโอนเงิน
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- รายละเอียดคำสั่งซื้อ -->
            <div class="order-box">
                <div class="order-header-line">
                    <span class="order-num">#<?= htmlspecialchars($order['order_number']) ?></span>
                    <span class="order-date"><?= date('d/m/Y H:i น.', strtotime($order['created_at'])) ?></span>
                </div>

                <div class="info-row">
                    <div class="info-label">ผู้รับ:</div>
                    <div class="info-val"><?= htmlspecialchars($order['customer_name']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">เบอร์โทรศัพท์:</div>
                    <div class="info-val"><?= htmlspecialchars($order['customer_phone']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">ที่อยู่จัดส่ง:</div>
                    <div class="info-val"><?= nl2br(htmlspecialchars($order['customer_address'])) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">การชำระเงิน:</div>
                    <div class="info-val">
                        <?= ($isTransfer ? 'โอนผ่านธนาคาร / พร้อมเพย์' : 'ชำระเงินปลายทาง') ?>
                    </div>
                </div>

                <?php if (!empty($order['delivery_date'])): ?>
                    <div class="info-row">
                        <div class="info-label">เวลานัดรับเค้ก:</div>
                        <div class="info-val" style="color: #8c4c2d; font-weight: 600;">
                            📅 <?= htmlspecialchars($order['delivery_date']) ?> (<?= htmlspecialchars($order['delivery_time'] ?? '-') ?>)
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($order['cake_message'])): ?>
                    <div class="info-row">
                        <div class="info-label">ข้อความหน้าเค้ก:</div>
                        <div class="info-val" style="color: #6a3d9a; font-weight: 500;">
                            🎂 "<?= htmlspecialchars($order['cake_message']) ?>"
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($orderItems)): ?>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>รายการสินค้า</th>
                                <th style="text-align:center;">จำนวน</th>
                                <th style="text-align:right;">ราคา</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderItems as $it): ?>
                                <tr>
                                    <td><?= htmlspecialchars($it['product_name']) ?></td>
                                    <td style="text-align:center;"><?= (int)$it['quantity'] ?></td>
                                    <td style="text-align:right;">฿<?= number_format($it['subtotal'], 0) ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (!empty($order['discount_amount']) && (float)$order['discount_amount'] > 0): ?>
                                <tr>
                                    <td colspan="2" style="color:#b73824; font-weight:500; padding-top:8px;">
                                        ส่วนลดโปรโมชั่น (<?= htmlspecialchars($order['discount_code'] ?? '') ?>)
                                    </td>
                                    <td style="text-align:right; color:#b73824; font-weight:600; padding-top:8px;">
                                        -฿<?= number_format($order['discount_amount'], 0) ?>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <tr>
                                <td colspan="2" style="font-weight:700; padding-top:14px;">ยอดชำระสุทธิ (จัดส่งฟรี)</td>
                                <td style="text-align:right; padding-top:14px;" class="total-highlight">
                                    ฿<?= number_format($order['total_amount'], 0) ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="email-notice">
                ✉️ ระบบได้จัดส่งอีเมลยืนยันคำสั่งซื้อไปยังอีเมลของคุณเรียบร้อยแล้ว
            </div>
        <?php endif; ?>

        <div class="actions">
            <a href="profile.php#orders" class="btn-home">ดูประวัติการสั่งซื้อของฉัน</a>
            <a href="products.php" class="btn-shop">เลือกซื้อเค้กเพิ่ม</a>
        </div>
    </div>
</div>

<script>
    function previewSlip(input) {
        const display = document.getElementById('chosenFileName');
        if (input.files && input.files[0]) {
            display.textContent = 'ไฟล์ที่เลือก: ' + input.files[0].name;
            display.style.display = 'block';
        } else {
            display.style.display = 'none';
        }
    }
</script>
<script src="cart-badge.js"></script>

</body>
</html>
