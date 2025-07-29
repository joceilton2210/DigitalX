<?php
// api/gerenciar_produtos.php
header('Content-Type: application/json');
require '../configs/database.php';

session_start();
if (!isset($_SESSION['usuario_id'])) { http_response_code(403); echo json_encode(['erro' => 'Acesso negado']); exit; }

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- Lógica de Upload de Imagem ---
    function uploadImagem($file) {
       // LINHA CORRIGIDA E MAIS ROBUSTA
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/digital/uploads/produtos/';
        // Validações básicas
        if ($file['error'] !== UPLOAD_ERR_OK) return [null, 'Erro no upload do arquivo.'];
        if ($file['size'] > 5 * 1024 * 1024) return [null, 'Arquivo muito grande (máx 5MB).'];
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) return [null, 'Tipo de arquivo não permitido.'];

        $fileName = uniqid() . '_' . basename($file['name']);
        $uploadPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return ['uploads/produtos/' . $fileName, null]; // Retorna caminho relativo
        }
        return [null, 'Falha ao mover o arquivo.'];
    }

    switch ($action) {
        case 'create':
        case 'update':
            $id = $_POST['id'] ?? null;
            $nome = $_POST['nome'] ?? '';
            $descricao = $_POST['descricao'] ?? '';
            $preco = $_POST['preco'] ?? 0;
            $categoria_id = $_POST['categoria_id'] ?? 0;
            $disponivel = isset($_POST['disponivel']) ? 1 : 0;
            $imagem_url = null;

            if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == UPLOAD_ERR_OK) {
                list($imagem_url, $erro) = uploadImagem($_FILES['imagem']);
                if ($erro) { http_response_code(400); echo json_encode(['erro' => $erro]); exit; }
            }

            try {
                if ($action === 'create') {
                    $sql = "INSERT INTO produtos (nome, descricao, preco, categoria_id, disponivel, imagem_url) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$nome, $descricao, $preco, $categoria_id, $disponivel, $imagem_url]);
                } else { // update
                    $sql = "UPDATE produtos SET nome=?, descricao=?, preco=?, categoria_id=?, disponivel=? ";
                    $params = [$nome, $descricao, $preco, $categoria_id, $disponivel];
                    if ($imagem_url) { // Se uma nova imagem foi enviada, atualiza o caminho
                        $sql .= ", imagem_url=? ";
                        $params[] = $imagem_url;
                        // Opcional: deletar a imagem antiga do servidor aqui
                    }
                    $sql .= "WHERE id=?";
                    $params[] = $id;
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                }
                echo json_encode(['sucesso' => true]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['erro' => 'Erro de banco de dados: ' . $e->getMessage()]);
            }
            break;
        
        case 'delete':
            $id = $_POST['id'] ?? 0;
            if (empty($id)) { http_response_code(400); echo json_encode(['erro' => 'ID inválido.']); exit; }
            
            // Primeiro, busca o caminho da imagem para deletá-la do servidor
            $stmt = $pdo->prepare("SELECT imagem_url FROM produtos WHERE id = ?");
            $stmt->execute([$id]);
            $produto = $stmt->fetch();
            
            // Deleta o registro do banco
            $stmt_delete = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
            $stmt_delete->execute([$id]);

            // Se o registro foi deletado e havia uma imagem, deleta o arquivo
            if ($stmt_delete->rowCount() > 0 && $produto && !empty($produto['imagem_url'])) {
                if (file_exists('../' . $produto['imagem_url'])) {
                    unlink('../' . $produto['imagem_url']);
                }
            }
            echo json_encode(['sucesso' => true]);
            break;

        case 'get':
            $id = $_GET['id'] ?? 0;
             if (empty($id)) { http_response_code(400); echo json_encode(['erro' => 'ID inválido.']); exit; }
            $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
            $stmt->execute([$id]);
            $produto = $stmt->fetch();
            echo json_encode($produto);
            break;

        default:
            http_response_code(400);
            echo json_encode(['erro' => 'Ação desconhecida.']);
            break;
    }
} else if ($method === 'GET') {
    // Implementa a busca de um único produto para o formulário de edição
    $id = $_GET['id'] ?? 0;
    if (empty($id)) { http_response_code(400); echo json_encode(['erro' => 'ID inválido.']); exit; }
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->execute([$id]);
    $produto = $stmt->fetch();
    echo json_encode($produto);
}

?>