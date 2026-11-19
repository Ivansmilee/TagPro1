<?php
require_once __DIR__ . '/includes/db.php'; require_login();
$rows = db()->query('SELECT * FROM qr_links ORDER BY created_at DESC')->fetchAll();
$total = count($rows); $scans = array_sum(array_column($rows, 'scans'));
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>TagPro QR · Panel</title><link rel="stylesheet" href="assets/style.css"></head><body>
<header class="topbar"><a class="brand" href="index.php">TAGPRO <span>QR</span></a><div>Hola, <?=e($_SESSION['admin_name'])?> · <a href="logout.php">Cerrar sesión</a></div></header>
<main class="container"><div class="heading"><div><p class="eyebrow">TU CENTRO DE CONTROL</p><h1>Códigos dinámicos</h1><p class="muted">El QR impreso no cambia. Solo actualizas el destino desde aquí.</p></div><a class="btn primary" href="create.php">＋ Crear código</a></div>
<section class="stats"><div class="stat"><span>Códigos creados</span><strong><?=$total?></strong></div><div class="stat"><span>Escaneos registrados</span><strong><?=$scans?></strong></div><div class="stat"><span>Tipo de enlace</span><strong>Dinámico</strong></div></section>
<div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Enlace corto (QR/NFC)</th><th>Destino actual</th><th>Escaneos</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
<?php if (!$rows): ?><tr><td colspan="6" class="empty">Aún no has creado códigos. Pulsa “Crear código”.</td></tr><?php endif; ?>
<?php foreach ($rows as $r): $short = APP_URL . '/r/' . $r['code']; ?>
<tr><td><strong><?=e($r['title'])?></strong><small>Creado <?=e(date('d/m/Y', strtotime($r['created_at'])))?></small></td>
<td><a href="<?=e($short)?>" target="_blank"><?=e($short)?></a><button class="copy" data-copy="<?=e($short)?>">Copiar</button></td>
<td class="destination"><?=e($r['destination_url'])?></td><td><?=number_format((int)$r['scans'])?></td><td><span class="pill <?=$r['status']?>"><?=$r['status']==='active'?'Activo':'Pausado'?></span></td>
<td class="actions"><a href="edit.php?id=<?=$r['id']?>">Editar</a><a href="design.php?id=<?=$r['id']?>">Diseñar QR</a><a href="delete.php?id=<?=$r['id']?>" onclick="return confirm('¿Eliminar este código? El QR dejará de funcionar.')">Eliminar</a></td></tr>
<?php endforeach; ?></tbody></table></div>
<div class="tip"><strong>Importante:</strong> programa el NFC con el mismo enlace corto que aparece aquí. Así, al cambiar el destino en TagPro, cambiarán tanto el QR impreso como el NFC.</div>
</main><script src="assets/app.js"></script></body></html>
