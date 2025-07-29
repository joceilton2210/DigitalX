<?php
header('Content-Type: application/json');
require '../configs/database.php';

session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado.']);
    exit;
}

try {
    // ✅ NOVO: Calcular faturamento do dia
    $stmt_faturamento = $pdo->query("
        SELECT COALESCE(SUM(valor_total), 0) as faturamento_hoje
        FROM pedidos 
        WHERE DATE(data_hora) = CURDATE() 
        AND status = 'Finalizado'
    ");
    $faturamento_hoje = $stmt_faturamento->fetchColumn();

    // ✅ CORREÇÃO: Remover metodo_pagamento por enquanto até criar a coluna
    $stmt_pedidos = $pdo->query("
        SELECT 
            p.id, p.valor_total, p.status, 
            DATE_FORMAT(p.data_hora, '%Y-%m-%dT%H:%i:%s') as data_hora, 
            p.origem, 
            COALESCE(m.numero_mesa, 'N/A') as mesa_numero,
            p.mesa_id
        FROM pedidos p
        LEFT JOIN mesas m ON p.mesa_id = m.id
        WHERE p.status IN ('Pendente', 'Em Preparo', 'Pronto', 'Pago')
        ORDER BY p.data_hora ASC
    ");
    $pedidos = $stmt_pedidos->fetchAll();

    if ($pedidos) {
        $ids_pedidos = array_column($pedidos, 'id');
        $placeholders = implode(',', array_fill(0, count($ids_pedidos), '?'));

        $stmt_itens = $pdo->prepare("
            SELECT pi.pedido_id, pi.quantidade, pr.nome as produto_nome 
            FROM pedido_itens pi
            JOIN produtos pr ON pi.produto_id = pr.id
            WHERE pi.pedido_id IN ($placeholders)
        ");
        $stmt_itens->execute($ids_pedidos);
        $itens = $stmt_itens->fetchAll();

        $itens_por_pedido = [];
        foreach ($itens as $item) {
            $itens_por_pedido[$item['pedido_id']][] = $item;
        }

        foreach ($pedidos as $key => $pedido) {
            $pedidos[$key]['itens'] = $itens_por_pedido[$pedido['id']] ?? [];
            
            // ✅ DEBUG: Adicionar informação para debug
            if ($pedido['mesa_numero'] === 'N/A') {
                $pedidos[$key]['debug_mesa_id'] = $pedido['mesa_id'];
            }
        }
    }

    // ✅ NOVO: Retornar pedidos e faturamento
    echo json_encode([
        'pedidos' => $pedidos,
        'faturamento_hoje' => $faturamento_hoje
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao buscar pedidos: ' . $e->getMessage()]);
}
?>