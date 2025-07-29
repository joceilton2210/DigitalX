<?php
// admin/mesas.php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
require '../configs/database.php';

// Busca as mesas existentes para listar na tabela
$stmt = $pdo->query("SELECT id, numero_mesa, identificador_unico FROM mesas ORDER BY numero_mesa ASC");
$mesas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Mesas - Painel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Painel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Dashboard de Pedidos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="mesas.php">Gerenciar Mesas</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <span class="navbar-text me-3">Olá, <?php echo $_SESSION['usuario_login']; ?></span>
                    <a href="logout.php" class="btn btn-outline-light">Sair</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">
                <h3>Adicionar Nova Mesa</h3>
                <div class="card">
                    <div class="card-body">
                        <form id="form-add-mesa">
                            <div class="mb-3">
                                <label for="numero_mesa" class="form-label">Número ou Nome da Mesa</label>
                                <input type="text" class="form-control" id="numero_mesa" name="numero_mesa" placeholder="Ex: 05, Varanda, etc." required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Adicionar Mesa</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <h3>Mesas Cadastradas</h3>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th scope="col">#ID</th>
                                <th scope="col">Número/Nome</th>
                                <th scope="col" class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mesas as $mesa): ?>
                            <tr>
                                <th scope="row"><?php echo $mesa['id']; ?></th>
                                <td><?php echo htmlspecialchars($mesa['numero_mesa']); ?></td>
                                <td class="text-end">
                                    <?php
                                        // Monta a URL completa para o QR Code
                                        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
                                        $projectPath = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
                                        $projectPath = str_replace('/admin/', '/', $projectPath); // Ajusta o caminho para a raiz do projeto
                                        $qrUrl = $baseUrl . $projectPath . 'index.php?mesa=' . urlencode($mesa['identificador_unico']);
                                    ?>
                                    <button class="btn btn-success btn-sm btn-ver-qrcode" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modal-qrcode"
                                            data-url="<?php echo $qrUrl; ?>"
                                            data-mesa-nome="<?php echo htmlspecialchars($mesa['numero_mesa']); ?>">
                                        <i class="bi bi-qr-code"></i> Ver QR Code
                                    </button>
                                    <button class="btn btn-danger btn-sm btn-excluir-mesa" data-id="<?php echo $mesa['id']; ?>">
                                        <i class="bi bi-trash"></i> Excluir
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($mesas)): ?>
                                <tr>
                                    <td colspan="3" class="text-center">Nenhuma mesa cadastrada.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-qrcode" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-qrcode-title">QR Code da Mesa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="qrcode-container" class="d-flex justify-content-center p-3"></div>
                    <p>Aponte a câmera do celular para escanear. Clique com o botão direito para salvar a imagem e imprimir.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', () => {

        // Adicionar Mesa
        const formAddMesa = document.getElementById('form-add-mesa');
        formAddMesa.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formAddMesa);
            
            try {
                const response = await fetch('../api/gerenciar_mesas.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.sucesso) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: 'Mesa adicionada com sucesso!',
                        confirmButtonText: 'Ótimo!'
                    });
                    window.location.reload(); // Recarrega a página para ver a nova mesa
                } else {
                    throw new Error(result.erro || 'Erro desconhecido');
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: `Erro ao adicionar mesa: ${error.message}`,
                    confirmButtonText: 'Entendi'
                });
            }
        });

        // Excluir Mesa
        document.querySelectorAll('.btn-excluir-mesa').forEach(button => {
            button.addEventListener('click', async () => {
                if (!confirm('Tem certeza que deseja excluir esta mesa?')) {
                    return;
                }
                const mesaId = button.dataset.id;
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', mesaId);

                try {
                    const response = await fetch('../api/gerenciar_mesas.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (result.sucesso) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso!',
                            text: 'Mesa excluída com sucesso!',
                            confirmButtonText: 'Ótimo!'
                        });
                        window.location.reload();
                    } else {
                        throw new Error(result.erro || 'Erro desconhecido');
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: `Erro ao excluir mesa: ${error.message}`,
                        confirmButtonText: 'Entendi'
                    });
                }
            });
        });

        // Ver QR Code
        const modalQrCode = new bootstrap.Modal(document.getElementById('modal-qrcode'));
        const qrcodeContainer = document.getElementById('qrcode-container');
        const modalTitle = document.getElementById('modal-qrcode-title');
        
        document.querySelectorAll('.btn-ver-qrcode').forEach(button => {
            button.addEventListener('click', () => {
                const url = button.dataset.url;
                const nomeMesa = button.dataset.mesaNome;

                modalTitle.textContent = `QR Code - Mesa ${nomeMesa}`;
                qrcodeContainer.innerHTML = ''; // Limpa o QR code anterior
                new QRCode(qrcodeContainer, {
                    text: url,
                    width: 256,
                    height: 256,
                    colorDark : "#000000",
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.H
                });
            });
        });
    });
    </script>
</body>
</html>