<?php
session_start();
$isLoggedIn = isset($_SESSION["user_id"]);
$userName = $_SESSION["first_name"] ?? "";
?>
<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เค้กทั้งหมด | Grand Bake</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Thai:wght@400;500;600;700&family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="style.css">

    <style>
        /* PRODUCTS CONTROLS */
        .products-controls {
            width: 82%;
            max-width: 1250px;
            margin: 0 auto 30px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .search-bar-wrap {
            position: relative;
            width: 100%;
            max-width: 720px;
            margin: 0 auto;
        }

        .search-bar-wrap input {
            width: 100%;
            padding: 14px 44px 14px 46px;
            border-radius: 30px;
            border: 1.5px solid #ded0c3;
            background: #ffffff;
            font-size: 15px;
            color: #4f3528;
            outline: none;
            box-shadow: 0 4px 16px rgba(80, 50, 30, 0.05);
            transition: 0.25s;
        }

        .search-bar-wrap input:focus {
            border-color: #8c5d42;
            box-shadow: 0 6px 20px rgba(140, 93, 66, 0.12);
        }

        .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: #9c8374;
        }

        #clearSearchBtn {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: #eee3da;
            color: #664a39;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .filter-sort-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-top: 10px;
        }

        .category-pills {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .pill {
            padding: 9px 20px;
            border-radius: 25px;
            border: 1px solid #dfcfc2;
            background: #fff;
            color: #6d4b38;
            font-size: 13.5px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
        }

        .pill:hover {
            background: #f7ede3;
            border-color: #caa893;
        }

        .pill.active {
            background: #75452f;
            border-color: #75452f;
            color: #fff;
            box-shadow: 0 4px 12px rgba(117, 69, 47, 0.2);
        }

        .sort-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13.5px;
            color: #785a49;
        }

        .sort-wrap select {
            padding: 8px 14px;
            border-radius: 12px;
            border: 1px solid #ded0c3;
            background: white;
            color: #553b2d;
            font-size: 13.5px;
            outline: none;
            cursor: pointer;
        }

        .results-info {
            font-size: 13px;
            color: #927b6e;
            margin-top: 4px;
        }

        .no-cakes-box {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 22px;
            border: 1px solid #ebdcd0;
            box-shadow: 0 8px 24px rgba(70, 50, 30, 0.04);
        }

        .no-cakes-box span {
            font-size: 54px;
            display: block;
            margin-bottom: 12px;
        }

        .no-cakes-box h3 {
            font-family: "Noto Serif Thai", serif;
            font-size: 22px;
            color: #563525;
            margin-bottom: 8px;
        }

        .no-cakes-box p {
            color: #8a7062;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .btn-reset-search {
            padding: 10px 24px;
            border-radius: 25px;
            background: #75452f;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: 0.2s;
        }

        .btn-reset-search:hover {
            background: #5c3523;
        }

        @media (max-width: 768px) {
            .filter-sort-row {
                flex-direction: column;
                align-items: stretch;
            }
            .sort-wrap {
                justify-content: flex-end;
            }
        }
    </style>

</head>

<body>


<!-- ================= NAVBAR ================= -->

<nav class="navbar">

    <!-- LOGO -->
    <div class="logo">
        <a href="index.php">
            <img src="logo.jpg" alt="Grand Bake Logo">
        </a>
    </div>

    <!-- MENU -->
    <div class="menu">

        <a href="index.php">หน้าแรก</a>
        <a href="products.php" class="active">สินค้า</a>
        <a href="index.php#about">เกี่ยวกับเรา</a>
        <a href="index.php#contact">ติดต่อเรา</a>

        <!-- ACCOUNT -->
        <div class="account-box">

            <button
                type="button"
                class="account-button"
                id="accountButton"
                aria-label="บัญชีผู้ใช้"
            >
                👤
            </button>

            <!-- ACCOUNT DROPDOWN -->
            <div class="account-dropdown" id="accountDropdown">

                <div class="account-header">
                    <div class="account-avatar">👤</div>
                    <div>
                        <div class="account-title">
                            <?= $isLoggedIn && !empty($userName) ? htmlspecialchars($userName) : "บัญชีสมาชิก" ?>
                        </div>
                        <div class="account-subtitle">
                            Grand Bake
                        </div>
                    </div>
                </div>

                <div class="account-divider"></div>

                <?php if ($isLoggedIn): ?>

                    <!-- ORDER HISTORY -->
                    <a href="profile.php#orders" class="account-link">
                        <span class="account-item-icon">📦</span>
                        <span class="account-item-text">
                            <strong>ประวัติการสั่งซื้อ</strong>
                            <small>ติดตามสถานะคำสั่งซื้อ</small>
                        </span>
                    </a>

                    <!-- PROFILE -->
                    <a href="profile.php#account" class="account-link">
                        <span class="account-item-icon">👤</span>
                        <span class="account-item-text">
                            <strong>ดูบัญชีของฉัน</strong>
                            <small>จัดการข้อมูลส่วนตัว</small>
                        </span>
                    </a>

                    <!-- LOGOUT -->
                    <a href="logout.php" class="account-link">
                        <span class="account-item-icon">🚪</span>
                        <span class="account-item-text">
                            <strong>ออกจากระบบ</strong>
                            <small>ออกจากบัญชีของคุณ</small>
                        </span>
                    </a>

                <?php else: ?>

                    <!-- LOGIN -->
                    <a href="login.php" class="account-link">
                        <span class="account-item-icon">🔑</span>
                        <span class="account-item-text">
                            <strong>เข้าสู่ระบบ</strong>
                            <small>เข้าสู่บัญชีของคุณ</small>
                        </span>
                    </a>

                    <!-- REGISTER -->
                    <a href="register.php" class="account-link">
                        <span class="account-item-icon">📝</span>
                        <span class="account-item-text">
                            <strong>สมัครสมาชิก</strong>
                            <small>สร้างบัญชีใหม่</small>
                        </span>
                    </a>

                <?php endif; ?>

            </div>

        </div>

        <!-- CART -->
        <a href="cart.html" class="cart-icon" aria-label="ตะกร้าสินค้า">
            🛒
        </a>

    </div>

</nav>


<!-- ================= PRODUCTS HEADER ================= -->

<section class="products-header">
    <p class="products-eyebrow">GRAND BAKE</p>
    <h1>เค้กของทางร้าน</h1>
    <p>เลือกความอร่อยที่เหมาะกับช่วงเวลาพิเศษของคุณ</p>
</section>

<!-- ================= SEARCH & CONTROLS ================= -->

<section class="products-controls">
    <!-- SEARCH BAR -->
    <div class="search-bar-wrap">
        <span class="search-icon">🔍</span>
        <input 
            type="text" 
            id="cakeSearchInput" 
            placeholder="ค้นหาเค้กที่ชอบ เช่น สตรอว์เบอร์รี, ช็อกโกแลต, มัทฉะ, ชีสเค้ก..." 
            oninput="filterCakes()"
        >
        <button type="button" id="clearSearchBtn" onclick="clearCakeSearch()" style="display:none;" title="ล้างการค้นหา">✕</button>
    </div>

    <!-- FILTER & SORT ROW -->
    <div class="filter-sort-row">
        <!-- CATEGORY PILLS -->
        <div class="category-pills">
            <button type="button" class="pill active" data-cat="all" onclick="selectCategory('all', this)">
                🍰 ทั้งหมด (<span id="count-all">10</span>)
            </button>
            <button type="button" class="pill" data-cat="fruit" onclick="selectCategory('fruit', this)">
                🍓 ผลไม้สด
            </button>
            <button type="button" class="pill" data-cat="chocolate" onclick="selectCategory('chocolate', this)">
                🍫 ช็อกโกแลต
            </button>
            <button type="button" class="pill" data-cat="tea" onclick="selectCategory('tea', this)">
                🍵 ชา & มัทฉะ
            </button>
            <button type="button" class="pill" data-cat="cheese" onclick="selectCategory('cheese', this)">
                🧀 ชีส & คาราเมล
            </button>
        </div>

        <!-- SORT -->
        <div class="sort-wrap">
            <label for="cakeSortSelect">เรียงตาม:</label>
            <select id="cakeSortSelect" onchange="filterCakes()">
                <option value="featured">✨ เมนูแนะนำ</option>
                <option value="price-asc">💵 ราคา: ต่ำ ➔ สูง</option>
                <option value="price-desc">💎 ราคา: สูง ➔ ต่ำ</option>
                <option value="name-asc">🔤 ชื่อเค้ก A-Z</option>
            </select>
        </div>
    </div>

    <div class="results-info">
        <span id="resultsCount">แสดงเค้กทั้งหมด 10 รายการ</span>
    </div>
</section>


<!-- ================= PRODUCTS GRID ================= -->

<section class="products" id="productsGrid">

    <!-- 1 STRAWBERRY -->
    <div class="product-card" data-id="1" data-category="fruit" data-price="450" data-name="Strawberry Cream Cake สตรอว์เบอร์รี ครีมสด หวานละมุน">
        <img src="cake1.png" alt="Strawberry Cream Cake">
        <div class="product-content">
            <h2>Strawberry Cream Cake</h2>
            <p>สตรอว์เบอร์รีสด หวานละมุน หอมสดชื่น</p>
            <div class="product-bottom">
                <span class="price">฿450</span>
                <a href="detail.html?cake=1" class="detail-button">ดูรายละเอียด</a>
            </div>
        </div>
    </div>

    <!-- 2 CHOCOLATE FUDGE -->
    <div class="product-card" data-id="2" data-category="chocolate" data-price="490" data-name="Chocolate Fudge Cake ช็อกโกแลต ฟัดจ์ เข้มข้น">
        <img src="cake2.png" alt="Chocolate Fudge Cake">
        <div class="product-content">
            <h2>Chocolate Fudge Cake</h2>
            <p>ช็อกโกแลตเข้มข้น หอมละมุนทุกคำ</p>
            <div class="product-bottom">
                <span class="price">฿490</span>
                <a href="detail.html?cake=2" class="detail-button">ดูรายละเอียด</a>
            </div>
        </div>
    </div>

    <!-- 3 BLUEBERRY -->
    <div class="product-card" data-id="3" data-category="fruit" data-price="470" data-name="Blueberry Cream Cake บลูเบอร์รี ครีมสด หวานอมเปรี้ยว">
        <img src="cake3.png" alt="Blueberry Cream Cake">
        <div class="product-content">
            <h2>Blueberry Cream Cake</h2>
            <p>บลูเบอร์รีสด หวานอมเปรี้ยว สดชื่น</p>
            <div class="product-bottom">
                <span class="price">฿470</span>
                <a href="detail.html?cake=3" class="detail-button">ดูรายละเอียด</a>
            </div>
        </div>
    </div>

    <!-- 4 CHOCOLATE -->
    <div class="product-card" data-id="4" data-category="chocolate" data-price="490" data-name="Chocolate Cake ช็อกโกแลต เค้ก เนื้อนุ่ม เข้มข้น">
        <img src="cake4.png" alt="Chocolate Cake">
        <div class="product-content">
            <h2>Chocolate Cake</h2>
            <p>เค้กช็อกโกแลตเนื้อนุ่ม รสชาติเข้มข้น</p>
            <div class="product-bottom">
                <span class="price">฿490</span>
                <a href="detail.html?cake=4" class="detail-button">ดูรายละเอียด</a>
            </div>
        </div>
    </div>

    <!-- 5 MANGO -->
    <div class="product-card" data-id="5" data-category="fruit" data-price="480" data-name="Mango Cream Cake มะม่วง น้ำดอกไม้ หวานฉ่ำ ครีมสด">
        <img src="cake5.png" alt="Mango Cream Cake">
        <div class="product-content">
            <h2>Mango Cream Cake</h2>
            <p>มะม่วงน้ำดอกไม้ หวานฉ่ำจากธรรมชาติ</p>
            <div class="product-bottom">
                <span class="price">฿480</span>
                <a href="detail.html?cake=5" class="detail-button">ดูรายละเอียด</a>
            </div>
        </div>
    </div>

    <!-- 6 MATCHA -->
    <div class="product-card" data-id="6" data-category="tea" data-price="490" data-name="Matcha Cream Cake มัทฉะ ชาเขียว ครีมสด เข้มข้น">
        <img src="cake6.png" alt="Matcha Cream Cake">
        <div class="product-content">
            <h2>Matcha Cream Cake</h2>
            <p>มัทฉะหอมเข้มข้น ผสานครีมสดเนื้อนุ่ม</p>
            <div class="product-bottom">
                <span class="price">฿490</span>
                <a href="detail.html?cake=6" class="detail-button">ดูรายละเอียด</a>
            </div>
        </div>
    </div>

    <!-- 7 EARL GREY -->
    <div class="product-card" data-id="7" data-category="tea" data-price="480" data-name="Earl Grey Milk Cake ชาเอิร์ลเกรย์ นมสด หอมละมุน">
        <img src="cake7.png" alt="Earl Grey Milk Cake">
        <div class="product-content">
            <h2>Earl Grey Milk Cake</h2>
            <p>ชาเอิร์ลเกรย์หอมละมุน เนื้อนุ่มละลายในปาก</p>
            <div class="product-bottom">
                <span class="price">฿480</span>
                <a href="detail.html?cake=7" class="detail-button">ดูรายละเอียด</a>
            </div>
        </div>
    </div>

    <!-- 8 BASQUE -->
    <div class="product-card" data-id="8" data-category="cheese" data-price="520" data-name="Basque Cheesecake ชีสเค้ก หน้าไหม้ บาสก์ เนียนนุ่ม">
        <img src="cake8.png" alt="Basque Cheesecake">
        <div class="product-content">
            <h2>Basque Cheesecake</h2>
            <p>ชีสเค้กหน้าไหม้ เนื้อเนียนนุ่ม ละมุนลิ้น</p>
            <div class="product-bottom">
                <span class="price">฿520</span>
                <a href="detail.html?cake=8" class="detail-button">ดูรายละเอียด</a>
            </div>
        </div>
    </div>

    <!-- 9 CARAMEL -->
    <div class="product-card" data-id="9" data-category="cheese" data-price="450" data-name="Caramel Butter Cake คาราเมล บัตเตอร์เค้ก เนย หอมหวาน">
        <img src="cake9.png" alt="Caramel Butter Cake">
        <div class="product-content">
            <h2>Caramel Butter Cake</h2>
            <p>คาราเมลหอมหวาน ผสานความหอมมันของเนย</p>
            <div class="product-bottom">
                <span class="price">฿450</span>
                <a href="detail.html?cake=9" class="detail-button">ดูรายละเอียด</a>
            </div>
        </div>
    </div>

    <!-- 10 VANILLA -->
    <div class="product-card" data-id="10" data-category="fruit" data-price="460" data-name="Vanilla Cloud Cake วานิลลา คลาวด์ ครีมสด นุ่ม">
        <img src="cake10.png" alt="Vanilla Cloud Cake">
        <div class="product-content">
            <h2>Vanilla Cloud Cake</h2>
            <p>วานิลลาครีมสด เนื้อนุ่ม หอมวานิลลา</p>
            <div class="product-bottom">
                <span class="price">฿460</span>
                <a href="detail.html?cake=10" class="detail-button">ดูรายละเอียด</a>
            </div>
        </div>
    </div>

    <!-- NO RESULTS BOX -->
    <div id="noCakesBox" class="no-cakes-box" style="display:none;">
        <span>🧁</span>
        <h3>ไม่พบเค้กที่คุณกำลังค้นหา</h3>
        <p>ลองค้นหาด้วยคำอื่น หรือคลิกดูเค้กทั้งหมดของ Grand Bake</p>
        <button type="button" class="btn-reset-search" onclick="clearCakeSearch()">
            แสดงเค้กทั้งหมด
        </button>
    </div>

</section>


<!-- ================= JAVASCRIPT ================= -->

<script>
// ACCOUNT DROPDOWN
const accountButton = document.getElementById("accountButton");
const accountDropdown = document.getElementById("accountDropdown");

if (accountButton && accountDropdown) {
    accountButton.addEventListener("click", function(event) {
        event.stopPropagation();
        accountDropdown.classList.toggle("account-open");
    });

    document.addEventListener("click", function(event) {
        if (!accountDropdown.contains(event.target) && !accountButton.contains(event.target)) {
            accountDropdown.classList.remove("account-open");
        }
    });
}

// SEARCH & FILTER LOGIC
let selectedCategory = "all";

function selectCategory(cat, btnElem) {
    selectedCategory = cat;
    document.querySelectorAll(".pill").forEach(p => p.classList.remove("active"));
    if (btnElem) btnElem.classList.add("active");
    filterCakes();
}

function clearCakeSearch() {
    const input = document.getElementById("cakeSearchInput");
    input.value = "";
    document.getElementById("clearSearchBtn").style.display = "none";
    selectedCategory = "all";
    document.querySelectorAll(".pill").forEach((p, idx) => {
        if (idx === 0) p.classList.add("active");
        else p.classList.remove("active");
    });
    filterCakes();
}

function filterCakes() {
    const input = document.getElementById("cakeSearchInput");
    const clearBtn = document.getElementById("clearSearchBtn");
    const query = input.value.trim().toLowerCase();
    const sortVal = document.getElementById("cakeSortSelect").value;
    const cards = Array.from(document.querySelectorAll(".product-card"));
    const grid = document.getElementById("productsGrid");
    const noBox = document.getElementById("noCakesBox");
    const resultsCount = document.getElementById("resultsCount");

    if (query) {
        clearBtn.style.display = "flex";
    } else {
        clearBtn.style.display = "none";
    }

    let visibleCount = 0;

    // Filter
    cards.forEach(card => {
        const cat = card.getAttribute("data-category");
        const name = (card.getAttribute("data-name") || "").toLowerCase();

        const matchCat = (selectedCategory === "all" || cat === selectedCategory);
        const matchQuery = (!query || name.includes(query));

        if (matchCat && matchQuery) {
            card.style.display = "";
            visibleCount++;
        } else {
            card.style.display = "none";
        }
    });

    // Sort visible cards
    const sortedCards = cards.filter(c => c.style.display !== "none");
    if (sortVal === "price-asc") {
        sortedCards.sort((a, b) => parseFloat(a.getAttribute("data-price")) - parseFloat(b.getAttribute("data-price")));
    } else if (sortVal === "price-desc") {
        sortedCards.sort((a, b) => parseFloat(b.getAttribute("data-price")) - parseFloat(a.getAttribute("data-price")));
    } else if (sortVal === "name-asc") {
        sortedCards.sort((a, b) => a.querySelector("h2").textContent.localeCompare(b.querySelector("h2").textContent));
    } else {
        // default / featured (sort by data-id)
        sortedCards.sort((a, b) => parseInt(a.getAttribute("data-id")) - parseInt(b.getAttribute("data-id")));
    }

    // Re-append sorted cards
    sortedCards.forEach(c => grid.appendChild(c));

    // Results info
    if (visibleCount === 0) {
        noBox.style.display = "block";
        grid.appendChild(noBox);
        resultsCount.textContent = "ไม่พบรายการเค้กที่ตรงกับเงื่อนไข";
    } else {
        noBox.style.display = "none";
        resultsCount.textContent = `แสดงเค้ก ${visibleCount} จากทั้งหมด ${cards.length} รายการ`;
    }
}
</script>

<!-- CART BADGE & RESPONSIVE NAVBAR -->
<script src="cart-badge.js"></script>
<script src="navbar.js"></script>

</body>

</html>
