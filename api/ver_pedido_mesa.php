<?php
header('Content-Type: application/json');
require '../configs/database.php';

session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado.']);
    exit;
}

$mesa_identificador = $_GET['mesa_identificador'] ?? null;
if (!$mesa_identificador) {
    http_response_code(400);
    echo json_encode(['erro' => 'Identificador da mesa não fornecido.']);
    exit;
}

try {
    // 1. Encontra o ID da mesa
    $stmt_mesa = $pdo->prepare("SELECT id FROM mesas WHERE identificador_unico = ?");
    $stmt_mesa->execute([$mesa_identificador]);
    $mesa = $stmt_mesa->fetch();

    if (!$mesa) {
        throw new Exception("Mesa não encontrada.");
    }
    $mesa_id = $mesa['id'];

    // 2. Encontra TODOS os pedidos com status 'Pendente' ou 'Em Preparo' para esta mesa
    $stmt_pedidos_abertos = $pdo->prepare("SELECT id FROM pedidos WHERE mesa_id = ? AND status IN ('Pendente', 'Em Preparo')");
    $stmt_pedidos_abertos->execute([$mesa_id]);
    $pedidos_ids = $stmt_pedidos_abertos->fetchAll(PDO::FETCH_COLUMN);

    if (empty($pedidos_ids)) {
        // Se não houver pedidos abertos, retorna um objeto vazio
        echo json_encode(['id' => null, 'valor_total' => 0, 'itens' => []]);
        exit;
    }

    // 3. Monta a query para buscar todos os itens de todos os pedidos abertos
    $placeholders = implode(',', array_fill(0, count($pedidos_ids), '?'));
    
    $stmt_itens = $pdo->prepare("
        SELECT pr.nome, SUM(pi.quantidade) as quantidade, SUM(pi.quantidade * pi.preco_unitario) as subtotal
        FROM pedido_itens pi
        JOIN produtos pr ON pi.produto_id = pr.id
        WHERE pi.pedido_id IN ($placeholders)
        GROUP BY pr.id, pr.nome
        ORDER BY pr.nome
    ");
    $stmt_itens->execute($pedidos_ids);
    $itens_consolidados = $stmt_itens->fetchAll();

    // 4. Calcula o subtotal somando o valor de todos os pedidos abertos
    $stmt_total = $pdo->prepare("SELECT SUM(valor_total) as total FROM pedidos WHERE id IN ($placeholders)");
    $stmt_total->execute($pedidos_ids);
    $valor_total_consolidado = $stmt_total->fetchColumn();

    // 5. Monta o objeto de resposta final
    // Usamos o ID do pedido mais recente como referência, mas isso é apenas para o botão de fechar conta.
    $pedido_referencia_id = max($pedidos_ids);

    $pedido_consolidado = [
        'id' => $pedido_referencia_id,
        'valor_total' => $valor_total_consolidado,
        'itens' => $itens_consolidados
    ];
    
    echo json_encode($pedido_consolidado);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}
?>