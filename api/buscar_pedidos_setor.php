<?php
header('Content-Type: application/json');
require '../configs/database.php';

session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado.']);
    exit;
}

$setor = $_GET['setor'] ?? '';

if (!in_array($setor, ['cozinha', 'deposito'])) {
    http_response_code(400);
    echo json_encode(['erro' => 'Setor inválido.']);
    exit;
}

try {
    // Buscar pedidos atuais (não finalizados)
    $sql = "SELECT p.id, p.valor_total, p.status, p.data_hora, p.origem, 
                   COALESCE(m.numero, 'N/A') as mesa_numero
            FROM pedidos p 
            LEFT JOIN mesas m ON p.mesa_id = m.id 
            WHERE p.status IN ('Pendente', 'Em Preparo', 'Pronto', 'Pago', 'Finalizado')
            ORDER BY p.data_hora ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Para cada pedido, buscar os itens
    foreach ($pedidos as &$pedido) {
        $stmt_itens = $pdo->prepare("
            SELECT pi.quantidade, pi.preco_unitario as preco, pr.nome 
            FROM pedido_itens pi 
            JOIN produtos pr ON pi.produto_id = pr.id 
            WHERE pi.pedido_id = ?
        ");
        $stmt_itens->execute([$pedido['id']]);
        $pedido['itens'] = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'sucesso' => true,
        'pedidos' => $pedidos
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno do servidor: ' . $e->getMessage()]);
}
?>