<?php
session_start();
require '../configs/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'];
    $senha = $_POST['senha'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE login = ?");
    $stmt->execute([$login]);
    $usuario = $stmt->fetch();

    // Verifica a senha E se o cargo é 'garcom' ou 'admin' (admin também pode usar)
    if ($usuario && password_verify($senha, $usuario['senha']) && in_array($usuario['role'], ['garcom', 'admin'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_login'] = $usuario['login'];
        $_SESSION['usuario_role'] = $usuario['role'];
        header("Location: index.php");
        exit();
    } else {
        header("Location: login.php?erro=1");
        exit();
    }
}
?>