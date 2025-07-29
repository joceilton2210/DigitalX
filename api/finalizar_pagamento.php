<?php
header('Content-Type: application/json');
require '../configs/database.php';

session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar se é JSON ou form data
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        // Dados JSON
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            http_response_code(400);
            echo json_encode(['erro' => 'Dados JSON inválidos.']);
            exit;
        }
        $pedido_id = filter_var($input['pedido_id'] ?? null, FILTER_VALIDATE_INT);
        $metodo_pagamento = filter_var($input['metodo_pagamento'] ?? null, FILTER_SANITIZE_STRING);
    } else {
        // Dados de formulário
        $pedido_id = filter_var($_POST['pedido_id'] ?? null, FILTER_VALIDATE_INT);
        $metodo_pagamento = filter_var($_POST['metodo_pagamento'] ?? null, FILTER_SANITIZE_STRING);
    }
    
    $metodos_permitidos = ['PIX', 'Crédito', 'Débito'];

    if ($pedido_id && $metodo_pagamento && in_array($metodo_pagamento, $metodos_permitidos)) {
        try {
            $pdo->beginTransaction();
            
            // Verificar se o pedido existe e está com status 'Pago'
            $stmt_verificar = $pdo->prepare("SELECT status FROM pedidos WHERE id = ?");
            $stmt_verificar->execute([$pedido_id]);
            $pedido = $stmt_verificar->fetch();
            
            if (!$pedido) {
                throw new Exception('Pedido não encontrado.');
            }
            
            if ($pedido['status'] !== 'Pago') {
                throw new Exception('Pedido não está marcado como pago.');
            }
            
            // Verificar se as colunas existem antes de usar
            $stmt_check = $pdo->prepare("SHOW COLUMNS FROM pedidos LIKE 'metodo_pagamento'");
            $stmt_check->execute();
            $metodo_column_exists = $stmt_check->rowCount() > 0;
            
            $stmt_check = $pdo->prepare("SHOW COLUMNS FROM pedidos LIKE 'data_finalizacao'");
            $stmt_check->execute();
            $data_column_exists = $stmt_check->rowCount() > 0;
            
            // Atualizar status para 'Finalizado' e salvar método de pagamento se as colunas existirem
            if ($metodo_column_exists && $data_column_exists) {
                $stmt = $pdo->prepare("UPDATE pedidos SET status = 'Finalizado', metodo_pagamento = ?, data_finalizacao = NOW() WHERE id = ?");
                $stmt->execute([$metodo_pagamento, $pedido_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE pedidos SET status = 'Finalizado' WHERE id = ?");
                $stmt->execute([$pedido_id]);
            }
            
            if ($stmt->rowCount() > 0) {
                $pdo->commit();
                echo json_encode(['sucesso' => true]);
            } else {
                throw new Exception('Nenhum pedido foi atualizado.');
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['erro' => $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            'erro' => 'Dados inválidos.',
            'debug' => [
                'pedido_id' => $pedido_id,
                'metodo_pagamento' => $metodo_pagamento,
                'metodo_valido' => in_array($metodo_pagamento, $metodos_permitidos),
                'metodos_permitidos' => $metodos_permitidos
            ]
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido.']);
}
?>