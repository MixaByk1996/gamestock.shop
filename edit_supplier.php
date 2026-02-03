<?php
// /admin/edit_supplier.php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$supplier_id = $_GET['id'] ?? 1;

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Получаем данные поставщика
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch();
    
    if (!$supplier) {
        die("Поставщик не найден");
    }
    
    // Обновление наценки
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $markup_type = $_POST['markup_type'];
        $markup_value = (float) $_POST['markup_value'];
        
        $update = $pdo->prepare("UPDATE suppliers SET markup_type = ?, markup_value = ? WHERE id = ?");
        if ($update->execute([$markup_type, $markup_value, $supplier_id])) {
            $success = "Наценка обновлена!";
            $supplier['markup_type'] = $markup_type;
            $supplier['markup_value'] = $markup_value;
        }
    }
    
} catch (Exception $e) {
    die("Ошибка: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Настройка наценки</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <span class="navbar-brand">⚡ Настройка наценки</span>
        <a href="suppliers_info.php" class="btn btn-light">← Назад</a>
    </div>
</nav>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Наценка для: <?= htmlspecialchars($supplier['name']) ?></h4>
                </div>
                <div class="card-body">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Тип наценки:</label>
                            <select name="markup_type" class="form-select">
                                <option value="percent" <?= $supplier['markup_type'] == 'percent' ? 'selected' : '' ?>>Процентная (%)</option>
                                <option value="fixed" <?= $supplier['markup_type'] == 'fixed' ? 'selected' : '' ?>>Фиксированная (сумма)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Значение наценки:</label>
                            <div class="input-group">
                                <input type="number" 
                                       name="markup_value" 
                                       class="form-control" 
                                       value="<?= $supplier['markup_value'] ?>" 
                                       step="0.01"
                                       min="0"
                                       required>
                                <span class="input-group-text">
                                    <?= $supplier['markup_type'] == 'percent' ? '%' : '₽' ?>
                                </span>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                        <a href="suppliers_info.php" class="btn btn-secondary">Отмена</a>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Пример расчета</h5>
                </div>
                <div class="card-body">
                    <p>Текущая наценка: <strong><?= $supplier['markup_value'] ?><?= $supplier['markup_type'] == 'percent' ? '%' : '₽' ?></strong></p>
                    
                    <?php if ($supplier['markup_type'] == 'percent'): ?>
                        <p><strong>Формула:</strong> Цена × (1 + Наценка/100)</p>
                        <p><strong>Пример для 100₽:</strong></p>
                        <p>100 × (1 + <?= $supplier['markup_value'] ?>/100) = 
                           <strong><?= 100 * (1 + $supplier['markup_value']/100) ?>₽</strong></p>
                    <?php else: ?>
                        <p><strong>Формула:</strong> Цена + Фиксированная сумма</p>
                        <p><strong>Пример для 100₽:</strong></p>
                        <p>100 + <?= $supplier['markup_value'] ?> = 
                           <strong><?= 100 + $supplier['markup_value'] ?>₽</strong></p>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <h6>Примеры из реальных товаров:</h6>
                    <table class="table table-sm">
                        <tr>
                            <th>Товар</th>
                            <th>Цена поставщика</th>
                            <th>Наша цена</th>
                        </tr>
                        <?php
                        // Получаем несколько товаров для примера
                        $stmt = $pdo->prepare("SELECT name, price, our_price FROM supplier_products WHERE supplier_id = ? LIMIT 3");
                        $stmt->execute([$supplier_id]);
                        $products = $stmt->fetchAll();
                        
                        foreach ($products as $product):
                        ?>
                        <tr>
                            <td><?= htmlspecialchars(substr($product['name'], 0, 20)) ?>...</td>
                            <td><?= $product['price'] ?>₽</td>
                            <td><strong class="text-success"><?= $product['our_price'] ?>₽</strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>