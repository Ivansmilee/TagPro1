
<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/tagpro_store.php';

require_login();

$id = (int) ($_GET['id'] ?? 0);
$r = null;
$error = '';

try {
    $rows = tagpro_list_documents('qr_links');

    foreach ($rows as $row) {
        if ((int) ($row['id'] ?? 0) === $id) {
            $r = $row;
            break;
        }
    }
} catch (Throwable $e) {
    error_log('Error buscando QR: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo consultar el código.');
}

if (!$r) {
    http_response_code(404);
    exit('Código no encontrado');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $url = trim($_POST['destination_url'] ?? '');
    $status = ($_POST['status'] ?? '') === 'paused'
        ? 'paused'
        : 'active';

    $scheme = strtolower(parse_url($url, PHP_URL_SCHEME) ?? '');

    if (
        $title === '' ||
        !filter_var($url, FILTER_VALIDATE_URL) ||
        !in_array($scheme, ['http', 'https'], true)
    ) {
        $error = 'Revisa el nombre y la URL.';
    } else {
        try {
            // Conservar los datos existentes y cambiar solo los editables.
            tagpro_save_document(
                'qr_links',
                $r['_document_id'],
                [
                    'id' => (int) $r['id'],
                    'code' => $r['code'],
                    'title' => $title,
                    'destination_url' => $url,
                    'status' => $status,
                    'scans' => (int) $r['scans'],
                    'created_at' => $r['created_at'],
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );

            header('Location: index.php');
            exit;

        } catch (Throwable $e) {
            error_log('Error editando QR: ' . $e->getMessage());
            $error = 'No se pudieron guardar los cambios. Intenta nuevamente.';
        }
    }
}
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Editar enlace · TagPro</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<header class="topbar">
    <a class="brand" href="index.php">TAGPRO <span>QR</span></a>
    <a href="index.php">← Volver</a>
</header>

<main class="narrow">

    <p class="eyebrow">EDITAR DESTINO</p>

    <h1><?= e($r['title']) ?></h1>

    <p class="muted">
        Puedes cambiar el enlace todas las veces que quieras.
        El QR y el NFC no necesitan reprogramarse si usan el enlace corto de TagPro.
    </p>

    <?php if ($error): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>

    <form class="panel" method="post">

        <label>Nombre</label>

        <input
            name="title"
            required
            value="<?= e($r['title']) ?>"
        >

        <label>Nuevo enlace de destino</label>

        <input
            name="destination_url"
            type="url"
            required
            value="<?= e($r['destination_url']) ?>"
        >

        <label>Estado</label>

        <select name="status">
            <option
                value="active"
                <?= $r['status'] === 'active' ? 'selected' : '' ?>
            >
                Activo
            </option>

            <option
                value="paused"
                <?= $r['status'] === 'paused' ? 'selected' : '' ?>
            >
                Pausado
            </option>
        </select>

        <button class="btn primary">Guardar cambios</button>

    </form>

</main>

</body>
</html>
