<?php
// admin/users.php - Управление пользователями
session_start();
require_once '../includes/config.php';

// Проверка авторизации администратора
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: index.php');
    exit;
}

// Устанавливаем user_id если его нет (для старых администраторов)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // ID админа по умолчанию
}

$pdo = getDBConnection();

// Настройки для шаблона
$page_title = 'Управление пользователями';
$page_icon = 'fas fa-users';
$page_subtitle = 'Просмотр и управление клиентами';
$active_menu = 'users';

// Подключаем шапку
require_once __DIR__ . '/templates/header.php';

// Пагинация
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Фильтры
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';

// Подготовка условий WHERE
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role_filter === 'admin') {
    $where_conditions[] = "is_admin = 1";
} elseif ($role_filter === 'user') {
    $where_conditions[] = "is_admin = 0";
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
}

try {
    // Получаем пользователей
    $sql = "
        SELECT u.*, 
               (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as orders_count,
               (SELECT SUM(total_amount) FROM orders WHERE user_id = u.id AND status = 'completed') as total_spent
        FROM users u
        $where_sql
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?
    ";

    $params[] = $per_page;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();

    // Общее количество для пагинации
    $total_sql = "SELECT COUNT(*) FROM users u $where_sql";
    $total_stmt = $pdo->prepare($total_sql);
    $total_params = array_slice($params, 0, -2);
    if (!empty($total_params)) {
        $total_stmt->execute($total_params);
    } else {
        $total_stmt->execute();
    }
    $total = $total_stmt->fetchColumn();
    $total_pages = ceil($total / $per_page);

} catch (Exception $e) {
    $users = [];
    $total = 0;
    $total_pages = 1;
}
?>

<div class="container-fluid">
    <!-- Заголовок и кнопки -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-users me-2"></i>Управление пользователями
        </h1>
        <div>
            <button class="btn btn-primary" onclick="addNewUser()">
                <i class="fas fa-user-plus me-2"></i>Добавить пользователя
            </button>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="card-admin mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <input type="text" class="form-control" placeholder="Поиск по имени или email..." 
                           name="search" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="role">
                        <option value="">Все роли</option>
                        <option value="admin" <?= ($role_filter == 'admin') ? 'selected' : '' ?>>Администраторы</option>
                        <option value="user" <?= ($role_filter == 'user') ? 'selected' : '' ?>>Пользователи</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Найти
                        </button>
                        <a href="users.php" class="btn btn-secondary">Сбросить</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Статистика -->
    <?php
    $total_users = count($users);
    $admins_count = 0;
    $total_balance = 0;
    $total_orders = 0;
    
    foreach ($users as $user) {
        if ($user['is_admin']) $admins_count++;
        $total_balance += $user['balance'];
        $total_orders += $user['orders_count'];
    }
    ?>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title"><?= number_format($total) ?></h5>
                    <p class="card-text">Всего пользователей</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-warning"><?= $admins_count ?></h5>
                    <p class="card-text">Администраторов</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-success"><?= number_format($total_balance, 2) ?> ₽</h5>
                    <p class="card-text">Общий баланс</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-info"><?= $total_orders ?></h5>
                    <p class="card-text">Всего заказов</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Таблица пользователей -->
    <div class="card-admin">
        <div class="card-body">
            <?php if (empty($users)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-user-friends fa-3x text-muted mb-3"></i>
                    <h4>Пользователей не найдено</h4>
                    <p class="text-muted">
                        <?php if (!empty($search)): ?>
                            Попробуйте изменить параметры поиска
                        <?php else: ?>
                            Пользователи появятся после регистрации на сайте
                        <?php endif; ?>
                    </p>
                    <button class="btn btn-primary" onclick="addNewUser()">
                        <i class="fas fa-user-plus me-2"></i>Добавить пользователя
                    </button>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Пользователь</th>
                                <th>Email</th>
                                <th>Баланс</th>
                                <th>Заказов</th>
                                <th>Потрачено</th>
                                <th>Роль</th>
                                <th>Дата регистрации</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <small class="text-muted">#<?= $user['id'] ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($user['username']) ?></strong>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($user['email']) ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $user['balance'] > 0 ? 'success' : 'secondary' ?>">
                                            <?= number_format($user['balance'], 2) ?> ₽
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= $user['orders_count'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= number_format($user['total_spent'] ?? 0, 2) ?> ₽
                                    </td>
                                    <td>
                                        <?php if ($user['is_admin']): ?>
                                            <span class="badge bg-warning">Администратор</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Пользователь</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= date('d.m.Y', strtotime($user['created_at'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" 
                                                    onclick="viewUser(<?= $user['id'] ?>)"
                                                    title="Просмотр">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-warning" 
                                                    onclick="editUser(<?= $user['id'] ?>)"
                                                    title="Редактировать">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if (!$user['is_admin'] || $user['id'] != $_SESSION['user_id']): ?>
                                                <button class="btn btn-outline-danger" 
                                                        onclick="deleteUser(<?= $user['id'] ?>, '<?= addslashes($user['username']) ?>')"
                                                        title="Удалить">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
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
                            if (!empty($role_filter)) $query_params[] = "role=" . $role_filter;
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

<!-- Модальное окно просмотра пользователя -->
<div class="modal fade" id="viewUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Информация о пользователе</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="userDetails">
                <!-- Данные будут загружены через AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<script>
// Функция для добавления нового пользователя
function addNewUser() {
    // Сброс формы
    document.getElementById('edit_user_id').value = '0';
    document.getElementById('edit_username').value = '';
    document.getElementById('edit_email').value = '';
    document.getElementById('edit_balance').value = '0';
    document.getElementById('edit_is_admin').checked = false;
    
    // Показ модального окна
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

// Просмотр пользователя
function viewUser(id) {
    fetch('ajax/get_user.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                const modal = document.getElementById('userDetails');
                
                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>ID:</strong> ${user.id}</p>
                            <p><strong>Имя пользователя:</strong> ${user.username}</p>
                            <p><strong>Email:</strong> ${user.email}</p>
                            <p><strong>Роль:</strong> ${user.is_admin ? '<span class="badge bg-warning">Администратор</span>' : '<span class="badge bg-secondary">Пользователь</span>'}</p>
                            <p><strong>Дата регистрации:</strong> ${user.created_at}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Баланс:</strong> <span class="badge bg-success">${parseFloat(user.balance).toFixed(2)} ₽</span></p>
                            <p><strong>Заказов:</strong> <span class="badge bg-info">${user.orders_count || 0}</span></p>
                            <p><strong>Потрачено:</strong> ${parseFloat(user.total_spent || 0).toFixed(2)} ₽</p>
                            <p><strong>Последний вход:</strong> ${user.last_login || 'Не входил'}</p>
                        </div>
                    </div>
                `;
                
                modal.innerHTML = html;
                new bootstrap.Modal(document.getElementById('viewUserModal')).show();
            } else {
                alert('Ошибка загрузки данных');
            }
        })
        .catch(error => {
            alert('Ошибка соединения');
        });
}

// Редактирование пользователя
function editUser(id) {
    fetch('ajax/get_user.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                document.getElementById('edit_user_id').value = user.id;
                document.getElementById('edit_username').value = user.username;
                document.getElementById('edit_email').value = user.email;
                document.getElementById('edit_balance').value = user.balance;
                document.getElementById('edit_is_admin').checked = user.is_admin == 1;
                
                new bootstrap.Modal(document.getElementById('editUserModal')).show();
            } else {
                alert('Ошибка загрузки данных');
            }
        })
        .catch(error => {
            alert('Ошибка соединения');
        });
}

// Сохранение пользователя
function saveUser() {
    const form = document.getElementById('editUserForm');
    const formData = new FormData(form);
    
    fetch('ajax/save_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Пользователь сохранен');
            $('#editUserModal').modal('hide');
            location.reload();
        } else {
            alert('Ошибка: ' + data.message);
        }
    })
    .catch(error => {
        alert('Ошибка соединения');
    });
}

// Удаление пользователя
function deleteUser(id, name) {
    if (confirm('Вы уверены, что хотите удалить пользователя "' + name + '"?')) {
        fetch('ajax/delete_user.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Пользователь удален');
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.message);
                }
            })
            .catch(error => {
                alert('Ошибка соединения');
            });
    }
}
</script>

<?php
require_once __DIR__ . '/templates/footer.php';