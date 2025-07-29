<?php
// api/enviar_pedido.php
header('Content-Type: application/json');
require '../configs/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['mesa']) || !isset($data['itens']) || empty($data['itens'])) {
    http_response_code(400);
    echo json_encode(['erro' => 'Dados do pedido inválidos.']);
    exit;
}

// A variável $data['mesa'] agora contém o 'identificador_unico'
$identificador_mesa = $data['mesa'];
$itens = $data['itens'];
$valor_total = 0;

try {
    // Inicia uma transação para garantir a consistência dos dados
    $pdo->beginTransaction();

     // 1. Encontrar o ID da mesa usando o IDENTIFICADOR_UNICO
     $stmt_mesa = $pdo->prepare("SELECT id FROM mesas WHERE identificador_unico = ?");
     $stmt_mesa->execute([$identificador_mesa]);
     $mesa = $stmt_mesa->fetch();
 
     if (!$mesa) {
         throw new Exception('Mesa não encontrada.');
     }
     $mesa_id = $mesa['id'];

    // 2. Calcular o valor total (usando os preços do banco para segurança)
    $ids_produtos = array_column($itens, 'id');
    $placeholders = implode(',', array_fill(0, count($ids_produtos), '?'));
    
    $stmt_precos = $pdo->prepare("SELECT id, preco FROM produtos WHERE id IN ($placeholders)");
    $stmt_precos->execute($ids_produtos);
    $precos_db = $stmt_precos->fetchAll(PDO::FETCH_KEY_PAIR);

    foreach ($itens as $item) {
        if (!isset($precos_db[$item['id']])) {
            throw new Exception("Produto com ID {$item['id']} não encontrado ou indisponível.");
        }
        $valor_total += $precos_db[$item['id']] * $item['quantidade'];
    }

    // 3. Inserir na tabela `pedidos`
    // ✅ MODIFICAÇÃO: Adicionamos a coluna 'origem' e o valor 'Cliente'
    $stmt_pedido = $pdo->prepare("INSERT INTO pedidos (mesa_id, valor_total, status, origem) VALUES (?, ?, 'Pendente', 'Cliente')");
    $stmt_pedido->execute([$mesa_id, $valor_total]);
    $pedido_id = $pdo->lastInsertId();

    // 4. Inserir na tabela `pedido_itens`
    $stmt_itens = $pdo->prepare("INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
    foreach ($itens as $item) {
        $stmt_itens->execute([$pedido_id, $item['id'], $item['quantidade'], $precos_db[$item['id']]]);
    }

    // Se tudo deu certo, confirma a transação
    $pdo->commit();

     // ✅ MODIFICAÇÃO: Retorna o ID e a data/hora do novo pedido
     $data_hora_pedido = $pdo->query("SELECT DATE_FORMAT(data_hora, '%Y-%m-%dT%H:%i:%s') as data_hora FROM pedidos WHERE id = $pedido_id")->fetchColumn();
     echo json_encode(['sucesso' => true, 'pedido_id' => $pedido_id, 'data_hora' => $data_hora_pedido]);


} catch (Exception $e) {
    // Se algo deu errado, reverte a transação
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}
?>