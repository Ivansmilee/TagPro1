
<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/tagpro_store.php';

require_login();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
try {
    $rows = tagpro_list_documents('qr_links');

    // Ordenar del más reciente al más antiguo.
    usort($rows, function ($a, $b) {
        return strtotime($b['created_at'] ?? '')
            <=> strtotime($a['created_at'] ?? '');
    });

    $total = count($rows);

    $scans = array_sum(array_map(
        fn($row) => (int) ($row['scans'] ?? 0),
        $rows
    ));

    $loadError = '';

} catch (Throwable $e) {
    error_log('Error cargando panel TagPro: ' . $e->getMessage());

    $rows = [];
    $total = 0;
    $scans = 0;
    $loadError = 'No se pudieron cargar los códigos QR. Intenta nuevamente.';
}

?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>TagPro QR · Panel</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<header class="topbar">
    <a class="brand" href="index.php">TAGPRO <span>QR</span></a>

    <div>
        Hola, <?= e($_SESSION['admin_name']) ?>
        ·
        <a href="logout.php">Cerrar sesión</a>
    </div>
</header>

<main class="container">

    <div class="heading">
        <div>
            <p class="eyebrow">TU CENTRO DE CONTROL</p>
            <h1>Códigos dinámicos</h1>
            <p class="muted">
                El QR impreso no cambia. Solo actualizas el destino desde aquí.
            </p>
        </div>

        <a class="btn primary" href="create.php">
            ＋ Crear código
        </a>
    </div>

    <?php if ($loadError): ?>
        <div class="error"><?= e($loadError) ?></div>
    <?php endif; ?>

    <section class="stats">
        <div class="stat">
            <span>Códigos creados</span>
            <strong><?= $total ?></strong>
        </div>

        <div class="stat">
            <span>Escaneos registrados</span>
            <strong><?= number_format($scans) ?></strong>
        </div>

        <div class="stat">
            <span>Tipo de enlace</span>
            <strong>Dinámico</strong>
        </div>
    </section>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Enlace corto (QR/NFC)</th>
                    <th>Destino actual</th>
                    <th>Escaneos</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>

                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="6" class="empty">
                            Aún no has creado códigos. Pulsa “Crear código”.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $r): ?>
                    <?php
                    $short = APP_URL . '/r/' . $r['code'];
                    $id = (int) $r['id'];
                    ?>

                    <tr>
                        <td>
                            <strong><?= e($r['title']) ?></strong>
                            <small>
                                Creado <?= e(date(
                                    'd/m/Y',
                                    strtotime($r['created_at'])
                                )) ?>
                            </small>
                        </td>

                        <td>
                            <a href="<?= e($short) ?>" target="_blank">
                                <?= e($short) ?>
                            </a>

                            <button class="copy" data-copy="<?= e($short) ?>">
                                Copiar
                            </button>
                        </td>

                        <td class="destination">
                            <?= e($r['destination_url']) ?>
                        </td>

                        <td><?= number_format((int) $r['scans']) ?></td>

                        <td>
                            <span class="pill <?= e($r['status']) ?>">
                                <?= $r['status'] === 'active' ? 'Activo' : 'Pausado' ?>
                            </span>
                        </td>

                        <td class="actions">
                            <a href="edit.php?id=<?= $id ?>">Editar</a>
                            <a href="design.php?id=<?= $id ?>">Diseñar QR</a>
                            <form
    method="post"
    action="delete.php"
    onsubmit="return confirm('¿Eliminar este código? El QR dejará de funcionar.')"
>
    <input type="hidden" name="id" value="<?= $id ?>">
    <input
        type="hidden"
        name="csrf_token"
        value="<?= e($_SESSION['csrf_token']) ?>"
    >
    <button
        type="submit"
        style="background:none;border:0;padding:0;color:#d95d12;cursor:pointer;font:inherit"
    >
        Eliminar
    </button>
</form>
                        </td>
                    </tr>

                <?php endforeach; ?>

            </tbody>
        </table>
    </div>

    <div class="tip">
        <strong>Importante:</strong>
        programa el NFC con el mismo enlace corto que aparece aquí.
        Así, al cambiar el destino en TagPro, cambiarán tanto el QR impreso como el NFC.
    </div>

</main>

<script src="assets/app.js"></script>

</body>
</html>
