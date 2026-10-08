/**
 * Grand Bake - Responsive Navigation & Mobile Drawer
 * Impeccable Craft UI: Touch-ergonomic, smooth animations, safe areas
 */
(function() {
    'use strict';

    function initNavbar() {
        const navbar = document.querySelector('.navbar');
        if (!navbar) return;

        // 1. Scroll elevation effect on sticky navbar
        let lastScroll = 0;
        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            if (currentScroll > 12) {
                navbar.classList.add('navbar-scrolled');
            } else {
                navbar.classList.remove('navbar-scrolled');
            }
            lastScroll = currentScroll;
        }, { passive: true });

        // 2. Ensure mobile hamburger toggle button exists
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

        // 3. Ensure mobile drawer & backdrop exist
        let drawer = document.getElementById('mobileDrawer');
        let backdrop = document.getElementById('drawerBackdrop');

        if (!drawer) {
            // Build drawer dynamically from page context
            backdrop = document.createElement('div');
            backdrop.className = 'drawer-backdrop';
            backdrop.id = 'drawerBackdrop';

            drawer = document.createElement('aside');
            drawer.className = 'mobile-drawer';
            drawer.id = 'mobileDrawer';
            drawer.setAttribute('aria-label', 'เมนูหลักมือถือ');

            // Gather page links and current path
            const currentPath = window.location.pathname.split('/').pop() || 'index.php';
            const isHomePage = currentPath === '' || currentPath === 'index.php' || currentPath === 'index.html';
            const isProductsPage = currentPath === 'products.php' || currentPath === 'products.html';

            // Check if user is logged in (from account dropdown text or avatar if present)
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

        // Helper to toggle drawer state
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

        // Close when clicking nav links inside drawer
        const drawerLinks = drawer.querySelectorAll('.drawer-nav-item, .drawer-btn-sub');
        drawerLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                // If anchor hash on same page, close drawer smoothly
                closeDrawer();
            });
        });

        // Keyboard accessibility: Escape key closes drawer and dropdown
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

        // 4. Uniform account dropdown handling
        const accountBox = navbar.querySelector('.account-box');
        const accountBtn = navbar.querySelector('.account-button') || document.getElementById('accountButton');
        const accountDropdown = document.getElementById('accountDropdown');

        if (accountBtn && accountDropdown) {
            accountBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                accountDropdown.classList.toggle('account-open');
            });

            document.addEventListener('click', function(e) {
                if (!accountDropdown.contains(e.target) && !accountBtn.contains(e.target)) {
                    accountDropdown.classList.remove('account-open');
                }
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
