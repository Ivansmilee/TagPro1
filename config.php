<?php
// Configuración de TagPro QR. Ajusta estos datos a tu instalación de XAMPP.
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'tagpro_qr');
define('DB_USER', 'root');
define('DB_PASS', '');

// URL pública donde estará instalado el proyecto, SIN diagonal final.
// En pruebas locales puedes usar http://localhost/tagpro_qr_dinamico
// Para vender tarjetas, cámbiala por tu dominio HTTPS real.
define('APP_URL', 'http://localhost/tagpro_qr_dinamico');

define('APP_NAME', 'TagPro QR');
date_default_timezone_set('America/Mexico_City');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
