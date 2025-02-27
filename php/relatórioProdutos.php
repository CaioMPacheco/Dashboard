<?php
session_start();
include_once('config.php');

// Verificar autenticação
if (!isset($_SESSION['email']) || $_SESSION['nivel_usuario'] != 'Administrador') {
    header('Location: ./login/index.html');
    exit();
}

// Incluir Dompdf
require_once '../dompdf/autoload.inc.php';
use Dompdf\Dompdf;

// Consulta aos produtos
$produtos = $conn->query("SELECT *,
    CONCAT('data:', tipo_imagem, ';base64,', imagem) as imagem_base64 
    FROM produtos ORDER BY id DESC");


date_default_timezone_set('America/Sao_Paulo');


// HTML do PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        /* Cores do Tema Assistec */
        :root {
            --primary: #8a2be2;
            --secondary: #8a2be2;
            --background: #F5F7FA;
            --text: #2D3748;
            --success: #4CAF50;
            --error: #F44336;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: var(--text);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid var(--primary);
            padding-bottom: 20px;
        }

        .logo {
            width: 150px;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
        }

        th {
            background-color: var(--primary);
            color: white;
            padding: 12px;
            text-align: left;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .product-image {
            max-width: 50px;
            max-height: 50px;
        }

        .title {
            color: var(--primary);
            font-size: 24px;
            margin: 10px 0;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            color: var(--primary);
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="caminho/para/sua-logo-assistec.png" class="logo"> <!-- Altere o caminho -->
        <h1 class="title">Relatório de Produtos - Assistec</h1>
        <p>Gerado em: '.date('d/m/Y H:i:s').'</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Imagem</th>
                <th>Nome</th>
                <th>Preço</th>
                <th>Categoria</th>
                <th>Estoque</th>
            </tr>
        </thead>
        <tbody>';

while ($produto = $produtos->fetch_assoc()) {
    $html .= '
            <tr>
                <td>'.(!empty($produto['imagem_base64']) ? 
                    '<img src="'.$produto['imagem_base64'].'" class="product-image">' : 
                    'Sem imagem').'</td>
                <td>'.$produto['nome'].'</td>
                <td>R$ '.number_format($produto['preco'], 2, ',', '.').'</td>
                <td>'.ucfirst($produto['categoria']).'</td>
                <td>'.$produto['estoque'].'</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>

    <div class="footer">
        Assistec Tecnologia e Serviços - '.date('Y').'
    </div>
</body>
</html>';

// Configurar Dompdf
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Saída do PDF (Download)
$dompdf->stream("relatorio-assistec-".date('Ymd-His').".pdf", [
    "Attachment" => true
]);