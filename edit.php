<?php
require_once __DIR__ . '/includes/db.php'; require_login();
$id=(int)($_GET['id']??0); $q=db()->prepare('SELECT * FROM qr_links WHERE id=?');$q->execute([$id]);$r=$q->fetch();if(!$r){http_response_code(404);exit('Código no encontrado');}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $title=trim($_POST['title']??'');$url=trim($_POST['destination_url']??'');$status=$_POST['status']==='paused'?'paused':'active';
 if($title===''||!filter_var($url,FILTER_VALIDATE_URL)||!in_array(parse_url($url,PHP_URL_SCHEME),['http','https'],true))$error='Revisa el nombre y la URL.';
 else{$s=db()->prepare('UPDATE qr_links SET title=?,destination_url=?,status=? WHERE id=?');$s->execute([$title,$url,$status,$id]);header('Location: index.php');exit;}
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Editar enlace · TagPro</title><link rel="stylesheet" href="assets/style.css"></head><body><header class="topbar"><a class="brand" href="index.php">TAGPRO <span>QR</span></a><a href="index.php">← Volver</a></header><main class="narrow"><p class="eyebrow">EDITAR DESTINO</p><h1><?=e($r['title'])?></h1><p class="muted">Puedes cambiar el enlace todas las veces que quieras. El QR y el NFC no necesitan reprogramarse si usan el enlace corto de TagPro.</p>
<?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?>
<form class="panel" method="post"><label>Nombre</label><input name="title" required value="<?=e($r['title'])?>"><label>Nuevo enlace de destino</label><input name="destination_url" type="url" required value="<?=e($r['destination_url'])?>"><label>Estado</label><select name="status"><option value="active" <?=$r['status']==='active'?'selected':''?>>Activo</option><option value="paused" <?=$r['status']==='paused'?'selected':''?>>Pausado</option></select><button class="btn primary">Guardar cambios</button></form></main></body></html>
