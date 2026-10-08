<?php
require_once "db.php";
require_once "send_subscribe_email.php";

$conn->query("
    CREATE TABLE IF NOT EXISTS subscribers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(150) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$message = "";
$message_type = "";
$redirectMessage = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");

    // ==========================================
    // ตรวจสอบ Email
    // ==========================================

    if ($email === "") {
        header("Location: index.php?subscribe=empty#contact");
        exit;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: index.php?subscribe=invalid#contact");
        exit;
    } else {
        // ==========================================
        // ตรวจสอบว่าเคย Subscribe แล้วหรือยัง
        // ==========================================
        $check = $conn->prepare(
            "SELECT id FROM subscribers WHERE email = ?"
        );
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            // หากมีอีเมลในระบบแล้ว ส่งอีเมลยืนยันซ้ำอีกครั้ง (เพื่อให้ผู้ใช้ทดสอบหรือได้รับเมลยืนยัน)
            $emailSent = sendSubscribeEmail($email);
            if ($emailSent) {
                header("Location: index.php?subscribe=resend_success#contact");
            } else {
                header("Location: index.php?subscribe=exists#contact");
            }
            exit;
        } else {
            // ==========================================
            // บันทึก Email ลงฐานข้อมูล
            // ==========================================
            $stmt = $conn->prepare(
                "INSERT INTO subscribers (email) VALUES (?)"
            );
            $stmt->bind_param("s", $email);

            if ($stmt->execute()) {
                // ==========================================
                // ส่ง Email แจ้งเตือน
                // ==========================================
                $emailSent = sendSubscribeEmail($email);

                if ($emailSent) {
                    header("Location: index.php?subscribe=success#contact");
                    exit;
                } else {
                    header("Location: index.php?subscribe=email_error#contact");
                    exit;
                }
            } else {
                $message = "เกิดข้อผิดพลาด กรุณาลองใหม่";
                $message_type = "error";
            }
            $stmt->close();
        }
        $check->close();
    }
}

?>

<!DOCTYPE html>

<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Subscribe | Grand Bake</title>

    <style>

        body {
            margin: 0;
            min-height: 100vh;

            display: flex;
            justify-content: center;
            align-items: center;

            font-family: Arial, sans-serif;

            background: #f5eee6;
            color: #5b4030;
        }

        .subscribe-card {

            width: 90%;
            max-width: 520px;

            padding: 45px;

            background: white;

            border-radius: 24px;

            box-shadow:
                0 20px 60px rgba(75,52,36,0.10);

            text-align: center;
        }

        .brand {

            font-family: Georgia, serif;

            font-size: 36px;

            font-weight: bold;

            margin-bottom: 5px;
        }

        .subtitle {

            font-size: 11px;

            letter-spacing: 4px;

            color: #a78973;

            margin-bottom: 30px;
        }

        h2 {

            font-size: 28px;

            margin-bottom: 12px;
        }

        .description {

            color: #806b5b;

            line-height: 1.8;

            font-size: 15px;

            margin-bottom: 25px;
        }

        .message {

            padding: 12px 15px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-size: 14px;

            line-height: 1.6;
        }

        .message.success {

            background: #edf7ee;

            border: 1px solid #d5ead8;

            color: #47704b;
        }

        .message.error {

            background: #fff0ee;

            border: 1px solid #f1d4d0;

            color: #a24f47;
        }

        input[type="email"] {

            width: 100%;

            padding: 14px;

            box-sizing: border-box;

            border-radius: 12px;

            border: 1px solid #e3d9cf;

            background: #fdfbf9;

            font-size: 15px;

            outline: none;
        }

        input[type="email"]:focus {

            border-color: #a88a6c;

            box-shadow:
                0 0 0 4px rgba(168,138,108,0.10);
        }

        button {

            width: 100%;

            margin-top: 14px;

            padding: 14px;

            border: none;

            border-radius: 12px;

            background: #674b38;

            color: white;

            font-size: 15px;

            cursor: pointer;
        }

        button:hover {

            background: #50382b;
        }

        .back {

            display: block;

            margin-top: 20px;

            color: #977d69;

            text-decoration: none;

            font-size: 13px;
        }

        .back:hover {

            color: #674b38;
        }

    </style>

</head>

<body>

    <div class="subscribe-card">

        <div class="brand">
            Grand Bake
        </div>

        <div class="subtitle">
            KOREAN STYLE CAKE
        </div>

        <h2>
            รับข่าวสารจาก Grand Bake
        </h2>

        <p class="description">
            สมัครรับข่าวสาร โปรโมชั่น
            เมนูเค้กใหม่ และสิทธิพิเศษ
            ส่งตรงถึงอีเมลของคุณ
        </p>

        <?php if ($message !== ""): ?>

            <div class="message <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>

        <?php endif; ?>


        <form
            action="subscribe.php"
            method="POST"
        >

            <input
                type="email"
                name="email"
                placeholder="กรอกอีเมลของคุณ"
                required
            >

            <button type="submit">
                สมัครรับข่าวสาร
            </button>

        </form>


        <a
            href="index.html"
            class="back"
        >
            ← กลับหน้าแรก
        </a>

    </div>

</body>

</html>