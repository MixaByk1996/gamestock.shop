<!-- Navigation -->
<nav class="navbar fixed-top" id="mainNavbar">
    <!-- Убираем контейнер и делаем свои отступы -->
    <div class="w-full px-4 sm:px-8 lg:px-12 flex items-center justify-between">
        <!-- Логотип слева -->
        <a class="inline-block py-0.5 text-xl whitespace-nowrap hover:no-underline focus:no-underline" href="index.php">
            <img src="images/logo.svg" alt="alternative" class="h-8" />
        </a>
        <!-- Меню справа -->
        <div class="navbar-collapse offcanvas-collapse lg:flex lg:items-center" id="navbarsExampleDefault">
            <ul class="pl-0 mt-3 mb-2 flex flex-col list-none lg:mt-0 lg:mb-0 lg:flex-row lg:items-center lg:gap-6">
                <li>
                    <a class="nav-link page-scroll flex items-center gap-2" href="/cabinet">
                        <img class="inline w-5 h-5" src="https://gamestock.shop/icons/sign-up.png" alt="icon"> Регистрация
                    </a>
                </li>
                <li>
                    <a class="nav-link page-scroll flex items-center gap-2" href="/catalog.php">
                        <img class="inline w-5 h-5" src="https://gamestock.shop/icons/list.png" alt="icon"> Каталог
                    </a>
                </li>
                <li>
                    <a class="nav-link page-scroll flex items-center gap-2" href="/cabinet">
                        <img class="inline w-5 h-5" src="https://gamestock.shop/icons/login.png" alt="icon"> Вход
                    </a>
                </li>
                <li>
                    <a class="nav-link page-scroll flex items-center gap-2" href="../index.php#fast_order">
                        <img class="inline w-5 h-5" src="https://gamestock.shop/icons/order.png" alt="icon"> Быстрый заказ
                    </a>
                </li>
            </ul>
        </div> <!-- end of navbar-collapse -->
    </div>
</nav> <!-- end of navbar -->
<style>
    /* Navigation Styles */
    .navbar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1030;
        background-color: rgb(2, 55, 241);
        padding: 0.5rem 1rem;
        display: flex;
        align-items: center;
        font-weight: 600;
        font-size: 0.875rem;
        line-height: 0.75rem;
        transition: all 0.2s ease;
        height: 64px;
        box-sizing: border-box;
    }

    .navbar > div {
        display: flex;
        width: 100%;
        align-items: center;
        justify-content: space-between;
    }

    .navbar.top-nav-collapse {
        background-color: rgb(2, 55, 241);
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .navbar-toggler-icon {
        content: "";
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='30' height='30' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.9%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: center center;
        background-size: 100% 100%;
        display: inline-block;
        width: 30px;
        height: 30px;
    }

    /* Мобильное меню */
    .navbar-collapse {
        display: none;
    }

    .offcanvas-collapse {
        position: fixed;
        top: 64px;
        bottom: 0;
        left: 100%;
        width: 100%;
        padding: 1rem;
        overflow-y: auto;
        visibility: hidden;
        background-color: rgb(2, 55, 241);
        transition: transform 0.3s ease-in-out, visibility 0.3s ease-in-out;
        z-index: 1020;
    }

    .offcanvas-collapse.open {
        visibility: visible;
        transform: translateX(-100%);
        display: block;
    }

    .nav-link {
        display: flex;
        align-items: center;
        padding: 0.5rem 0.75rem;
        color: white;
        text-decoration: none;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .nav-link:hover,
    .nav-link:focus {
        color: white;
        text-decoration: none;
        opacity: 0.9;
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 4px;
    }

    .nav-link img {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
    }

    /* Десктопная версия - ключевые изменения */
    @media (min-width: 1024px) {
        .navbar {
            background-color: transparent;
            padding: 1.75rem 3rem 0 3rem;
            height: auto;
        }

        .navbar.top-nav-collapse {
            padding: 0.5rem 3rem;
            background-color: rgb(2, 55, 241) !important;
            height: 64px;
        }

        /* Логотип остается слева */
        .navbar > div > a {
            margin-left: 0;
        }

        /* Меню выравниваем строго по правому краю */
        .navbar-collapse {
            display: flex !important;
            margin-left: auto; /* Это ключевое свойство */
            justify-content: flex-end;
        }

        .offcanvas-collapse {
            position: static;
            display: flex !important;
            visibility: visible;
            transform: none !important;
            background-color: transparent;
            width: auto;
            padding: 0;
            margin: 0;
            top: auto;
            bottom: auto;
            left: auto;
            overflow-y: visible;
        }

        /* Горизонтальное меню */
        .offcanvas-collapse ul {
            display: flex !important;
            flex-direction: row !important;
            align-items: center;
            justify-content: flex-end;
            gap: 1.5rem;
            margin: 0;
            padding: 0;
            list-style: none;
            width: 100%;
        }

        .offcanvas-collapse li {
            margin: 0 !important;
            padding: 0 !important;
        }

        .nav-link {
            padding: 0.5rem 1rem;
            margin: 0;
        }

        /* Убираем все отступы для мобильной версии на десктопе */
        .pl-0, .mt-3, .mb-2 {
            padding-left: 0 !important;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
        }
    }

    /* Убираем лишние отступы для мобильного меню */
    @media (max-width: 1023px) {
        .offcanvas-collapse ul {
            padding-left: 0;
            margin: 1rem 0;
        }

        .offcanvas-collapse li {
            margin-bottom: 0.5rem;
        }

        .offcanvas-collapse .nav-link {
            padding: 0.75rem 1rem;
        }
    }

    /* Для очень широких экранов */
    @media (min-width: 1280px) {
        .navbar {
            padding-left: 4rem;
            padding-right: 4rem;
        }

        .navbar.top-nav-collapse {
            padding-left: 4rem;
            padding-right: 4rem;
        }

        .offcanvas-collapse ul {
            gap: 2rem;
        }
    }
</style>
<script>
    // Скрипт для топ-коллапс эффекта (как на главной странице)
    document.addEventListener('DOMContentLoaded', function() {
        const navbar = document.getElementById('mainNavbar');
        const toggler = document.querySelector('[data-toggle="offcanvas"]');
        const menu = document.getElementById('navbarsExampleDefault');

        // На главной странице добавляем класс при скролле
        const isHomePage = window.location.pathname === '/index.php' ||
            window.location.pathname === '/' ||
            window.location.pathname === '/index.html' ||
            window.location.pathname.endsWith('/') ||
            window.location.pathname === '';

        if (isHomePage) {
            function updateNavbar() {
                if (window.scrollY > 50) {
                    navbar.classList.add('top-nav-collapse');
                } else {
                    navbar.classList.remove('top-nav-collapse');
                }
            }

            // Проверяем при загрузке
            updateNavbar();

            // Проверяем при скролле
            window.addEventListener('scroll', updateNavbar);
        } else {
            // На других страницах навигация всегда с синим фоном
            navbar.classList.add('top-nav-collapse');
        }

        // Обработка мобильного меню
        if (toggler && menu) {
            toggler.addEventListener('click', function() {
                menu.classList.toggle('open');
                this.setAttribute('aria-expanded', menu.classList.contains('open'));
            });

            // Закрытие меню при клике на ссылку
            const links = menu.querySelectorAll('.nav-link');
            links.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 1024) {
                        menu.classList.remove('open');
                        toggler.setAttribute('aria-expanded', 'false');
                    }
                });
            });

            // Закрытие меню при клике вне его
            document.addEventListener('click', function(event) {
                if (window.innerWidth < 1024 &&
                    !menu.contains(event.target) &&
                    !toggler.contains(event.target)) {
                    menu.classList.remove('open');
                    toggler.setAttribute('aria-expanded', 'false');
                }
            });

            // Закрытие меню при изменении размера окна
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 1024) {
                    menu.classList.remove('open');
                    toggler.setAttribute('aria-expanded', 'false');
                }
            });
        }
    });
</script>