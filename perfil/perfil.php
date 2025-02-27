<?php
session_start();
include_once('../php/config.php');

// Verifica autenticação
if (!isset($_SESSION['email'])) {
    header('Location: ./login/login.html');
    exit();
}

// Dados da sessão
$email = $_SESSION['email'];

// Processar atualização de tema
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tema'])) {
    $novoTema = $_POST['tema'];
    try {
        $stmt = $conn->prepare("UPDATE usuarios SET tema = ? WHERE email = ?");
        $stmt->bind_param("ss", $novoTema, $email);
        $stmt->execute();
        $_SESSION['tema'] = $novoTema;
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

// Processar upload de imagem
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['imageInput'])) {
    try {
        // Validação da imagem
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['imageInput']['type'];
        $file_tmp = $_FILES['imageInput']['tmp_name'];
        
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception("Tipo de arquivo inválido");
        }

        if ($_FILES['imageInput']['size'] > 2097152) {
            throw new Exception("Tamanho máximo 2MB");
        }

        // Converter para base64
        $imagem_data = file_get_contents($file_tmp);
        $base64_image = 'data:' . $file_type . ';base64,' . base64_encode($imagem_data);

        // Atualizar banco
        $stmt = $conn->prepare("UPDATE usuarios SET foto_perfil = ? WHERE email = ?");
        $stmt->bind_param("ss", $base64_image, $email);
        $stmt->execute();
        
        $_SESSION['foto_perfil'] = $base64_image;
        $_SESSION['sucesso'] = "Foto atualizada!";

    } catch (Exception $e) {
        $_SESSION['erro'] = $e->getMessage();
    }
    header("Location: perfil.php");
    exit();
}

// Buscar dados do usuário
try {
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Usuário não encontrado");
    }
    
    $usuario = $result->fetch_assoc();
    $stmt->close();

} catch (Exception $e) {
    die("Erro: " . $e->getMessage());
}

// Configurar tema
$tema = $_SESSION['tema'] ?? 'light';
?>

<!DOCTYPE html>
<html lang="pt-BR" data-theme="<?= $tema ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistec - Perfil</title>
    <link rel="stylesheet" href="./perfil.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Estilos da barra lateral */
        .sidebar {
            position: fixed;
            left: -250px;
            top: 0;
            width: 250px;
            height: 100%;
            background: var(--sidebar-bg);
            transition: all 0.3s;
            z-index: 1000;
        }

        .sidebar.open {
            left: 0;
        }

        .sidebar-header {
            padding: 20px;
            background: var(--sidebar-header-bg);
        }

        .close-sidebar {
            position: absolute;
            right: 10px;
            top: 10px;
            background: none;
            border: none;
            color: white;
            cursor: pointer;
        }

        /* Restante dos estilos */
        .mensagem {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
        }
        .mensagem.sucesso { background: #28a745; color: white; }
        .mensagem.erro { background: #dc3545; color: white; }
        .image-overlay {
            position: absolute;
            bottom: 0;
            right: 0;
            background: rgba(0,0,0,0.5);
            border-radius: 50%;
            padding: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .image-overlay:hover {
            background: rgba(0,0,0,0.7);
        }
        .main-content {
            margin-left: 0;
            transition: margin 0.3s;
        }
        @media (min-width: 768px) {
            .sidebar {
                left: 0;
            }
            .main-content {
                margin-left: 250px;
            }
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
<body>
    <!-- Menu Lateral -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-tools"></i>
                <h1>Assistec</h1>
            </div>
            <button class="close-sidebar" id="closeSidebar">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <nav class="sidebar-nav">
            <a href="../dashboard.php" class="nav-item">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="./loja/gerenciar_produtos.php" class="nav-item">
                <i class="fas fa-users"></i>
                <span>Pessoas Cadastradas</span>
            </a>
            <a href="../php/relatórioProdutos.php" class="nav-item">
                <i class="fas fa-chart-bar"></i>
                <span>Relatórios</span>
            </a>
            <a href="perfil.php" class="nav-item active ">
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
                <h2>Editar Perfil</h2>
            </div>
            <div class="right-section">
                <button class="theme-toggle" id="themeToggle">
                    <i class="fas fa-sun"></i>
                </button>
                <div class="user-profile">
                    <img src="<?= $_SESSION['foto_perfil'] ?? 'https://via.placeholder.com/150' ?>" 
                         alt="Foto de Perfil" 
                         class="user-avatar">
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($usuario['nome'] ?? '') ?></span>
                        <span class="user-role"><?= htmlspecialchars($usuario['nivel_usuario'] ?? '') ?></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Área do Perfil -->
        <div class="profile-content">
            <?php if(isset($_SESSION['sucesso'])): ?>
                <div class="mensagem sucesso"><?= $_SESSION['sucesso'] ?></div>
                <?php unset($_SESSION['sucesso']); ?>
            <?php endif; ?>

            <?php if(isset($_SESSION['erro'])): ?>
                <div class="mensagem erro"><?= $_SESSION['erro'] ?></div>
                <?php unset($_SESSION['erro']); ?>
            <?php endif; ?>

            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-image-container">
                        <img src="<?= $_SESSION['foto_perfil'] ??  'https://filestore.community.support.microsoft.com/api/images/6061bd47-2818-4f2b-b04a-5a9ddb6f6467?upload=true' ?>" 
                             alt="Foto de Perfil" 
                             class="profile-image">
                        <div class="image-overlay">
                            <form method="POST" enctype="multipart/form-data">
                                <label for="imageInput" class="image-upload-label">
                                    <i class="fas fa-camera"></i>
                                </label>
                                <input type="file" 
                                       id="imageInput" 
                                       name="imageInput" 
                                       accept="image/*" 
                                       hidden
                                       onchange="this.form.submit()">
                            </form>
                        </div>
                    </div>
                </div>

                <form class="profile-form" method="POST" action="atualizar_perfil.php">
                    <div class="form-group">
                        <label for="nome">Nome Completo</label>
                        <input type="text" 
                               id="nome" 
                               name="nome" 
                               value="<?= htmlspecialchars($usuario['nome']) ?>" 
                               class="form-input" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?= htmlspecialchars($usuario['email']) ?>" 
                               class="form-input" 
                               required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nivel">Nível de Acesso</label>
                            <select id="nivel" class="form-input" disabled>
                                <option><?= htmlspecialchars($usuario['nivel_usuario']) ?></option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" class="form-input" disabled>
                                <option><?= htmlspecialchars($usuario['nivel_usuario']) ?></option>
                            </select>
                        </div>
                    </div>

                    <!-- Seção de senha (opcional) -->
                </form>
            </div>
        </div>
    </main>

    <script>
        // Controle da barra lateral
        const sidebar = document.getElementById('sidebar');
        const menuToggle = document.getElementById('menuToggle');
        const closeSidebar = document.getElementById('closeSidebar');

        function toggleSidebar() {
            sidebar.classList.toggle('open');
            document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : 'auto';
        }

        menuToggle.addEventListener('click', toggleSidebar);
        closeSidebar.addEventListener('click', toggleSidebar);

        // Fechar ao clicar fora
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('open');
                document.body.style.overflow = 'auto';
            }
        });

        // Tema toggle
        document.getElementById('themeToggle').addEventListener('click', () => {
            const theme = document.documentElement.getAttribute('data-theme');
            const newTheme = theme === 'dark' ? 'light' : 'dark';
            
            fetch('perfil.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `tema=${newTheme}`
            })
            .then(() => {
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
            });
        });

        // Mobile: fechar ao redimensionar
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                sidebar.classList.remove('open');
            }
        });
    </script>
</body>
</html>