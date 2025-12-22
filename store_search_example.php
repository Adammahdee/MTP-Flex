<?php
// Example logic for the Store-front (User view)
require_once 'admin/init.php';

$tag_filter = $_GET['tag'] ?? null;
$search_query = $_GET['q'] ?? null;

$sql = "
    SELECT DISTINCT p.* 
    FROM products p
    LEFT JOIN product_tags pt ON p.id = pt.product_id
    LEFT JOIN tags t ON pt.tag_id = t.id
    WHERE p.status = 'Available'
";

$params = [];

if ($tag_filter) {
    $sql .= " AND t.name = ?";
    $params[] = $tag_filter;
}

if ($search_query) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

$sql .= " ORDER BY p.created_at DESC LIMIT 20";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output (JSON for example)
header('Content-Type: application/json');
echo json_encode([
    'filter_tag' => $tag_filter,
    'count' => count($products),
    'results' => $products
]);
?>
