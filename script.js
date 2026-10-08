const cakes = [

    {
        name: "Strawberry<br>Cream Cake",
        description: "สตรอว์เบอร์รีครีมสด<br>หวานละมุน หอมสดชื่น",
        price: "฿450",
        image: "cake1.png"
    },

    {
        name: "Chocolate<br>Fudge Cake",
        description: "เค้กช็อกโกแลตเข้มข้น<br>หอมละมุนทุกคำ",
        price: "฿490",
        image: "cake2.png"
    },

    {
        name: "Blueberry<br>Cream Cake",
        description: "บลูเบอร์รีครีมสด<br>หวานอมเปรี้ยว สดชื่น",
        price: "฿470",
        image: "cake3.png"
    }

];


let currentCake = 0;


// แสดงสินค้า
function showCake(index) {

    currentCake = index;

    const cake = cakes[currentCake];

    document.getElementById("cake-name").innerHTML =
        cake.name;

    document.getElementById("cake-description").innerHTML =
        cake.description;

    document.getElementById("cake-price").innerHTML =
        cake.price;

    document.getElementById("cake-image").src =
        cake.image;

    document.getElementById("cake-image").alt =
        cake.name.replace("<br>", " ");

    // เปลี่ยนจุด Slider
    const dots = document.querySelectorAll(".dot");

    dots.forEach(function(dot, i) {

        dot.classList.remove("active");

        if (i === currentCake) {
            dot.classList.add("active");
        }

    });

}


// ปุ่มถัดไป
function nextCake() {

    currentCake++;

    if (currentCake >= cakes.length) {
        currentCake = 0;
    }

    showCake(currentCake);
}


// ปุ่มย้อนกลับ
function previousCake() {

    currentCake--;

    if (currentCake < 0) {
        currentCake = cakes.length - 1;
    }

    showCake(currentCake);
}


// เปลี่ยนอัตโนมัติทุก 5 วินาที
setInterval(function() {

    nextCake();

}, 5000);


// เริ่มต้นที่ Strawberry
showCake(0);


// ==============================
// ACCOUNT DROPDOWN
// ==============================

function toggleAccount(event) {

    event.stopPropagation();

    var dropdown = document.getElementById("accountDropdown");

    if (dropdown) {
        dropdown.classList.toggle("account-open");
    }

}


document.addEventListener("click", function(event) {

    var dropdown = document.getElementById("accountDropdown");
    var button = document.querySelector(".account-button");

    if (
        dropdown &&
        !dropdown.contains(event.target) &&
        button &&
        !button.contains(event.target)
    ) {
        dropdown.classList.remove("account-open");
    }

});


// ==============================
// HAMBURGER MENU
// ==============================

function toggleMenu() {

    var menu = document.querySelector(".menu");

    if (menu) {
        menu.classList.toggle("active");
    }

}
/* =================================
   CAKE RECOMMENDATION FILTER
================================= */

const cakeFilters =
    document.querySelectorAll(".cake-filter");

const recommendCards =
    document.querySelectorAll(".recommend-card");


cakeFilters.forEach(function(button) {

    button.addEventListener("click", function() {

        /* เปลี่ยนปุ่มที่ active */

        cakeFilters.forEach(function(btn) {
            btn.classList.remove("active");
        });

        button.classList.add("active");


        /* อ่านประเภทที่เลือก */

        const filter =
            button.getAttribute("data-filter");


        /* แสดง / ซ่อนเค้ก */

        recommendCards.forEach(function(card) {

            const categories =
                card.getAttribute("data-category");


            if (
                filter === "all" ||
                categories.includes(filter)
            ) {

                card.style.display = "block";

            } else {

                card.style.display = "none";

            }

        });

    });

});