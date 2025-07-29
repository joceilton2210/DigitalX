<?php
header('Content-Type: application/json');
require '../configs/database.php';

session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado.']);
    exit;
}

$pedido_id = $_POST['pedido_id'] ?? null;
if (!$pedido_id) {
    http_response_code(400);
    echo json_encode(['erro' => 'ID do pedido não fornecido.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Busca o valor atual do pedido
    $stmt = $pdo->prepare("SELECT valor_total FROM pedidos WHERE id = ? AND status IN ('Pendente', 'Em Preparo')");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch();

    if (!$pedido) {
        throw new Exception("Pedido não encontrado ou já está fechado.");
    }
    
    // 2. Calcula o novo total com 10% de serviço
    $novo_total = $pedido['valor_total'] * 1.10;

    // 3. Atualiza o pedido com o novo total e muda o status para 'Pronto'
    // O status 'Pronto' indica que está pronto para o caixa finalizar o pagamento.
    $stmt_update = $pdo->prepare("UPDATE pedidos SET valor_total = ?, status = 'Pronto' WHERE id = ?");
    $stmt_update->execute([$novo_total, $pedido_id]);

    $pdo->commit();
    echo json_encode(['sucesso' => true, 'mensagem' => 'Conta fechada com sucesso!']);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}
?>