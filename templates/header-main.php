<!-- Navigation -->
<nav class="navbar fixed-top" id="mainNavbar">
    <div class="navbar-container">
        <!-- Логотип слева -->
        <a class="navbar-logo" href="index.php">
            <img src="images/logo.svg" alt="Логотип сайта" />
        </a>

        <!-- Гамбургер для мобильных и планшетов -->
        <button class="navbar-toggler" type="button" id="navbarToggle" aria-label="Открыть меню" aria-expanded="false">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Меню -->
        <div class="navbar-menu" id="navbarMenu">
            <ul class="navbar-nav">
                <li>
                    <a class="nav-link" href="/cabinet/?tab=register">
                        <img src="https://gamestock.shop/icons/sign-up.png" alt="Регистрация">
                        <span>Регистрация</span>
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="/catalog.php">
                        <img src="https://gamestock.shop/icons/list.png" alt="Каталог">
                        <span>Каталог</span>
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="/cabinet">
                        <img src="https://gamestock.shop/icons/login.png" alt="Вход">
                        <span>Вход</span>
                    </a>
                </li>
                <li>
                    <a class="nav-link" href="../index.php#fast_order">
                        <img src="https://gamestock.shop/icons/order.png" alt="Быстрый заказ">
                        <span>Быстрый заказ</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Отступ для фиксированной навигации -->
<div class="navbar-spacer"></div>

<style>
    /* ===== СБРОС СТИЛЕЙ ===== */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    /* Отступ для фиксированной навигации */
    .navbar-spacer {
        display: block;
        width: 100%;
        height: 0;
        transition: height 0.2s ease;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        margin: 0;
        padding: 0;
    }

    /* ===== НАВИГАЦИЯ ===== */
    .navbar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1030;
        background-color: rgb(2, 55, 241);
        transition: all 0.2s ease;
        height: 64px;
        width: 100%;
    }

    .navbar-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        height: 100%;
        padding: 0 1rem;
        margin: 0 auto;
        max-width: 100%;
    }

    /* Логотип */
    .navbar-logo {
        display: flex;
        align-items: center;
        height: 100%;
    }

    .navbar-logo img {
        height: 32px;
        width: auto;
        transition: transform 0.2s ease;
    }

    .navbar-logo:hover img {
        transform: scale(1.05);
    }

    /* Гамбургер меню */
    .navbar-toggler {
        display: none;
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 8px;
        z-index: 1002;
    }

    .navbar-toggler-icon {
        display: block;
        width: 24px;
        height: 2px;
        background-color: white;
        position: relative;
        transition: all 0.3s ease;
    }

    .navbar-toggler-icon::before,
    .navbar-toggler-icon::after {
        content: '';
        position: absolute;
        width: 24px;
        height: 2px;
        background-color: white;
        left: 0;
        transition: all 0.3s ease;
    }

    .navbar-toggler-icon::before {
        top: -6px;
    }

    .navbar-toggler-icon::after {
        bottom: -6px;
    }

    .navbar-toggler.active .navbar-toggler-icon {
        background-color: transparent;
    }

    .navbar-toggler.active .navbar-toggler-icon::before {
        transform: rotate(45deg);
        top: 0;
    }

    .navbar-toggler.active .navbar-toggler-icon::after {
        transform: rotate(-45deg);
        bottom: 0;
    }

    /* Меню */
    .navbar-menu {
        display: flex;
        align-items: center;
        height: 100%;
    }

    /* Навигационный список - ГОРИЗОНТАЛЬНЫЙ НА ДЕСКТОПЕ */
    .navbar-nav {
        display: flex;
        list-style: none;
        margin: 0;
        padding: 0;
        align-items: center;
        gap: 8px;
    }

    /* Стили ссылок */
    .nav-link {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        color: white;
        text-decoration: none;
        transition: all 0.2s ease;
        white-space: nowrap;
        border-radius: 4px;
        font-weight: 500;
        font-size: 14px;
        line-height: 1;
    }

    .nav-link:hover,
    .nav-link:focus {
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
        text-decoration: none;
    }

    .nav-link img {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
        display: block;
    }

    /* ===== МОБИЛЬНЫЕ УСТРОЙСТВА (до 1023px) ===== */
    @media (max-width: 1023px) {
        .navbar-toggler {
            display: block;
        }

        .navbar-menu {
            position: fixed;
            top: 64px;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgb(2, 55, 241);
            padding: 20px;
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
            z-index: 1020;
            overflow-y: auto;
            display: block;
            height: auto;
        }

        .navbar-menu.active {
            transform: translateX(0);
        }

        /* ВЕРТИКАЛЬНОЕ МЕНЮ НА МОБИЛЬНЫХ */
        .navbar-nav {
            flex-direction: column;
            align-items: stretch;
            gap: 4px;
        }

        .nav-link {
            width: 100%;
            padding: 14px 16px;
            font-size: 16px;
            gap: 10px;
        }

        .nav-link img {
            width: 20px;
            height: 20px;
        }
    }

    /* ПЛАНШЕТЫ (768px - 1023px) */
    @media (min-width: 768px) and (max-width: 1023px) {
        .navbar-container {
            padding: 0 24px;
        }

        .navbar-menu {
            width: 320px;
            left: auto;
            right: 0;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
        }
    }

    /* ===== ДЕСКТОП (1024px и больше) - МЕНЮ ПРИЖАТО К ПРАВОМУ КРАЮ ===== */
    @media (min-width: 1024px) {
        .navbar {
            background-color: transparent !important;
            padding: 1.75rem 3rem 0 3rem !important;
            height: auto;
        }

        .navbar.top-nav-collapse {
            padding: 0.5rem 3rem !important;
            background-color: rgb(2, 55, 241) !important;
            height: 64px;
        }

        .navbar-container {
            padding: 0 3rem;
            max-width: none;
            width: 100%;
        }

        .navbar-logo img {
            height: 40px;
        }

        .navbar.top-nav-collapse .navbar-logo img {
            height: 32px;
        }

        /* МЕНЮ ПРИЖАТО К ПРАВОМУ КРАЮ */
        .navbar-menu {
            margin-left: auto;
            justify-content: flex-end;
            width: auto;
            flex-grow: 0;
        }

        /* ГОРИЗОНТАЛЬНОЕ МЕНЮ НА ДЕСКТОПЕ */
        .navbar-nav {
            flex-direction: row !important;
            display: flex !important;
            gap: 12px;
            margin-right: 0;
        }

        .nav-link {
            padding: 8px 16px;
            font-size: 14px;
            gap: 8px;
        }

        .nav-link img {
            width: 20px;
            height: 20px;
        }

        /* Эффект при скролле */
        .navbar:not(.top-nav-collapse) .nav-link {
            padding: 10px 20px;
        }
    }

    /* Большие экраны (1280px+) */
    @media (min-width: 1280px) {
        .navbar {
            padding-left: 4rem !important;
            padding-right: 4rem !important;
        }

        .navbar.top-nav-collapse {
            padding-left: 4rem !important;
            padding-right: 4rem !important;
        }

        .navbar-container {
            padding: 0 4rem;
        }

        /* МЕНЮ ПРИЖАТО К ПРАВОМУ КРАЮ */
        .navbar-menu {
            margin-right: 0;
            padding-right: 0;
        }

        .navbar-nav {
            gap: 16px;
        }

        .nav-link {
            padding: 8px 20px;
            font-size: 15px;
            gap: 10px;
        }

        .nav-link img {
            width: 22px;
            height: 22px;
        }

        .navbar:not(.top-nav-collapse) .nav-link {
            padding: 12px 24px;
        }
    }

    /* Очень широкие экраны (1600px+) */
    @media (min-width: 1600px) {
        .navbar {
            padding-left: 6rem !important;
            padding-right: 6rem !important;
        }

        .navbar.top-nav-collapse {
            padding-left: 6rem !important;
            padding-right: 6rem !important;
        }

        .navbar-container {
            padding: 0 6rem;
        }
    }

    /* Очень маленькие экраны (до 359px) */
    @media (max-width: 359px) {
        .navbar-container {
            padding: 0 12px;
        }

        .navbar-logo img {
            height: 28px;
        }

        .nav-link {
            padding: 12px 14px;
            font-size: 15px;
        }
    }

    /* ===== СТИЛИ ДЛЯ ГЛАВНОЙ СТРАНИЦЫ ===== */
    body.home-page .navbar {
        background-color: transparent !important;
        box-shadow: none !important;
    }

    body.home-page .navbar.top-nav-collapse {
        background-color: rgb(2, 55, 241) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
    }

    /* ===== АНИМАЦИЯ ===== */
    @keyframes slideDown {
        from {
            transform: translateY(-100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .navbar {
        animation: slideDown 0.5s ease-out;
    }

    /* Убираем анимацию после загрузки */
    .navbar.loaded {
        animation: none;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const navbar = document.getElementById('mainNavbar');
        const navbarToggle = document.getElementById('navbarToggle');
        const navbarMenu = document.getElementById('navbarMenu');
        const navLinks = document.querySelectorAll('.nav-link');
        const navbarSpacer = document.querySelector('.navbar-spacer');

        // Определяем главная ли это страница
        const isHomePage = window.location.pathname === '/' ||
            window.location.pathname === '/index.php' ||
            window.location.pathname === '/index.html' ||
            window.location.pathname.endsWith('/index.php') ||
            window.location.pathname.endsWith('/index.html');

        if (isHomePage) {
            document.body.classList.add('home-page');
        }

        // Функция для обновления отступа навигации
        function updateNavbarSpacer() {
            if (!navbarSpacer) return;

            const navbarHeight = navbar.offsetHeight;
            navbarSpacer.style.height = navbarHeight + 'px';

            // Если на главной и без скролла - добавляем дополнительный отступ
            if (isHomePage && !navbar.classList.contains('top-nav-collapse')) {
                if (window.innerWidth >= 1024) {
                    navbarSpacer.style.height = (navbarHeight + 20) + 'px';
                }
            }
        }

        // Обработка скролла для главной страницы
        function handleScroll() {
            if (!isHomePage) return;

            if (window.scrollY > 50) {
                navbar.classList.add('top-nav-collapse');
            } else {
                navbar.classList.remove('top-nav-collapse');
            }

            // Обновляем отступ после скролла
            setTimeout(updateNavbarSpacer, 10);
        }

        // На других страницах сразу добавляем стиль
        if (!isHomePage) {
            navbar.classList.add('top-nav-collapse');
        } else {
            window.addEventListener('scroll', handleScroll);
        }

        // Инициализация отступа
        updateNavbarSpacer();
        handleScroll(); // Для главной страницы

        // Обновляем отступ при изменении размера окна
        window.addEventListener('resize', function() {
            updateNavbarSpacer();
            if (isHomePage) {
                handleScroll();
            }
        });

        // Переключение мобильного меню
        navbarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            this.classList.toggle('active');
            this.setAttribute('aria-expanded', !isExpanded);
            navbarMenu.classList.toggle('active');

            // Блокируем скролл при открытом меню на мобильных
            if (window.innerWidth <= 1023 && navbarMenu.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });

        // Закрытие меню при клике на ссылку
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 1023 && navbarMenu.classList.contains('active')) {
                    navbarToggle.classList.remove('active');
                    navbarToggle.setAttribute('aria-expanded', 'false');
                    navbarMenu.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });

        // Закрытие меню при клике вне его
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 1023 &&
                navbarMenu.classList.contains('active') &&
                !navbarMenu.contains(event.target) &&
                !navbarToggle.contains(event.target)) {

                navbarToggle.classList.remove('active');
                navbarToggle.setAttribute('aria-expanded', 'false');
                navbarMenu.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        // Закрытие меню при изменении размера окна
        window.addEventListener('resize', function() {
            if (window.innerWidth > 1023) {
                navbarToggle.classList.remove('active');
                navbarToggle.setAttribute('aria-expanded', 'false');
                navbarMenu.classList.remove('active');
                document.body.style.overflow = '';
            }

            // Обновляем скролл-эффект
            if (isHomePage) {
                handleScroll();
            }
        });

        // Поддержка клавиши ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && window.innerWidth <= 1023) {
                if (navbarMenu.classList.contains('active')) {
                    navbarToggle.classList.remove('active');
                    navbarToggle.setAttribute('aria-expanded', 'false');
                    navbarMenu.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }
        });

        // Убираем анимацию после загрузки
        setTimeout(() => {
            navbar.classList.add('loaded');
            updateNavbarSpacer();
        }, 500);

        // Плавный скролл для якорных ссылок
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href !== '#' && href.startsWith('#')) {
                    const targetElement = document.querySelector(href);
                    if (targetElement) {
                        e.preventDefault();

                        // Если меню открыто на мобильном - закрываем его
                        if (window.innerWidth <= 1023 && navbarMenu.classList.contains('active')) {
                            navbarToggle.classList.remove('active');
                            navbarToggle.setAttribute('aria-expanded', 'false');
                            navbarMenu.classList.remove('active');
                            document.body.style.overflow = '';

                            setTimeout(() => {
                                const navbarHeight = navbar.offsetHeight;
                                window.scrollTo({
                                    top: targetElement.offsetTop - navbarHeight,
                                    behavior: 'smooth'
                                });
                            }, 300);
                        } else {
                            const navbarHeight = navbar.offsetHeight;
                            window.scrollTo({
                                top: targetElement.offsetTop - navbarHeight,
                                behavior: 'smooth'
                            });
                        }
                    }
                }
            });
        });

        // Обновляем отступ при любых изменениях навигации
        const observer = new MutationObserver(function() {
            setTimeout(updateNavbarSpacer, 10);
        });

        observer.observe(navbar, {
            attributes: true,
            attributeFilter: ['class', 'style']
        });
    });
</script>