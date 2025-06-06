<?php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: http://localhost:1006");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Max-Age: 86400");
    http_response_code(204);
    exit;
}
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
require_once(__DIR__ . '/../../middlewares/JWT.php');
require_once(__DIR__ . '/../../config/Database.php');
$data = json_decode(file_get_contents("php://input"), true);
$token      = $data['token'] ?? '';
$product_id = $data['product_id'] ?? '';
$name       = $data['name'] ?? '';
$description= $data['description'] ?? '';
$price      = $data['price'] ?? '';
$quantity   = $data['quantity'] ?? '';
$image_url  = $data['image_url'] ?? '';
if (!$token || !$product_id || !$name || !$description || !$price || !$quantity) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}
$jwt = new JWT();
$decoded = $jwt->decodeJWT($token);
if (isset($decoded['error'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid token"]);
    exit;
}
if ($decoded['role'] !== 'farmer') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Only farmers can update products"]);
    exit;
}
$email = $decoded['email'];
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$farmer = $result->fetch_assoc();
$farmer_id = $farmer['id'];
$stmt->close();
$stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND farmer_id = ?");
$stmt->bind_param("ii", $product_id, $farmer_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Unauthorized: You don't own this product"]);
    exit;
}
$stmt->close();
$stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, quantity = ?, image_url = ? WHERE id = ?");
$stmt->bind_param("ssdisi", $name, $description, $price, $quantity, $image_url, $product_id);
if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Product updated successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update product"]);
}
$stmt->close();
$conn->close();
?>
