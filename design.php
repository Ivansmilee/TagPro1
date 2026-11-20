
<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/tagpro_store.php';

require_login();

$id = (int) ($_GET['id'] ?? 0);
$r = null;

try {
    $rows = tagpro_list_documents('qr_links');

    foreach ($rows as $row) {
        if ((int) ($row['id'] ?? 0) === $id) {
            $r = $row;
            break;
        }
    }
} catch (Throwable $e) {
    error_log('Error consultando QR para diseño: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo consultar el código.');
}

if (!$r) {
    http_response_code(404);
    exit('Código no encontrado');
}

$url = APP_URL . '/r/' . $r['code'];

?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Diseñar QR · TagPro</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/qr-code-styling@1.6.0/lib/qr-code-styling.js"></script>
</head>

<body>

<header class="topbar">
    <a class="brand" href="index.php">TAGPRO <span>QR</span></a>
    <a href="index.php">← Volver al panel</a>
</header>

<main class="container">

    <p class="eyebrow">PERSONALIZACIÓN</p>
    <h1>Diseña tu código QR</h1>

    <p class="muted">
        Este QR contiene el enlace permanente de TagPro,
        no el enlace directo del negocio.
    </p>

    <div class="designer">

        <section class="panel controls">

            <h2><?= e($r['title']) ?></h2>

            <label>Color del QR</label>
            <input type="color" id="dots" value="#101828">

            <label>Color de fondo</label>
            <input type="color" id="background" value="#ffffff">

            <label>Estilo de puntos</label>
            <select id="dotType">
                <option value="rounded">Redondeado</option>
                <option value="square">Cuadrado</option>
                <option value="dots">Puntos</option>
                <option value="classy">Elegante</option>
                <option value="extra-rounded">Muy redondeado</option>
            </select>

            <label>Estilo de esquinas</label>
            <select id="cornerType">
                <option value="extra-rounded">Redondeadas</option>
                <option value="square">Cuadradas</option>
                <option value="dot">Puntos</option>
            </select>

            <label>Texto inferior (opcional)</label>
            <input id="caption" maxlength="45" value="ESCANÉAME">

            <button class="btn primary full" id="download">
                Descargar QR PNG
            </button>

            <button class="btn secondary full" id="refresh">
                Actualizar vista
            </button>

            <div class="notice">
                Prueba el QR con varios teléfonos antes de imprimir.
                Evita colores con poco contraste y logos grandes.
            </div>

        </section>

        <section class="preview">

            <div id="qr"></div>

            <strong id="captionPreview">ESCANÉAME</strong>

            <p>Enlace permanente</p>

            <code><?= e($url) ?></code>

            <button class="copy" data-copy="<?= e($url) ?>">
                Copiar enlace para NFC
            </button>

        </section>

    </div>

</main>

<script>

const target = <?= json_encode($url) ?>;
let qr;

function renderQR() {

    const opts = {
        width: 800,
        height: 800,
        type: 'canvas',
        data: target,
        margin: 24,

        qrOptions: {
            errorCorrectionLevel: 'H'
        },

        dotsOptions: {
            color: document.getElementById('dots').value,
            type: document.getElementById('dotType').value
        },

        backgroundOptions: {
            color: document.getElementById('background').value
        },

        cornersSquareOptions: {
            color: document.getElementById('dots').value,
            type: document.getElementById('cornerType').value
        },

        cornersDotOptions: {
            color: document.getElementById('dots').value,
            type: 'dot'
        }
    };

    document.getElementById('qr').innerHTML = '';

    qr = new QRCodeStyling(opts);

    qr.append(document.getElementById('qr'));
}

document.getElementById('refresh').addEventListener('click', renderQR);

document.getElementById('download').addEventListener('click', () => {

    if (qr) {
        qr.download({
            name: 'tagpro-' + <?= json_encode($r['code']) ?>,
            extension: 'png'
        });
    }

});

document.getElementById('caption').addEventListener('input', () => {
    document.getElementById('captionPreview').textContent =
        document.getElementById('caption').value;
});

renderQR();

</script>

<script src="assets/app.js"></script>

</body>
</html>
