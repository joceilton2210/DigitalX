<?php
// api/atualizar_status.php
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
        $status = filter_var($input['status'] ?? null, FILTER_SANITIZE_STRING);
    } else {
        // Dados de formulário
        $pedido_id = filter_var($_POST['pedido_id'] ?? null, FILTER_VALIDATE_INT);
        $status = filter_var($_POST['status'] ?? null, FILTER_SANITIZE_STRING);
    }
    
    // ✅ CORREÇÃO: Adicionar "Pago" aos status permitidos
    $status_permitidos = ['Em Preparo', 'Pronto', 'Pago', 'Finalizado'];

    if ($pedido_id && $status && in_array($status, $status_permitidos)) {
        try {
            $stmt = $pdo->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
            $stmt->execute([$status, $pedido_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['sucesso' => true]);
            } else {
                throw new Exception('Nenhum pedido foi atualizado.');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['erro' => $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            'erro' => 'Dados inválidos.',
            'debug' => [
                'pedido_id' => $pedido_id,
                'status' => $status,
                'status_valido' => in_array($status, $status_permitidos),
                'status_permitidos' => $status_permitidos
            ]
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido.']);
}
?>