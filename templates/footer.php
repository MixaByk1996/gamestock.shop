<?php
// templates/footer.php - Общий подвал для всего сайта (идентичный главной странице)
?>
    </div><!-- /.container mt-4 from header -->
</div><!-- /.page-content-wrapper from header -->

<!-- Copyright (идентичен главной странице) -->
<div class="copyright">
<div class="container px-4 sm:px-8 lg:grid lg:grid-cols-3">
<ul class="mb-4 list-unstyled p-small">
<b><li class="mb-2"><a href="/privacy.php">  Правила  </a><a href="/privacy.php">  Соглашение  </a><a href="/privacy.php">  Конфиденциальность</a></li></b>
</ul>
<p class="pb-2 p-small statement"><b>gamestock.shop &copy; 2019-<?= date('Y') ?></b></p>
<p class="pb-2 p-small statement"><b>gamestock.shop &copy; 2019-<?= date('Y') ?></b><a href="#your-link" class="no-underline"></a></p>
</div></div>
<style>
.copyright {
    padding-top: 1.5rem;
    background-color: rgb(2, 55, 241);
    text-align: center;
}
.copyright a {
    color: white;
    text-decoration: none;
}
.copyright a:hover {
    text-decoration: underline;
}
.copyright .statement {
    color: white;
}
.copyright .list-unstyled li {
    display: inline-block;
    margin-right: 1rem;
}
.copyright .list-unstyled a {
    color: white;
}
.copyright p {
    color: white;
}
@media (min-width: 1024px) {
    .copyright {
        text-align: left;
    }
    .copyright .list-unstyled li {
        display: inline-block;
        margin-right: 1rem;
    }
    .copyright .statement {
        text-align: right;
    }
}
</style>

<!-- Scripts (как на главной странице) -->
<script src="https://gamestock.shop/scripts/jquery.min.js"></script>
<script src="https://gamestock.shop/scripts/jquery.easing.min.js"></script>
<script src="https://gamestock.shop/scripts/swiper.min.js"></script>
<script src="https://gamestock.shop/scripts/jquery.magnific-popup.js"></script>
<script src="https://gamestock.shop/scripts/scripts.js"></script>
<!-- Bootstrap JS (для контента страниц) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>