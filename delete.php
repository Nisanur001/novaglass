```php
<?php
require_once 'config.php';

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    http_response_code(403);
    exit('Silme yetkiniz yok.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Geçersiz istek.');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit('Geçersiz kod numarası.');
}

$stmt = $pdo->prepare("DELETE FROM snippets WHERE id = ?");
$stmt->execute([$id]);

header('Location: index.php');
exit;
