<?php
// api/gerenciar_mesas.php
header('Content-Type: application/json');
require '../configs/database.php';

session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado.']);
    exit;
}

// Verifica o método da requisição
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        // --- LÓGICA PARA CRIAR MESA ---
        $numero_mesa = filter_input(INPUT_POST, 'numero_mesa', FILTER_SANITIZE_STRING);

        if (empty($numero_mesa)) {
            http_response_code(400);
            echo json_encode(['erro' => 'O número/nome da mesa é obrigatório.']);
            exit;
        }

        try {
            // Gera um identificador único para a mesa, para ser usado na URL
            $identificador_unico = uniqid('mesa_');

            $stmt = $pdo->prepare("INSERT INTO mesas (numero_mesa, identificador_unico) VALUES (?, ?)");
            $stmt->execute([$numero_mesa, $identificador_unico]);

            echo json_encode(['sucesso' => true, 'id' => $pdo->lastInsertId()]);

        } catch (PDOException $e) {
            // Código 23000 é erro de violação de integridade (ex: valor duplicado)
            if ($e->getCode() == 23000) {
                http_response_code(409); // Conflict
                echo json_encode(['erro' => 'Já existe uma mesa com este número/nome.']);
            } else {
                http_response_code(500);
                echo json_encode(['erro' => 'Erro de banco de dados: ' . $e->getMessage()]);
            }
        }

    } elseif ($action === 'delete') {
        // --- LÓGICA PARA DELETAR MESA ---
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['erro' => 'ID da mesa inválido.']);
            exit;
        }

        try {
            // ATENÇÃO: Se a chave estrangeira na tabela `pedidos` estiver como ON DELETE RESTRICT (como sugeri),
            // o sistema não deixará excluir uma mesa que já tenha pedidos. Isso é bom para manter o histórico.
            // Se quiser permitir, mude para ON DELETE SET NULL.
            $stmt = $pdo->prepare("DELETE FROM mesas WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['sucesso' => true]);
            } else {
                http_response_code(404);
                echo json_encode(['erro' => 'Mesa não encontrada ou não pôde ser excluída (pode ter pedidos associados).']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro de banco de dados: ' . $e->getMessage()]);
        }
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['erro' => 'Método não permitido.']);
}