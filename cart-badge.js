/**
 * Grand Bake - Cart Badge Counter
 * แสดงป้ายตัวเลขจำนวนสินค้าบนไอคอนตะกร้าอัตโนมัติทุกหน้า
 */
(function() {
    // ฉีดสไตล์ CSS สำหรับ Cart Badge
    const style = document.createElement("style");
    style.textContent = `
        .cart-icon-wrapper {
            position: relative !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-decoration: none !important;
        }
        .cart-badge {
            position: absolute !important;
            top: -7px !important;
            right: -10px !important;
            background: #d33c2a !important;
            color: #ffffff !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            min-width: 19px !important;
            height: 19px !important;
            padding: 0 4px !important;
            border-radius: 10px !important;
            display: none;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: 0 2px 7px rgba(211, 60, 42, 0.45) !important;
            border: 2px solid #ffffff !important;
            line-height: 1 !important;
            font-family: 'Prompt', sans-serif !important;
            z-index: 99 !important;
            pointer-events: none !important;
            animation: cartBadgePop 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
        }
        @keyframes cartBadgePop {
            0% { transform: scale(0.3); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
    `;
    document.head.appendChild(style);

    // ฟังก์ชันอัปเดตตัวเลขในตะกร้า
    window.updateCartBadge = function() {
        try {
            const cart = JSON.parse(localStorage.getItem("grandBakeCart")) || [];
            const totalCount = cart.reduce((sum, item) => sum + (parseInt(item.quantity) || 1), 0);

            // ค้นหาทุกลิงก์ที่ชี้ไปตะกร้าสินค้า
            const cartLinks = document.querySelectorAll("a[href='cart.html'], .cart-icon, .cart-link");
            cartLinks.forEach(link => {
                link.classList.add("cart-icon-wrapper");
                let badge = link.querySelector(".cart-badge");
                if (!badge) {
                    badge = document.createElement("span");
                    badge.className = "cart-badge";
                    link.appendChild(badge);
                }

                if (totalCount > 0) {
                    badge.textContent = totalCount > 99 ? '99+' : totalCount;
                    badge.style.display = 'inline-flex';
                } else {
                    badge.style.display = 'none';
                }
            });
        } catch (e) {
            console.error("Cart badge error:", e);
        }
    };

    // อัปเดตทันทีเมื่อโหลดหน้าเสร็จ
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", window.updateCartBadge);
    } else {
        window.updateCartBadge();
    }

    // อัปเดตเมื่อมีการเปลี่ยนแปลงในหน้าต่างอื่น (Tab อื่น)
    window.addEventListener("storage", window.updateCartBadge);
})();
