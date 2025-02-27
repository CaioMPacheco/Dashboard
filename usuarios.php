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
$usuario_edit = null;

// Processar edição (GET)
if (isset($_GET['edit'])) {
    try {
        $id = intval($_GET['edit']);
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id_usuario = ? AND nivel_usuario = 'Cliente'");
        if (!$stmt) {
            throw new Exception("Erro na preparação da consulta: " . $conn->error);
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario_edit = $result->fetch_assoc();
        $stmt->close();
    } catch (Exception $e) {
        die("Erro: " . $e->getMessage());
    }
}

// Processar formulário (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validação básica
        $campos_requeridos = ['nome', 'email'];
        foreach ($campos_requeridos as $campo) {
            if (empty($_POST[$campo])) {
                throw new Exception("O campo $campo é obrigatório!");
            }
        }

        // Preparar dados
        $dados = [
            'nome' => htmlspecialchars($_POST['nome']),
            'email' => htmlspecialchars($_POST['email']),
            'nivel_usuario' => 'Cliente'
        ];

        // Operação de atualização
        if (isset($_POST['id'])) {
            $id = intval($_POST['id']);
            $sql = "UPDATE usuarios SET nome = ?, email = ? WHERE id_usuario = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Erro na preparação da consulta: " . $conn->error);
            }
            $stmt->bind_param('ssi', $dados['nome'], $dados['email'], $id);

            if (!$stmt->execute()) {
                throw new Exception("Erro na atualização: " . $stmt->error);
            }

            $stmt->close();
            header("Location: clientes.php");
            exit();
        } else {
            if (empty($_POST['senha'])) {
                throw new Exception("Senha é obrigatória para novos clientes!");
            }

            $senha_hash = password_hash($_POST['senha']);
            
            $sql = "INSERT INTO usuarios (nome, email, senha, nivel_usuario)
                    VALUES (?, ?, ?, 'Cliente')";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Erro na preparação da consulta: " . $conn->error);
            }
            $stmt->bind_param('sss', $dados['nome'], $dados['email'], $senha_hash);

            if (!$stmt->execute()) {
                throw new Exception("Erro no cadastro: " . $stmt->error);
            }

            $stmt->close();
            header("Location: usuarios.php");
            exit();
        }
    } catch (Exception $e) {
        $mensagem = "Erro: " . $e->getMessage();
        header("Location: usuarios.php?erro=" . urlencode($mensagem));
        exit();
    }
}

// Processar exclusão
if (isset($_GET['delete'])) {
    try {
        $id = intval($_GET['delete']);
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ? AND nivel_usuario = 'Cliente'");
        if (!$stmt) {
            throw new Exception("Erro na preparação da consulta: " . $conn->error);
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        
        header("Location: clientes.php?sucesso=Cliente+excluído+com+sucesso");
        exit();
    } catch (Exception $e) {
        header("Location: clientes.php?erro=" . urlencode($e->getMessage()));
        exit();
    }
}

// Listar clientes
$clientes = $conn->query("SELECT id_usuario, nome, email FROM usuarios WHERE nivel_usuario = 'Cliente' ORDER BY id_usuario DESC");
if (!$clientes) {
    die("Erro na consulta: " . $conn->error);
}

// Mensagens via GET
if (isset($_GET['sucesso'])) {
    $mensagem = '<div class="mensagem sucesso">' . urldecode($_GET['sucesso']) . '</div>';
} elseif (isset($_GET['erro'])) {
    $mensagem = '<div class="mensagem erro">' . urldecode($_GET['erro']) . '</div>';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistec - Clientes</title>
    <link rel="stylesheet" href="./css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #6a1b9a;
            --secondary: #9c27b0;
            --background: #ffffff;
            --text-color: #333333;
            --card-bg: #f5f5f5;
            --border-color: #dddddd;
            --shadow-color: rgba(0,0,0,0.1);
        }

        .dark-theme {
            --background: #1a1a1a;
            --text-color: #ffffff;
            --card-bg: #2d2d2d;
            --border-color: #444444;
            --shadow-color: rgba(255,255,255,0.1);
        }

        body {
            background-color: var(--background);
            color: var(--text-color);
            transition: all 0.3s ease;
        }

        .user-profile img.foto {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid var(--primary);
        }

        .user-management {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 2px 10px var(--shadow-color);
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .users-table th, .users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--text-color);
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: var(--card-bg);
            color: var(--text-color);
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
            background: #dc3545;
            color: white;
        }

        .btn-edit {
            background: #28a745;
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
</head>

<body class="light-theme">
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
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="clientes.php" class="nav-item active">
                <i class="fas fa-users"></i>
                <span>Clientes</span>
            </a>
            <a href="./perfil/perfil.php" class="nav-item">
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
                <button class="theme-toggle" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            <div class="right-section">
                <div class="user-profile">
                    <img src="<?= $_SESSION['foto_perfil'] ?? 'https://via.placeholder.com/70' ?>" class="foto">
                    <div class="user-info">
                        <span class="user-name"><?= $_SESSION['nome'] ?></span>
                        <span class="user-role"><?= $_SESSION['nivel_usuario'] ?></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Área do Dashboard -->
        <div class="dashboard-content">
            <div class="user-management">
                <h2><?= isset($usuario_edit) ? 'Editar' : 'Adicionar' ?> Cliente</h2>

                <?php if ($mensagem): ?>
                    <div class="mensagem"><?= $mensagem ?></div>
                <?php endif; ?>

                <form class="user-form" method="POST">
                    <?php if (isset($usuario_edit)): ?>
                        <input type="hidden" name="id" value="<?= $usuario_edit['id'] ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nome Completo</label>
                            <input type="text" name="nome" required value="<?= $usuario_edit['nome'] ?? '' ?>">
                        </div>

                        <div class="form-group">
                            <label>E-mail</label>
                            <input type="email" name="email" required value="<?= $usuario_edit['email'] ?? '' ?>">
                        </div>

                        <?php if (!isset($usuario_edit)): ?>
                            <div class="form-group">
                                <label>Senha</label>
                                <input type="password" name="senha" required>
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <?= isset($usuario_edit) ? 'Atualizar' : 'Cadastrar' ?> Cliente
                    </button>
                </form>

                <h3>Lista de Clientes</h3>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($cliente = $clientes->fetch_assoc()): ?>
                            <tr>
                                <td><?= $cliente['id_usuario'] ?></td>
                                <td><?= $cliente['nome'] ?></td>
                                <td><?= $cliente['email'] ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?edit=<?= $cliente['id_usuario'] ?>" class="btn btn-edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete=<?= $cliente['id_usuario'] ?>" 
                                           class="btn btn-danger"
                                           onclick="return confirm('Tem certeza que deseja excluir este cliente?')">
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

    <script>
   const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const closeSidebar = document.getElementById('closeSidebar');
    const mainContent = document.querySelector('.main-content');

    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
        mainContent.classList.toggle('menu-active');
    });

    closeSidebar.addEventListener('click', () => {
        sidebar.classList.remove('active');
        mainContent.classList.remove('menu-active');
    });

    // Fechar menu ao clicar fora
    document.addEventListener('click', (e) => {
        if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
            sidebar.classList.remove('active');
            mainContent.classList.remove('menu-active');
        }
    });

    // Tema
    const themeToggle = document.getElementById('themeToggle');
    themeToggle.addEventListener('click', () => {
        document.body.classList.toggle('dark-theme');
        localStorage.setItem('theme', document.body.classList.contains('dark-theme') ? 'dark' : 'light');
        themeToggle.querySelector('i').classList.toggle('fa-moon');
        themeToggle.querySelector('i').classList.toggle('fa-sun');
    });

    // Carregar tema salvo
    const savedTheme = localStorage.getItem('theme') || 'light';
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
        themeToggle.querySelector('i').classList.replace('fa-moon', 'fa-sun');
    }
    </script>
</body>
</html>