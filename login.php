
<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/tagpro_store.php';

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $admins = tagpro_list_documents('admins');
        $admin = null;

        foreach ($admins as $item) {
            if (
                isset($item['username']) &&
                hash_equals((string) $item['username'], $username)
            ) {
                $admin = $item;
                break;
            }
        }

        if (
            $admin &&
            password_verify($password, $admin['password_hash'] ?? '')
        ) {
            session_regenerate_id(true);

            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['username'];

            header('Location: index.php');
            exit;
        }

        $error = 'Usuario o contraseña incorrectos.';

    } catch (Throwable $e) {
        error_log('Error de inicio de sesión TagPro: ' . $e->getMessage());
        $error = 'No se pudo conectar con el sistema. Intenta nuevamente.';
    }
}
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Acceso · TagPro QR</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body class="auth-bg">
    <main class="auth-card">
        <div class="brand">TAGPRO <span>QR</span></div>

        <h1>Panel de control</h1>
        <p>Administra tus códigos QR dinámicos.</p>

        <?php if ($error): ?>
            <div class="error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <label>Usuario</label>
            <input
                name="username"
                required
                autofocus
                autocomplete="username"
            >

            <label>Contraseña</label>
            <input
                name="password"
                type="password"
                required
                autocomplete="current-password"
            >

            <button class="btn primary full">Entrar</button>
        </form>
    </main>
</body>
</html>
