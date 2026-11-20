
<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/tagpro_store.php';

require_login();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $url = trim($_POST['destination_url'] ?? '');

    $scheme = strtolower(parse_url($url, PHP_URL_SCHEME) ?? '');

    if (
        $title === '' ||
        !filter_var($url, FILTER_VALIDATE_URL) ||
        !in_array($scheme, ['http', 'https'], true)
    ) {
        $error = 'Escribe un nombre y una URL válida que comience con http:// o https://.';
    } else {
        try {
            // Obtener el siguiente identificador numérico.
            $existingLinks = tagpro_list_documents('qr_links');

            $ids = array_map(
                fn($row) => (int) ($row['id'] ?? 0),
                $existingLinks
            );

            $id = empty($ids) ? 1 : max($ids) + 1;

            // Generar código único de 10 caracteres.
            do {
                $code = strtolower(bin2hex(random_bytes(5)));
            } while (tagpro_get_document('qr_links', $code) !== null);

            $now = date('Y-m-d H:i:s');

            // Guardar directamente en Firestore.
            tagpro_save_document('qr_links', $code, [
                'id' => $id,
                'code' => $code,
                'title' => $title,
                'destination_url' => $url,
                'status' => 'active',
                'scans' => 0,
                'created_at' => $now,
                'updated_at' => $now
            ]);

            header('Location: index.php?created=1');
            exit;

        } catch (Throwable $e) {
            error_log('Error creando QR en TagPro: ' . $e->getMessage());
            $error = 'No se pudo crear el código. Intenta nuevamente.';
        }
    }
}
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Crear código · TagPro</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<header class="topbar">
    <a class="brand" href="index.php">TAGPRO <span>QR</span></a>
    <a href="index.php">← Volver al panel</a>
</header>

<main class="narrow">

    <p class="eyebrow">NUEVO QR DINÁMICO</p>

    <h1>Crear código</h1>

    <p class="muted">
        Asigna un nombre y el primer destino. Después podrás cambiarlo sin reimprimir.
    </p>

    <?php if ($error): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>

    <form class="panel" method="post">

        <label>Nombre interno</label>

        <input
            name="title"
            required
            placeholder="Ej. Tarjeta demo - Cafetería Luna"
        >

        <label>Enlace de destino</label>

        <input
            name="destination_url"
            type="url"
            required
            placeholder="https://g.page/r/.../review"
        >

        <button class="btn primary">Crear código</button>

    </form>

</main>

</body>
</html>
