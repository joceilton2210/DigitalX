<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
require '../configs/database.php';

// Busca categorias para listar e para o select do formulário de produtos
$stmt_cat = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC");
$categorias = $stmt_cat->fetchAll();

// Busca produtos para listar na tabela
$stmt_prod = $pdo->query("
    SELECT p.*, c.nome AS categoria_nome 
    FROM produtos p 
    LEFT JOIN categorias c ON p.categoria_id = c.id 
    ORDER BY p.nome ASC
");
$produtos = $stmt_prod->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Cardápio - Painel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="index.php">Dashboard de Pedidos</a></li>
                <li class="nav-item"><a class="nav-link" href="mesas.php">Gerenciar Mesas</a></li>
                <li class="nav-item"><a class="nav-link active" href="cardapio.php">Gerenciar Cardápio</a></li>
            </ul>
            <div class="d-flex"><span class="navbar-text me-3">Olá, <?php echo $_SESSION['usuario_login']; ?></span><a href="logout.php" class="btn btn-outline-light">Sair</a></div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Categorias</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-categoria" id="btn-nova-categoria"><i class="bi bi-plus-circle"></i> Nova Categoria</button>
        </div>
        <table class="table table-striped table-hover">
            <thead><tr><th>ID</th><th>Nome</th><th class="text-end">Ações</th></tr></thead>
            <tbody>
                <?php foreach ($categorias as $cat): ?>
                <tr>
                    <td><?php echo $cat['id']; ?></td>
                    <td><?php echo htmlspecialchars($cat['nome']); ?></td>
                    <td class="text-end">
                        <button class="btn btn-warning btn-sm btn-edit-cat" data-id="<?php echo $cat['id']; ?>" data-nome="<?php echo htmlspecialchars($cat['nome']); ?>"><i class="bi bi-pencil"></i> Editar</button>
                        <button class="btn btn-danger btn-sm btn-delete-cat" data-id="<?php echo $cat['id']; ?>"><i class="bi bi-trash"></i> Excluir</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <hr class="my-5">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Produtos</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-produto" id="btn-novo-produto"><i class="bi bi-plus-circle"></i> Novo Produto</button>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead><tr><th>Imagem</th><th>Nome</th><th>Categoria</th><th>Preço</th><th>Disponível</th><th class="text-end">Ações</th></tr></thead>
                <tbody>
                    <?php foreach ($produtos as $prod): ?>
                    <tr>
                        <td><img src="../<?php echo !empty($prod['imagem_url']) ? htmlspecialchars($prod['imagem_url']) : 'https://via.placeholder.com/50'; ?>" alt="<?php echo htmlspecialchars($prod['nome']); ?>" width="50" height="50" style="object-fit: cover;"></td>
                        <td><?php echo htmlspecialchars($prod['nome']); ?></td>
                        <td><?php echo htmlspecialchars($prod['categoria_nome'] ?? 'Sem Categoria'); ?></td>
                        <td>R$ <?php echo number_format($prod['preco'], 2, ',', '.'); ?></td>
                        <td><?php echo $prod['disponivel'] ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-danger">Não</span>'; ?></td>
                        <td class="text-end">
                            <button class="btn btn-warning btn-sm btn-edit-prod" data-id="<?php echo $prod['id']; ?>"><i class="bi bi-pencil"></i> Editar</button>
                            <button class="btn btn-danger btn-sm btn-delete-prod" data-id="<?php echo $prod['id']; ?>"><i class="bi bi-trash"></i> Excluir</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal-categoria" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-categoria">
                    <div class="modal-header"><h5 class="modal-title" id="modal-categoria-title">Nova Categoria</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="cat-id">
                        <input type="hidden" name="action" id="cat-action" value="create">
                        <div class="mb-3"><label for="cat-nome" class="form-label">Nome da Categoria</label><input type="text" class="form-control" id="cat-nome" name="nome" required></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-produto" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="form-produto" enctype="multipart/form-data">
                    <div class="modal-header"><h5 class="modal-title" id="modal-produto-title">Novo Produto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="prod-id">
                        <input type="hidden" name="action" id="prod-action" value="create">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3"><label for="prod-nome" class="form-label">Nome</label><input type="text" class="form-control" id="prod-nome" name="nome" required></div>
                                <div class="mb-3"><label for="prod-descricao" class="form-label">Descrição</label><textarea class="form-control" id="prod-descricao" name="descricao" rows="3"></textarea></div>
                                <div class="row">
                                    <div class="col-md-6 mb-3"><label for="prod-preco" class="form-label">Preço</label><input type="number" step="0.01" class="form-control" id="prod-preco" name="preco" required></div>
                                    <div class="col-md-6 mb-3"><label for="prod-categoria" class="form-label">Categoria</label><select class="form-select" id="prod-categoria" name="categoria_id" required><option value="">Selecione...</option><?php foreach($categorias as $cat){echo "<option value='{$cat['id']}'>".htmlspecialchars($cat['nome'])."</option>";} ?></select></div>
                                </div>
                                <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" role="switch" id="prod-disponivel" name="disponivel" checked><label class="form-check-label" for="prod-disponivel">Produto Disponível</label></div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3"><label for="prod-imagem" class="form-label">Imagem</label><input class="form-control" type="file" name="imagem" id="prod-imagem" accept="image/png, image/jpeg, image/webp"></div>
                                <img id="prod-imagem-preview" src="https://via.placeholder.com/150" class="img-fluid rounded">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // <-- COLOQUE TODO O CÓDIGO JAVASCRIPT AQUI DENTRO -->
    document.addEventListener('DOMContentLoaded', () => {
        const modalCategoria = new bootstrap.Modal(document.getElementById('modal-categoria'));
        const formCategoria = document.getElementById('form-categoria');
        
        const modalProduto = new bootstrap.Modal(document.getElementById('modal-produto'));
        const formProduto = document.getElementById('form-produto');

        // --- LÓGICA PARA CATEGORIAS ---
        document.getElementById('btn-nova-categoria').addEventListener('click', () => {
            formCategoria.reset();
            document.getElementById('modal-categoria-title').textContent = 'Nova Categoria';
            document.getElementById('cat-action').value = 'create';
            document.getElementById('cat-id').value = '';
        });

        document.querySelectorAll('.btn-edit-cat').forEach(btn => {
            btn.addEventListener('click', () => {
                formCategoria.reset();
                document.getElementById('modal-categoria-title').textContent = 'Editar Categoria';
                document.getElementById('cat-action').value = 'update';
                document.getElementById('cat-id').value = btn.dataset.id;
                document.getElementById('cat-nome').value = btn.dataset.nome;
                modalCategoria.show();
            });
        });

        formCategoria.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formCategoria);
            const response = await fetch('../api/gerenciar_categorias.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.sucesso) {
                window.location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: result.erro,
                    confirmButtonText: 'Entendi'
                });
            }
        });

        document.querySelectorAll('.btn-delete-cat').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('Deseja excluir esta categoria? Isso pode deixar produtos sem categoria.')) return;
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', btn.dataset.id);
                const response = await fetch('../api/gerenciar_categorias.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.sucesso) {
                    window.location.reload();
                } else {
                    Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: result.erro,
                    confirmButtonText: 'Entendi'
                });
                }
            });
        });

        // --- LÓGICA PARA PRODUTOS ---
        document.getElementById('btn-novo-produto').addEventListener('click', () => {
            formProduto.reset();
            document.getElementById('modal-produto-title').textContent = 'Novo Produto';
            document.getElementById('prod-action').value = 'create';
            document.getElementById('prod-id').value = '';
            document.getElementById('prod-imagem-preview').src = 'https://via.placeholder.com/150';
        });

        document.querySelectorAll('.btn-edit-prod').forEach(btn => {
            btn.addEventListener('click', async () => {
                formProduto.reset();
                const id = btn.dataset.id;
                const response = await fetch(`../api/gerenciar_produtos.php?id=${id}`);
                const prod = await response.json();
                
                document.getElementById('modal-produto-title').textContent = 'Editar Produto';
                document.getElementById('prod-action').value = 'update';
                document.getElementById('prod-id').value = prod.id;
                document.getElementById('prod-nome').value = prod.nome;
                document.getElementById('prod-descricao').value = prod.descricao;
                document.getElementById('prod-preco').value = prod.preco;
                document.getElementById('prod-categoria').value = prod.categoria_id;
                document.getElementById('prod-disponivel').checked = !!parseInt(prod.disponivel);
                document.getElementById('prod-imagem-preview').src = prod.imagem_url ? `../${prod.imagem_url}` : 'https://via.placeholder.com/150';

                modalProduto.show();
            });
        });

        formProduto.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formProduto);
            const response = await fetch('../api/gerenciar_produtos.php', { method: 'POST', body: formData });
            const result = await response.json();
             if (result.sucesso) {
                window.location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: result.erro,
                    confirmButtonText: 'Entendi'
                });
            }
        });

         document.querySelectorAll('.btn-delete-prod').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('Deseja excluir este produto? A ação não pode ser desfeita.')) return;
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', btn.dataset.id);
                const response = await fetch('../api/gerenciar_produtos.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.sucesso) {
                    window.location.reload();
                } else {
                    Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: result.erro,
                    confirmButtonText: 'Entendi'
                });
                }
            });
        });
    });
    </script>
    </body>
</html>