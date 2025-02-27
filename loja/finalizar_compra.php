<?php
session_start();
include_once('config.php');

if (!isset($_SESSION['email'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Não autenticado']));
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['items']) || !is_array($data['items'])) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Dados inválidos']));
}

try {
    $conn->begin_transaction();

    foreach ($data['items'] as $item) {
        $productId = intval($item['id']);
        $quantity = intval($item['quantity']);
        
        $stmt = $conn->prepare("UPDATE produtos 
                               SET estoque = estoque - ? 
                               WHERE id = ? AND estoque >= ?");
        $stmt->bind_param("iii", $quantity, $productId, $quantity);
        
        if (!$stmt->execute() || $stmt->affected_rows === 0) {
            throw new Exception("Estoque insuficiente para o produto ID $productId");
        }
        
        $stmt->close();
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>