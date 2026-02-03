<?php
// admin/products.php - Управление товарами
session_start();
require_once '../includes/config.php';

// Проверка авторизации администратора
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: index.php');
    exit;
}

$pdo = getDBConnection();

// Настройки для шаблона
$page_title = 'Управление товарами';
$page_icon = 'fas fa-box';
$page_subtitle = 'Добавление, редактирование и удаление товаров';
$active_menu = 'products';

// Пагинация
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Фильтры
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$supplier = isset($_GET['supplier']) ? (int)$_GET['supplier'] : 0;
$stock_filter = isset($_GET['stock']) ? $_GET['stock'] : '';

// Подготовка условий WHERE
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(sp.name LIKE ? OR sp.external_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category > 0) {
    $where_conditions[] = "sp.category = ?";
    $params[] = $category;
}

if ($supplier > 0) {
    $where_conditions[] = "sp.supplier_id = ?";
    $params[] = $supplier;
}

if ($stock_filter === 'in_stock') {
    $where_conditions[] = "sp.stock > 0";
} elseif ($stock_filter === 'out_of_stock') {
    $where_conditions[] = "sp.stock = 0";
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
}

try {
    // Получаем товары
    $sql = "
        SELECT sp.*, s.name as supplier_name, 
               (SELECT COUNT(*) FROM orders WHERE product_id = sp.id) as orders_count
        FROM supplier_products sp
        LEFT JOIN suppliers s ON sp.supplier_id = s.id
        $where_sql
        ORDER BY sp.last_updated DESC
        LIMIT ? OFFSET ?
    ";

    $params[] = $per_page;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Общее количество для пагинации
    $total_sql = "SELECT COUNT(*) FROM supplier_products sp $where_sql";
    $total_stmt = $pdo->prepare($total_sql);
    $total_params = array_slice($params, 0, -2); // Убираем LIMIT и OFFSET
    if (!empty($total_params)) {
        $total_stmt->execute($total_params);
    } else {
        $total_stmt->execute();
    }
    $total = $total_stmt->fetchColumn();
    $total_pages = ceil($total / $per_page);

    // Получаем список категорий
    $categories_stmt = $pdo->query("SELECT DISTINCT category FROM supplier_products ORDER BY category");
    $categories = $categories_stmt->fetchAll();

    // Получаем список поставщиков
    $suppliers_stmt = $pdo->query("SELECT id, name FROM suppliers ORDER BY name");
    $suppliers = $suppliers_stmt->fetchAll();

} catch (Exception $e) {
    $products = [];
    $total = 0;
    $total_pages = 1;
    $categories = [];
    $suppliers = [];
}

// Названия категорий
$category_names = [
    2 => 'Facebook',
    5 => 'Мобильные прокси',
    10 => 'Facebook Samofarm',
    13 => 'Discord',
    15 => 'Reddit',
    18 => 'Yandex Zen',
    21 => 'SEO - Ссылки',
    25 => 'Skype',
    26 => 'Instagram',
    29 => 'Google Ads',
    30 => 'Yandex.Direct',
    42 => 'Google iOS',
    44 => 'TikTok Ads',
    50 => 'Twitter',
    51 => 'Epic Games',
    53 => 'Трафик/SEO',
    68 => 'VK.com',
    75 => 'Почта (Email)'
];

// Подключаем шапку с модальным окном
require_once __DIR__ . '/templates/header.php';
?>

    <div class="container-fluid">
        <!-- Заголовок и кнопки -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-box me-2"></i>Управление товарами
            </h1>
            <div>
                <button type="button" class="btn btn-success me-2" onclick="addProduct()">
                    <i class="fas fa-plus me-2"></i>Добавить товар
                </button>
                <a href="sync_buyaccs.php" class="btn btn-primary">
                    <i class="fas fa-sync me-2"></i>Синхронизировать
                </a>
            </div>
        </div>

        <!-- Фильтры -->
        <div class="card-admin mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" placeholder="Поиск товаров..."
                               name="search" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="category">
                            <option value="0">Все категории</option>
                            <?php
                            foreach ($category_names as $id => $name) {
                                // Проверяем, есть ли товары в этой категории
                                $has_products = false;
                                foreach ($categories as $cat) {
                                    if ((int)$cat['category'] == $id) {
                                        $has_products = true;
                                        break;
                                    }
                                }

                                if ($has_products) {
                                    echo '<option value="' . $id . '" ';
                                    echo ($category == $id) ? 'selected' : '';
                                    echo '>' . $name . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="supplier">
                            <option value="0">Все поставщики</option>
                            <?php foreach ($suppliers as $supp): ?>
                                <option value="<?= $supp['id'] ?>" <?= ($supplier == $supp['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($supp['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="stock">
                            <option value="">Все</option>
                            <option value="in_stock" <?= ($stock_filter == 'in_stock') ? 'selected' : '' ?>>В наличии</option>
                            <option value="out_of_stock" <?= ($stock_filter == 'out_of_stock') ? 'selected' : '' ?>>Нет в наличии</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Найти
                            </button>
                            <a href="products.php" class="btn btn-secondary">Сбросить</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Статистика -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title"><?= number_format($total) ?></h5>
                        <p class="card-text">Всего товаров</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title"><?= number_format(count($products)) ?></h5>
                        <p class="card-text">На странице</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <?php
                $in_stock = 0;
                $out_of_stock = 0;
                foreach ($products as $product) {
                    if ($product['stock'] > 0) {
                        $in_stock++;
                    } else {
                        $out_of_stock++;
                    }
                }
                ?>
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-success"><?= $in_stock ?></h5>
                        <p class="card-text">В наличии</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-danger"><?= $out_of_stock ?></h5>
                        <p class="card-text">Нет в наличии</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Таблица товаров -->
        <div class="card-admin">
            <div class="card-body">
                <?php if (empty($products)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <h4>Товаров не найдено</h4>
                        <p class="text-muted">
                            <?php if (!empty($search) || $category > 0 || $supplier > 0): ?>
                                Попробуйте изменить параметры поиска
                            <?php else: ?>
                                Запустите синхронизацию товаров или добавьте товар вручную
                            <?php endif; ?>
                        </p>
                        <div class="mt-3">
                            <button type="button" class="btn btn-success me-2" onclick="addProduct()">
                                <i class="fas fa-plus me-2"></i>Добавить товар
                            </button>
                            <a href="sync_buyaccs.php" class="btn btn-primary">
                                <i class="fas fa-sync me-2"></i>Синхронизировать
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Название</th>
                                <th>Категория</th>
                                <th>Описание</th>
                                <th>Поставщик</th>
                                <th>Цена</th>
                                <th>Наша цена</th>
                                <th>В наличии</th>
                                <th>Заказов</th>
                                <th>Обновлено</th>
                                <th>Действия</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($products as $product):
                                $category_name = $category_names[$product['category']] ?? 'Категория ' . $product['category'];
                                $stock_class = $product['stock'] > 10 ? 'bg-success' :
                                    ($product['stock'] > 0 ? 'bg-warning' : 'bg-danger');
                                ?>
                                <tr>
                                    <td>
                                        <small class="text-muted">#<?= $product['external_id'] ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars(substr($product['name'], 0, 50)) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <?= htmlspecialchars($category_name) ?>
                                        </span>
                                    </td><td>
                                        <span class="badge bg-light text-dark">
                                            <?= $product['description'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($product['supplier_name']) ?>
                                    </td>
                                    <td>
                                        <span class="text-primary"><?= number_format($product['price'], 2) ?> ₽</span>
                                    </td>
                                    <td>
                                        <strong class="text-success"><?= number_format($product['our_price'], 2) ?> ₽</strong>
                                    </td>
                                    <td>
                                        <span class="badge <?= $stock_class ?>">
                                            <?= $product['stock'] ?> шт.
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= $product['orders_count'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= date('d.m.Y H:i', strtotime($product['last_updated'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary"
                                                    onclick="editProduct(<?= $product['id'] ?>)"
                                                    title="Редактировать">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger"
                                                    onclick="deleteProduct(<?= $product['id'] ?>, '<?= addslashes(substr($product['name'], 0, 30)) ?>')"
                                                    title="Удалить">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Пагинация -->
                    <?php if ($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php
                                $query_params = [];
                                if (!empty($search)) $query_params[] = "search=" . urlencode($search);
                                if ($category > 0) $query_params[] = "category=" . $category;
                                if ($supplier > 0) $query_params[] = "supplier=" . $supplier;
                                if (!empty($stock_filter)) $query_params[] = "stock=" . $stock_filter;
                                $query_string = !empty($query_params) ? '&' . implode('&', $query_params) : '';
                                ?>

                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page-1 ?><?= $query_string ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);

                                for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?><?= $query_string ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page+1 ?><?= $query_string ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Редактирование товара
        function editProduct(id) {
            // Сначала показываем модальное окно
            const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
            modal.show();

            // Показываем индикатор загрузки в форме
            const form = document.getElementById('editProductForm');
            const originalFormHTML = form.innerHTML;

            form.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
            <p class="mt-2">Загрузка данных товара...</p>
        </div>
    `;

            // Загружаем данные товара
            fetch('ajax/get_product.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const product = data.product;

                        // Восстанавливаем форму
                        form.innerHTML = originalFormHTML;

                        // Заполняем поля
                        document.getElementById('product_id').value = product.id;
                        document.getElementById('product_name').value = product.name;
                        document.getElementById('product_description').value = product.description;
                        document.getElementById('product_price').value = product.price;
                        document.getElementById('product_our_price').value = product.our_price;
                        document.getElementById('product_category').value = product.category || '';
                        document.getElementById('product_stock').value = product.stock || 0;
                        document.getElementById('product_external_id').value = product.external_id || '';

                        // Обновляем заголовок
                        document.querySelector('#editProductModal .modal-title').textContent =
                            'Редактирование: ' + (product.name.length > 30 ? product.name.substring(0, 30) + '...' : product.name);

                    } else {
                        alert('Ошибка: ' + data.message);
                        modal.hide();
                    }
                })
                .catch(error => {
                    alert('Ошибка загрузки данных: ' + error);
                    modal.hide();
                });
        }

        // Добавление товара
        function addProduct() {
            // Сбрасываем форму
            document.getElementById('product_id').value = '';
            document.getElementById('product_name').value = '';
            document.getElementById('product_price').value = '';
            document.getElementById('product_description').value = '';
            document.getElementById('product_our_price').value = '';
            document.getElementById('product_category').value = '';
            document.getElementById('product_stock').value = '0';
            document.getElementById('product_external_id').value = '';

            // Обновляем заголовок
            document.querySelector('#editProductModal .modal-title').textContent = 'Добавление товара';

            // Показываем модальное окно
            const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
            modal.show();
        }

        // Удаление товара
        function deleteProduct(id, name) {
            if (confirm('Вы уверены, что хотите удалить товар "' + name + '"?')) {
                // Показываем индикатор загрузки
                const deleteBtn = event.target.closest('.btn-outline-danger');
                const originalHtml = deleteBtn.innerHTML;
                deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                deleteBtn.disabled = true;

                // AJAX-запрос на удаление
                fetch('ajax/delete_product.php?id=' + id)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Обновляем таблицу без перезагрузки страницы
                            location.reload();
                        } else {
                            alert('Ошибка: ' + data.message);
                            deleteBtn.innerHTML = originalHtml;
                            deleteBtn.disabled = false;
                        }
                    })
                    .catch(error => {
                        alert('Ошибка соединения');
                        deleteBtn.innerHTML = originalHtml;
                        deleteBtn.disabled = false;
                    });
            }
        }

        // Сохранение товара
        function saveProduct() {
            const form = document.getElementById('editProductForm');
            const formData = new FormData(form);

            // Проверка обязательных полей
            const name = document.getElementById('product_name').value;
            const externalId = document.getElementById('product_external_id').value;

            if (!name.trim()) {
                alert('Название товара не может быть пустым');
                return;
            }

            if (!externalId.trim()) {
                alert('Внешний ID не может быть пустым');
                return;
            }

            // Показываем индикатор загрузки
            const saveBtn = document.querySelector('#editProductModal .btn-primary');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Сохранение...';
            saveBtn.disabled = true;

            fetch('ajax/save_product.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Товар сохранен');
                        // Закрываем модальное окно
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editProductModal'));
                        modal.hide();
                        // Обновляем страницу
                        location.reload();
                    } else {
                        alert('Ошибка: ' + data.message);
                        saveBtn.innerHTML = originalText;
                        saveBtn.disabled = false;
                    }
                })
                .catch(error => {
                    alert('Ошибка соединения');
                    saveBtn.innerHTML = originalText;
                    saveBtn.disabled = false;
                });
        }
    </script>

<?php
require_once __DIR__ . '/templates/footer.php';
?>