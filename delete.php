
<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/tagpro_store.php';

require_login();

// La eliminación solamente se permite mediante POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Método no permitido.');
}

// Validar token CSRF.
$token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !is_string($token) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    http_response_code(403);
    exit('Solicitud no autorizada.');
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

try {
    $rows = tagpro_list_documents('qr_links');
    $documentId = null;

    foreach ($rows as $row) {
        if ((int) ($row['id'] ?? 0) === $id) {
            $documentId = $row['_document_id'];
            break;
        }
    }

    if ($documentId !== null) {
        tagpro_delete_document('qr_links', $documentId);
    }

    header('Location: index.php');
    exit;

} catch (Throwable $e) {
    error_log('Error eliminando QR de TagPro: ' . $e->getMessage());

    http_response_code(500);
    exit('No se pudo eliminar el código. Intenta nuevamente.');
}
