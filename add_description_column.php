<?php
// add_description_column.php - Adds description column to supplier_products
require_once 'includes/config.php';

$pdo = getDBConnection();

echo "<h2>Adding description column to supplier_products</h2>";

try {
    // Check if column already exists
    $columns = $pdo->query("DESCRIBE supplier_products")->fetchAll(PDO::FETCH_COLUMN, 0);

    if (in_array('description', $columns)) {
        echo "<p>Column 'description' already exists.</p>";
    } else {
        $pdo->exec("ALTER TABLE supplier_products ADD COLUMN description TEXT AFTER name");
        echo "<p style='color: green;'>Column 'description' added successfully.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
