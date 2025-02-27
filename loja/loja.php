<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost/dashboard1/loja/loja.html");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

include_once('config.php');

// Verificar autenticação
if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado']);
    exit();
}


// Verificar e criar tabela se necessário

// Função para atualizar o estoque
function updateStock($conn, $productId, $quantity, $operation = 'subtract') {
    if (!in_array($operation, ['subtract', 'add'])) {
        return false;
    }

    // Obter informações do produto
    $query = "SELECT estoque, preco, categoria FROM produtos WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'error' => 'Produto não encontrado'];
    }
    
    $produto = $result->fetch_assoc();
    $currentStock = $produto['estoque'];
    $preco = $produto['preco'];
    $categoria = $produto['categoria'];
    $stmt->close();

    // Calcular novo estoque
    $newStock = ($operation === 'subtract') 
        ? $currentStock - $quantity 
        : $currentStock + $quantity;

    if ($newStock < 0) {
        return ['success' => false, 'error' => 'Estoque insuficiente'];
    }

    // Atualizar estoque
    $stmt = $conn->prepare("UPDATE produtos SET estoque = ? WHERE id = ?");
    $stmt->bind_param("ii", $newStock, $productId);
    $atualizou = $stmt->execute();
    $stmt->close();
    
    if (!$atualizou) {
        return ['success' => false, 'error' => 'Erro ao atualizar estoque: ' . $conn->error];
    }
    
    // Se for uma venda, registrar na tabela vendas
    if ($operation === 'subtract') {
        $valor_total = $preco * $quantity;
        $data_atual = date('Y-m-d H:i:s');
        
        // Criar log para debug (opcional)
        error_log("Tentando registrar venda - Produto: $productId, Categoria: $categoria, Quantidade: $quantity, Valor: $valor_total");
        
        $stmt = $conn->prepare("INSERT INTO vendas (idproduto, idcategoria, quantidade, valor_total, data_venda) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            error_log("Erro ao preparar query: " . $conn->error);
            return ['success' => true, 'venda_registrada' => false, 'error' => 'Erro ao preparar query'];
        }
        
        $stmt->bind_param("isids", $productId, $categoria, $quantity, $valor_total, $data_atual);
        $registrou = $stmt->execute();
        
        if (!$registrou) {
            error_log("Erro ao registrar venda: " . $stmt->error);
            return ['success' => true, 'venda_registrada' => false, 'error' => $stmt->error];
        }
        
        $stmt->close();
        return ['success' => true, 'venda_registrada' => true];
    }
    
    return ['success' => true, 'venda_registrada' => false];
}

// Endpoint para obter produtos
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT 
                id,
                nome AS name,
                categoria AS category,
                CAST(preco AS DECIMAL(10,2)) AS price,
                estoque AS stock,
                CONCAT('data:', tipo_imagem, ';base64,', imagem) AS image,
                descricao AS description
            FROM produtos 
            WHERE estoque > 0";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro no servidor: ' . $conn->error]);
        exit();
    }

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'category' => $row['category'],
            'price' => $row['price'],
            'stock' => $row['stock'],
            'image' => $row['image'],
            'description' => $row['description']
        ];
    }

    echo json_encode($products);
    exit();
}

// Endpoint para atualizar estoque e registrar vendas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['productId']) || !isset($data['quantity']) || !isset($data['operation'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Parâmetros inválidos']);
        exit();
    }

    $productId = filter_var($data['productId'], FILTER_VALIDATE_INT);
    $quantity = filter_var($data['quantity'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
    
    if ($productId === false || $quantity === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Valores inválidos']);
        exit();
    }

    $resultado = updateStock(
        $conn,
        $productId,
        $quantity,
        $data['operation']
    );

    echo json_encode($resultado);
    exit();
}

$conn->close();
?>