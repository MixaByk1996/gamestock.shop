<?php
// templates/footer.php - Общий подвал для всего сайта
?>

<!-- Copyright -->
<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-content">
            <!-- Ссылки на документы -->
            <ul class="footer-links">
                <li><a href="/privacy.php">Правила</a></li>
                <li><a href="/privacy.php">Соглашение</a></li>
                <li><a href="/privacy.php">Конфиденциальность</a></li>
            </ul>

            <!-- Копирайт -->
            <div class="footer-copyright">
                <p class="copyright-text">gamestock.shop &copy; 2019-<?= date('Y') ?></p>
            </div>
        </div>
    </div>
</footer>

<style>
    /* ===== ОСНОВНЫЕ СТИЛИ ДЛЯ ПРИЖАТИЯ ФУТЕРА К НИЗУ ===== */
    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
    }

    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    /* Основной контент должен растягиваться */
    .page-content-wrapper {
        flex: 1 0 auto;
        width: 100%;
    }

    /* ===== СТИЛИ ФУТЕРА (ПОЛНАЯ ШИРИНА) ===== */
    .site-footer {
        background-color: rgb(2, 55, 241);
        color: white;
        width: 100%;
        flex-shrink: 0;
        margin-top: auto;
    }

    /* Контейнер для центрирования контента */
    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
        width: 100%;
        box-sizing: border-box;
    }

    /* Содержимое футера */
    .footer-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1.5rem;
        width: 100%;
    }

    /* Ссылки в футере */
    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 1rem 2rem;
        width: 100%;
    }

    .footer-links li {
        margin: 0;
    }

    .footer-links a {
        color: white;
        text-decoration: none;
        font-weight: bold;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        padding: 0.5rem 0;
        display: inline-block;
    }

    .footer-links a:hover {
        opacity: 0.9;
        text-decoration: underline;
        transform: translateY(-2px);
    }

    /* Копирайт */
    .footer-copyright {
        text-align: center;
        width: 100%;
    }

    .copyright-text {
        margin: 0;
        font-weight: bold;
        font-size: 0.95rem;
        color: white;
    }

    /* ===== АДАПТИВНОСТЬ ===== */

    /* Мобильные устройства (до 640px) */
    @media (max-width: 640px) {
        .footer-container {
            padding: 1.5rem 1rem;
        }

        .footer-content {
            gap: 1.2rem;
        }

        .footer-links {
            flex-direction: column;
            align-items: center;
            gap: 0.8rem;
        }

        .footer-links a {
            font-size: 0.9rem;
            padding: 0.4rem 0;
        }

        .copyright-text {
            font-size: 0.9rem;
        }
    }

    /* Планшеты (641px - 768px) */
    @media (min-width: 641px) and (max-width: 768px) {
        .footer-container {
            padding: 1.75rem 1.5rem;
        }

        .footer-links {
            gap: 1.5rem;
        }

        .footer-links a {
            font-size: 0.92rem;
        }
    }

    /* Десктоп (1024px и больше) - ПОЛНАЯ ШИРИНА */
    @media (min-width: 769px) {
        .site-footer {
            /* На десктопе растягиваем фон на всю ширину */
            background: linear-gradient(90deg,
            rgb(2, 55, 241) 0%,
            rgb(2, 55, 241) 50%,
            rgb(2, 55, 241) 100%
            );
        }

        .footer-container {
            padding: 2rem 2rem;
        }

        .footer-content {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }

        .footer-links {
            justify-content: flex-start;
            flex-wrap: nowrap;
            gap: 2rem;
            width: auto;
            order: 1;
        }

        .footer-copyright {
            text-align: right;
            width: auto;
            order: 2;
        }

        .footer-links a {
            font-size: 1rem;
        }

        .copyright-text {
            font-size: 1rem;
        }
    }

    /* Большие экраны (1200px+) */
    @media (min-width: 1200px) {
        .footer-container {
            padding: 2.5rem 2rem;
        }

        .footer-links {
            gap: 3rem;
        }
    }

    /* Очень большие экраны (более 1600px) */
    @media (min-width: 1600px) {
        .footer-container {
            max-width: 1400px;
        }
    }

    /* ===== ДОПОЛНИТЕЛЬНЫЕ СТИЛИ ДЛЯ СОВМЕСТИМОСТИ ===== */

    /* Если используется Bootstrap */
    .site-footer .container {
        max-width: 100% !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    /* Убираем горизонтальный скролл */
    body {
        overflow-x: hidden;
    }

    /* Гарантируем, что футер не перекрывает контент */
    .site-footer {
        position: relative;
        z-index: 10;
    }
</style>

<!-- Scripts -->
<script src="https://gamestock.shop/scripts/jquery.min.js"></script>
<script src="https://gamestock.shop/scripts/jquery.easing.min.js"></script>
<script src="https://gamestock.shop/scripts/swiper.min.js"></script>
<script src="https://gamestock.shop/scripts/jquery.magnific-popup.js"></script>
<script src="https://gamestock.shop/scripts/scripts.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>