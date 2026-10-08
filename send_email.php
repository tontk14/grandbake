<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/send_order_email.php';

function sendWelcomeEmail($customerEmail, $customerName)
{
    $mail = new PHPMailer(true);

    try {

        // ==========================================
        // SMTP SETTINGS
        // ==========================================

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        // Gmail ของ Grand Bake
        $mail->Username = 'hhddmg@gmail.com';

        // ใส่ App Password ใหม่ของคุณ
        $mail->Password = 'ysvmhmrweranmgxz';

        // Gmail : Port 587 + STARTTLS
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->CharSet = 'UTF-8';

        // ==========================================
        // ผู้ส่ง
        // ==========================================

        $mail->setFrom(
            'hhddmg@gmail.com',
            'Grand Bake'
        );

        // ==========================================
        // ผู้รับ
        // ==========================================

        $mail->addAddress(
            $customerEmail,
            $customerName
        );

        // ==========================================
        // รูป Welcome
        // ==========================================

        $imagePath = __DIR__ . '/email-welcome.png';

        if (!file_exists($imagePath)) {
            throw new Exception(
                'ไม่พบไฟล์ email-welcome.png'
            );
        }

        if (!is_readable($imagePath)) {
            throw new Exception(
                'ไม่สามารถอ่านไฟล์ email-welcome.png ได้'
            );
        }

        $mail->addEmbeddedImage(
            $imagePath,
            'welcomeImage',
            'email-welcome.png',
            PHPMailer::ENCODING_BASE64,
            'image/png',
            'inline'
        );

        // ==========================================
        // HTML EMAIL
        // ==========================================

        $mail->isHTML(true);

        $mail->Subject =
            '🎂 ยินดีต้อนรับสู่ Grand Bake';

        $safeName = htmlspecialchars(
            $customerName,
            ENT_QUOTES,
            'UTF-8'
        );

        $safeEmail = htmlspecialchars(
            $customerEmail,
            ENT_QUOTES,
            'UTF-8'
        );

        // ==========================================
        // GitHub Pages
        // ==========================================

        $shopUrl = 'https://tontk14.github.io/grandbake/index.html';

        // ==========================================
        // EMAIL BODY
        // ==========================================

        $mail->Body = '

<!DOCTYPE html>

<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>ยินดีต้อนรับสู่ Grand Bake</title>

</head>

<body style="
    margin:0;
    padding:0;
    background:#f5eee6;
    font-family:Arial, Helvetica, sans-serif;
">

<div style="
    width:100%;
    padding:30px 0;
    background:#f5eee6;
">

    <div style="
        width:100%;
        max-width:650px;
        margin:0 auto;
        background:#ffffff;
        border-radius:20px;
        overflow:hidden;
    ">


        <!-- =====================================
             WELCOME IMAGE
        ====================================== -->

        <div style="
            width:100%;
            margin:0;
            padding:0;
            background:#f8f0e8;
        ">

            <img
                src="cid:welcomeImage"
                alt="Grand Bake Welcome"
                width="650"
                style="
                    display:block;
                    width:100%;
                    max-width:650px;
                    height:auto;
                    margin:0 auto;
                    border:0;
                    outline:none;
                    text-decoration:none;
                "
            >

        </div>


        <!-- =====================================
             TEXT CONTENT
        ====================================== -->

        <div style="
            padding:35px;
            text-align:center;
            color:#5b4030;
        ">


            <p style="
                margin:0 0 8px;
                font-size:16px;
                color:#8b7160;
            ">
                สวัสดีคุณ
            </p>


            <h2 style="
                margin:0 0 20px;
                font-size:28px;
                color:#5b4030;
            ">
                ' . $safeName . '
            </h2>


            <p style="
                margin:0 0 18px;
                font-size:17px;
                line-height:1.9;
                color:#6b5241;
            ">
                ขอบคุณที่สมัครสมาชิกกับ
                <strong>Grand Bake</strong>
            </p>


            <p style="
                margin:0 0 22px;
                font-size:15px;
                line-height:1.9;
                color:#806b5b;
            ">
                ตอนนี้คุณได้เป็นส่วนหนึ่งของครอบครัว
                Grand Bake แล้ว
                <br>
                เรายินดีเป็นอย่างยิ่งที่ได้ต้อนรับคุณ
            </p>


            <!-- =====================================
                 CUSTOMER EMAIL
            ====================================== -->

            <div style="
                max-width:430px;
                margin:25px auto;
                padding:15px 20px;
                background:#faf3ed;
                border:1px solid #eadbce;
                border-radius:12px;
            ">

                <p style="
                    margin:0 0 6px;
                    font-size:12px;
                    color:#a18875;
                ">
                    อีเมลสมาชิก
                </p>


                <p style="
                    margin:0;
                    font-size:15px;
                    font-weight:bold;
                    color:#674b38;
                    word-break:break-word;
                ">
                    ' . $safeEmail . '
                </p>

            </div>


            <!-- =====================================
                 SHOP BUTTON
            ====================================== -->

            <div style="
                margin:30px 0;
                text-align:center;
            ">

                <a
                    href="' . $shopUrl . '"
                    target="_blank"
                    style="
                        display:inline-block;
                        padding:14px 30px;
                        background:#7d4930;
                        color:#ffffff;
                        text-decoration:none;
                        border-radius:30px;
                        font-size:15px;
                        font-weight:bold;
                    "
                >
                    🍰 เข้าสู่หน้าร้าน Grand Bake
                </a>

            </div>


            <p style="
                margin:25px 0 0;
                font-size:14px;
                line-height:1.9;
                color:#968276;
            ">
                ขอให้ทุกช่วงเวลาของคุณ
                <br>
                เต็มไปด้วยความอร่อยและความสุข ♡
            </p>


            <!-- =====================================
                 FOOTER
            ====================================== -->

            <div style="
                margin-top:30px;
                padding-top:22px;
                border-top:1px solid #eee2d8;
            ">

                <p style="
                    margin:0;
                    font-size:14px;
                    color:#8c7462;
                ">
                    ด้วยความห่วงใยจาก
                </p>


                <p style="
                    margin:8px 0 0;
                    font-size:21px;
                    font-weight:bold;
                    color:#5b4030;
                ">
                    Grand Bake
                </p>


                <p style="
                    margin:6px 0 0;
                    font-size:10px;
                    letter-spacing:3px;
                    color:#aa907c;
                ">
                    KOREAN STYLE CAKE
                </p>


                <p style="
                    margin:15px 0 0;
                    font-size:18px;
                ">
                    ♡
                </p>

            </div>

        </div>

    </div>

</div>

</body>

</html>

        ';


        // ==========================================
        // PLAIN TEXT
        // ==========================================

        $mail->AltBody =
            "ยินดีต้อนรับสู่ Grand Bake\n\n" .
            "สวัสดีคุณ {$customerName}\n\n" .
            "ขอบคุณที่สมัครสมาชิกกับ Grand Bake\n" .
            "ตอนนี้คุณได้เป็นส่วนหนึ่งของครอบครัว Grand Bake แล้ว\n\n" .
            "อีเมลสมาชิก: {$customerEmail}\n\n" .
            "เข้าหน้าร้าน Grand Bake:\n" .
            $shopUrl . "\n\n" .
            "ขอให้ทุกช่วงเวลาของคุณเต็มไปด้วยความอร่อยและความสุข\n\n" .
            "ด้วยความห่วงใยจาก\n" .
            "Grand Bake";


        // ==========================================
        // SEND EMAIL
        // ==========================================

        $mail->send();

        return true;

    } catch (Exception $e) {

        return false;
    }
}