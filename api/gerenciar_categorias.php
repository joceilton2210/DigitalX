<?php
// api/gerenciar_categorias.php
header('Content-Type: application/json');
require '../configs/database.php';

session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $nome = $_POST['nome'] ?? '';
            if (empty($nome)) { http_response_code(400); echo json_encode(['erro' => 'Nome é obrigatório.']); exit; }
            $stmt = $pdo->prepare("INSERT INTO categorias (nome) VALUES (?)");
            $stmt->execute([$nome]);
            echo json_encode(['sucesso' => true, 'id' => $pdo->lastInsertId()]);
            break;

        case 'update':
            $id = $_POST['id'] ?? 0;
            $nome = $_POST['nome'] ?? '';
            if (empty($nome) || empty($id)) { http_response_code(400); echo json_encode(['erro' => 'Dados inválidos.']); exit; }
            $stmt = $pdo->prepare("UPDATE categorias SET nome = ? WHERE id = ?");
            $stmt->execute([$nome, $id]);
            echo json_encode(['sucesso' => true]);
            break;

        case 'delete':
            $id = $_POST['id'] ?? 0;
            if (empty($id)) { http_response_code(400); echo json_encode(['erro' => 'ID inválido.']); exit; }
            // Opcional: Verificar se a categoria não tem produtos antes de excluir
            $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['sucesso' => true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['erro' => 'Ação desconhecida.']);
            break;
    }
}
?>