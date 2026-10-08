<?php
session_start();
$subscribeMessage = "";
$subscribeType = "";

if (isset($_GET["subscribe"])) {

    switch ($_GET["subscribe"]) {

        case "success":

            $subscribeMessage =
                "สมัครรับข่าวสารสำเร็จ! เราได้ส่งอีเมลยืนยันไปให้คุณแล้ว";

            $subscribeType = "success";

            break;


        case "exists":

            $subscribeMessage =
                "อีเมลนี้สมัครรับข่าวสารไว้แล้ว";

            $subscribeType = "error";

            break;


        case "email_error":

            $subscribeMessage =
                "สมัครรับข่าวสารสำเร็จ แต่ไม่สามารถส่งอีเมลแจ้งเตือนได้";

            $subscribeType = "error";

            break;


        case "invalid":

            $subscribeMessage =
                "กรุณากรอกอีเมลให้ถูกต้อง";

            $subscribeType = "error";

            break;


        case "empty":

            $subscribeMessage =
                "กรุณากรอกอีเมล";

            $subscribeType = "error";

            break;
    }
}
$isLoggedIn = isset($_SESSION["user_id"]);
?>
<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Grand Bake | Korean Style Cake</title>


    <!-- Google Fonts -->

    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Thai:wght@400;500;600;700&family=Prompt:wght@300;400;500;600&display=swap"
          rel="stylesheet">


    <!-- CSS -->

    <link rel="stylesheet"
          href="style.css">


    <!-- ACCOUNT MENU -->

    <style>

        .navbar .account-box {
            position: relative !important;
            display: flex !important;
            align-items: center !important;
        }


        .navbar .account-button {
            width: 46px !important;
            height: 46px !important;

            padding: 0 !important;

            border: 1px solid #e5d7ca !important;
            border-radius: 50% !important;

            background: #fffaf6 !important;

            display: flex !important;
            align-items: center !important;
            justify-content: center !important;

            font-size: 20px !important;

            cursor: pointer !important;

            color: #68432f !important;
        }


        .navbar .account-button:hover {
            background: #f5e9df !important;
        }


        .navbar .account-dropdown {
            position: absolute !important;

            top: calc(100% + 12px) !important;
            right: 0 !important;

            width: 280px !important;

            background: #ffffff !important;

            border: 1px solid #eaded5 !important;

            border-radius: 16px !important;

            padding: 14px !important;

            box-sizing: border-box !important;

            box-shadow: 0 15px 35px rgba(70, 45, 30, 0.16) !important;

            z-index: 99999 !important;

            display: none !important;
        }


        .navbar .account-dropdown.account-open {
            display: block !important;
        }


        .navbar .account-header {
            display: flex !important;

            align-items: center !important;

            gap: 12px !important;

            padding: 4px 4px 14px !important;
        }


        .navbar .account-avatar {
            width: 44px !important;
            height: 44px !important;

            border-radius: 50% !important;

            background: #f4e8dd !important;

            display: flex !important;
            align-items: center !important;
            justify-content: center !important;

            font-size: 20px !important;

            flex-shrink: 0 !important;
        }


        .navbar .account-title {
            font-family: "Noto Serif Thai", serif !important;

            font-size: 16px !important;

            font-weight: 600 !important;

            color: #4d2e20 !important;
        }


        .navbar .account-subtitle {
            font-family: "Prompt", sans-serif !important;

            font-size: 12px !important;

            color: #9a8171 !important;

            margin-top: 2px !important;
        }


        .navbar .account-divider {
            height: 1px !important;

            background: #eee3da !important;

            margin: 0 0 8px !important;
        }


        .navbar .account-link {
            display: flex !important;

            align-items: center !important;

            gap: 12px !important;

            width: 100% !important;

            padding: 11px 8px !important;

            box-sizing: border-box !important;

            text-decoration: none !important;

            border-radius: 10px !important;

            transition: 0.2s !important;
        }


        .navbar .account-link:hover {
            background: #faf3ed !important;
        }


        .navbar .account-item-icon {
            width: 34px !important;
            height: 34px !important;

            border-radius: 9px !important;

            background: #f5e9df !important;

            display: flex !important;

            align-items: center !important;
            justify-content: center !important;

            font-size: 16px !important;

            flex-shrink: 0 !important;
        }


        .navbar .account-item-text {
            display: flex !important;

            flex-direction: column !important;

            gap: 2px !important;

            text-align: left !important;
        }


        .navbar .account-item-text strong {
            font-family: "Prompt", sans-serif !important;

            font-size: 14px !important;

            font-weight: 500 !important;

            color: #4d2e20 !important;
        }


        .navbar .account-item-text small {
            font-family: "Prompt", sans-serif !important;

            font-size: 11px !important;

            color: #9a8171 !important;
        }


        .navbar .cart-icon {
            text-decoration: none !important;

            font-size: 20px !important;

            margin-left: 8px !important;
        }
        /* ==========================================
   NEWSLETTER
========================================== */

.newsletter-section {
    padding: 80px 20px;
    background: #f5eee6;
    text-align: center;
}

.newsletter-content {
    max-width: 700px;
    margin: 0 auto;
}

.newsletter-label {
    display: inline-block;
    margin-bottom: 12px;

    font-size: 11px;
    letter-spacing: 3px;

    color: #ad8c6c;
}

.newsletter-content h2 {
    margin: 0 0 15px;

    font-family: "Noto Serif Thai", serif;

    font-size: 34px;
    font-weight: 600;

    color: #503b2e;
}

.newsletter-content p {
    margin: 0 auto 25px;

    max-width: 600px;

    font-size: 14px;

    line-height: 1.9;

    color: #806b5b;
}

.newsletter-form {
    display: flex;

    max-width: 580px;

    margin: 0 auto 15px;

    gap: 10px;
}

.newsletter-form input {
    flex: 1;

    padding: 14px 18px;

    border: 1px solid #dfd2c5;

    border-radius: 12px;

    background: white;

    font-family: "Prompt", sans-serif;

    font-size: 14px;

    outline: none;
}

.newsletter-form input:focus {
    border-color: #a88a6c;

    box-shadow:
        0 0 0 4px rgba(168,138,108,0.10);
}

.newsletter-form button {
    padding: 14px 24px;

    border: none;

    border-radius: 12px;

    background: #674b38;

    color: white;

    font-family: "Prompt", sans-serif;

    font-size: 14px;

    font-weight: 500;

    cursor: pointer;

    white-space: nowrap;

    transition: 0.25s;
}

.newsletter-form button:hover {
    background: #50382b;

    transform: translateY(-2px);
}

.newsletter-content small {
    display: block;

    font-size: 11px;

    color: #a39488;
}


/* ==========================================
   MOBILE
========================================== */

@media (max-width: 600px) {

    .newsletter-form {
        flex-direction: column;
    }

    .newsletter-form button {
        width: 100%;
    }

    .newsletter-content h2 {
        font-size: 28px;
    }

}

.subscribe-alert {
    max-width: 700px;

    margin: 0 auto 25px;

    padding: 14px 18px;

    border-radius: 12px;

    font-size: 14px;

    line-height: 1.6;
}

.subscribe-alert.success {
    background: #edf7ee;

    border: 1px solid #d5ead8;

    color: #47704b;
}

.subscribe-alert.error {
    background: #fff0ee;

    border: 1px solid #f1d4d0;

    color: #a24f47;
}
    </style>

</head>


<body>


<!-- ==================================================
     NAVBAR
================================================== -->

<nav class="navbar">


    <!-- LOGO -->

    <div class="logo">

        <a href="index.php">

            <img src="logo.jpg"
                 alt="Grand Bake Logo">

        </a>

    </div>


    <!-- MENU -->

    <div class="menu">

        <a href="index.php"
           class="active">
            หน้าแรก
        </a>

        <a href="products.html">
            สินค้า
        </a>

        <a href="#">
            เกี่ยวกับเรา
        </a>

        <a href="#">
            ติดต่อเรา
        </a>


        <!-- ACCOUNT -->

        <div class="account-box">

            <button
                type="button"
                class="account-button"
                onclick="toggleAccount(event)"
                aria-label="บัญชีผู้ใช้">

                👤

            </button>


           <div
    class="account-dropdown"
    id="accountDropdown">

    <div class="account-header">

        <div class="account-avatar">
            👤
        </div>

        <div>

            <?php if ($isLoggedIn): ?>

                <div class="account-title">
                    <?= htmlspecialchars($_SESSION["first_name"] ?? "สมาชิก") ?>
                </div>

                <div class="account-subtitle">
                    สมาชิก Grand Bake
                </div>

            <?php else: ?>

                <div class="account-title">
                    บัญชีสมาชิก
                </div>

                <div class="account-subtitle">
                    Grand Bake
                </div>

            <?php endif; ?>

        </div>

    </div>


    <div class="account-divider"></div>


    <?php if ($isLoggedIn): ?>

        <!-- =========================
             กรณี LOGIN แล้ว
        ========================== -->

        <a href="profile.php"
           class="account-link">

            <span class="account-item-icon">
                👤
            </span>

            <span class="account-item-text">

                <strong>
                    ดูบัญชีของฉัน
                </strong>

                <small>
                    จัดการข้อมูลส่วนตัว
                </small>

            </span>

        </a>


        <a href="logout.php"
           class="account-link">

            <span class="account-item-icon">
                🚪
            </span>

            <span class="account-item-text">

                <strong>
                    ออกจากระบบ
                </strong>

                <small>
                    ออกจากบัญชีของคุณ
                </small>

            </span>

        </a>


    <?php else: ?>

        <!-- =========================
             กรณียังไม่ได้ LOGIN
        ========================== -->

        <a href="login.php"
           class="account-link">

            <span class="account-item-icon">
                🔑
            </span>

            <span class="account-item-text">

                <strong>
                    เข้าสู่ระบบ
                </strong>

                <small>
                    เข้าสู่บัญชีของคุณ
                </small>

            </span>

        </a>


        <a href="register.php"
           class="account-link">

            <span class="account-item-icon">
                📝
            </span>

            <span class="account-item-text">

                <strong>
                    สมัครสมาชิก
                </strong>

                <small>
                    สร้างบัญชีใหม่
                </small>

            </span>

        </a>

    <?php endif; ?>

</div>


        <!-- CART -->

        <a href="cart.html"
           class="cart-icon"
           aria-label="ตะกร้าสินค้า">

            🛒

        </a>

    </div>

</nav>



<!-- ==================================================
     HERO
================================================== -->

<section class="hero">


    <div class="hero-text">

        <p class="small-title">
            KOREAN STYLE CAKE
        </p>


        <h1>

            สัมผัสความอร่อย<br>
            ระดับพรีเมียม

        </h1>


        <p class="slogan">
            ในทุกปอนด์ที่คุณเลือก
        </p>


        <a href="products.html"
           class="buy-button">

            เลือกซื้อเค้ก

            <span>
                →
            </span>

        </a>

    </div>



    <!-- HERO SLIDER -->

    <div class="cake-slider">


        <button
            class="slider-button prev"
            onclick="previousCake()">

            ‹

        </button>


        <div class="slide">


            <div class="slide-info">

                <span class="badge">
                    BEST SELLER
                </span>


                <h2 id="cake-name">

                    Strawberry<br>
                    Cream Cake

                </h2>


                <div class="line"></div>


                <p id="cake-description">

                    เค้กครีมสดสตรอว์เบอร์รี
                    <br>
                    เนื้อนุ่ม หอมละมุน

                </p>


                <h3 id="cake-price">
                    ฿450
                </h3>

            </div>


            <div class="slide-image">

                <img
                    id="cake-image"
                    src="cake1.png"
                    alt="Strawberry Cream Cake">

            </div>

        </div>


        <button
            class="slider-button next"
            onclick="nextCake()">

            ›

        </button>


        <div class="slider-dots">

            <span
                class="dot active"
                onclick="showCake(0)">
            </span>

            <span
                class="dot"
                onclick="showCake(1)">
            </span>

            <span
                class="dot"
                onclick="showCake(2)">
            </span>

        </div>

    </div>

</section>



<!-- ==================================================
     CAKE RECOMMENDATION
================================================== -->

<section class="cake-recommend">


    <!-- HEADER -->

    <div class="recommend-header">

        <p class="recommend-eyebrow">
            FIND YOUR FAVORITE
        </p>


        <h2>
            คุณชอบเค้กสไตล์ไหน?
        </h2>


        <p class="recommend-subtitle">
            เลือกสไตล์ที่คุณชอบ แล้ว Grand Bake จะแนะนำเค้กให้คุณ
        </p>

    </div>



    <!-- FILTER -->

    <div class="cake-filters">


        <button
            class="cake-filter active"
            data-filter="all">

            ✨ ทั้งหมด

        </button>


        <button
            class="cake-filter"
            data-filter="fruit">

            🍓 ผลไม้

        </button>


        <button
            class="cake-filter"
            data-filter="chocolate">

            🍫 ช็อกโกแลต

        </button>


        <button
            class="cake-filter"
            data-filter="matcha">

            🍵 มัทฉะ

        </button>


        <button
            class="cake-filter"
            data-filter="cheesecake">

            🧀 ชีสเค้ก

        </button>


        <button
            class="cake-filter"
            data-filter="korean">

            🎀 Korean Style

        </button>

    </div>



    <!-- CAKE GRID -->

    <div class="recommend-grid">


        <!-- 1 Strawberry -->

        <div
            class="recommend-card"
            data-category="fruit">

            <img
                src="cake1.png"
                alt="Strawberry Cream Cake">

            <div class="recommend-info">

                <h3>
                    Strawberry Cream Cake
                </h3>

                <p>
                    สตรอว์เบอร์รีสด
                    หอมหวานละมุน
                </p>

                <div class="recommend-bottom">

                    <span>
                        ฿450
                    </span>

                    <a href="detail.html?cake=1">
                        ดูรายละเอียด →
                    </a>

                </div>

            </div>

        </div>



        <!-- 2 Chocolate Fudge -->

        <div
            class="recommend-card"
            data-category="chocolate">

            <img
                src="cake2.png"
                alt="Chocolate Fudge Cake">

            <div class="recommend-info">

                <h3>
                    Chocolate Fudge Cake
                </h3>

                <p>
                    ช็อกโกแลตเข้มข้น
                    เนื้อนุ่มละมุน
                </p>

                <div class="recommend-bottom">

                    <span>
                        ฿490
                    </span>

                    <a href="detail.html?cake=2">
                        ดูรายละเอียด →
                    </a>

                </div>

            </div>

        </div>



        <!-- 3 Blueberry -->

        <div
            class="recommend-card"
            data-category="fruit">

            <img
                src="cake3.png"
                alt="Blueberry Cream Cake">

            <div class="recommend-info">

                <h3>
                    Blueberry Cream Cake
                </h3>

                <p>
                    บลูเบอร์รีครีมสด
                    หอมหวานสดชื่น
                </p>

                <div class="recommend-bottom">

                    <span>
                        ฿470
                    </span>

                    <a href="detail.html?cake=3">
                        ดูรายละเอียด →
                    </a>

                </div>

            </div>

        </div>



        <!-- 4 Chocolate -->

        <div
            class="recommend-card"
            data-category="chocolate">

            <img
                src="cake4.png"
                alt="Chocolate Cake">

            <div class="recommend-info">

                <h3>
                    Chocolate Cake
                </h3>

                <p>
                    เค้กช็อกโกแลตเข้มข้น
                    สำหรับคนรักโกโก้
                </p>

                <div class="recommend-bottom">

                    <span>
                        ฿490
                    </span>

                    <a href="detail.html?cake=4">
                        ดูรายละเอียด →
                    </a>

                </div>

            </div>

        </div>



        <!-- 5 Mango -->

        <div
            class="recommend-card"
            data-category="fruit">

            <img
                src="cake5.png"
                alt="Mango Cream Cake">

            <div class="recommend-info">

                <h3>
                    Mango Cream Cake
                </h3>

                <p>
                    มะม่วงหวานหอม
                    สดชื่นและละมุน
                </p>

                <div class="recommend-bottom">

                    <span>
                        ฿480
                    </span>

                    <a href="detail.html?cake=5">
                        ดูรายละเอียด →
                    </a>

                </div>

            </div>

        </div>



        <!-- 6 Matcha -->

        <div
            class="recommend-card"
            data-category="matcha">

            <img
                src="cake6.png"
                alt="Matcha Cream Cake">

            <div class="recommend-info">

                <h3>
                    Matcha Cream Cake
                </h3>

                <p>
                    มัทฉะครีมสด
                    หอมละมุน รสกลมกล่อม
                </p>

                <div class="recommend-bottom">

                    <span>
                        ฿490
                    </span>

                    <a href="detail.html?cake=6">
                        ดูรายละเอียด →
                    </a>

                </div>

            </div>

        </div>



        <!-- 7 Earl Grey -->

        <div
            class="recommend-card"
            data-category="korean">

            <img
                src="cake7.png"
                alt="Earl Grey Milk Cake">

            <div class="recommend-info">

                <h3>
                    Earl Grey Milk Cake
                </h3>

                <p>
                    ชาเอิร์ลเกรย์หอมละมุน
                    เนื้อนุ่มละลายในปาก
                </p>

                <div class="recommend-bottom">

                    <span>
                        ฿480
                    </span>

                    <a href="detail.html?cake=7">
                        ดูรายละเอียด →
                    </a>

                </div>

            </div>

        </div>



        <!-- 8 Basque -->

        <div
            class="recommend-card"
            data-category="cheesecake">

            <img
                src="cake8.png"
                alt="Basque Cheesecake">

            <div class="recommend-info">

                <h3>
                    Basque Cheesecake
                </h3>

                <p>
                    ชีสเค้กหน้าไหม้
                    เนื้อเนียนนุ่ม ละมุนลิ้น
                </p>

                <div class="recommend-bottom">

                    <span>
                        ฿520
                    </span>

                    <a href="detail.html?cake=8">
                        ดูรายละเอียด →
                    </a>

                </div>

            </div>

        </div>



        <!-- 9 Caramel -->

        <div
            class="recommend-card"
            data-category="caramel">

            <img
                src="cake9.png"
                alt="Caramel Butter Cake">

            <div class="recommend-info">

                <h3>
                    Caramel Butter Cake
                </h3>

                <p>
                    คาราเมลบัตเตอร์เค้ก
                    หอมเนยและคาราเมล
                </p>

                <div class="recommend-bottom">

                    <span>
                        ฿450
                    </span>

                    <a href="detail.html?cake=9">
                        ดูรายละเอียด →
                    </a>

                </div>

            </div>

        </div>



        <!-- 10 Vanilla -->

        <div
            class="recommend-card"
            data-category="korean">

            <img
                src="cake10.png"
                alt="Vanilla Cloud Cake">

            <div class="recommend-info">

                <h3>
                    Vanilla Cloud Cake
                </h3>

                <p>
                    วานิลลาครีมสด
                    เนื้อนุ่ม หอมวานิลลา
                </p>

                <div class="recommend-bottom">

                    <span>
                        ฿460
                    </span>

                    <a href="detail.html?cake=10">
                        ดูรายละเอียด →
                    </a>

                </div>

            </div>

        </div>


    </div>

</section>
<!-- ==========================================
     NEWSLETTER SUBSCRIBE
========================================== -->

<section class="newsletter-section">

    <div class="newsletter-content">

        <span class="newsletter-label">
            GRAND BAKE NEWSLETTER
        </span>

        <h2>
            รับข่าวสารจาก Grand Bake
        </h2>

        <p>
            สมัครรับข่าวสาร โปรโมชั่น เมนูเค้กใหม่
            และสิทธิพิเศษ ส่งตรงถึงอีเมลของคุณ
        </p>
        <?php if ($subscribeMessage !== ""): ?>

    <div class="subscribe-alert <?= $subscribeType ?>">

        <?= htmlspecialchars($subscribeMessage) ?>

    </div>

<?php endif; ?>
        <form
            action="subscribe.php"
            method="POST"
            class="newsletter-form"
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

        <small>
            เราจะส่งเฉพาะข่าวสารที่เกี่ยวข้องกับ Grand Bake เท่านั้น ♡
        </small>

    </div>

</section>


<!-- ==================================================
     ACCOUNT JAVASCRIPT
================================================== -->

<script>

function toggleAccount(event) {

    event.stopPropagation();

    const dropdown =
        document.getElementById("accountDropdown");

    dropdown.classList.toggle("account-open");

}


document.addEventListener("click", function(event) {

    const account =
        document.querySelector(".account-box");

    const dropdown =
        document.getElementById("accountDropdown");


    if (
        account &&
        dropdown &&
        !account.contains(event.target)
    ) {

        dropdown.classList.remove("account-open");

    }

});

</script>



<!-- ==================================================
     CAKE FILTER JAVASCRIPT
================================================== -->

<script>

document.addEventListener("DOMContentLoaded", function () {


    const buttons =
        document.querySelectorAll(".cake-filter");


    const cards =
        document.querySelectorAll(".recommend-card");


    buttons.forEach(function(button) {


        button.addEventListener("click", function() {


            /* เปลี่ยนปุ่ม Active */

            buttons.forEach(function(btn) {

                btn.classList.remove("active");

            });


            this.classList.add("active");


            /* อ่านหมวดหมู่ */

            const selectedCategory =
                this.getAttribute("data-filter");


            /* กรองสินค้า */

            cards.forEach(function(card) {


                const category =
                    card.getAttribute("data-category");


                if (
                    selectedCategory === "all" ||
                    category === selectedCategory
                ) {

                    card.style.display = "";

                } else {

                    card.style.display = "none";

                }

            });

        });

    });

});

</script>



<!-- ==================================================
     MAIN JAVASCRIPT
================================================== -->

<script src="script.js"></script>


</body>

</html>