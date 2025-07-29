<?php
require '../configs/database.php';

echo "<h2>🧪 Teste do Sistema de Pagamento</h2>";

// Verificar se as colunas existem
try {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM pedidos LIKE 'metodo_pagamento'");
    $stmt->execute();
    $metodo_exists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->prepare("SHOW COLUMNS FROM pedidos LIKE 'data_finalizacao'");
    $stmt->execute();
    $data_exists = $stmt->rowCount() > 0;
    
    echo "<p>✅ Coluna metodo_pagamento: " . ($metodo_exists ? "EXISTE" : "NÃO EXISTE") . "</p>";
    echo "<p>✅ Coluna data_finalizacao: " . ($data_exists ? "EXISTE" : "NÃO EXISTE") . "</p>";
    
    // Buscar um pedido de teste
    $stmt = $pdo->prepare("SELECT id, status FROM pedidos ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $pedido = $stmt->fetch();
    
    if ($pedido) {
        echo "<p>📋 Último pedido: #" . $pedido['id'] . " - Status: " . $pedido['status'] . "</p>";
        
        // Testar atualização para Pago
        echo "<h3>Teste 1: Marcar como Pago</h3>";
        $stmt = $pdo->prepare("UPDATE pedidos SET status = 'Pago' WHERE id = ?");
        $result = $stmt->execute([$pedido['id']]);
        echo "<p>" . ($result ? "✅ SUCESSO" : "❌ ERRO") . " - Marcar como Pago</p>";
        
        // Testar finalização
        echo "<h3>Teste 2: Finalizar Pagamento</h3>";
        if ($metodo_exists && $data_exists) {
            $stmt = $pdo->prepare("UPDATE pedidos SET status = 'Finalizado', metodo_pagamento = 'PIX', data_finalizacao = NOW() WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE pedidos SET status = 'Finalizado' WHERE id = ?");
        }
        $result = $stmt->execute([$pedido['id']]);
        echo "<p>" . ($result ? "✅ SUCESSO" : "❌ ERRO") . " - Finalizar Pagamento</p>";
        
    } else {
        echo "<p>❌ Nenhum pedido encontrado para teste</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<br><a href='index.php'>← Voltar ao Dashboard</a>";
?>