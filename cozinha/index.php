<?php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_role'], ['cozinha', 'admin'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cozinha - Sistema Restaurante</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- CSS Customizado -->
    <link href="../admin/assets/css/dashboard.css" rel="stylesheet">
    
    <style>
        .setor-header {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        .btn-pronto-cozinha {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-pronto-cozinha:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-fire"></i> Cozinha
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> <?= $_SESSION['usuario_login'] ?>
                </span>
                <a class="nav-link" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> Sair
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Header da Cozinha -->
        <div class="setor-header">
            <h2><i class="bi bi-fire"></i> COZINHA</h2>
            <p class="mb-0">Gerencie os pedidos de comida</p>
        </div>

        <!-- Colunas de Pedidos da Cozinha -->
        <div class="row pedidos-container g-3">
            <!-- Pendentes -->
            <div class="col-lg-3">
                <div class="coluna-pedidos">
                    <div class="coluna-header">
                        <h5 class="coluna-title">
                            <div class="title-left">
                                <i class="bi bi-clock text-warning"></i>
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

            <!-- Em Preparo -->
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

            <!-- Prontos -->
            <div class="col-lg-3">
                <div class="coluna-pedidos">
                    <div class="coluna-header">
                        <h5 class="coluna-title">
                            <div class="title-left">
                                <i class="bi bi-check-circle text-success"></i>
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

            <!-- Finalizados -->
            <div class="col-lg-3">
                <div class="coluna-pedidos">
                    <div class="coluna-header">
                        <h5 class="coluna-title">
                            <div class="title-left">
                                <i class="bi bi-check-all text-primary"></i>
                                Finalizados
                            </div>
                            <span class="coluna-count" id="count-cozinha-finalizado">0</span>
                        </h5>
                    </div>
                    <div id="coluna-cozinha-finalizado" class="pedidos-lista">
                        <div class="loading-spinner">
                            <div class="spinner"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript da Cozinha -->
    <script src="assets/js/cozinha.js"></script>
</body>
</html>