<?php
header('Content-Type: application/json');
require '../configs/database.php';

$pedido_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$pedido_id) {
    http_response_code(400);
    echo json_encode(['erro' => 'ID do pedido inválido.']);
    exit;
}

try {
    // Buscar informações completas do pedido
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.status,
            p.valor_total,
            p.data_hora,
            m.numero_mesa,
            TIMESTAMPDIFF(MINUTE, p.data_hora, NOW()) as tempo_decorrido
        FROM pedidos p 
        JOIN mesas m ON p.mesa_id = m.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pedido) {
        // Buscar itens do pedido com informações da categoria
        $stmt_itens = $pdo->prepare("
            SELECT 
                pi.quantidade,
                pi.preco_unitario,
                pr.nome as produto_nome,
                c.nome as categoria_nome
            FROM pedido_itens pi
            JOIN produtos pr ON pi.produto_id = pr.id
            JOIN categorias c ON pr.categoria_id = c.id
            WHERE pi.pedido_id = ?
        ");
        $stmt_itens->execute([$pedido_id]);
        $itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);

        // ✅ NOVA FUNCIONALIDADE: Identificar tipo de pedido e calcular tempo
        $tem_comida = false;
        $tem_bebida = false;
        
        // Palavras-chave para identificar bebidas
        $palavras_bebida = [
            'bebida', 'drink', 'cerveja', 'refrigerante', 'suco', 'água', 'coca', 'pepsi', 
            'guaraná', 'fanta', 'sprite', 'heineken', 'skol', 'brahma', 'stella', 'corona',
            'vinho', 'caipirinha', 'vodka', 'whisky', 'rum', 'gin', 'tequila', 'licor',
            'café', 'chá', 'cappuccino', 'expresso', 'latte', 'smoothie', 'milkshake',
            'energético', 'isotônico', 'água', 'mineral', 'gasosa', 'natural'
        ];
        
        // Palavras-chave para identificar comidas
        $palavras_comida = [
            'comida', 'prato', 'lanche', 'hambur', 'pizza', 'massa', 'macarrão', 'lasanha',
            'risotto', 'salada', 'sanduíche', 'wrap', 'burrito', 'taco', 'hot dog',
            'batata', 'fritas', 'nuggets', 'chicken', 'frango', 'carne', 'peixe', 'salmão',
            'camarão', 'picanha', 'alcatra', 'costela', 'linguiça', 'bacon', 'ovo',
            'queijo', 'presunto', 'torta', 'bolo', 'sobremesa', 'pudim', 'mousse',
            'sorvete', 'açaí', 'crepe', 'waffle', 'panqueca', 'pastel', 'coxinha',
            'empada', 'esfiha', 'kibe', 'pão', 'croissant', 'misto', 'natural'
        ];

        foreach ($itens as $item) {
            $nome_produto = strtolower($item['produto_nome']);
            $nome_categoria = strtolower($item['categoria_nome']);
            $texto_completo = $nome_produto . ' ' . $nome_categoria;
            
            // Verificar se é bebida
            foreach ($palavras_bebida as $palavra) {
                if (strpos($texto_completo, $palavra) !== false) {
                    $tem_bebida = true;
                    break;
                }
            }
            
            // Verificar se é comida
            foreach ($palavras_comida as $palavra) {
                if (strpos($texto_completo, $palavra) !== false) {
                    $tem_comida = true;
                    break;
                }
            }
        }

        // Calcular tempo estimado baseado no tipo de pedido e status
        $tempo_estimado = '';
        switch($pedido['status']) {
            case 'Pendente':
                $tempo_estimado = 'Aguardando confirmação da cozinha';
                break;
            case 'Em Preparo':
                if ($tem_comida && $tem_bebida) {
                    // Pedido misto: usar tempo da comida
                    $tempo_estimado = 'Tempo estimado: 35-40 minutos';
                } elseif ($tem_comida) {
                    // Apenas comida
                    $tempo_estimado = 'Tempo estimado: 35-40 minutos';
                } elseif ($tem_bebida) {
                    // Apenas bebidas
                    $tempo_estimado = 'Tempo estimado: 5-10 minutos';
                } else {
                    // Fallback caso não identifique
                    $tempo_estimado = 'Tempo estimado: 15-25 minutos';
                }
                break;
            case 'Pronto':
                $tempo_estimado = 'Seu pedido está pronto!';
                break;
        }

        echo json_encode([
            'status' => $pedido['status'],
            'mesa' => $pedido['numero_mesa'],
            'valor_total' => $pedido['valor_total'],
            'data_hora' => $pedido['data_hora'],
            'tempo_decorrido' => $pedido['tempo_decorrido'],
            'tempo_estimado' => $tempo_estimado,
            'itens' => $itens,
            // ✅ Informações adicionais para debug
            'debug_tipo_pedido' => [
                'tem_comida' => $tem_comida,
                'tem_bebida' => $tem_bebida
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['erro' => 'Pedido não encontrado']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno do servidor: ' . $e->getMessage()]);
}
?>