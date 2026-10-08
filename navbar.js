/**
 * Grand Bake - Responsive Navigation & Mobile Drawer
 * Impeccable Craft UI: Touch-ergonomic, smooth animations, safe areas
 */
(function() {
    'use strict';

    // 1. Inject core responsive styles for navbar drawer & toggle if not present
    if (!document.getElementById('gb-navbar-styles')) {
        const navStyle = document.createElement('style');
        navStyle.id = 'gb-navbar-styles';
        navStyle.textContent = `
            /* Hamburger button */
            .nav-toggle {
                display: none;
                width: 44px;
                height: 44px;
                padding: 0;
                border: 1px solid #ebdcd0;
                border-radius: 12px;
                background: #fffaf6;
                cursor: pointer;
                align-items: center;
                justify-content: center;
                transition: all 0.2s ease;
                touch-action: manipulation;
                z-index: 1001;
                margin-left: 6px;
                flex-shrink: 0;
            }
            .nav-toggle:hover { background: #f5ebe0; border-color: #dcc7b5; }
            .nav-toggle:active { transform: scale(0.95); }
            .hamburger-box {
                width: 20px;
                height: 14px;
                position: relative;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                pointer-events: none;
            }
            .hamburger-bar {
                display: block;
                width: 100%;
                height: 2px;
                background: #56382b;
                border-radius: 2px;
                transition: transform 0.25s ease, opacity 0.2s ease;
            }
            .nav-toggle.toggle-active .hamburger-bar:nth-child(1) { transform: translateY(6px) rotate(45deg); }
            .nav-toggle.toggle-active .hamburger-bar:nth-child(2) { opacity: 0; }
            .nav-toggle.toggle-active .hamburger-bar:nth-child(3) { transform: translateY(-6px) rotate(-45deg); }

            /* Drawer & Backdrop */
            .drawer-backdrop {
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(45, 25, 15, 0.48);
                backdrop-filter: blur(4px);
                -webkit-backdrop-filter: blur(4px);
                z-index: 99998;
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transition: opacity 0.3s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.3s;
            }
            .drawer-backdrop.backdrop-open {
                opacity: 1;
                visibility: visible;
                pointer-events: auto;
            }
            .mobile-drawer {
                position: fixed;
                top: 0; right: 0; bottom: 0;
                width: min(340px, 86vw);
                background: #fffdfa;
                z-index: 99999;
                box-shadow: -10px 0 35px rgba(55, 30, 15, 0.16);
                display: flex;
                flex-direction: column;
                transform: translateX(100%);
                transition: transform 0.32s cubic-bezier(0.16, 1, 0.3, 1);
                padding: max(16px, env(safe-area-inset-top, 16px)) 0 max(20px, env(safe-area-inset-bottom, 20px));
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
            .mobile-drawer.drawer-open { transform: translateX(0); }
            body.nav-drawer-active { overflow: hidden !important; }

            .drawer-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 8px 20px 16px;
                border-bottom: 1px solid #eee2d7;
            }
            .drawer-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; }
            .drawer-brand img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
            .drawer-brand span { font-family: "Noto Serif Thai", serif; font-size: 17px; font-weight: 600; color: #513322; }
            .drawer-close {
                width: 38px; height: 38px; border: none; border-radius: 50%;
                background: #f5ebe0; color: #68432f; font-size: 16px;
                cursor: pointer; display: flex; align-items: center; justify-content: center;
                transition: background 0.2s, transform 0.2s;
            }
            .drawer-close:hover { background: #ebdcd0; }
            .drawer-close:active { transform: scale(0.92); }

            .drawer-body { padding: 16px 20px 24px; display: flex; flex-direction: column; gap: 18px; }
            .drawer-user-card { background: #fbf5ee; border: 1px solid #eadcd0; border-radius: 16px; padding: 14px; }
            .drawer-user-info { display: flex; align-items: center; gap: 12px; padding-bottom: 12px; border-bottom: 1px solid #ebdcd0; }
            .drawer-user-avatar { width: 42px; height: 42px; border-radius: 50%; background: #f0e2d3; display: flex; align-items: center; justify-content: center; font-size: 18px; }
            .drawer-user-name { font-family: "Noto Serif Thai", serif; font-size: 15px; font-weight: 600; color: #4b3223; }
            .drawer-user-sub { font-size: 11.5px; color: #927867; }
            .drawer-user-actions { display: flex; flex-direction: column; gap: 4px; margin-top: 10px; }
            .drawer-btn-sub { padding: 8px 10px; border-radius: 8px; font-size: 13px; color: #5b3d2c; text-decoration: none; display: flex; align-items: center; gap: 8px; transition: background 0.2s; }
            .drawer-btn-sub:hover { background: #f3e5d7; }
            .drawer-btn-sub.drawer-logout { color: #a72727; }

            .drawer-guest-box { display: flex; flex-direction: column; gap: 10px; }
            .guest-text strong { font-size: 14px; color: #4d3324; display: block; }
            .guest-text small { font-size: 11.5px; color: #8c7263; }
            .guest-buttons { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 4px; }
            .btn-guest-login { background: #7d4930; color: white; padding: 9px 12px; border-radius: 10px; font-size: 13px; font-weight: 500; text-align: center; text-decoration: none; transition: background 0.2s; }
            .btn-guest-login:hover { background: #633822; }
            .btn-guest-register { background: #fff; color: #6d4b38; border: 1px solid #dfcfc2; padding: 9px 12px; border-radius: 10px; font-size: 13px; font-weight: 500; text-align: center; text-decoration: none; transition: background 0.2s; }
            .btn-guest-register:hover { background: #fbf4ee; }

            .drawer-nav { display: flex; flex-direction: column; gap: 6px; }
            .drawer-nav-item {
                display: flex; align-items: center; gap: 12px;
                padding: 13px 14px; border-radius: 14px; font-size: 14.5px;
                color: #513526; text-decoration: none; transition: background 0.2s, transform 0.15s;
                touch-action: manipulation;
            }
            .drawer-nav-item:hover { background: #f6ebe0; transform: translateX(2px); }
            .drawer-nav-item.active { background: #f2e2d3; color: #7d4930; font-weight: 600; }
            .drawer-icon { font-size: 17px; width: 24px; text-align: center; }
            .drawer-text { flex: 1; }
            .drawer-arrow { font-size: 18px; color: #bfa593; }
            .drawer-footer { margin-top: auto; padding-top: 14px; border-top: 1px solid #eee2d7; font-size: 11.5px; color: #9c8374; text-align: center; }

            @media (max-width: 850px) {
                .nav-toggle { display: inline-flex !important; }
            }
        `;
        document.head.appendChild(navStyle);
    }

    // 2. Global safe toggleAccount function (compatible with inline onclick="toggleAccount(event)")
    window.toggleAccount = function(event) {
        if (event) {
            if (event.stopPropagation) event.stopPropagation();
            if (event.preventDefault) event.preventDefault();
        }
        const dropdown = document.getElementById("accountDropdown");
        if (dropdown) {
            dropdown.classList.toggle("account-open");
        }
    };

    // Global listener to close account dropdown when clicking outside
    document.addEventListener("click", function(event) {
        const accountBox = document.querySelector(".account-box");
        const dropdown = document.getElementById("accountDropdown");
        if (accountBox && dropdown && !accountBox.contains(event.target)) {
            dropdown.classList.remove("account-open");
        }
    });

    // 3. Initialize Navbar on DOM ready
    function initNavbar() {
        const navbar = document.querySelector('.navbar');
        if (!navbar) return;

        // Sticky scroll elevation
        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            if (currentScroll > 12) {
                navbar.classList.add('navbar-scrolled');
            } else {
                navbar.classList.remove('navbar-scrolled');
            }
        }, { passive: true });

        // Ensure hamburger button exists in .menu
        const menu = navbar.querySelector('.menu');
        if (!menu) return;

        let navToggle = navbar.querySelector('.nav-toggle') || menu.querySelector('.nav-toggle');
        if (!navToggle) {
            navToggle = document.createElement('button');
            navToggle.type = 'button';
            navToggle.className = 'nav-toggle';
            navToggle.id = 'navToggle';
            navToggle.setAttribute('aria-label', 'เปิดเมนู');
            navToggle.setAttribute('aria-expanded', 'false');
            navToggle.innerHTML = `
                <span class="hamburger-box">
                    <span class="hamburger-bar"></span>
                    <span class="hamburger-bar"></span>
                    <span class="hamburger-bar"></span>
                </span>
            `;
            menu.appendChild(navToggle);
        }

        // Ensure mobile drawer & backdrop exist
        let drawer = document.getElementById('mobileDrawer');
        let backdrop = document.getElementById('drawerBackdrop');

        if (!drawer) {
            backdrop = document.createElement('div');
            backdrop.className = 'drawer-backdrop';
            backdrop.id = 'drawerBackdrop';

            drawer = document.createElement('aside');
            drawer.className = 'mobile-drawer';
            drawer.id = 'mobileDrawer';
            drawer.setAttribute('aria-label', 'เมนูหลักมือถือ');

            const currentPath = window.location.pathname.split('/').pop() || 'index.php';
            const isHomePage = currentPath === '' || currentPath === 'index.php' || currentPath === 'index.html';
            const isProductsPage = currentPath === 'products.php' || currentPath === 'products.html';

            const accountTitleElem = navbar.querySelector('.account-title');
            const isLoggedIn = accountTitleElem && !accountTitleElem.textContent.includes('บัญชีสมาชิก');
            const userName = isLoggedIn ? accountTitleElem.textContent.trim() : '';

            drawer.innerHTML = `
                <div class="drawer-header">
                    <a href="index.php" class="drawer-brand">
                        <img src="logo.jpg" alt="Grand Bake Logo">
                        <span>Grand Bake</span>
                    </a>
                    <button type="button" class="drawer-close" id="drawerClose" aria-label="ปิดเมนู">✕</button>
                </div>
                <div class="drawer-body">
                    <div class="drawer-user-card">
                        ${isLoggedIn ? `
                            <div class="drawer-user-info">
                                <div class="drawer-user-avatar">👤</div>
                                <div>
                                    <div class="drawer-user-name">${escapeHtml(userName)}</div>
                                    <div class="drawer-user-sub">สมาชิก Grand Bake</div>
                                </div>
                            </div>
                            <div class="drawer-user-actions">
                                <a href="profile.php#orders" class="drawer-btn-sub">📦 ประวัติคำสั่งซื้อ</a>
                                <a href="profile.php#account" class="drawer-btn-sub">👤 ดูบัญชีของฉัน</a>
                                <a href="logout.php" class="drawer-btn-sub drawer-logout">🚪 ออกจากระบบ</a>
                            </div>
                        ` : `
                            <div class="drawer-guest-box">
                                <div class="guest-text">
                                    <strong>ยินดีต้อนรับสู่ Grand Bake</strong>
                                    <small>เข้าสู่ระบบเพื่อสั่งซื้อและสะสมความสุข</small>
                                </div>
                                <div class="guest-buttons">
                                    <a href="login.php" class="btn-guest-login">🔑 เข้าสู่ระบบ</a>
                                    <a href="register.php" class="btn-guest-register">📝 สมัครสมาชิก</a>
                                </div>
                            </div>
                        `}
                    </div>

                    <nav class="drawer-nav">
                        <a href="index.php" class="drawer-nav-item ${isHomePage ? 'active' : ''}">
                            <span class="drawer-icon">🏠</span>
                            <span class="drawer-text">หน้าแรก</span>
                            <span class="drawer-arrow">›</span>
                        </a>
                        <a href="products.php" class="drawer-nav-item ${isProductsPage ? 'active' : ''}">
                            <span class="drawer-icon">🧁</span>
                            <span class="drawer-text">เค้กและสินค้าทั้งหมด</span>
                            <span class="drawer-arrow">›</span>
                        </a>
                        <a href="index.php#about" class="drawer-nav-item">
                            <span class="drawer-icon">✨</span>
                            <span class="drawer-text">เกี่ยวกับเรา</span>
                            <span class="drawer-arrow">›</span>
                        </a>
                        <a href="index.php#contact" class="drawer-nav-item">
                            <span class="drawer-icon">💬</span>
                            <span class="drawer-text">ติดต่อเรา</span>
                            <span class="drawer-arrow">›</span>
                        </a>
                        <a href="cart.html" class="drawer-nav-item drawer-cart-item">
                            <span class="drawer-icon">🛒</span>
                            <span class="drawer-text">ตะกร้าสินค้า</span>
                            <span class="drawer-arrow">›</span>
                        </a>
                    </nav>

                    <div class="drawer-footer">
                        <div class="drawer-contact-line">
                            📍 เค้กสไตล์เกาหลี พรีเมียม อบสดใหม่ทุกวัน
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(backdrop);
            document.body.appendChild(drawer);
        }

        const closeBtn = drawer.querySelector('.drawer-close');

        function openDrawer() {
            drawer.classList.add('drawer-open');
            if (backdrop) backdrop.classList.add('backdrop-open');
            navToggle.classList.add('toggle-active');
            navToggle.setAttribute('aria-expanded', 'true');
            document.body.classList.add('nav-drawer-active');
        }

        function closeDrawer() {
            drawer.classList.remove('drawer-open');
            if (backdrop) backdrop.classList.remove('backdrop-open');
            navToggle.classList.remove('toggle-active');
            navToggle.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('nav-drawer-active');
        }

        navToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            if (drawer.classList.contains('drawer-open')) {
                closeDrawer();
            } else {
                openDrawer();
            }
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                closeDrawer();
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', closeDrawer);
        }

        const drawerLinks = drawer.querySelectorAll('.drawer-nav-item, .drawer-btn-sub');
        drawerLinks.forEach(function(link) {
            link.addEventListener('click', closeDrawer);
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                if (drawer.classList.contains('drawer-open')) {
                    closeDrawer();
                }
                const accountDropdown = document.getElementById('accountDropdown');
                if (accountDropdown && accountDropdown.classList.contains('account-open')) {
                    accountDropdown.classList.remove('account-open');
                }
            }
        });

        // Safe fallback for account button ONLY if page has no inline onclick handler
        const accountBtn = navbar.querySelector('.account-button');
        if (accountBtn && !accountBtn.hasAttribute('onclick') && !accountBtn.dataset.bound) {
            accountBtn.dataset.bound = '1';
            accountBtn.addEventListener('click', function(e) {
                window.toggleAccount(e);
            });
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNavbar);
    } else {
        initNavbar();
    }
})();
