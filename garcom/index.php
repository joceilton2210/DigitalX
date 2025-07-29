<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel do Garçom</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid"><a class="navbar-brand">Painel do Garçom</a><div class="d-flex"><span class="navbar-text me-3">Olá, <?php echo $_SESSION['usuario_login']; ?></span><a href="logout.php" class="btn btn-outline-light">Sair</a></div></div>
    </nav>
    <div class="container text-center mt-5">
        <h1 class="mb-4">Anotar Pedido</h1>
        <p class="lead">Para anotar um pedido, escaneie o QR Code da mesa com seu dispositivo ou cole a URL do QR Code abaixo.</p>
        
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="qr-url-input" placeholder="Cole a URL do QR Code aqui para testar">
                    <button class="btn btn-primary" type="button" id="go-to-mesa-btn">Ir para Mesa</button>
                </div>
                <p class="text-muted">(No celular, um botão "Escanear" ativaria a câmera)</p>
            </div>
        </div>
    </div>
<script>
    // Simulação do scan de QR Code para teste no computador
    document.getElementById('go-to-mesa-btn').addEventListener('click', () => {
        const url = document.getElementById('qr-url-input').value;
        if (url) {
            // Extrai o parâmetro 'mesa' da URL colada
            try {
                const urlObj = new URL(url);
                const mesaIdentifier = urlObj.searchParams.get('mesa');
                if (mesaIdentifier) {
                    window.location.href = `anotar-pedido.php?mesa=${mesaIdentifier}`;
                } else {
                    alert('URL inválida. Não foi possível encontrar o parâmetro da mesa.');
                }
            } catch (e) {
                alert('URL inválida. Por favor, cole a URL completa do QR Code.');
            }
        }
    });
</script>
</body>
</html>