<?php
// admin/templates/footer.php - Подвал админ-панели
?>
    </div> <!-- закрываем .main-content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Подтверждение выхода
        document.querySelector('a[href="?logout"]')?.addEventListener('click', function(e) {
            if (!confirm('Вы уверены, что хотите выйти из админ-панели?')) {
                e.preventDefault();
            }
        });

        // Подтверждение опасных действий
        function confirmAction(message) {
            return confirm(message || 'Вы уверены, что хотите выполнить это действие?');
        }

        // Автоматическое закрытие алертов через 5 секунд
        setTimeout(() => {
            document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>