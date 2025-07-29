<?php
require '../configs/database.php';

try {
    // Criar usuário da cozinha
    $loginCozinha = 'cozinha';
    $senhaCozinha = password_hash('cozinha123', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO usuarios (login, senha, role) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE senha = VALUES(senha)");
    $stmt->execute([$loginCozinha, $senhaCozinha, 'cozinha']);
    
    // Criar usuário do depósito
    $loginDeposito = 'deposito';
    $senhaDeposito = password_hash('deposito123', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO usuarios (login, senha, role) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE senha = VALUES(senha)");
    $stmt->execute([$loginDeposito, $senhaDeposito, 'deposito']);
    
    echo "<h2>✅ Usuários criados com sucesso!</h2>";
    echo "<p><strong>Cozinha:</strong> Login: cozinha | Senha: cozinha123</p>";
    echo "<p><strong>Depósito:</strong> Login: deposito | Senha: deposito123</p>";
    echo "<br><a href='../cozinha/login.php'>Acessar Cozinha</a> | <a href='../deposito/login.php'>Acessar Depósito</a>";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>