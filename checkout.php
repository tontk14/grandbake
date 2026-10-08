<?php

session_start();

$isLoggedIn = isset($_SESSION["user_id"]);

$userName = "";
$userEmail = "";
$userPhone = "";
$userAddress = "";

if ($isLoggedIn) {

    require_once "db.php";

    $user_id = $_SESSION["user_id"];

    $stmt = $conn->prepare(
        "SELECT first_name, last_name, email, phone, address
         FROM users
         WHERE id = ?"
    );

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        $userName =
            $user["first_name"] . " " . $user["last_name"];

        $userEmail = $user["email"];
        $userPhone = $user["phone"];
        $userAddress = $user["address"];
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สั่งซื้อ & กำหนดเวลารับเค้ก | Grand Bake</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Thai:wght@400;500;600;700&family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Prompt", sans-serif;
            background: #fcf8f4;
            color: #4f3528;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* =========================
           NAVBAR
        ========================= */
        .navbar {
            width: 100%;
            height: 78px;
            background: #fff;
            border-bottom: 1px solid #eadfd7;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 7%;
        }

        .logo img {
            width: 125px;
            height: auto;
            display: block;
        }

        .menu {
            display: flex;
            align-items: center;
            gap: 28px;
        }

        .menu a {
            font-size: 15px;
            color: #604638;
            transition: 0.2s;
        }

        .menu a:hover {
            color: #9a5b3c;
        }

        /* =========================
           CONTAINER
        ========================= */
        .checkout-container {
            width: 100%;
            max-width: 1120px;
            margin: 0 auto;
            padding: 60px 25px 90px;
        }

        .checkout-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .checkout-title h1 {
            font-family: "Noto Serif Thai", serif;
            font-size: 38px;
            color: #563525;
            margin-bottom: 8px;
        }

        .checkout-title p {
            color: #8a7062;
            font-size: 15px;
        }

        /* =========================
           GRID
        ========================= */
        .checkout-grid {
            display: grid;
            grid-template-columns: 1.25fr 0.95fr;
            gap: 32px;
            align-items: start;
        }

        .checkout-card {
            background: #fff;
            border: 1px solid #ede1d6;
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 12px 40px rgba(80, 50, 35, 0.08);
        }

        .checkout-card h2 {
            font-family: "Noto Serif Thai", serif;
            font-size: 23px;
            color: #563525;
            margin-bottom: 22px;
        }

        .card-subheading {
            font-size: 16px;
            font-weight: 600;
            color: #654330;
            margin: 26px 0 14px;
            padding-top: 18px;
            border-top: 1px dashed #ebdcd0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* =========================
           FORM
        ========================= */
        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-size: 13.5px;
            color: #604638;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #dfd2c8;
            border-radius: 12px;
            background: #fdfbf9;
            font-family: "Prompt", sans-serif;
            font-size: 14px;
            color: #4f3528;
            outline: none;
            transition: 0.2s;
        }

        .form-group textarea {
            min-height: 90px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            background: white;
            border-color: #8c5d42;
            box-shadow: 0 0 0 3px rgba(140, 93, 66, 0.12);
        }

        /* DELIVERY SCHEDULING GRID */
        .delivery-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        /* PAYMENT OPTIONS */
        .payment-option {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 15px 18px;
            margin-bottom: 12px;
            border: 1px solid #eadfd7;
            border-radius: 14px;
            cursor: pointer;
            transition: 0.2s;
            background: #fdfaf7;
        }

        .payment-option:hover {
            border-color: #8c5d42;
            background: #fff;
        }

        .payment-option input {
            accent-color: #7d4930;
            width: 18px;
            height: 18px;
        }

        /* COUPON PROMO BOX */
        .coupon-card {
            background: #faf4ed;
            border: 1px solid #ead8c8;
            border-radius: 16px;
            padding: 16px 18px;
            margin: 20px 0 16px;
        }

        .coupon-title {
            font-size: 13.5px;
            font-weight: 600;
            color: #794a32;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .coupon-flex {
            display: flex;
            gap: 8px;
        }

        .coupon-input {
            flex: 1;
            padding: 10px 14px;
            border: 1px solid #dccdc0;
            border-radius: 10px;
            background: white;
            font-size: 13.5px;
            outline: none;
            text-transform: uppercase;
        }

        .coupon-input:focus {
            border-color: #8c5d42;
        }

        .btn-apply-coupon {
            padding: 0 18px;
            border-radius: 10px;
            background: #7d4930;
            color: white;
            border: none;
            font-size: 13.5px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
            white-space: nowrap;
        }

        .btn-apply-coupon:hover {
            background: #633822;
        }

        .coupon-feedback {
            font-size: 12.5px;
            margin-top: 8px;
            display: none;
        }

        .coupon-hint {
            font-size: 11.5px;
            color: #9c8374;
            margin-top: 6px;
        }

        /* ORDER ITEMS */
        .order-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 14px 0;
            border-bottom: 1px solid #f2e9e2;
        }

        .order-item img {
            width: 58px;
            height: 58px;
            object-fit: cover;
            border-radius: 12px;
            background: #efe4d8;
            border: 1px solid #ebdcd0;
        }

        .order-info {
            flex: 1;
        }

        .order-info h3 {
            font-family: "Noto Serif Thai", serif;
            font-size: 15px;
            color: #563525;
            margin-bottom: 2px;
        }

        .order-info p {
            font-size: 12.5px;
            color: #95796b;
        }

        .order-price {
            font-size: 15px;
            font-weight: 600;
            color: #8b4f32;
        }

        /* SUMMARY */
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 7px 0;
            font-size: 14px;
            color: #765d50;
        }

        .discount-row {
            color: #b73824 !important;
            font-weight: 600;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 14px;
            padding-top: 16px;
            border-top: 1px solid #eadfd7;
        }

        .summary-total span:first-child {
            font-size: 17px;
            font-weight: 500;
        }

        .summary-total span:last-child {
            font-size: 26px;
            font-weight: 700;
            color: #8b4f32;
        }

        /* BUTTONS */
        .confirm-button {
            width: 100%;
            height: 54px;
            margin-top: 22px;
            border: none;
            border-radius: 28px;
            background: #7d4930;
            color: white;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            box-shadow: 0 10px 25px rgba(125,73,48,0.22);
            transition: 0.25s;
        }

        .confirm-button:hover {
            background: #633822;
            transform: translateY(-1px);
        }

        .back-cart {
            display: block;
            text-align: center;
            margin-top: 16px;
            font-size: 14px;
            color: #8a7062;
            transition: 0.2s;
        }

        .back-cart:hover {
            color: #633822;
        }

        .login-notice {
            background: #fbf5ee;
            padding: 14px 18px;
            margin-bottom: 22px;
            border: 1px solid #ead8c9;
            border-radius: 12px;
            font-size: 13px;
            line-height: 1.6;
            color: #765d50;
        }

        .login-notice a {
            color: #70432e;
            font-weight: 600;
        }

        @media (max-width: 850px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
            .delivery-grid {
                grid-template-columns: 1fr;
            }
            .navbar {
                padding: 0 20px;
            }
        }
    </style>
</head>

<body>

<!-- ================= NAVBAR ================= -->
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
        <a href="cart.html" class="cart-link">🛒 ตะกร้า</a>
    </div>
</nav>

<!-- ================= CHECKOUT ================= -->
<main class="checkout-container">

    <div class="checkout-title">
        <h1>สั่งซื้อเค้ก & เลือกเวลารับ</h1>
        <p>กรอกข้อมูลสำหรับจัดส่ง กำหนดเวลารับเค้ก และตรวจสอบรายการสั่งซื้อของคุณ</p>
    </div>

    <div class="checkout-grid">

        <!-- LEFT COLUMN: CUSTOMER & DELIVERY SCHEDULE -->
        <div class="checkout-card">
            <h2>ข้อมูลสำหรับจัดส่ง</h2>

            <?php if (!$isLoggedIn): ?>
                <div class="login-notice">
                    คุณกำลังสั่งซื้อในฐานะบุคคลทั่วไป 
                    <a href="login.php">เข้าสู่ระบบ</a> หรือ <a href="register.php">สมัครสมาชิก</a> เพื่อสะสมประวัติการสั่งซื้อ
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="customerName">ชื่อ - นามสกุล ผู้รับ *</label>
                <input type="text" id="customerName" value="<?= htmlspecialchars($userName) ?>" placeholder="เช่น สมชาย ใจดี" required>
            </div>

            <div class="form-group">
                <label for="customerEmail">อีเมล (สำหรับรับใบเสร็จและติดตามสถานะ) *</label>
                <input type="email" id="customerEmail" value="<?= htmlspecialchars($userEmail) ?>" placeholder="example@email.com" required>
            </div>

            <div class="form-group">
                <label for="customerPhone">เบอร์โทรศัพท์ติดต่อ *</label>
                <input type="tel" id="customerPhone" value="<?= htmlspecialchars($userPhone) ?>" placeholder="08xxxxxxxx" required>
            </div>

            <div class="form-group">
                <label for="customerAddress">ที่อยู่สำหรับจัดส่งเค้ก *</label>
                <textarea id="customerAddress" placeholder="บ้านเลขที่, ถนน/ซอย, แขวง/ตำบล, เขต/อำเภอ, จังหวัด, รหัสไปรษณีย์" required><?= htmlspecialchars($userAddress) ?></textarea>
            </div>

            <!-- DELIVERY DATE & TIME PICKER -->
            <div class="card-subheading">
                📅 กำหนดวันและเวลาจัดส่งเค้ก
            </div>

            <div class="delivery-grid">
                <div class="form-group">
                    <label for="deliveryDate">วันที่ต้องการให้จัดส่ง *</label>
                    <input type="date" id="deliveryDate" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-group">
                    <label for="deliveryTime">ช่วงเวลาที่สะดวกรับเค้ก *</label>
                    <select id="deliveryTime">
                        <option value="10:00 - 12:00 น.">รอบเช้า: 10:00 - 12:00 น.</option>
                        <option value="13:00 - 15:00 น.">รอบบ่าย: 13:00 - 15:00 น.</option>
                        <option value="15:00 - 18:00 น." selected>รอบเย็น: 15:00 - 18:00 น.</option>
                        <option value="18:00 - 20:00 น.">รอบค่ำ: 18:00 - 20:00 น.</option>
                    </select>
                </div>
            </div>

            <!-- CUSTOM CAKE MESSAGE -->
            <div class="form-group" style="margin-top: 6px;">
                <label for="cakeMessage">✍️ ข้อความเขียนหน้าเค้ก / การ์ดอวยพร (ไม่ระบุก็ได้)</label>
                <input type="text" id="cakeMessage" placeholder="เช่น Happy Birthday P'Mint หรือ สุขสันต์วันครบรอบ">
            </div>

            <!-- PAYMENT OPTIONS -->
            <div class="card-subheading">
                💳 วิธีการชำระเงิน
            </div>

            <label class="payment-option">
                <input type="radio" name="payment" value="transfer" checked>
                <div>
                    <strong>โอนผ่านบัญชีธนาคาร / QR พร้อมเพย์</strong>
                    <div style="font-size:12px; color:#95796b; margin-top:3px;">
                        สแกน QR Code พร้อมเพย์ตามยอดจริง และแนบสลิปในขั้นตอนถัดไป
                    </div>
                </div>
            </label>

            <label class="payment-option">
                <input type="radio" name="payment" value="cash">
                <div>
                    <strong>ชำระเงินปลายทาง (Cash on Delivery)</strong>
                    <div style="font-size:12px; color:#95796b; margin-top:3px;">
                        ชำระด้วยเงินสดเมื่อไรเดอร์ส่งมอบเค้กถึงมือคุณ
                    </div>
                </div>
            </label>
        </div>

        <!-- RIGHT COLUMN: ORDER SUMMARY & PROMO CODE -->
        <div class="checkout-card">
            <h2>สรุปรายการสั่งซื้อ</h2>

            <div id="orderItems"></div>

            <!-- PROMO CODE -->
            <div class="coupon-card">
                <div class="coupon-title">
                    🎟️ โค้ดส่วนลดโปรโมชั่น
                </div>
                <div class="coupon-flex">
                    <input type="text" id="couponInput" class="coupon-input" placeholder="ใส่โค้ดส่วนลด เช่น GBNEW10">
                    <button type="button" class="btn-apply-coupon" onclick="applyCoupon()">ใช้โค้ด</button>
                </div>
                <div id="couponFeedback" class="coupon-feedback"></div>
                <div class="coupon-hint">
                    💡 โค้ดแนะนำ: <strong>GBNEW10</strong> (ลด 10%) หรือ <strong>CAKE50</strong> (ลด 50฿)
                </div>
            </div>

            <div style="margin-top:20px;">
                <div class="summary-row">
                    <span>จำนวนสินค้าทั้งหมด</span>
                    <span id="totalItems">0 ชิ้น</span>
                </div>

                <div class="summary-row">
                    <span>ยอดรวมสินค้า</span>
                    <span id="subtotalPrice">฿0</span>
                </div>

                <div class="summary-row discount-row" id="discountRow" style="display:none;">
                    <span>ส่วนลด (<span id="appliedCodeText"></span>)</span>
                    <span id="discountPrice">-฿0</span>
                </div>

                <div class="summary-row" style="color: #2e7d32;">
                    <span>ค่าจัดส่งเค้ก</span>
                    <span>ฟรี (Free)</span>
                </div>

                <div class="summary-total">
                    <span>ยอดชำระสุทธิ</span>
                    <span id="totalPrice">฿0</span>
                </div>
            </div>

            <button type="button" class="confirm-button" onclick="confirmOrder()">
                ยืนยันการสั่งซื้อเค้ก ➔
            </button>

            <a href="cart.html" class="back-cart">
                ← กลับไปแก้ไขตะกร้าสินค้า
            </a>
        </div>

    </div>

</main>

<script>
let currentSubtotal = 0;
let appliedDiscount = 0;
let appliedCode = "";

/* CART */
function getCart() {
    return JSON.parse(localStorage.getItem("grandBakeCart")) || [];
}

/* RENDER ORDER */
function renderOrder() {
    const cart = getCart();
    const orderItems = document.getElementById("orderItems");
    const totalItems = document.getElementById("totalItems");
    const subtotalPrice = document.getElementById("subtotalPrice");

    if (cart.length === 0) {
        orderItems.innerHTML = `
            <div style="text-align:center; padding:30px 10px; color:#9c8374;">
                ไม่มีสินค้าในตะกร้า<br>
                <a href="products.php" style="color:#7d4930; font-weight:600; text-decoration:underline; margin-top:8px; display:inline-block;">ไปเลือกซื้อเค้ก</a>
            </div>
        `;
        totalItems.textContent = "0 ชิ้น";
        subtotalPrice.textContent = "฿0";
        document.getElementById("totalPrice").textContent = "฿0";
        return;
    }

    let itemsCount = 0;
    let subtotal = 0;
    let html = "";

    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        itemsCount += item.quantity;
        subtotal += itemTotal;

        html += `
            <div class="order-item">
                <img src="${item.image || 'cake1.png'}" alt="${item.name}">
                <div class="order-info">
                    <h3>${item.name}</h3>
                    <p>฿${item.price.toLocaleString()} × ${item.quantity} ชิ้น</p>
                </div>
                <div class="order-price">
                    ฿${itemTotal.toLocaleString()}
                </div>
            </div>
        `;
    });

    orderItems.innerHTML = html;
    currentSubtotal = subtotal;
    totalItems.textContent = itemsCount + " ชิ้น";
    subtotalPrice.textContent = "฿" + subtotal.toLocaleString();

    recalculateTotal();
}

/* APPLY COUPON */
function applyCoupon() {
    const input = document.getElementById("couponInput");
    const feedback = document.getElementById("couponFeedback");
    const code = input.value.trim().toUpperCase();

    if (!code) {
        feedback.textContent = "กรุณากรอกโค้ดส่วนลด";
        feedback.style.color = "#c62828";
        feedback.style.display = "block";
        return;
    }

    if (currentSubtotal <= 0) {
        feedback.textContent = "ไม่มีสินค้าในตะกร้าเพื่อใช้โค้ด";
        feedback.style.color = "#c62828";
        feedback.style.display = "block";
        return;
    }

    let discount = 0;
    if (code === "GBNEW10") {
        discount = Math.round(currentSubtotal * 0.10);
    } else if (code === "CAKE50") {
        discount = 50;
    } else if (code === "GRAND20") {
        discount = Math.round(currentSubtotal * 0.20);
    } else {
        feedback.textContent = "โค้ดส่วนลดไม่ถูกต้องหรือหมดอายุ";
        feedback.style.color = "#c62828";
        feedback.style.display = "block";
        appliedDiscount = 0;
        appliedCode = "";
        recalculateTotal();
        return;
    }

    appliedDiscount = Math.min(currentSubtotal, discount);
    appliedCode = code;

    feedback.textContent = `✓ ใช้โค้ด ${code} สำเร็จ! ลดทันที ฿${appliedDiscount.toLocaleString()}`;
    feedback.style.color = "#2e7d32";
    feedback.style.display = "block";

    recalculateTotal();
}

/* RECALCULATE TOTAL */
function recalculateTotal() {
    const discountRow = document.getElementById("discountRow");
    const appliedCodeText = document.getElementById("appliedCodeText");
    const discountPrice = document.getElementById("discountPrice");
    const totalPrice = document.getElementById("totalPrice");

    if (appliedDiscount > 0) {
        discountRow.style.display = "flex";
        appliedCodeText.textContent = appliedCode;
        discountPrice.textContent = "-฿" + appliedDiscount.toLocaleString();
    } else {
        discountRow.style.display = "none";
    }

    const finalTotal = Math.max(0, currentSubtotal - appliedDiscount);
    totalPrice.textContent = "฿" + finalTotal.toLocaleString();
}

/* CONFIRM ORDER */
function confirmOrder() {
    const cart = getCart();
    if (cart.length === 0) {
        alert("กรุณาเพิ่มสินค้าลงตะกร้าก่อน");
        return;
    }

    const name = document.getElementById("customerName").value.trim();
    const email = document.getElementById("customerEmail").value.trim();
    const phone = document.getElementById("customerPhone").value.trim();
    const address = document.getElementById("customerAddress").value.trim();
    const deliveryDate = document.getElementById("deliveryDate").value;
    const deliveryTime = document.getElementById("deliveryTime").value;
    const cakeMessage = document.getElementById("cakeMessage").value.trim();

    if (!name || !email || !phone || !address || !deliveryDate) {
        alert("กรุณากรอกข้อมูลสำหรับจัดส่งและกำหนดวันรับเค้กให้ครบถ้วน");
        return;
    }

    if (!email.includes("@") || !email.includes(".")) {
        alert("กรุณากรอกอีเมลให้ถูกต้อง");
        return;
    }

    const payment = document.querySelector('input[name="payment"]:checked').value;

    const btn = document.querySelector(".confirm-button");
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.style.opacity = "0.7";
    btn.style.cursor = "not-allowed";
    btn.textContent = "กำลังบันทึกและส่งอีเมลยืนยัน... ⏳";

    fetch("process_order.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            name: name,
            email: email,
            phone: phone,
            address: address,
            delivery_date: deliveryDate,
            delivery_time: deliveryTime,
            cake_message: cakeMessage,
            discount_code: appliedCode,
            payment: payment,
            cart: cart
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            localStorage.removeItem("grandBakeCart");
            window.location.href = "order_success.php?order=" + encodeURIComponent(data.order_number);
        } else {
            alert(data.message || "เกิดข้อผิดพลาดในการสั่งซื้อ");
            btn.disabled = false;
            btn.style.opacity = "1";
            btn.style.cursor = "pointer";
            btn.textContent = originalText;
        }
    })
    .catch(err => {
        console.error(err);
        alert("เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง");
        btn.disabled = false;
        btn.style.opacity = "1";
        btn.style.cursor = "pointer";
        btn.textContent = originalText;
    });
}

// START
renderOrder();
</script>

<!-- CART BADGE -->
<script src="cart-badge.js"></script>

</body>
</html>