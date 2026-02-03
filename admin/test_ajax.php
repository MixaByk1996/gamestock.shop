<?php
// test_ajax.php - Тест AJAX запросов
session_start();
require_once '../includes/config.php';

// Проверка авторизации
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    die('Доступ запрещен');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Тест AJAX</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h1>Тест AJAX запросов</h1>
        
        <div class="mb-3">
            <button onclick="testGetProduct()" class="btn btn-primary">Тест get_product.php</button>
            <div id="result1" class="mt-2"></div>
        </div>
        
        <div class="mb-3">
            <button onclick="testSaveProduct()" class="btn btn-success">Тест save_product.php</button>
            <div id="result2" class="mt-2"></div>
        </div>
        
        <div class="mb-3">
            <button onclick="testDeleteProduct()" class="btn btn-danger">Тест delete_product.php</button>
            <div id="result3" class="mt-2"></div>
        </div>
    </div>
    
    <script>
    function testGetProduct() {
        document.getElementById('result1').innerHTML = '<div class="spinner-border"></div> Загрузка...';
        
        fetch('ajax/get_product.php?id=1')
            .then(response => response.json())
            .then(data => {
                document.getElementById('result1').innerHTML = 
                    '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            })
            .catch(error => {
                document.getElementById('result1').innerHTML = 
                    '<div class="alert alert-danger">Ошибка: ' + error + '</div>';
            });
    }
    
    function testSaveProduct() {
        document.getElementById('result2').innerHTML = '<div class="spinner-border"></div> Загрузка...';
        
        const formData = new FormData();
        formData.append('product_id', 1);
        formData.append('name', 'Тестовый товар');
        formData.append('price', 100);
        formData.append('our_price', 150);
        formData.append('category', 2);
        formData.append('stock', 10);
        
        fetch('ajax/save_product.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('result2').innerHTML = 
                '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        })
        .catch(error => {
            document.getElementById('result2').innerHTML = 
                '<div class="alert alert-danger">Ошибка: ' + error + '</div>';
        });
    }
    
    function testDeleteProduct() {
        document.getElementById('result3').innerHTML = '<div class="spinner-border"></div> Загрузка...';
        
        fetch('ajax/delete_product.php?id=9999')
            .then(response => response.json())
            .then(data => {
                document.getElementById('result3').innerHTML = 
                    '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            })
            .catch(error => {
                document.getElementById('result3').innerHTML = 
                    '<div class="alert alert-danger">Ошибка: ' + error + '</div>';
            });
    }
    </script>
</body>
</html>