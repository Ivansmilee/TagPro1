<?php
require_once __DIR__ . '/includes/db.php'; require_login();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? ''); $url = trim($_POST['destination_url'] ?? '');
    if ($title === '' || !filter_var($url, FILTER_VALIDATE_URL) || !in_array(parse_url($url, PHP_URL_SCHEME), ['http','https'], true)) {
        $error = 'Escribe un nombre y una URL válida que comience con http:// o https://.';
    } else {
        $code = strtolower(bin2hex(random_bytes(5)));
        $stmt = db()->prepare('INSERT INTO qr_links (code,title,destination_url) VALUES (?,?,?)');
        $stmt->execute([$code,$title,$url]);
        header('Location: design.php?id=' . db()->lastInsertId() . '&new=1'); exit;
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Crear código · TagPro</title><link rel="stylesheet" href="assets/style.css"></head><body><header class="topbar"><a class="brand" href="index.php">TAGPRO <span>QR</span></a><a href="index.php">← Volver al panel</a></header><main class="narrow"><p class="eyebrow">NUEVO QR DINÁMICO</p><h1>Crear código</h1><p class="muted">Asigna un nombre y el primer destino. Después podrás cambiarlo sin reimprimir.</p>
<?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?>
<form class="panel" method="post"><label>Nombre interno</label><input name="title" required placeholder="Ej. Tarjeta demo - Cafetería Luna"><label>Enlace de destino</label><input name="destination_url" type="url" required placeholder="https://g.page/r/.../review"><button class="btn primary">Crear y diseñar QR</button></form></main></body></html>
