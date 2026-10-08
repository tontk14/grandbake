<?php

use PHPMailer\PHPMailer\PHPMailer;

require __DIR__ . '/vendor/autoload.php';

$mail = new PHPMailer(true);

try {

    // SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;

    // Gmail ของ Grand Bake
    $mail->Username = 'hhddmg@gmail.com';

    // App Password ใหม่
    $mail->Password = 'ysvmhmrweranmgxz';

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->CharSet = 'UTF-8';

    // ผู้ส่ง
    $mail->setFrom(
        'hhddmg@gmail.com',
        'Grand Bake'
    );

    // ==================================================
    // ใส่อีเมลที่จะใช้ทดสอบรับเมลตรงนี้
    // ==================================================
    $mail->addAddress(
        'hhddmg@gmail.com',
        'ทดสอบ Grand Bake'
    );

    $mail->isHTML(true);

    $mail->Subject = '🎂 ทดสอบระบบ Email - Grand Bake';

    $mail->Body = '
    <div style="
        max-width:600px;
        margin:auto;
        padding:30px;
        background:#fffaf5;
        font-family:Arial,sans-serif;
        color:#5b4030;
        text-align:center;
    ">

        <h1>Grand Bake</h1>

        <p style="letter-spacing:3px;">
            KOREAN STYLE CAKE
        </p>

        <div style="
            font-size:50px;
            margin:20px;
        ">
            🎂
        </div>

        <h2>ระบบส่งอีเมลทำงานแล้ว!</h2>

        <p style="font-size:16px;line-height:1.8;">
            นี่คืออีเมลทดสอบจากระบบ Grand Bake
            <br>
            หากคุณได้รับอีเมลนี้ แสดงว่า PHPMailer
            เชื่อมต่อกับ Gmail SMTP สำเร็จแล้ว
        </p>

        <p>
            ด้วยความห่วงใยจาก
            <br>
            <strong>Grand Bake</strong>
        </p>

    </div>
    ';

    $mail->AltBody =
        "ระบบส่งอีเมล Grand Bake ทำงานแล้ว";

    $mail->send();

    echo "ส่งอีเมลสำเร็จ ✅";

} catch (Exception $e) {

    echo "ส่งอีเมลไม่สำเร็จ ❌<br>";
    echo "Error: " . $mail->ErrorInfo;
}