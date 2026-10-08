<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

function sendSubscribeEmail($customerEmail)
{
    $mail = new PHPMailer(true);

    try {

        // ==========================================
        // SMTP
        // ==========================================

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        // Gmail ของ Grand Bake
        $mail->Username = 'hhddmg@gmail.com';

        // ใส่ App Password ใหม่ของ Gmail ตรงนี้
        $mail->Password = 'ysvmhmrweranmgxz';

        // Port 587 + STARTTLS
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->CharSet = 'UTF-8';

        // ป้องกัน SSL verify error บนสภาพแวดล้อม Windows / XAMPP Localhost
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

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

        $mail->addAddress($customerEmail);

        // ==========================================
        // HTML
        // ==========================================

        $mail->isHTML(true);

        $mail->Subject = '♡ ขอบคุณที่สมัครรับข่าวสารจาก Grand Bake';

        $safeEmail = htmlspecialchars(
            $customerEmail,
            ENT_QUOTES,
            'UTF-8'
        );

        // ==========================================
        // เนื้อหา Email
        // ==========================================

        $mail->Body = '

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Grand Bake Newsletter</title>

</head>


<body style="
    margin:0;
    padding:0;
    background:#f4ece4;
    font-family:Arial, Helvetica, sans-serif;
">

<table width="100%"
       cellpadding="0"
       cellspacing="0"
       border="0"
       style="
            margin:0;
            padding:35px 12px;
            background:#f4ece4;
       ">

<tr>
<td align="center">


<!-- ==========================================
     MAIN EMAIL
========================================== -->

<table width="680"
       cellpadding="0"
       cellspacing="0"
       border="0"
       style="
            width:100%;
            max-width:680px;
            background:#fffdfa;
            border-radius:22px;
            overflow:hidden;
       ">


<!-- ==========================================
     BRAND HEADER
========================================== -->

<tr>

<td align="center"
    style="
        padding:38px 25px 25px;
        background:#fbf4ed;
    ">

    <div style="
        font-family:Georgia, Times New Roman, serif;
        font-size:34px;
        font-weight:bold;
        letter-spacing:5px;
        color:#563a2b;
    ">
        Grand Bake
    </div>


    <div style="
        margin-top:8px;
        font-size:10px;
        letter-spacing:5px;
        color:#a88b76;
    ">
        KOREAN STYLE CAKE
    </div>


    <div style="
        width:70px;
        height:1px;
        background:#d7bca6;
        margin:18px auto 0;
    "></div>

</td>

</tr>



<!-- ==========================================
     WELCOME
========================================== -->

<tr>

<td align="center"
    style="
        padding:38px 30px 20px;
        color:#543a2b;
    ">


    <!-- ICON -->

    <div style="
        width:74px;
        height:74px;
        line-height:74px;
        margin:0 auto 20px;
        border-radius:50%;
        background:#f3e4d8;
        font-size:32px;
    ">
        💌
    </div>


    <!-- TITLE -->

    <div style="
        font-family:Georgia, Times New Roman, serif;
        font-size:33px;
        line-height:1.35;
        font-weight:bold;
        color:#513727;
    ">
        ขอบคุณที่สมัครรับข่าวสาร
    </div>


    <div style="
        margin-top:5px;
        font-family:Georgia, Times New Roman, serif;
        font-size:29px;
        color:#9b7358;
    ">
        จาก Grand Bake
    </div>


    <div style="
        width:70px;
        height:1px;
        background:#d7bca6;
        margin:18px auto 20px;
    "></div>


    <!-- MESSAGE -->

    <div style="
        font-size:17px;
        line-height:1.9;
        color:#614938;
    ">
        เรายินดีต้อนรับคุณเข้าสู่ครอบครัว Grand Bake ♡
    </div>


    <div style="
        margin-top:5px;
        font-size:15px;
        line-height:1.9;
        color:#806b5b;
    ">
        คุณจะได้รับข่าวสาร โปรโมชั่น เมนูเค้กใหม่ ๆ<br>
        และสิทธิพิเศษต่าง ๆ จากเราผ่านอีเมลของคุณ
    </div>

</td>

</tr>



<!-- ==========================================
     CUSTOMER EMAIL
========================================== -->

<tr>

<td align="center"
    style="padding:12px 30px 28px;">

    <table width="88%"
           cellpadding="0"
           cellspacing="0"
           border="0"
           style="
                background:#f8e8dd;
                border-radius:38px;
           ">

        <tr>

            <td width="60"
                align="center"
                style="
                    padding:15px 5px 15px 16px;
                    font-size:27px;
                ">
                ✉
            </td>


            <td align="left"
                style="
                    padding:14px 18px 14px 4px;
                ">

                <div style="
                    font-size:12px;
                    color:#a0826f;
                    margin-bottom:3px;
                ">
                    อีเมลที่สมัครรับข่าวสาร
                </div>


                <div style="
                    font-size:16px;
                    line-height:1.5;
                    font-weight:bold;
                    color:#5a4030;
                    word-break:break-all;
                ">
                    ' . $safeEmail . '
                </div>

            </td>

        </tr>

    </table>

</td>

</tr>



<!-- ==========================================
     BENEFITS TITLE
========================================== -->

<tr>

<td align="center"
    style="padding:0 25px 15px;">

    <div style="
        font-family:Georgia, Times New Roman, serif;
        font-size:25px;
        font-weight:bold;
        color:#563a2b;
    ">
        ✦ สิทธิพิเศษสำหรับสมาชิก ✦
    </div>

</td>

</tr>



<!-- ==========================================
     BENEFITS
========================================== -->

<tr>

<td style="
    padding:0 18px 35px;
">

<table width="100%"
       cellpadding="0"
       cellspacing="0"
       border="0">

<tr>


    <!-- ITEM 1 -->

    <td width="25%"
        align="center"
        style="
            padding:8px 6px;
            border-right:1px solid #ead9cc;
        ">

        <div style="
            width:60px;
            height:60px;
            line-height:60px;
            margin:auto;
            border-radius:50%;
            background:#f3e4d8;
            font-size:26px;
        ">
            📣
        </div>

        <div style="
            margin-top:11px;
            font-size:14px;
            font-weight:bold;
            color:#583d2d;
        ">
            อัปเดตโปรโมชั่น
        </div>

        <div style="
            margin-top:4px;
            font-size:12px;
            line-height:1.5;
            color:#775d4b;
        ">
            และส่วนลดพิเศษ
        </div>

    </td>



    <!-- ITEM 2 -->

    <td width="25%"
        align="center"
        style="
            padding:8px 6px;
            border-right:1px solid #ead9cc;
        ">

        <div style="
            width:60px;
            height:60px;
            line-height:60px;
            margin:auto;
            border-radius:50%;
            background:#f3e4d8;
            font-size:26px;
        ">
            🍰
        </div>

        <div style="
            margin-top:11px;
            font-size:14px;
            font-weight:bold;
            color:#583d2d;
        ">
            เมนูเค้กใหม่ ๆ
        </div>

        <div style="
            margin-top:4px;
            font-size:12px;
            line-height:1.5;
            color:#775d4b;
        ">
            อัปเดตก่อนใคร
        </div>

    </td>



    <!-- ITEM 3 -->

    <td width="25%"
        align="center"
        style="
            padding:8px 6px;
            border-right:1px solid #ead9cc;
        ">

        <div style="
            width:60px;
            height:60px;
            line-height:60px;
            margin:auto;
            border-radius:50%;
            background:#f3e4d8;
            font-size:26px;
        ">
            🎁
        </div>

        <div style="
            margin-top:11px;
            font-size:14px;
            font-weight:bold;
            color:#583d2d;
        ">
            สิทธิพิเศษ
        </div>

        <div style="
            margin-top:4px;
            font-size:12px;
            line-height:1.5;
            color:#775d4b;
        ">
            สำหรับสมาชิก
        </div>

    </td>



    <!-- ITEM 4 -->

    <td width="25%"
        align="center"
        style="
            padding:8px 6px;
        ">

        <div style="
            width:60px;
            height:60px;
            line-height:60px;
            margin:auto;
            border-radius:50%;
            background:#f3e4d8;
            font-size:26px;
        ">
            ♡
        </div>

        <div style="
            margin-top:11px;
            font-size:14px;
            font-weight:bold;
            color:#583d2d;
        ">
            เรื่องราวความอร่อย
        </div>

        <div style="
            margin-top:4px;
            font-size:12px;
            line-height:1.5;
            color:#775d4b;
        ">
            จาก Grand Bake
        </div>

    </td>


</tr>

</table>

</td>

</tr>



<!-- ==========================================
     SWEET NEWS + BUTTON
========================================== -->

<tr>

<td align="center"
    style="
        padding:30px 25px 38px;
        background:#faf2ea;
    ">


    <div style="
        font-family:Georgia, Times New Roman, serif;
        font-size:27px;
        font-style:italic;
        color:#674532;
    ">
        Sweet News Coming Soon ♡
    </div>


    <div style="
        margin-top:10px;
        font-size:14px;
        line-height:1.8;
        color:#7b6352;
    ">
        ติดตามข่าวสารดี ๆ และความอร่อยใหม่ ๆ<br>
        ไปด้วยกันกับ Grand Bake นะคะ ♡
    </div>


    <!-- BUTTON -->

    <div style="margin-top:24px;">

        <a href="https://tontk14.github.io/grandbake/"
           target="_blank"
           style="
                display:inline-block;
                padding:14px 32px;
                background:#684835;
                color:#ffffff;
                text-decoration:none;
                border-radius:30px;
                font-size:15px;
                font-weight:bold;
           ">
            🍰 เข้าชม Grand Bake
        </a>

    </div>


    <div style="
        margin-top:12px;
        font-size:11px;
        color:#a68e7c;
        word-break:break-all;
    ">
        tontk14.github.io/grandbake
    </div>

</td>

</tr>



<!-- ==========================================
     FOOTER
========================================== -->

<tr>

<td align="center"
    style="
        padding:30px 20px;
        background:#634632;
        color:#ffffff;
    ">

    <div style="
        font-family:Georgia, Times New Roman, serif;
        font-size:23px;
        letter-spacing:4px;
    ">
        Grand Bake
    </div>


    <div style="
        margin-top:8px;
        font-size:10px;
        letter-spacing:4px;
        color:#ead8c9;
    ">
        KOREAN STYLE CAKE
    </div>


    <div style="
        margin-top:15px;
        font-size:13px;
        color:#e8d5c6;
    ">
        Sweet Moments, Always ♡
    </div>

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
        // Plain Text
        // ==========================================

        $mail->AltBody =
            "ขอบคุณที่สมัครรับข่าวสารจาก Grand Bake\n\n" .
            "อีเมลที่สมัครรับข่าวสาร: {$customerEmail}\n\n" .
            "คุณจะได้รับข่าวสาร โปรโมชั่น เมนูเค้กใหม่ ๆ " .
            "และสิทธิพิเศษต่าง ๆ จาก Grand Bake\n\n" .
            "เข้าชม Grand Bake:\n" .
            "https://tontk14.github.io/grandbake/\n\n" .
            "Sweet Moments, Always ♡";


        // ==========================================
        // ส่ง Email
        // ==========================================

        $mail->send();

        return true;

    } catch (\Throwable $e) {
        error_log("PHPMailer subscribe error: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage());
        return false;
    }
}