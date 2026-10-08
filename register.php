    <?php
    
    require_once "db.php";
    require_once "send_email.php";

    // สร้างตาราง users หากยังไม่มี
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

    $message = "";
    $message_type = "";

    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $first_name = trim($_POST["first_name"] ?? "");
        $last_name = trim($_POST["last_name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $address = trim($_POST["address"] ?? "");
        $password = $_POST["password"] ?? "";
        $confirm_password = $_POST["confirm_password"] ?? "";
        $consent = $_POST["consent"] ?? "";

        if (
            $first_name === "" ||
            $last_name === "" ||
            $email === "" ||
            $phone === "" ||
            $password === "" ||
            $confirm_password === ""
        ) {

            $message = "กรุณากรอกข้อมูลให้ครบถ้วน";
            $message_type = "error";
            
        } elseif ($consent !== "1") {

             $message = "กรุณายอมรับข้อกำหนดการใช้งานและนโยบายความเป็นส่วนตัว";
             $message_type = "error";

        }


         elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $message = "กรุณากรอกอีเมลให้ถูกต้อง";
            $message_type = "error";

        } elseif (strlen($password) < 8) {

            $message = "รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร";
            $message_type = "error";

        } elseif ($password !== $confirm_password) {

            $message = "รหัสผ่านไม่ตรงกัน";
            $message_type = "error";

        } else {

            $check = $conn->prepare(
                "SELECT id FROM users WHERE email = ?"
            );

            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {

                $message = "อีเมลนี้ถูกใช้งานแล้ว";
                $message_type = "error";

            } else {

                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $conn->prepare(
                    "INSERT INTO users
                    (first_name, last_name, email, phone, password, address)
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "ssssss",
                    $first_name,
                    $last_name,
                    $email,
                    $phone,
                    $hashed_password,
                    $address
                );

               if ($stmt->execute()) {

    // ส่ง Email แจ้งเตือนลูกค้า
    $emailSent = sendWelcomeEmail(
        $email,
        $first_name . " " . $last_name
    );

    if ($emailSent) {

        $message = "สมัครสมาชิกสำเร็จ! เราได้ส่งอีเมลยืนยันการสมัครสมาชิกไปที่ " . $email;
        $message_type = "success";

    } else {

        $message = "สมัครสมาชิกสำเร็จ แต่ไม่สามารถส่งอีเมลแจ้งเตือนได้";
        $message_type = "error";

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

    <title>สมัครสมาชิก | Grand Bake</title>


    <!-- Google Font -->

    <link rel="preconnect"
        href="https://fonts.googleapis.com">

    <link rel="preconnect"
        href="https://fonts.gstatic.com">

    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Thai:wght@400;500;600;700&family=Prompt:wght@300;400;500;600&display=swap"
        rel="stylesheet">


    <!-- =========================================
        CSS ของหน้านี้
    ========================================= -->

    <style>

    * {
        box-sizing: border-box;
    }

    html,
    body {
        margin: 0;
        padding: 0;
        min-height: 100%;
    }

    body {

        font-family: "Prompt", sans-serif;

        background: #f5eee6;

        color: #4f3b2d;
    }


    /* =========================================
    MAIN
    ========================================= */

    .register-page {

        min-height: 100vh;

        display: flex;
    }


    /* =========================================
    LEFT
    ========================================= */

    .left-side {

        width: 42%;

        min-height: 100vh;

        display: flex;

        justify-content: center;

        align-items: center;

        text-align: center;

        position: relative;

        overflow: hidden;

        background:
            linear-gradient(
                145deg,
                #eadbca,
                #f8f1e9
            );
    }


    /* วงกลมตกแต่ง */

    .left-side::before {

        content: "";

        position: absolute;

        width: 450px;

        height: 450px;

        border: 1px solid rgba(112,78,53,0.12);

        border-radius: 50%;

        top: -220px;

        left: -180px;
    }


    .left-side::after {

        content: "";

        position: absolute;

        width: 550px;

        height: 550px;

        border: 1px solid rgba(112,78,53,0.10);

        border-radius: 50%;

        bottom: -300px;

        right: -250px;
    }


    /* =========================================
    BRAND
    ========================================= */

    .brand {

        position: relative;

        z-index: 2;
    }


    .brand img {

        width: 130px;

        height: 130px;

        object-fit: cover;

        border-radius: 50%;

        border: 5px solid rgba(255,255,255,0.8);

        box-shadow:
            0 15px 40px rgba(75,52,36,0.15);

        margin-bottom: 25px;
    }


    .brand h1 {

        margin: 0;

        font-family: "Noto Serif Thai", serif;

        font-size: 44px;

        font-weight: 600;

        color: #594132;
    }


    .brand p {

        margin-top: 12px;

        font-size: 14px;

        line-height: 2;

        color: #806b5b;
    }


    /* =========================================
    RIGHT
    ========================================= */

    .right-side {

        width: 58%;

        min-height: 100vh;

        display: flex;

        justify-content: center;

        align-items: center;

        padding: 40px;

        background: #fbfaf8;
    }


    /* =========================================
    CARD
    ========================================= */

    .register-card {

        width: 100%;

        max-width: 650px;

        padding: 42px 48px;

        background: white;

        border-radius: 24px;

        border: 1px solid #eee4da;

        box-shadow:
            0 20px 60px rgba(75,52,36,0.10);
    }


    /* =========================================
    HEADER
    ========================================= */

    .header {

        margin-bottom: 28px;
    }


    .header span {

        font-size: 11px;

        letter-spacing: 3px;

        color: #ad8c6c;
    }


    .header h2 {

        margin: 8px 0;

        font-family: "Noto Serif Thai", serif;

        font-size: 31px;

        font-weight: 600;

        color: #503b2e;
    }


    .header p {

        margin: 0;

        font-size: 13px;

        line-height: 1.8;

        color: #928278;
    }


    /* =========================================
    MESSAGE
    ========================================= */

    .message {

        padding: 11px 14px;

        border-radius: 10px;

        margin-bottom: 20px;

        font-size: 13px;
    }


    .message.success {

        background: #edf7ee;

        color: #47704b;

        border: 1px solid #d5ead8;
    }


    .message.error {

        background: #fff0ee;

        color: #a24f47;

        border: 1px solid #f1d4d0;
    }


    /* =========================================
    FORM ROW
    ========================================= */

    .form-row {

        display: grid;

        grid-template-columns: 1fr 1fr;

        gap: 18px;
    }


    /* =========================================
    INPUT
    ========================================= */

    .form-group {

        margin-bottom: 17px;
    }


    .form-group label {

        display: block;

        margin-bottom: 7px;

        font-size: 13px;

        font-weight: 500;

        color: #594638;
    }


    .form-group input,
    .form-group textarea {

        width: 100%;

        padding: 12px 14px;

        border-radius: 11px;

        border: 1px solid #e3d9cf;

        background: #fdfbf9;

        font-family: "Prompt", sans-serif;

        font-size: 13px;

        color: #4d3a2e;

        outline: none;

        transition: 0.25s;
    }


    .form-group textarea {

        resize: vertical;

        min-height: 80px;
    }


    .form-group input::placeholder,
    .form-group textarea::placeholder {

        color: #b8ada4;
    }


    .form-group input:focus,
    .form-group textarea:focus {

        background: white;

        border-color: #a88a6c;

        box-shadow:
            0 0 0 4px rgba(168,138,108,0.10);
    }


    /* =========================================
    BUTTON
    ========================================= */

    .submit-btn {

        width: 100%;

        padding: 14px;

        margin-top: 4px;

        border: none;

        border-radius: 12px;

        background: #674b38;

        color: white;

        font-family: "Prompt", sans-serif;

        font-size: 15px;

        font-weight: 500;

        cursor: pointer;

        transition: 0.25s;

        box-shadow:
            0 8px 20px rgba(103,75,56,0.20);
    }


    .submit-btn:hover {

        background: #50382b;

        transform: translateY(-2px);

        box-shadow:
            0 12px 25px rgba(103,75,56,0.25);
    }


    /* =========================================
    LOGIN
    ========================================= */

    .login {

        text-align: center;

        margin-top: 20px;

        font-size: 13px;

        color: #93857b;
    }


    .login a {

        color: #75543d;

        font-weight: 600;

        text-decoration: none;

        margin-left: 5px;
    }


    .login a:hover {

        text-decoration: underline;
    }


    /* =========================================
    HOME
    ========================================= */

    .home {

        display: block;

        text-align: center;

        margin-top: 12px;

        font-size: 12px;

        color: #ad9b8d;

        text-decoration: none;
    }


    .home:hover {

        color: #75543d;
    }


    /* =========================================
    MOBILE
    ========================================= */

    @media (max-width: 900px) {

        .register-page {

            flex-direction: column;
        }

        .left-side {

            width: 100%;

            min-height: 300px;

            padding: 40px 20px;
        }

        .right-side {

            width: 100%;

            min-height: auto;

            padding: 30px 20px;
        }

    }


    @media (max-width: 600px) {

        .register-card {

            padding: 30px 22px;

            border-radius: 18px;
        }

        .form-row {

            grid-template-columns: 1fr;

            gap: 0;
        }

        .brand img {

            width: 95px;

            height: 95px;
        }

        .brand h1 {

            font-size: 32px;
        }

        .header h2 {

            font-size: 26px;
        }

    }
    /* =========================
   CONSENT
========================= */

.consent-box {
    margin: 18px 0 22px;
}

.consent-label {
    display: flex;
    align-items: flex-start;
    gap: 10px;

    font-size: 13px;
    line-height: 1.6;
    color: #6b5748;

    cursor: pointer;
}

.consent-label input {
    width: 17px;
    height: 17px;

    margin-top: 3px;

    accent-color: #704b35;

    cursor: pointer;

    flex-shrink: 0;
}

.consent-label span {
    flex: 1;
}

.consent-label a {
    color: #704b35;
    font-weight: 500;
    text-decoration: underline;
}

.consent-label a:hover {
    color: #4f3424;
}

    </style>

    </head>


    <body>


    <div class="register-page">


        <!-- =====================================
            LEFT SIDE
        ====================================== -->

        <div class="left-side">

            <div class="brand">

                <img
                    src="logo.jpg"
                    alt="Grand Bake"
                >

                <h1>
                    Grand Bake
                </h1>

                <p>
                    สัมผัสความอร่อยระดับพรีเมียม<br>
                    ในทุกปอนด์ที่คุณเลือก
                </p>

            </div>

        </div>



        <!-- =====================================
            RIGHT SIDE
        ====================================== -->

        <div class="right-side">

            <div class="register-card">


                <div class="header">

                    <span>
                        GRAND BAKE MEMBER
                    </span>

                    <h2>
                        สร้างบัญชีสมาชิก
                    </h2>

                    <p>
                        สมัครสมาชิกเพื่อสั่งซื้อเค้ก
                        และรับสิทธิพิเศษจาก Grand Bake
                    </p>

                </div>


                <?php if ($message !== ""): ?>

                    <div class="message <?= $message_type ?>">

                        <?= htmlspecialchars($message) ?>

                    </div>

                <?php endif; ?>


                <form
                    action="register.php"
                    method="POST"
                >


                    <!-- ชื่อ / นามสกุล -->

                    <div class="form-row">

                        <div class="form-group">

                            <label>
                                ชื่อ
                            </label>

                            <input
                                type="text"
                                name="first_name"
                                placeholder="กรอกชื่อ"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                นามสกุล
                            </label>

                            <input
                                type="text"
                                name="last_name"
                                placeholder="กรอกนามสกุล"
                                required
                            >

                        </div>

                    </div>


                    <!-- Email -->

                    <div class="form-group">

                        <label>
                            อีเมล
                        </label>

                        <input
                            type="email"
                            name="email"
                            placeholder="example@email.com"
                            required
                        >

                    </div>


                    <!-- Phone -->

                    <div class="form-group">

                        <label>
                            เบอร์โทรศัพท์
                        </label>

                        <input
                            type="tel"
                            name="phone"
                            placeholder="08xxxxxxxx"
                            required
                        >

                    </div>


                    <!-- Address -->

                    <div class="form-group">

                        <label>
                            ที่อยู่จัดส่ง
                        </label>

                        <textarea
                            name="address"
                            placeholder="กรอกที่อยู่สำหรับจัดส่งสินค้า"
                        ></textarea>

                    </div>


                    <!-- Password -->

                    <div class="form-row">

                        <div class="form-group">

                            <label>
                                รหัสผ่าน
                            </label>

                            <input
                                type="password"
                                name="password"
                                placeholder="อย่างน้อย 8 ตัว"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                ยืนยันรหัสผ่าน
                            </label>

                            <input
                                type="password"
                                name="confirm_password"
                                placeholder="กรอกรหัสผ่านอีกครั้ง"
                                required
                            >

                        </div>

                    </div>


                   <!-- Consent -->
<div class="consent-box">

    <label class="consent-label">

        <input
            type="checkbox"
            name="consent"
            value="1"
            required
        >

        <span>
            ฉันยอมรับ
            <a href="#" target="_blank">ข้อกำหนดการใช้งาน</a>
            และ
            <a href="#" target="_blank">นโยบายความเป็นส่วนตัว</a>
            ของ Grand Bake
        </span>

    </label>

</div>


<button
    type="submit"
    class="submit-btn"
>
    สมัครสมาชิก
</button>


                </form>


                <div class="login">

                    มีบัญชีอยู่แล้ว?

                    <a href="login.php">
                        เข้าสู่ระบบ
                    </a>

                </div>


                <a
                    href="index.php"
                    class="home"
                >
                    ← กลับหน้าแรก
                </a>


            </div>

        </div>

    </div>


    </body>

    </html>