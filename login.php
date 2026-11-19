<?php
require_once __DIR__ . '/includes/db.php';
if (!empty($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = db()->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([trim($_POST['username'] ?? '')]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($_POST['password'] ?? '', $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['username'];
        header('Location: index.php'); exit;
    }
    $error = 'Usuario o contraseña incorrectos.';
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Acceso · TagPro QR</title><link rel="stylesheet" href="assets/style.css"></head>
<body class="auth-bg"><main class="auth-card"><div class="brand">TAGPRO <span>QR</span></div><h1>Panel de control</h1><p>Administra tus códigos QR dinámicos.</p>
<?php if ($error): ?><div class="error"><?=e($error)?></div><?php endif; ?>
<form method="post"><label>Usuario</label><input name="username" required autofocus><label>Contraseña</label><input name="password" type="password" required><button class="btn primary full">Entrar</button></form></main></body></html>
