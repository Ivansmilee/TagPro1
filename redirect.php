
<?php

require_once __DIR__ . '/includes/tagpro_store.php';

/**
 * Mostrar página cuando el enlace no está disponible.
 */
function tagpro_show_unavailable(int $status = 404): void
{
    http_response_code($status);
    header('Content-Type: text/html; charset=UTF-8');

    echo '<!doctype html>
    <html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Enlace no disponible</title>
    </head>
    <body style="font-family:Arial;text-align:center;padding:50px">
        <h2>Este enlace no está disponible</h2>
        <p>Contacta al establecimiento.</p>
    </body>
    </html>';

    exit;
}

// Obtener y validar el código QR.
$code = strtolower(trim($_GET['code'] ?? ''));

if (!preg_match('/^[a-f0-9]{10}$/', $code)) {
    tagpro_show_unavailable();
}

try {
    // Buscar el código directamente en Firestore.
    $row = tagpro_get_document('qr_links', $code);

} catch (Throwable $e) {
    error_log('Error consultando QR en Firestore: ' . $e->getMessage());

    tagpro_show_unavailable(503);
}

// Comprobar que existe y está activo.
if (!$row || ($row['status'] ?? '') !== 'active') {
    tagpro_show_unavailable();
}

// Validar el destino.
$destination = trim((string) ($row['destination_url'] ?? ''));

$scheme = strtolower(
    parse_url($destination, PHP_URL_SCHEME) ?? ''
);

if (
    !filter_var($destination, FILTER_VALIDATE_URL) ||
    !in_array($scheme, ['http', 'https'], true)
) {
    error_log('Destino inválido para el QR: ' . $code);

    tagpro_show_unavailable(500);
}

// Incrementar escaneos de forma atómica en Firestore.
// Si falla el contador, no se interrumpe la redirección.
try {
    tagpro_increment_scans($code);

} catch (Throwable $e) {
    error_log('Error incrementando escaneos del QR: ' . $e->getMessage());
}

// Evitar que el navegador almacene la redirección.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Enviar al visitante al destino configurado.
header('Location: ' . $destination, true, 302);
exit;
