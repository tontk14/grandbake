<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';

/**
 * ส่งอีเมลยืนยันคำสั่งซื้อสำเร็จไปยังลูกค้า
 *
 * @param array $orderDetails ข้อมูลคำสั่งซื้อ
 * @return bool คืนค่า true หากส่งสำเร็จ, false หากล้มเหลว
 */
function sendOrderConfirmationEmail(array $orderDetails): bool
{
    $mail = new PHPMailer(true);

    try {
        // ==========================================
        // ตั้งค่า SMTP (Gmail ของ Grand Bake)
        // ==========================================
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'hhddmg@gmail.com';
        $mail->Password   = 'ysvmhmrweranmgxz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // ==========================================
        // ผู้ส่ง
        // ==========================================
        $mail->setFrom('hhddmg@gmail.com', 'Grand Bake');

        // ==========================================
        // ผู้รับ
        // ==========================================
        $customerEmail = trim($orderDetails['customer_email'] ?? '');
        $customerName  = trim($orderDetails['customer_name'] ?? 'ลูกค้าผู้มีอุปการคุณ');

        if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $mail->addAddress($customerEmail, $customerName);

        // ==========================================
        // เตรียมข้อมูลคำสั่งซื้อ
        // ==========================================
        $orderNumber    = $orderDetails['order_number'] ?? ('GB-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)));
        $orderDate      = $orderDetails['order_date'] ?? date('d/m/Y H:i น.');
        $customerPhone  = $orderDetails['customer_phone'] ?? '-';
        $customerAddr   = $orderDetails['customer_address'] ?? '-';
        $paymentMethod  = $orderDetails['payment_method'] ?? 'cash';
        $items          = $orderDetails['items'] ?? [];
        $totalItems     = $orderDetails['total_items'] ?? 0;
        $totalAmount    = $orderDetails['total_amount'] ?? 0;

        $paymentText = ($paymentMethod === 'transfer' || $paymentMethod === 'โอนผ่านธนาคาร')
            ? 'โอนผ่านธนาคาร'
            : 'ชำระเงินปลายทาง (COD)';

        // ==========================================
        // แทรกรูปภาพแบบ Inline CID
        // ==========================================
        $hasLogo = false;
        $logoPath = __DIR__ . '/logo.jpg';
        if (file_exists($logoPath) && is_readable($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'logoImage', 'logo.jpg', PHPMailer::ENCODING_BASE64, 'image/jpeg');
            $hasLogo = true;
        }

        // แทรกรูปสินค้าแต่ละรายการ
        $itemImagesCID = [];
        foreach ($items as $index => $item) {
            $rawImg = basename($item['image'] ?? '');
            $imgPath = __DIR__ . '/' . $rawImg;
            if (!empty($rawImg) && file_exists($imgPath) && is_readable($imgPath)) {
                $cid = 'item_img_' . $index;
                $mail->addEmbeddedImage($imgPath, $cid, $rawImg);
                $itemImagesCID[$index] = 'cid:' . $cid;
            } else {
                $itemImagesCID[$index] = null;
            }
        }

        // ==========================================
        // สร้าง HTML แถวรายการสินค้า
        // ==========================================
        $itemsHtml = '';
        $itemsPlain = '';
        $calcTotal = 0;
        $calcItems = 0;

        foreach ($items as $idx => $item) {
            $name     = htmlspecialchars($item['name'] ?? 'เค้ก Grand Bake', ENT_QUOTES, 'UTF-8');
            // ทำความสะอาด <br> ในชื่อถ้ามี
            $name     = str_ireplace(['<br>', '<br/>', '<br />'], ' ', $name);
            $qty      = (int)($item['quantity'] ?? 1);
            $price    = (float)($item['price'] ?? 0);
            $subtotal = $qty * $price;
            $calcTotal += $subtotal;
            $calcItems += $qty;

            $imgSrc = $itemImagesCID[$idx] ?? '';
            $imgTag = $imgSrc
                ? '<img src="' . $imgSrc . '" alt="' . $name . '" width="54" height="54" style="width:54px; height:54px; object-fit:cover; border-radius:10px; display:block; border:1px solid #ede3dc;">'
                : '<div style="width:54px; height:54px; background:#f4ece4; border-radius:10px; text-align:center; line-height:54px; font-size:24px;">🎂</div>';

            $itemsHtml .= '
                <tr>
                    <td style="padding:14px 10px; border-bottom:1px solid #f2e9e2; vertical-align:middle; width:64px;">
                        ' . $imgTag . '
                    </td>
                    <td style="padding:14px 10px; border-bottom:1px solid #f2e9e2; vertical-align:middle;">
                        <div style="font-size:15px; font-weight:600; color:#4f3528; line-height:1.4;">' . $name . '</div>
                        <div style="font-size:13px; color:#8e7464; margin-top:3px;">฿' . number_format($price, 0) . ' × ' . $qty . ' ชิ้น</div>
                    </td>
                    <td style="padding:14px 10px; border-bottom:1px solid #f2e9e2; vertical-align:middle; text-align:right; font-size:15px; font-weight:600; color:#674b38; white-space:nowrap;">
                        ฿' . number_format($subtotal, 0) . '
                    </td>
                </tr>';

            $itemsPlain .= "- {$name} จำนวน {$qty} ชิ้น (฿" . number_format($price, 0) . "/ชิ้น) = ฿" . number_format($subtotal, 0) . "\n";
        }

        if ($totalAmount <= 0) {
            $totalAmount = $calcTotal;
        }
        if ($totalItems <= 0) {
            $totalItems = $calcItems;
        }

        // ปลอดภัยกับ XSS
        $safeCustomerName = htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8');
        $safeEmail        = htmlspecialchars($customerEmail, ENT_QUOTES, 'UTF-8');
        $safePhone        = htmlspecialchars($customerPhone, ENT_QUOTES, 'UTF-8');
        $safeAddr         = nl2br(htmlspecialchars($customerAddr, ENT_QUOTES, 'UTF-8'));
        $safeOrderNumber  = htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8');
        $safeDate         = htmlspecialchars($orderDate, ENT_QUOTES, 'UTF-8');
        $shopUrl          = 'index.php';

        // กล่องวิธีชำระเงินโอนผ่านธนาคาร
        $bankTransferBox = '';
        if ($paymentMethod === 'transfer' || $paymentMethod === 'โอนผ่านธนาคาร') {
            $bankTransferBox = '
            <div style="margin:24px 0 10px; padding:18px 20px; background:#fffbf7; border:1px dashed #cbb09d; border-radius:14px;">
                <div style="font-size:14px; font-weight:bold; color:#7a4f37; margin-bottom:8px;">
                    🏦 ข้อมูลการโอนเงิน (Bank Transfer)
                </div>
                <div style="font-size:13px; color:#6b5241; line-height:1.8;">
                    <strong>ธนาคารกสิกรไทย (KBANK)</strong><br>
                    เลขที่บัญชี: <strong style="font-size:15px; color:#674b38; letter-spacing:1px;">123-4-56789-0</strong><br>
                    ชื่อบัญชี: <strong>บจก. แกรนด์ เบค (Grand Bake Co., Ltd.)</strong><br>
                    ยอดชำระ: <strong style="color:#b25032; font-size:15px;">฿' . number_format($totalAmount, 0) . '</strong>
                </div>
                <div style="margin-top:10px; font-size:12px; color:#937a6b; background:#f4ece4; padding:8px 12px; border-radius:8px;">
                    💡 หลังจากโอนเงินเรียบร้อยแล้ว กรุณาแจ้งหลักฐานการโอนพร้อมระบุรหัสคำสั่งซื้อ <strong>' . $safeOrderNumber . '</strong> ผ่านทางอีเมลนี้ หรือ Line: @grandbake
                </div>
            </div>';
        }

        // ==========================================
        // SUBJECT & BODY
        // ==========================================
        $mail->isHTML(true);
        $mail->Subject = "🎂 ยืนยันคำสั่งซื้อสำเร็จ หมายเลข #{$orderNumber} - Grand Bake";

        $mail->Body = '
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันคำสั่งซื้อ Grand Bake</title>
</head>
<body style="margin:0; padding:0; background-color:#f4ece4; font-family:-apple-system, BlinkMacSystemFont, \'Prompt\', \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing:antialiased; color:#4f3528;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0; padding:30px 10px; background-color:#f4ece4;">
    <tr>
        <td align="center">
            <!-- Main Card Container -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:620px; background:#ffffff; border-radius:22px; overflow:hidden; box-shadow:0 12px 35px rgba(80, 52, 36, 0.08);">
                
                <!-- Header Banner -->
                <tr>
                    <td style="background:linear-gradient(135deg, #70432e 0%, #503121 100%); padding:32px 30px; text-align:center;">
                        ' . ($hasLogo ? '<img src="cid:logoImage" alt="Grand Bake" style="max-height:55px; width:auto; margin-bottom:12px; border-radius:8px; display:inline-block;">' : '') . '
                        <div style="font-family:Georgia, serif; font-size:28px; font-weight:bold; color:#ffffff; letter-spacing:1px; margin-bottom:4px;">
                            Grand Bake
                        </div>
                        <div style="font-size:11px; letter-spacing:4px; color:#dec8bb; text-transform:uppercase;">
                            Korean Style Cake & Bakery
                        </div>
                    </td>
                </tr>

                <!-- Success Badge Banner -->
                <tr>
                    <td style="background:#fdf8f4; border-bottom:1px solid #ede3dc; padding:22px 30px; text-align:center;">
                        <div style="display:inline-block; background:#e8f5e9; border:1px solid #c8e6c9; color:#2e7d32; font-size:13px; font-weight:600; padding:6px 16px; border-radius:30px; margin-bottom:10px;">
                            ✓ สั่งซื้อสินค้าสำเร็จ
                        </div>
                        <h1 style="margin:0 0 8px; font-size:24px; color:#4f3528; font-weight:700;">
                            ขอบคุณสำหรับคำสั่งซื้อของคุณ!
                        </h1>
                        <p style="margin:0; font-size:14px; color:#856b5c; line-height:1.7;">
                            สวัสดีคุณ <strong>' . $safeCustomerName . '</strong> เราได้รับรายการสั่งซื้อของคุณเรียบร้อยแล้ว<br>
                            เชฟของเรากำลังจัดเตรียมเค้กสุดพิเศษเพื่อจัดส่งถึงคุณค่ะ
                        </p>
                    </td>
                </tr>

                <!-- Content Area -->
                <tr>
                    <td style="padding:28px 30px 20px;">

                        <!-- Order Info Card -->
                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#faf4ee; border:1px solid #ebdcd0; border-radius:14px; margin-bottom:24px;">
                            <tr>
                                <td style="padding:16px 20px;">
                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <td style="font-size:13px; color:#8c7160; padding-bottom:6px;">หมายเลขคำสั่งซื้อ:</td>
                                            <td align="right" style="font-size:15px; font-weight:bold; color:#674b38; padding-bottom:6px;">
                                                #' . $safeOrderNumber . '
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:13px; color:#8c7160; padding-bottom:6px;">วันที่สั่งซื้อ:</td>
                                            <td align="right" style="font-size:13px; color:#5b4030; padding-bottom:6px;">
                                                ' . $safeDate . '
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:13px; color:#8c7160;">การชำระเงิน:</td>
                                            <td align="right" style="font-size:13px; font-weight:600; color:#5b4030;">
                                                ' . $paymentText . '
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <!-- Order Items Section -->
                        <div style="font-size:16px; font-weight:700; color:#4f3528; margin-bottom:12px; padding-bottom:6px; border-bottom:2px solid #f0e6dd;">
                            🎂 รายการที่สั่งซื้อ
                        </div>

                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:20px;">
                            ' . $itemsHtml . '
                        </table>

                        <!-- Price Summary -->
                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#fdfbf9; border-top:1px dashed #e2d4c7; padding-top:14px; margin-bottom:24px;">
                            <tr>
                                <td style="padding:5px 0; font-size:14px; color:#806b5b;">จำนวนสินค้ารวม</td>
                                <td align="right" style="padding:5px 0; font-size:14px; color:#4f3528;">' . $totalItems . ' ชิ้น</td>
                            </tr>
                            <tr>
                                <td style="padding:5px 0; font-size:14px; color:#806b5b;">ค่าจัดส่ง</td>
                                <td align="right" style="padding:5px 0; font-size:14px; color:#2e7d32; font-weight:600;">ฟรี (Free)</td>
                            </tr>
                            <tr>
                                <td style="padding:14px 0 6px; font-size:17px; font-weight:bold; color:#4f3528; border-top:1px solid #ebdcd0;">
                                    ยอดชำระสุทธิ
                                </td>
                                <td align="right" style="padding:14px 0 6px; font-size:22px; font-weight:bold; color:#8a4425; border-top:1px solid #ebdcd0;">
                                    ฿' . number_format($totalAmount, 0) . '
                                </td>
                            </tr>
                        </table>

                        ' . $bankTransferBox . '

                        <!-- Delivery Address Info -->
                        <div style="margin-top:22px; background:#ffffff; border:1px solid #ede3dc; border-radius:14px; padding:18px 20px;">
                            <div style="font-size:14px; font-weight:700; color:#5b4030; margin-bottom:10px;">
                                📍 ข้อมูลสำหรับจัดส่ง
                            </div>
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:13px; color:#6b5241; line-height:1.7;">
                                <tr>
                                    <td width="90" style="color:#9e8473; vertical-align:top;">ชื่อผู้รับ:</td>
                                    <td><strong>' . $safeCustomerName . '</strong></td>
                                </tr>
                                <tr>
                                    <td style="color:#9e8473; vertical-align:top;">เบอร์โทรศัพท์:</td>
                                    <td>' . $safePhone . '</td>
                                </tr>
                                <tr>
                                    <td style="color:#9e8473; vertical-align:top;">อีเมล:</td>
                                    <td>' . $safeEmail . '</td>
                                </tr>
                                <tr>
                                    <td style="color:#9e8473; vertical-align:top;">ที่อยู่จัดส่ง:</td>
                                    <td>' . $safeAddr . '</td>
                                </tr>
                            </table>
                        </div>

                        <!-- CTA Button -->
                        <div style="margin:30px 0 10px; text-align:center;">
                            <a href="https://tontk14.github.io/grandbake/" target="_blank" style="display:inline-block; background:#674b38; color:#ffffff; font-size:15px; font-weight:600; text-decoration:none; padding:13px 36px; border-radius:30px; box-shadow:0 6px 16px rgba(103,75,56,0.25);">
                                กลับไปที่หน้าร้าน Grand Bake
                            </a>
                        </div>

                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="background:#faf4ee; border-top:1px solid #ebdcd0; padding:24px 30px; text-align:center; font-size:12px; color:#9c8373; line-height:1.8;">
                        <p style="margin:0 0 6px; font-size:14px; color:#674b38; font-weight:600;">
                            Grand Bake — Korean Style Cake
                        </p>
                        <p style="margin:0 0 10px;">
                            หากมีข้อสงสัยเกี่ยวกับออเดอร์ กรุณาติดต่อ <a href="mailto:hhddmg@gmail.com" style="color:#8a4425; text-decoration:none;">hhddmg@gmail.com</a>
                        </p>
                        <p style="margin:0; font-size:11px; color:#b09a8c;">
                            ขอให้ทุกคำของคุณเต็มไปด้วยความอร่อยและความสุข ♡<br>
                            © Grand Bake. All Rights Reserved.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
';

        // ==========================================
        // ข้อความสำรอง (Plain Text)
        // ==========================================
        $mail->AltBody = "ยืนยันคำสั่งซื้อสำเร็จ - Grand Bake\n\n" .
            "สวัสดีคุณ {$customerName}\n" .
            "ขอบคุณสำหรับการสั่งซื้อกับ Grand Bake ทางเราได้รับรายการคำสั่งซื้อของคุณเรียบร้อยแล้ว\n\n" .
            "หมายเลขคำสั่งซื้อ: #{$orderNumber}\n" .
            "วันที่สั่งซื้อ: {$orderDate}\n" .
            "วิธีการชำระเงิน: {$paymentText}\n\n" .
            "รายการสินค้า:\n" .
            $itemsPlain . "\n" .
            "รวมจำนวน: {$totalItems} ชิ้น\n" .
            "ยอดชำระสุทธิ: ฿" . number_format($totalAmount, 0) . "\n\n" .
            "ข้อมูลจัดส่ง:\n" .
            "ชื่อ: {$customerName}\n" .
            "เบอร์โทร: {$customerPhone}\n" .
            "ที่อยู่: {$customerAddr}\n\n" .
            "หน้าร้าน: {$shopUrl}\n\n" .
            "Grand Bake ขอขอบคุณค่ะ ♡";

        // ส่งอีเมล
        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Grand Bake Order Email Error: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage());
        return false;
    }
}
