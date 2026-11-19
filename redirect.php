<?php
require_once __DIR__ . '/includes/db.php';
$code = preg_replace('/[^a-f0-9]/', '', $_GET['code'] ?? '');
$stmt = db()->prepare('SELECT destination_url,status FROM qr_links WHERE code=? LIMIT 1');
$stmt->execute([$code]); $row=$stmt->fetch();
if (!$row || $row['status'] !== 'active') {
    http_response_code(404);
    echo '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Enlace no disponible</title><body style="font-family:Arial;text-align:center;padding:50px"><h2>Este enlace no está disponible</h2><p>Contacta al establecimiento.</p></body></html>';
    exit;
}
db()->prepare('UPDATE qr_links SET scans=scans+1 WHERE code=?')->execute([$code]);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Location: ' . $row['destination_url'], true, 302);
exit;
