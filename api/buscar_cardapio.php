<?php
// api/buscar_cardapio.php
header('Content-Type: application/json');
require '../configs/database.php';

try {
    // Busca categorias
    $stmt_cat = $pdo->query("SELECT * FROM categorias ORDER BY ordem, nome");
    $categorias = $stmt_cat->fetchAll();

    // Busca produtos
    $stmt_prod = $pdo->query("SELECT * FROM produtos WHERE disponivel = 1 ORDER BY nome");
    $produtos = $stmt_prod->fetchAll();

    // Organiza produtos por categoria
    $cardapio = [];
    foreach ($categorias as $categoria) {
        $cardapio[$categoria['id']] = [
            'id' => $categoria['id'],
            'nome' => $categoria['nome'],
            'produtos' => []
        ];
    }

    foreach ($produtos as $produto) {
        if (isset($cardapio[$produto['categoria_id']])) {
            $cardapio[$produto['categoria_id']]['produtos'][] = $produto;
        }
    }
    
    // Filtra categorias vazias
    $cardapio = array_filter($cardapio, function($cat) {
        return !empty($cat['produtos']);
    });

    echo json_encode(array_values($cardapio));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Não foi possível carregar o cardápio.']);
}
?>