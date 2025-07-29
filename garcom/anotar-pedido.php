<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit(); }
require '../configs/database.php';

$identificador_mesa = filter_input(INPUT_GET, 'mesa', FILTER_SANITIZE_STRING);
if (!$identificador_mesa) { die('<h1>Erro: Identificador de mesa inválido.</h1>'); }

$stmt = $pdo->prepare("SELECT numero_mesa FROM mesas WHERE identificador_unico = ?");
$stmt->execute([$identificador_mesa]);
$mesa = $stmt->fetch();
if (!$mesa) { die('<h1>Erro: Mesa não encontrada no sistema.</h1>'); }

$numero_mesa_para_exibir = $mesa['numero_mesa'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Anotar Pedido - Mesa <?php echo $numero_mesa_para_exibir; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-secondary sticky-top">
        <div class="container-fluid"><span class="navbar-brand">Anotando Pedido para Mesa: <strong><?php echo $numero_mesa_para_exibir; ?></strong></span><a href="index.php" class="btn btn-light btn-sm">Voltar ao Painel</a></div>
    </nav>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-7" id="cardapio-container">
                <div class="text-center"><div class="spinner-border" role="status"></div></div>
            </div>

            <div class="col-md-5">
                <div class="card sticky-top" style="top: 70px;">
                    <div class="card-header">
                        <h4 class="mb-0">Resumo do Pedido</h4>
                    </div>
                    <div class="card-body" style="max-height: 60vh; overflow-y: auto;">
                        <div id="resumo-pedido-itens">
                            <p class="text-muted">Nenhum item adicionado ainda.</p>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Subtotal:</h5>
                            <h5 class="mb-0" id="resumo-subtotal">R$ 0,00</h5>
                        </div>
                        <input type="hidden" id="pedido-id-atual" value="">
                        <button class="btn btn-success w-100" id="fechar-conta-btn" disabled>
                            <i class="bi bi-check-circle-fill"></i> Fechar Conta (+10%)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script>
    const MESA_ATUAL = "<?php echo htmlspecialchars($identificador_mesa); ?>";
</script>
<script src="assets/js/pedido.js"></script>
</body>
</html>