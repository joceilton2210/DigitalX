<?php
// configs/database.php

$host = 'localhost'; // ou o host do seu banco de dados
$db_name = 'cardapio'; // coloque o nome do seu banco
$username = 'root'; // seu usuário do banco
$password = ''; // sua senha do banco

try {
    $pdo = new PDO("mysql:host={$host};dbname={$db_name};charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Em um ambiente de produção, não exiba a mensagem de erro detalhada.
    // Logue o erro em um arquivo e mostre uma mensagem genérica.
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}
?>