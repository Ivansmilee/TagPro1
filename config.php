
<?php

// Configuración de MySQL.
// Se conserva temporalmente por compatibilidad y respaldo local.
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'tagpro_qr');
define('DB_USER', 'root');
define('DB_PASS', '');

// URL de la aplicación.
// En Vercel se configurará mediante la variable APP_URL.
define(
    'APP_URL',
    rtrim(
        getenv('APP_URL') ?: 'http://localhost/tagpro_qr_dinamico',
        '/'
    )
);

define('APP_NAME', 'TagPro QR');

date_default_timezone_set('America/Mexico_City');

// Configuración segura de sesiones.
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {

    $isVercel =
        getenv('VERCEL') === '1' ||
        getenv('VERCEL_ENV') !== false;

    if ($isVercel) {
        require_once __DIR__ . '/includes/firestore_session_handler.php';

        session_set_save_handler(
            new FirestoreSessionHandler(),
            true
        );
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isVercel,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}
