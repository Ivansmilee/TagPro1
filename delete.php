<?php
require_once __DIR__ . '/includes/db.php'; require_login();
$id=(int)($_GET['id']??0);$s=db()->prepare('DELETE FROM qr_links WHERE id=?');$s->execute([$id]);header('Location: index.php');exit;
