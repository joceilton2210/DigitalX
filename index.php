<?php
// index.php
require 'configs/database.php';
$mesa_valida = false;
$identificador_mesa = null;
$numero_mesa_para_exibir = '';
$mensagem_erro = '';
$identificador_param = filter_input(INPUT_GET, 'mesa', FILTER_SANITIZE_STRING);

if (!$identificador_param) {
    $mensagem_erro = 'Identificador de mesa ausente. Por favor, escaneie o QR Code de uma mesa.';
} else {
    $stmt = $pdo->prepare("SELECT numero_mesa, identificador_unico FROM mesas WHERE identificador_unico = ?");
    $stmt->execute([$identificador_param]);
    $mesa = $stmt->fetch();

    if ($mesa) {
        $mesa_valida = true;
        $identificador_mesa = $mesa['identificador_unico'];
        $numero_mesa_para_exibir = $mesa['numero_mesa'];
    } else {
        $mensagem_erro = 'Mesa não encontrada no sistema. O QR Code pode ser inválido ou antigo.';
    }
}

if ($mesa_valida):
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cardápio Digital - Mesa <?php echo htmlspecialchars($numero_mesa_para_exibir); ?></title>
    
    <!-- Meta tags para PWA -->
    <meta name="theme-color" content="#667eea">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- CSS Customizado -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Tela do Cardápio -->
    <div id="tela-cardapio" class="tela-container tela-visivel">
        <!-- Header Profissional -->
        <div class="cardapio-header">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="cardapio-title">
                            <i class="bi bi-shop"></i> Nosso Cardápio
                        </h1>
                        <p class="mesa-info">
                            Bem-vindo! Faça seu pedido diretamente pelo celular
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="mesa-badge">
                            <i class="bi bi-table"></i>
                            Mesa <strong><?php echo htmlspecialchars($numero_mesa_para_exibir); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Container do Cardápio -->
        <div class="container">
            <div id="cardapio-container">
                <div class="loading-container">
                    <div class="loading-spinner"></div>
                </div>
            </div>
        </div>

        <!-- Botão Flutuante do Carrinho -->
        <div class="carrinho-fab" data-bs-toggle="modal" data-bs-target="#carrinhoModal">
            <i class="bi bi-cart-fill"></i>
            <span class="badge" id="carrinho-contador">0</span>
        </div>

        <!-- Modal do Carrinho -->
        <div class="modal fade" id="carrinhoModal" tabindex="-1" aria-labelledby="carrinhoModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="carrinhoModalLabel">
                            <i class="bi bi-cart-check"></i> Meu Pedido
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="carrinho-itens">
                        <div class="estado-vazio">
                            <i class="bi bi-cart-x"></i>
                            <h5>Seu carrinho está vazio</h5>
                            <p>Adicione alguns itens deliciosos do nosso cardápio!</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <h5 class="total-display">
                                Total: <span id="carrinho-total">R$ 0,00</span>
                            </h5>
                            <button type="button" class="btn btn-fazer-pedido" id="enviar-pedido-btn" disabled>
                                <i class="bi bi-send"></i> Fazer Pedido
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tela de Status do Pedido -->
    <div id="tela-status-pedido" class="tela-container">
        <div class="status-container">
            <div class="status-card">
                <h2 class="status-title">
                    <i class="bi bi-clock-history"></i> Acompanhe seu Pedido
                </h2>
                
                <!-- Informações do Pedido -->
                <div class="pedido-info" id="pedido-info" style="display: none;">
                    <div class="row mb-4">
                        <div class="col-6">
                            <div class="info-item">
                                <i class="bi bi-table"></i>
                                <strong>Mesa:</strong> <span id="info-mesa">-</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-item">
                                <i class="bi bi-currency-dollar"></i>
                                <strong>Total:</strong> <span id="info-total">R$ 0,00</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tempo-info mb-4">
                        <div class="tempo-decorrido">
                            <i class="bi bi-stopwatch"></i>
                            <strong>Tempo decorrido:</strong> <span id="tempo-decorrido">0 min</span>
                        </div>
                        <div class="tempo-estimado">
                            <i class="bi bi-clock"></i>
                            <span id="tempo-estimado">Calculando...</span>
                        </div>
                    </div>

                    <!-- Resumo dos Itens -->
                    <div class="itens-resumo mb-4">
                        <h6><i class="bi bi-list-ul"></i> Itens do seu pedido:</h6>
                        <div id="lista-itens-pedido"></div>
                    </div>
                </div>
                
                <div class="status-spinner" id="status-spinner"></div>
                
                <div class="status-list">
                    <div id="status-enviado" class="status-item">
                        <i class="bi bi-check-circle-fill text-secondary"></i>
                        <div class="status-content">
                            <strong>Pedido enviado para a cozinha</strong>
                            <br><small>Seu pedido foi recebido com sucesso</small>
                        </div>
                    </div>
                    <div id="status-aceito" class="status-item">
                        <i class="bi bi-circle text-secondary"></i>
                        <div class="status-content">
                            <strong>Pedido aceito e em preparo</strong>
                            <br><small>Nossa equipe está preparando seu pedido</small>
                        </div>
                    </div>
                    <div id="status-pronto" class="status-item">
                        <i class="bi bi-circle text-secondary"></i>
                        <div class="status-content">
                            <strong>Seu pedido está pronto!</strong>
                            <br><small>Pode chamar o garçom para retirar</small>
                        </div>
                    </div>
                </div>
                
                <div id="alerta-demora" class="alerta-demora" style="display: none;">
                    <h5 class="alert-heading">
                        <i class="bi bi-clock"></i> Um momento, por favor!
                    </h5>
                    <p class="mb-0">
                        Nossa cozinha está com uma alta demanda. Agradecemos a sua paciência, 
                        seu pedido já será processado.
                    </p>
                </div>

                <!-- Botão para voltar ao cardápio -->
                <div class="text-center mt-4" id="botao-novo-pedido" style="display: none;">
                    <button class="btn btn-fazer-pedido" onclick="voltarAoCardapio()">
                        <i class="bi bi-arrow-left"></i> Fazer Novo Pedido
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const MESA_ATUAL = "<?php echo htmlspecialchars($identificador_mesa); ?>";
    </script>
    <script src="assets/js/app.js"></script>
</body>
</html>

<?php
else:
    // Tela de Erro com Design Profissional
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro - Mesa Inválida</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="status-container">
        <div class="status-card">
            <div class="text-center">
                <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                <h1 class="status-title text-warning">Ops! Ocorreu um erro</h1>
                <p class="lead mb-4"><?php echo htmlspecialchars($mensagem_erro); ?></p>
                <hr class="my-4">
                <p class="mb-4">
                    <i class="bi bi-qr-code-scan"></i>
                    Por favor, tente escanear o QR Code localizado na sua mesa novamente.
                </p>
                <button onclick="window.location.reload()" class="btn btn-fazer-pedido">
                    <i class="bi bi-arrow-clockwise"></i> Tentar Novamente
                </button>
            </div>
        </div>
    </div>
</body>
</html><?php
endif;
?>
