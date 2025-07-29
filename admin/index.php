<?php
session_start();
// Esta verificação está correta. Ela garante que apenas admins acessem.
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_role']) || $_SESSION['usuario_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema Restaurante</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- CSS Customizado -->
    <link href="assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar Moderna -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-shop"></i>
                Sistema Restaurante
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="mesas.php">
                            <i class="bi bi-table"></i> Mesas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cardapio.php">
                            <i class="bi bi-menu-button-wide"></i> Cardápio
                        </a>
                    </li>
                </ul>
                
                <div class="d-flex align-items-center">
                    <span class="navbar-text me-3">
                        <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['usuario_login']); ?>
                    </span>
                    <a href="logout.php" class="btn btn-outline-light">
                        <i class="bi bi-box-arrow-right"></i> Sair
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <!-- Header do Dashboard -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="dashboard-title">
                        <i class="bi bi-clipboard-data"></i>
                        Dashboard de Pedidos
                    </h1>
                    <p class="dashboard-subtitle">
                        Gerencie todos os pedidos do restaurante em tempo real
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex align-items-center justify-content-end">
                        <i class="bi bi-clock me-2 text-muted"></i>
                        <span id="current-time" class="fw-medium"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="stats-container">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon pendente">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="stat-number" id="stat-pendente">0</div>
                        <div class="stat-label">Pedidos Pendentes</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon preparo">
                            <i class="bi bi-fire"></i>
                        </div>
                        <div class="stat-number" id="stat-preparo">0</div>
                        <div class="stat-label">Em Preparo</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon pronto">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div class="stat-number" id="stat-pronto">0</div>
                        <div class="stat-label">Prontos</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: var(--primary-color);">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="stat-number" id="stat-faturamento">R$ 0</div>
                        <div class="stat-label">Faturamento Hoje</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colunas de Pedidos por Setor -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="text-center mb-4">
                    <i class="bi bi-building"></i> Gestão por Setores
                </h3>
            </div>
        </div>

        <!-- SETOR COZINHA -->
        <div class="setor-container mb-5">
            <div class="setor-header">
                <h4><i class="bi bi-fire text-danger"></i> COZINHA</h4>
            </div>
            <div class="row pedidos-container g-3">
                <!-- Cozinha - Pendentes -->
                <div class="col-lg-3">
                    <div class="coluna-pedidos">
                        <div class="coluna-header">
                            <h5 class="coluna-title">
                                <div class="title-left">
                                    <i class="bi bi-clock-history text-warning"></i>
                                    Pendentes
                                </div>
                                <span class="coluna-count" id="count-cozinha-pendente">0</span>
                            </h5>
                        </div>
                        <div id="coluna-cozinha-pendente" class="pedidos-lista">
                            <div class="loading-spinner">
                                <div class="spinner"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cozinha - Em Preparo -->
                <div class="col-lg-3">
                    <div class="coluna-pedidos">
                        <div class="coluna-header">
                            <h5 class="coluna-title">
                                <div class="title-left">
                                    <i class="bi bi-fire text-info"></i>
                                    Em Preparo
                                </div>
                                <span class="coluna-count" id="count-cozinha-preparo">0</span>
                            </h5>
                        </div>
                        <div id="coluna-cozinha-preparo" class="pedidos-lista">
                            <div class="loading-spinner">
                                <div class="spinner"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cozinha - Prontos -->
                <div class="col-lg-3">
                    <div class="coluna-pedidos">
                        <div class="coluna-header">
                            <h5 class="coluna-title">
                                <div class="title-left">
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                    Prontos
                                </div>
                                <span class="coluna-count" id="count-cozinha-pronto">0</span>
                            </h5>
                        </div>
                        <div id="coluna-cozinha-pronto" class="pedidos-lista">
                            <div class="loading-spinner">
                                <div class="spinner"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cozinha - Pago -->
                <div class="col-lg-3">
                    <div class="coluna-pedidos">
                        <div class="coluna-header">
                            <h5 class="coluna-title">
                                <div class="title-left">
                                    <i class="bi bi-credit-card text-primary"></i>
                                    Pago
                                </div>
                                <span class="coluna-count" id="count-cozinha-pago">0</span>
                            </h5>
                        </div>
                        <div id="coluna-cozinha-pago" class="pedidos-lista">
                            <div class="loading-spinner">
                                <div class="spinner"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SETOR DEPÓSITO DE BEBIDAS -->
        <div class="setor-container">
            <div class="setor-header">
                <h4><i class="bi bi-cup-straw text-info"></i> DEPÓSITO DE BEBIDAS</h4>
            </div>
            <div class="row pedidos-container g-3">
                <!-- Bebidas - Pendentes -->
                <div class="col-lg-3">
                    <div class="coluna-pedidos">
                        <div class="coluna-header">
                            <h5 class="coluna-title">
                                <div class="title-left">
                                    <i class="bi bi-clock-history text-warning"></i>
                                    Pendentes
                                </div>
                                <span class="coluna-count" id="count-bebidas-pendente">0</span>
                            </h5>
                        </div>
                        <div id="coluna-bebidas-pendente" class="pedidos-lista">
                            <div class="loading-spinner">
                                <div class="spinner"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bebidas - Em Preparo -->
                <div class="col-lg-3">
                    <div class="coluna-pedidos">
                        <div class="coluna-header">
                            <h5 class="coluna-title">
                                <div class="title-left">
                                    <i class="bi bi-fire text-info"></i>
                                    Em Preparo
                                </div>
                                <span class="coluna-count" id="count-bebidas-preparo">0</span>
                            </h5>
                        </div>
                        <div id="coluna-bebidas-preparo" class="pedidos-lista">
                            <div class="loading-spinner">
                                <div class="spinner"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bebidas - Prontos -->
                <div class="col-lg-3">
                    <div class="coluna-pedidos">
                        <div class="coluna-header">
                            <h5 class="coluna-title">
                                <div class="title-left">
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                    Prontos
                                </div>
                                <span class="coluna-count" id="count-bebidas-pronto">0</span>
                            </h5>
                        </div>
                        <div id="coluna-bebidas-pronto" class="pedidos-lista">
                            <div class="loading-spinner">
                                <div class="spinner"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bebidas - Pago -->
                <div class="col-lg-3">
                    <div class="coluna-pedidos">
                        <div class="coluna-header">
                            <h5 class="coluna-title">
                                <div class="title-left">
                                    <i class="bi bi-credit-card text-primary"></i>
                                    Pago
                                </div>
                                <span class="coluna-count" id="count-bebidas-pago">0</span>
                            </h5>
                        </div>
                        <div id="coluna-bebidas-pago" class="pedidos-lista">
                            <div class="loading-spinner">
                                <div class="spinner"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ✅ NOVA: Coluna Pago -->
        <div class="col-lg-3">
            <div class="coluna-pedidos">
                <div class="coluna-header">
                    <h4 class="coluna-title">
                        <div class="title-left">
                            <i class="bi bi-credit-card text-primary"></i>
                            Pago
                        </div>
                        <span class="coluna-count" id="count-pago">0</span>
                    </h4>
                </div>
                <div id="coluna-pago" class="pedidos-lista">
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Audio para notificações -->
    <audio id="notification-sound" src="assets/alerta.mp3" preload="auto" loop></audio>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dashboard.js"></script>
    
    <script>
        // Atualizar relógio em tempo real
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('pt-BR', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('current-time').textContent = timeString;
        }
        
        // Atualizar a cada segundo
        setInterval(updateClock, 1000);
        updateClock(); // Executar imediatamente
    </script>
</body>
</html>