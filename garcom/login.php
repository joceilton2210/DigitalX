<?php
session_start();
if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_role'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login do Garçom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style> body { display: flex; align-items: center; justify-content: center; height: 100vh; background-color: #f8f9fa; } .login-card { max-width: 400px; width: 100%; } </style>
</head>
<body>
    <div class="card login-card">
        <div class="card-body">
            <h3 class="card-title text-center mb-4">Área do Garçom</h3>
            <?php if (isset($_GET['erro'])): ?>
                <div class="alert alert-danger">Login, senha ou cargo inválidos.</div>
            <?php endif; ?>
            <form action="auth.php" method="POST">
                <div class="mb-3"><label for="login" class="form-label">Usuário</label><input type="text" class="form-control" id="login" name="login" required></div>
                <div class="mb-3"><label for="senha" class="form-label">Senha</label><input type="password" class="form-control" id="senha" name="senha" required></div>
                <button type="submit" class="btn btn-primary w-100">Entrar</button>
            </form>
        </div>
    </div>
</body>
</html>