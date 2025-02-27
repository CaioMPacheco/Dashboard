<?php
session_start();
include_once('config.php');

// Verificar autenticação
if (!isset($_SESSION['email']) || $_SESSION['nivel_usuario'] != 'Administrador') {
    header('Location: ./login/index.html');
    exit();
}

// Configurar erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Variáveis globais
$mensagem = '';
$produto_edit = null;


$usercount = "SELECT COUNT(*) as total FROM usuarios";

$resultcount = mysqli_query($conn, $usercount);




if ($resultcount) {
    $row = mysqli_fetch_assoc($resultcount);
    
    $_SESSION['total_usuarios'] = $row['total'];
} else {
    echo "Erro na consulta: " . mysqli_error($conn);
}

// Processar edição (GET)
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $produto_edit = $result->fetch_assoc();
    $stmt->close();
}

// Processar formulário (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validação básica
        $campos_requeridos = ['nome', 'preco', 'categoria', 'estoque'];
        foreach ($campos_requeridos as $campo) {
            if (empty($_POST[$campo])) {
                throw new Exception("O campo $campo é obrigatório!");
            }
        }

        // Preparar dados
        $dados = [
            'nome' => htmlspecialchars($_POST['nome']),
            'preco' => floatval($_POST['preco']),
            'categoria' => htmlspecialchars($_POST['categoria']),
            'estoque' => intval($_POST['estoque']),
            'descricao' => htmlspecialchars($_POST['descricao'] ?? '')
        ];

        // Processar imagem
        $imagem = null;
        $tipo_imagem = null;

        if (!empty($_FILES['imagem']['tmp_name'])) {
            if ($_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Erro no upload: " . $_FILES['imagem']['error']);
            }

            // Validar tipo e tamanho
            $tipo_imagem = mime_content_type($_FILES['imagem']['tmp_name']);
            $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif'];

            if (!in_array($tipo_imagem, $tipos_permitidos)) {
                throw new Exception("Tipo de arquivo inválido: " . $tipo_imagem);
            }

            if ($_FILES['imagem']['size'] > 2097152) {
                throw new Exception("Tamanho excede 2MB");
            }

            $imagem = file_get_contents($_FILES['imagem']['tmp_name']);
        }

        // Operação de atualização
        if (isset($_POST['id'])) {
            $id = intval($_POST['id']);
            $campos = [];
            $valores = [];
            $tipos = '';

            foreach ($dados as $campo => $valor) {
                $campos[] = "$campo = ?";
                $valores[] = $valor;
                $tipos .= 's';
            }

            if ($imagem) {
                $campos[] = "imagem = ?";
                $valores[] = $imagem;
                $tipos .= 's';

                $campos[] = "tipo_imagem = ?";
                $valores[] = $tipo_imagem;
                $tipos .= 's';
            }

            $valores[] = $id;
            $tipos .= 'i';

            $sql = "UPDATE produtos SET " . implode(', ', $campos) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($tipos, ...$valores);

            if (!$stmt->execute()) {
                throw new Exception("Erro na atualização: " . $stmt->error);
            }

            $stmt->close();
            header("Location: dashboard.php?sucesso=Produto+atualizado+com+sucesso");
            exit();

            // Operação de cadastro

        } else {
            if (!$imagem) {
                throw new Exception("Imagem é obrigatória para novos produtos!");
            }

            // Converter para base64 ANTES de armazenar
            $imagem_base64 = base64_encode($imagem); // Corrigido aqui

            $sql = "INSERT INTO produtos (nome, preco, categoria, estoque, descricao, imagem, tipo_imagem)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                'sdsisss',
                $dados['nome'],
                $dados['preco'],
                $dados['categoria'],
                $dados['estoque'],
                $dados['descricao'],
                $imagem_base64, // Usar a variável convertida
                $tipo_imagem
            );

            // Remova as linhas redundantes abaixo
            // $imagem_base64 = base64_encode(file_get_contents($_FILES['imagem']['tmp_name']));
            // $tipo_imagem = $_FILES['imagem']['type'];
            if (!$stmt->execute()) {
                throw new Exception("Erro no cadastro: " . $stmt->error);
            }

            $stmt->close();
            header("Location: dashboard.php?sucesso=Produto+cadastrado+com+sucesso");
            exit();
        }
    } catch (Exception $e) {
        $mensagem = "Erro: " . $e->getMessage();
        header("Location: dashboard.php?erro=" . urlencode($mensagem));
        exit();
    }
}

// Processar exclusão
if (isset($_GET['delete'])) {
    try {
        $id = intval($_GET['delete']);
        $conn->begin_transaction();

        // Excluir vendas relacionadas
        $stmt_vendas = $conn->prepare("DELETE FROM vendas WHERE id = ?");
        $stmt_vendas->bind_param('i', $id);
        $stmt_vendas->execute();
        $stmt_vendas->close();

        // Excluir produto
        $stmt_produto = $conn->prepare("DELETE FROM produtos WHERE id = ?");
        $stmt_produto->bind_param('i', $id);
        $stmt_produto->execute();
        $stmt_produto->close();

        $conn->commit();
        header("Location: dashboard.php?sucesso=Produto+excluído+com+sucesso");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: dashboard.php?erro=" . urlencode($e->getMessage()));
        exit();
    }
}

// Listar produtos
// Altere a query de seleção para:
$produtos = $conn->query("SELECT *,
    CONCAT('data:', tipo_imagem, ';base64,', imagem) as imagem_base64 
    FROM produtos ORDER BY id DESC");
// Mensagens via GET
if (isset($_GET['sucesso'])) {
    $mensagem = '<div class="mensagem sucesso">' . urldecode($_GET['sucesso']) . '</div>';
} elseif (isset($_GET['erro'])) {
    $mensagem = '<div class="mensagem erro">' . urldecode($_GET['erro']) . '</div>';
}




// Consulta para dados do gráfico (no PHP)
$dados_grafico = $conn->query("
    SELECT 
        nome,
        estoque
    FROM produtos
    ORDER BY estoque DESC
    LIMIT 10 
");


?>




<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistec - Dashboard</title>
    <link rel="stylesheet" href="./css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<style>
    .foto{
        width: 70px;
        height: 70px;
    }

    .chart-legend-text {
    color: var(--text-color) !important;
    font-family: 'Segoe UI', sans-serif !important;
}

        .chart-container {
    position: relative;
    padding: 20px;
    border-radius: 12px;
    transition: background-color 0.3s ease;
}

    .chart-container.dark {
        background-color: #2d2d2d;
    }

    .chart-container.light {
        background-color: #ffffff;
    }

    .chart-legend {
        padding: 15px;
        margin-top: 20px;
        border-radius: 8px;
        background-color: var(--card-bg);
    }

    .chart-title {
        color: var(--text-color);
        font-size: 1.4rem;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid var(--primary);
    }

    .chart-tooltip {
        background-color: var(--card-bg) !important;
        border: 1px solid var(--border-color) !important;
        border-radius: 6px !important;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1) !important;
    }

    .chart-tooltip .tooltip-header {
        color: var(--primary) !important;
        font-weight: 600 !important;
        margin-bottom: 5px !important;
    }

    .chart-tooltip .tooltip-body {
        color: var(--text-color) !important;
        font-size: 0.9rem !important;
    }
    .chart-wrapper {
        position: relative;
        min-height: 400px;
        margin: 20px 0;
        padding: 15px;
        background: var(--card-bg);
        border-radius: 10px;
        box-shadow: 0 2px 10px var(--shadow-color);
    }

    .mensagem {
        padding: 15px;
        margin: 20px 0;
        border-radius: 5px;
        color: #fff;
    }

    .mensagem.sucesso {
        background: #28a745;
    }

    .mensagem.erro {
        background: #dc3545;
    }


    .product-management {
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
        background: var(--card-bg);
        border-radius: 10px;
        box-shadow: 0 2px 10px var(--shadow-color);
    }

    .product-form {
        margin-bottom: 30px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        color: var(--text-color);
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid var(--border-color);
        border-radius: 4px;
        background: var(--input-bg);
        color: var(--text-color);
    }

    .products-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .products-table th,
    .products-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }

    .product-image-preview {
        max-width: 100px;
        max-height: 100px;
        border-radius: 4px;
    }

    .action-buttons {
        display: flex;
        gap: 10px;
    }

    .btn {
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .btn-primary {
        background: var(--primary);
        color: white;
    }

    .btn-danger {
        background: var(--error);
        color: white;
    }

    .btn-edit {
        background: var(--success);
        color: white;
    }

        /* Menu Lateral */
        .sidebar {
        position: fixed;
        left: -300px;
        top: 0;
        width: 300px;
        height: 100%;
        background: var(--card-bg);
        transition: all 0.3s ease;
        z-index: 1000;
    }

    .sidebar.active {
        left: 0;
    }

    .sidebar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid var(--border-color);
    }

    .sidebar-nav {
        padding: 20px;
    }

    .nav-item {
        display: flex;
        align-items: center;
        padding: 12px;
        color: var(--text-color);
        text-decoration: none;
        border-radius: 6px;
        margin-bottom: 8px;
        transition: all 0.3s ease;
    }

    .nav-item.active {
        background: var(--primary);
        color: white !important;
    }

    .nav-item:hover {
        background: var(--primary);
        color: white !important;
    }

    .nav-item i {
        margin-right: 12px;
        width: 20px;
    }

    #closeSidebar {
        cursor: pointer;
        transition: transform 0.3s ease;
    }

    #closeSidebar:hover {
        transform: rotate(90deg);
    }

    /* Ajuste no conteúdo principal */
    .main-content {
        margin-left: 0;
        transition: margin-left 0.3s ease;
    }

    .sidebar.active ~ .main-content {
        margin-left: 300px;
    }

    /* Botão do menu mobile */
    .menu-toggle {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: var(--text-color);
        margin-right: 20px;
    }

    @media (max-width: 768px) {
        .sidebar.active ~ .main-content {
            margin-left: 0;
        }
    }

</style>

<body>
    <!-- Menu Lateral -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-tools"></i>
                <h1>Assistec</h1>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" id="closeSidebar" width="21" height="21" fill="currentColor"
                cursor="pointer" class="bi bi-x-circle-fill" viewBox="0 0 16 16">
                <path
                    d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293z" />
            </svg>
            </button>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item active">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="usuarios.php" class="nav-item">
                <i class="fas fa-users"></i>
                <span>Pessoas Cadastradas</span>
            </a>
            <a href="./php/relatórioProdutos.php" class="nav-item">
                <i class="fas fa-chart-bar"></i>
                <span>Relatórios</span>
            </a>
            <a href="./perfil/perfil.php" class="nav-item ">
                <i class="fas fa-user-cog"></i>
                <span>Editar Perfil</span>
            </a>
        </nav>
    </aside>

    <!-- Conteúdo Principal -->
    <main class="main-content">
        <!-- Barra Superior -->
        <header class="top-bar">
            <div class="left-section">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <div class="right-section">
                <button class="theme-toggle" id="themeToggle">
                    <i class="fas fa-sun"></i>
                </button>
                <button class="notification-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
                <div class="user-profile">
                <img src="<?= $_SESSION['foto_perfil'] ??  'https://filestore.community.support.microsoft.com/api/images/6061bd47-2818-4f2b-b04a-5a9ddb6f6467?upload=true' ?>" class="foto">
                    <div class="user-info">
                        <span class="user-name">
                            <?php echo $_SESSION['nome']; ?>
                        </span>
                        <span class="user-role">
                            <?php echo $_SESSION['nivel_usuario']; ?>
                        </span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Área do Dashboard -->
        <div class="dashboard-content">
            <!-- Cards de Estatísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Receita Total</span>
                        <span class="stat-value" id="totalRevenue">R$ 8.000,00</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Vendas Online</span>
                        <span class="stat-value" id="onlineRevenue">R$ 7.300,00</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Despesas</span>
                        <span class="stat-value" id="expenses">R$ 6.700,00</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Usuários</span>
                        <span class="stat-value" id="totalUsers"><?php echo $_SESSION['total_usuarios'];?></span>
                    </div>
                </div>
            </div>

            <!-- Área de Gráficos -->
            <div class="charts-container">
            <div class="chart-card">
            <h3>Estoque por Categoria</h3>
            <div class="chart-wrapper">
                <canvas id="myChart"></canvas>
            </div>
        </div>
                    <div class="metrics-grid">
                        <div class="metric-card">
                            <div class="metric-title">Vendas</div>
                            <div class="metric-value" id="salesMetric">45</div>
                            <div class="metric-chart" id="salesChart"></div>
                        </div>

                        <div class="metric-card">
                            <div class="metric-title">Avaliações</div>
                            <div class="metric-value" id="reviewsMetric">22</div>
                            <div class="metric-chart" id="reviewsChart"></div>
                        </div>

                        <div class="metric-card">
                            <div class="metric-title">Visitantes</div>
                            <div class="metric-value" id="visitorsMetric">90</div>
                            <div class="metric-chart" id="visitorsChart"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="dashboard-content">
                <div class="product-management">
                    <h2><?= isset($produto_edit) ? 'Editar' : 'Adicionar' ?> Produto</h2>

                    <?php if ($mensagem): ?>
                        <div class="mensagem"><?= $mensagem ?></div>
                    <?php endif; ?>

                    <form class="product-form" method="POST" enctype="multipart/form-data">
                        <?php if (isset($produto_edit)): ?>
                            <input type="hidden" name="id" value="<?= $produto_edit['id'] ?>">
                        <?php endif; ?>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Nome do Produto</label>
                                <input type="text" name="nome" required
                                    value="<?= $produto_edit['nome'] ?? '' ?>">
                            </div>

                            <div class="form-group">
                                <label>Preço (R$)</label>
                                <input type="number" step="0.01" name="preco" required
                                    value="<?= $produto_edit['preco'] ?? '' ?>">
                            </div>

                            <div class="form-group">
                                <label>Categoria</label>
                                <select name="categoria" required>
                                    <option value="hardware" <?= ($produto_edit['categoria'] ?? '') == 'hardware' ? 'selected' : '' ?>>Hardware</option>
                                    <option value="servicos" <?= ($produto_edit['categoria'] ?? '') == 'servicos' ? 'selected' : '' ?>>Serviços</option>
                                    <option value="perifericos" <?= ($produto_edit['categoria'] ?? '') == 'perifericos' ? 'selected' : '' ?>>Periféricos</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Estoque</label>
                                <input type="number" name="estoque" required
                                    value="<?= $produto_edit['estoque'] ?? '' ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Descrição</label>
                            <textarea name="descricao" rows="3"><?= $produto_edit['descricao'] ?? '' ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Imagem do Produto</label>
                            <input type="file" name="imagem" accept="image/*" <?= !isset($produto_edit) ? 'required' : '' ?>>
                            <?php if (isset($produto_edit) && !empty($produto_edit['imagem'])): ?>
                                <img src="/loja/exibir_imagem.php $produto_edit['id'] ?>"
                                    class="product-image-preview"
                                    alt="Preview">
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <?= isset($produto_edit) ? 'Atualizar' : 'Cadastrar' ?> Produto
                        </button>
                    </form>

                    <h3>Lista de Produtos</h3>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Imagem</th>
                                <th>Nome</th>
                                <th>Preço</th>
                                <th>Categoria</th>
                                <th>Estoque</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($produto = $produtos->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($produto['imagem_base64'])): ?>
                                            <img src="<?= $produto['imagem_base64'] ?>"
                                                class="product-image-preview"
                                                alt="<?= $produto['nome'] ?>">
                                        <?php endif; ?>
                                    </td>

                                    <!-- Na pré-visualização do formulário -->
                                    <?php if (isset($produto_edit) && !empty($produto_edit['imagem'])): ?>
                                        <img src="data:<?= $produto_edit['tipo_imagem'] ?>;base64,<?= $produto_edit['imagem'] ?>"
                                            class="product-image-preview"
                                            alt="Preview">
                                    <?php endif; ?>
                                    </td>
                                    <td><?= $produto['nome'] ?></td>
                                    <td>R$ <?= number_format($produto['preco'], 2, ',', '.') ?></td>
                                    <td><?= ucfirst($produto['categoria']) ?></td>
                                    <td><?= $produto['estoque'] ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?edit=<?= $produto['id'] ?>" class="btn btn-edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete=<?= $produto['id'] ?>"
                                                class="btn btn-danger"
                                                onclick="return confirm('Tem certeza que deseja excluir este produto?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
    </main>

    <script src="./js/dashboard.js"></script>
    <!-- Resources -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/apexcharts/4.3.0/apexcharts.min.js"
        integrity="sha512-QgLS4OmTNBq9TujITTSh0jrZxZ55CFjs4wjK8NXsBoZb04UYl8wWQJNaS8jRiLq6/c60bEfOj3cPsxadHICNfw=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdn.amcharts.com/lib/4/core.js"></script>
    <script src="https://cdn.amcharts.com/lib/4/charts.js"></script>
    <script src="https://cdn.amcharts.com/lib/4/themes/animated.js"></script>

    <script>
    // Dados do PHP convertidos para JS
    const dadosGrafico = <?php 
        $grafico_data = [];
        while($row = $dados_grafico->fetch_assoc()) {
            $grafico_data[] = $row;
        }
        echo json_encode($grafico_data); 
    ?>;

    // Função para gerar cores dinâmicas em tons de roxo
    function generatePurpleColors(count) {
        const colors = [];
        const baseHue = 260; // Tom base roxo
        const saturation = 70; // Saturação fixa
        const lightness = 60; // Luminosidade fixa
        
        for(let i = 0; i < count; i++) {
            const hueVariation = Math.floor((i * 30) % 360); // Variação de 30 graus
            const alpha = 0.7 + (i * 0.03); // Transparência decrescente
            colors.push(`hsla(${baseHue + hueVariation}, ${saturation}%, ${lightness}%, ${alpha})`);
        }
        return colors;
    }

    // Função para obter as cores do tema
    function getThemeColors() {
        const isDarkTheme = document.body.classList.contains('dark-theme');
        return {
            bgColor: isDarkTheme ? '#2d2d2d' : '#ffffff',
            textColor: isDarkTheme ? '#ffffff' : '#2d2d2d',
            gridColor: isDarkTheme ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
            borderColor: isDarkTheme ? '#444' : '#ddd'
        };
    }

    // Função principal para renderizar o gráfico
    function renderPieChart() {
        const ctx = document.getElementById('myChart');
        const colors = getThemeColors();
        
        // Destruir gráfico anterior se existir
        if (window.myPieChart instanceof Chart) {
            window.myPieChart.destroy();
        }

        // Preparar dados
        const labels = dadosGrafico.map(item => 
            item.nome.length > 15 ? 
            item.nome.substring(0, 15) + '...' : 
            item.nome
        );
        
        const dataValues = dadosGrafico.map(item => item.estoque);
        const backgroundColors = generatePurpleColors(dadosGrafico.length);

        // Configuração do gráfico
        const config = {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: dataValues,
                    backgroundColor: backgroundColors,
                    borderColor: colors.bgColor,
                    borderWidth: 2,
                    hoverOffset: 20
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color:'rgb(148, 81, 230)',
                            font: {
                                size: 14,
                                family: "'Segoe UI', sans-serif"
                            },
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: colors.bgColor,
                        titleColor: colors.textColor,
                        bodyColor: colors.textColor,
                        borderColor: 'rgb(255, 255, 255)',
                        borderWidth: 2,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const value = context.parsed;
                                const percentage = ((value / total) * 100).toFixed(1);
                                return ` ${value} unidades (${percentage}%)`;
                            }
                        }
                    }
                },
                animation: {
                    duration: 800,
                    easing: 'easeOutQuart'
                }
            }
        };

        // Criar novo gráfico
        window.myPieChart = new Chart(ctx, config);
    }

    // Controle de redimensionamento
    function handleResize() {
        renderPieChart();
    }

    // Inicialização
    document.addEventListener('DOMContentLoaded', () => {
        renderPieChart();
        
        // Atualizar ao mudar tema
        const themeToggle = document.getElementById('themeToggle');
        if(themeToggle) {
            themeToggle.addEventListener('click', () => {
                setTimeout(renderPieChart, 300);
            });
        }

        // Atualizar ao redimensionar
        window.addEventListener('resize', handleResize);
    });
</script>
</body>

</html>