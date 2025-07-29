<?php
header('Content-Type: application/json');
require '../configs/database.php';

session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado. Faça login como garçom.']);
    exit;
}

$mesa_identificador = $_POST['mesa_identificador'] ?? null;
$produto_id = $_POST['produto_id'] ?? null;

if (!$mesa_identificador || !$produto_id) {
    http_response_code(400);
    echo json_encode(['erro' => 'Dados insuficientes.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Encontrar a mesa e o produto (lógica existente, está correta)
    $stmt_mesa = $pdo->prepare("SELECT id FROM mesas WHERE identificador_unico = ?");
    $stmt_mesa->execute([$mesa_identificador]);
    $mesa = $stmt_mesa->fetch();

    $stmt_prod = $pdo->prepare("SELECT preco FROM produtos WHERE id = ? AND disponivel = 1");
    $stmt_prod->execute([$produto_id]);
    $produto = $stmt_prod->fetch();

    if (!$mesa || !$produto) { throw new Exception("Mesa ou produto inválido/indisponível."); }
    $mesa_id = $mesa['id'];
    $preco_produto = $produto['preco'];

    // 2. Verificar se já existe um pedido ABERTO (lógica existente, está correta)
    $stmt_pedido_aberto = $pdo->prepare("SELECT id FROM pedidos WHERE mesa_id = ? AND status IN ('Pendente', 'Em Preparo') ORDER BY data_hora DESC LIMIT 1");
    $stmt_pedido_aberto->execute([$mesa_id]);
    $pedido_existente = $stmt_pedido_aberto->fetch();

    $pedido_id_alvo = null;
    if ($pedido_existente) {
        // --- JÁ EXISTE UM PEDIDO, APENAS ADICIONA O ITEM ---
        $pedido_id_alvo = $pedido_existente['id'];
    } else {
        // --- NÃO EXISTE PEDIDO ABERTO, CRIA UM NOVO ---
        // ✅ MODIFICAÇÃO: Adicionamos a coluna 'origem' e o valor 'Garçom'
        $stmt_novo_pedido = $pdo->prepare("INSERT INTO pedidos (mesa_id, valor_total, status, origem) VALUES (?, 0, 'Pendente', 'Garçom')");
        $stmt_novo_pedido->execute([$mesa_id]);
        $pedido_id_alvo = $pdo->lastInsertId();
    }
   
    // 3. Adicionar o item e atualizar o total do pedido específico (lógica existente, está correta)
    $stmt_add_item = $pdo->prepare("INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, 1, ?)");
    $stmt_add_item->execute([$pedido_id_alvo, $produto_id, $preco_produto]);

    $stmt_update_total = $pdo->prepare("UPDATE pedidos SET valor_total = (SELECT SUM(quantidade * preco_unitario) FROM pedido_itens WHERE pedido_id = ?) WHERE id = ?");
    $stmt_update_total->execute([$pedido_id_alvo, $pedido_id_alvo]);

    $pdo->commit();

    // ✅ NOVA LÓGICA DE RETORNO CONSOLIDADO
    // Após salvar, busca o estado atualizado de TODOS os pedidos abertos da mesa.
    $stmt_pedidos_abertos = $pdo->prepare("SELECT id FROM pedidos WHERE mesa_id = ? AND status IN ('Pendente', 'Em Preparo')");
    $stmt_pedidos_abertos->execute([$mesa_id]);
    $pedidos_ids = $stmt_pedidos_abertos->fetchAll(PDO::FETCH_COLUMN);

    $placeholders = implode(',', array_fill(0, count($pedidos_ids), '?'));
    
    $stmt_itens = $pdo->prepare("SELECT pr.nome, SUM(pi.quantidade) as quantidade, SUM(pi.quantidade * pi.preco_unitario) as subtotal FROM pedido_itens pi JOIN produtos pr ON pi.produto_id = pr.id WHERE pi.pedido_id IN ($placeholders) GROUP BY pr.id, pr.nome ORDER BY pr.nome");
    $stmt_itens->execute($pedidos_ids);
    $itens_consolidados = $stmt_itens->fetchAll();

    $stmt_total = $pdo->prepare("SELECT SUM(valor_total) as total FROM pedidos WHERE id IN ($placeholders)");
    $stmt_total->execute($pedidos_ids);
    $valor_total_consolidado = $stmt_total->fetchColumn();

    $pedido_atualizado = [
        'id' => max($pedidos_ids), // ID de referência para fechar a conta
        'valor_total' => $valor_total_consolidado,
        'itens' => $itens_consolidados
    ];

    echo json_encode(['sucesso' => true, 'mensagem' => 'Item adicionado com sucesso.', 'pedido_atualizado' => $pedido_atualizado]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}
?>