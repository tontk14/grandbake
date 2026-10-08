<?php
session_start();

/*
    ตอนนี้ใช้สำหรับออกแบบหน้า OTP ก่อน
    ภายหลังเราจะเชื่อมระบบส่ง OTP จริง
*/

$email = $_SESSION["register_email"] ?? "example@gmail.com";
?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>ยืนยันอีเมล | Grand Bake</title>


    <!-- Google Fonts -->

    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Thai:wght@400;500;600;700&family=Prompt:wght@300;400;500;600&display=swap"
          rel="stylesheet">


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            min-height: 100vh;

            font-family: "Prompt", sans-serif;

            background: #f6f0e9;

            color: #4f3b2d;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 30px 20px;

        }


        /* =====================================
           CONTAINER
        ===================================== */

        .otp-container {

            width: 100%;

            max-width: 480px;

        }


        /* =====================================
           LOGO
        ===================================== */

        .logo-area {

            text-align: center;

            margin-bottom: 22px;

        }


        .logo-area img {

            width: 72px;

            height: 72px;

            object-fit: cover;

            border-radius: 50%;

        }


        /* =====================================
           CARD
        ===================================== */

        .otp-card {

            background: #ffffff;

            border: 1px solid #eee3d8;

            border-radius: 24px;

            padding: 42px 45px;

            box-shadow:
                0 18px 50px rgba(75, 52, 36, 0.08);

            text-align: center;

        }


        /* =====================================
           ICON
        ===================================== */

        .otp-icon {

            width: 68px;

            height: 68px;

            margin: 0 auto 22px;

            border-radius: 50%;

            background: #f5e9df;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 30px;

        }


        /* =====================================
           TITLE
        ===================================== */

        .eyebrow {

            margin: 0 0 8px;

            font-size: 11px;

            letter-spacing: 3px;

            color: #ad8c6c;

        }


        .otp-card h1 {

            margin: 0;

            font-family: "Noto Serif Thai", serif;

            font-size: 28px;

            color: #503b2e;

            font-weight: 600;

        }


        .description {

            margin: 14px auto 0;

            max-width: 350px;

            font-size: 13px;

            line-height: 1.8;

            color: #94847a;

        }


        /* =====================================
           EMAIL
        ===================================== */

        .email-box {

            margin: 22px 0 28px;

            padding: 11px 16px;

            background: #faf5f0;

            border: 1px solid #eee2d8;

            border-radius: 10px;

            color: #674b38;

            font-size: 13px;

            word-break: break-word;

        }


        /* =====================================
           OTP INPUT
        ===================================== */

        .otp-inputs {

            display: flex;

            justify-content: center;

            gap: 9px;

            margin-bottom: 25px;

        }


        .otp-input {

            width: 48px;

            height: 56px;

            border: 1px solid #dccbc0;

            border-radius: 10px;

            background: #fffdfb;

            text-align: center;

            font-family: "Prompt", sans-serif;

            font-size: 22px;

            font-weight: 500;

            color: #604333;

            outline: none;

            transition: 0.2s;

        }


        .otp-input:focus {

            border-color: #8b6048;

            box-shadow:
                0 0 0 3px rgba(139, 96, 72, 0.10);

        }


        /* =====================================
           VERIFY BUTTON
        ===================================== */

        .verify-btn {

            width: 100%;

            border: none;

            border-radius: 11px;

            padding: 13px 20px;

            background: #674b38;

            color: white;

            font-family: "Prompt", sans-serif;

            font-size: 14px;

            cursor: pointer;

            transition: 0.25s;

        }


        .verify-btn:hover {

            background: #50382b;

            transform: translateY(-1px);

        }


        /* =====================================
           RESEND
        ===================================== */

        .resend-area {

            margin-top: 22px;

            font-size: 12px;

            color: #94847a;

        }


        .resend-btn {

            margin-top: 7px;

            padding: 0;

            border: none;

            background: none;

            color: #8b6048;

            font-family: "Prompt", sans-serif;

            font-size: 12px;

            cursor: pointer;

            text-decoration: underline;

        }


        .resend-btn:hover {

            color: #604333;

        }


        /* =====================================
           BACK
        ===================================== */

        .back-link {

            display: inline-block;

            margin-top: 22px;

            color: #8c7567;

            font-size: 12px;

            text-decoration: none;

            transition: 0.2s;

        }


        .back-link:hover {

            color: #604333;

        }


        /* =====================================
           SECURITY
        ===================================== */

        .security-text {

            margin-top: 25px;

            padding-top: 18px;

            border-top: 1px solid #eee5de;

            font-size: 11px;

            line-height: 1.7;

            color: #a39489;

        }


        /* =====================================
           RESPONSIVE
        ===================================== */

        @media (max-width: 500px) {

            .otp-card {

                padding: 35px 20px;

            }


            .otp-inputs {

                gap: 6px;

            }


            .otp-input {

                width: 42px;

                height: 52px;

                font-size: 20px;

            }

        }

    </style>

</head>


<body>


<div class="otp-container">


    <!-- LOGO -->

    <div class="logo-area">

        <a href="index.php">

            <img src="logo.jpg"
                 alt="Grand Bake">

        </a>

    </div>



    <!-- CARD -->

    <div class="otp-card">


        <!-- ICON -->

        <div class="otp-icon">

            ✉️

        </div>


        <!-- TITLE -->

        <p class="eyebrow">
            VERIFY YOUR EMAIL
        </p>


        <h1>
            ยืนยันอีเมลของคุณ
        </h1>


        <p class="description">

            เราได้ส่งรหัสยืนยัน 6 หลัก
            ไปยังอีเมลของคุณแล้ว
            กรุณากรอกรหัสเพื่อดำเนินการต่อ

        </p>


        <!-- EMAIL -->

        <div class="email-box">

            <?= htmlspecialchars($email) ?>

        </div>


        <!-- OTP -->

        <form action="#" method="POST">


            <div class="otp-inputs">


                <input
                    type="text"
                    class="otp-input"
                    maxlength="1"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    required
                >


                <input
                    type="text"
                    class="otp-input"
                    maxlength="1"
                    inputmode="numeric"
                    required
                >


                <input
                    type="text"
                    class="otp-input"
                    maxlength="1"
                    inputmode="numeric"
                    required
                >


                <input
                    type="text"
                    class="otp-input"
                    maxlength="1"
                    inputmode="numeric"
                    required
                >


                <input
                    type="text"
                    class="otp-input"
                    maxlength="1"
                    inputmode="numeric"
                    required
                >


                <input
                    type="text"
                    class="otp-input"
                    maxlength="1"
                    inputmode="numeric"
                    required
                >


            </div>


            <button
                type="submit"
                class="verify-btn">

                ยืนยันรหัส OTP

            </button>


        </form>


        <!-- RESEND -->

        <div class="resend-area">

            ยังไม่ได้รับรหัส?

            <br>

            <button
                type="button"
                class="resend-btn"
                onclick="resendOTP()">

                ส่งรหัสอีกครั้ง

            </button>

        </div>


        <!-- BACK -->

        <a
            href="register.php"
            class="back-link">

            ← กลับไปแก้ไขอีเมล

        </a>


        <!-- SECURITY -->

        <div class="security-text">

            🔒 รหัส OTP ใช้สำหรับยืนยันตัวตนของคุณ
            กรุณาอย่าเปิดเผยรหัสนี้ให้ผู้อื่น

        </div>


    </div>

</div>



<script>

    const inputs =
        document.querySelectorAll(".otp-input");


    inputs.forEach((input, index) => {


        input.addEventListener("input", function () {

            this.value =
                this.value.replace(/[^0-9]/g, "");


            if (
                this.value &&
                index < inputs.length - 1
            ) {

                inputs[index + 1].focus();

            }

        });


        input.addEventListener("keydown", function (event) {

            if (
                event.key === "Backspace" &&
                !this.value &&
                index > 0
            ) {

                inputs[index - 1].focus();

            }

        });


        input.addEventListener("paste", function (event) {

            event.preventDefault();

            const pasted =
                event.clipboardData
                    .getData("text")
                    .replace(/[^0-9]/g, "")
                    .slice(0, 6);


            pasted.split("").forEach((number, i) => {

                if (inputs[i]) {

                    inputs[i].value = number;

                }

            });


            if (inputs[pasted.length - 1]) {

                inputs[pasted.length - 1].focus();

            }

        });

    });


    function resendOTP() {

        alert("ระบบส่งรหัส OTP อีกครั้งจะเปิดใช้งานในขั้นตอนถัดไปครับ");

    }

</script>


</body>

</html>