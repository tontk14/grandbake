<?php

session_start();

require_once "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $error = "กรุณากรอกอีเมลและรหัสผ่าน";

    } else {

        $sql = "SELECT id, first_name, last_name, email, phone, password
                FROM users
                WHERE email = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {

                /*
                =================================
                LOGIN SUCCESS
                =================================
                */

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["first_name"] = $user["first_name"];
                $_SESSION["last_name"] = $user["last_name"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["phone"] = $user["phone"];

                // ไปหน้า Profile
                header("Location: profile.php");
                exit;

            } else {

                $error = "อีเมลหรือรหัสผ่านไม่ถูกต้อง";

            }

        } else {

            $error = "อีเมลหรือรหัสผ่านไม่ถูกต้อง";

        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>

<html lang="th">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>เข้าสู่ระบบ | Grand Bake</title>

<link rel="preconnect"
      href="https://fonts.googleapis.com">

<link rel="preconnect"
      href="https://fonts.gstatic.com">

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

    background: #f5eee6;

    color: #4f3b2d;
}


.login-page {

    min-height: 100vh;

    display: flex;
}


/* LEFT */

.left-side {

    width: 45%;

    min-height: 100vh;

    display: flex;

    justify-content: center;

    align-items: center;

    text-align: center;

    background:
        linear-gradient(
            145deg,
            #eadbca,
            #f8f1e9
        );
}


.brand img {

    width: 135px;

    height: 135px;

    object-fit: cover;

    border-radius: 50%;

    border: 5px solid white;

    box-shadow:
        0 15px 40px rgba(75,52,36,0.15);

    margin-bottom: 20px;
}


.brand h1 {

    margin: 0;

    font-family: "Noto Serif Thai", serif;

    font-size: 44px;

    color: #594132;
}


.brand p {

    font-size: 14px;

    line-height: 2;

    color: #806b5b;
}


/* RIGHT */

.right-side {

    width: 55%;

    min-height: 100vh;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 30px;
}


.login-card {

    width: 100%;

    max-width: 450px;

    padding: 45px;

    background: white;

    border-radius: 24px;

    box-shadow:
        0 20px 60px rgba(75,52,36,0.10);

    border: 1px solid #eee4da;
}


.small-title {

    font-size: 11px;

    letter-spacing: 3px;

    color: #ad8c6c;
}


h2 {

    margin: 10px 0;

    font-family: "Noto Serif Thai", serif;

    font-size: 32px;

    color: #503b2e;
}


.description {

    color: #928278;

    font-size: 13px;

    line-height: 1.8;

    margin-bottom: 25px;
}


/* ERROR */

.error {

    padding: 12px;

    margin-bottom: 20px;

    border-radius: 10px;

    background: #fff0ee;

    color: #a24f47;

    border: 1px solid #f1d4d0;

    font-size: 13px;
}


/* FORM */

.form-group {

    margin-bottom: 20px;
}


label {

    display: block;

    margin-bottom: 7px;

    font-size: 13px;

    color: #594638;

    font-weight: 500;
}


input {

    width: 100%;

    padding: 13px 14px;

    border-radius: 11px;

    border: 1px solid #e3d9cf;

    background: #fdfbf9;

    font-family: "Prompt", sans-serif;

    font-size: 13px;

    outline: none;
}


input:focus {

    background: white;

    border-color: #a88a6c;

    box-shadow:
        0 0 0 4px rgba(168,138,108,0.10);
}


/* BUTTON */

.login-btn {

    width: 100%;

    padding: 14px;

    border: none;

    border-radius: 12px;

    background: #674b38;

    color: white;

    font-family: "Prompt", sans-serif;

    font-size: 15px;

    cursor: pointer;

    transition: 0.25s;
}


.login-btn:hover {

    background: #50382b;

    transform: translateY(-2px);
}


/* LINKS */

.register-link {

    text-align: center;

    margin-top: 22px;

    font-size: 13px;

    color: #93857b;
}


.register-link a {

    color: #75543d;

    font-weight: 600;

    text-decoration: none;

    margin-left: 5px;
}


.home-link {

    display: block;

    text-align: center;

    margin-top: 15px;

    font-size: 12px;

    color: #ad9b8d;

    text-decoration: none;
}


/* MOBILE */

@media (max-width: 800px) {

    .login-page {

        flex-direction: column;
    }

    .left-side {

        width: 100%;

        min-height: 280px;
    }

    .right-side {

        width: 100%;

        min-height: auto;
    }

}


@media (max-width: 500px) {

    .login-card {

        padding: 30px 22px;
    }

    .brand img {

        width: 100px;

        height: 100px;
    }

    .brand h1 {

        font-size: 32px;
    }

}

</style>

</head>


<body>


<div class="login-page">


    <!-- LEFT -->

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


    <!-- RIGHT -->

    <div class="right-side">

        <div class="login-card">

            <div class="small-title">
                GRAND BAKE MEMBER
            </div>

            <h2>
                ยินดีต้อนรับกลับ
            </h2>

            <div class="description">
                เข้าสู่ระบบเพื่อสั่งซื้อเค้ก
                และจัดการข้อมูลสมาชิก
            </div>


            <?php if ($error !== ""): ?>

                <div class="error">
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>


            <form
                method="POST"
                action="login.php"
            >

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


                <div class="form-group">

                    <label>
                        รหัสผ่าน
                    </label>

                    <input
                        type="password"
                        name="password"
                        placeholder="กรอกรหัสผ่าน"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="login-btn"
                >
                    เข้าสู่ระบบ
                </button>

            </form>


            <div class="register-link">

                ยังไม่มีบัญชี?

                <a href="register.php">
                    สมัครสมาชิก
                </a>

            </div>


            <a
                href="index.php"
                class="home-link"
            >
                ← กลับหน้าแรก
            </a>

        </div>

    </div>

</div>


</body>

</html>