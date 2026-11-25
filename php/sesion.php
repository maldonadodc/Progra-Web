<?php
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();
header('Cache-Control: no-store');

function redirigir(string $pagina): void
{
    header('Location: ' . $pagina, true, 303);
    exit;
}

function escapar(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function verificarCsrf(): void
{
    $token = $_POST['csrf'] ?? null;
    if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(403);
        exit('La solicitud caducó. Vuelve a cargar la página.');
    }
}

function esAdministrador(): bool
{
    return ($_SESSION['rol'] ?? '') === 'administrador';
}

function esSocioAutenticado(): bool
{
    return ($_SESSION['rol'] ?? '') === 'socio' && isset($_SESSION['socio_id']);
}

function exigirAdministrador(): void
{
    if (!esAdministrador()) {
        redirigir('login.php');
    }
}

function exigirSocio(): void
{
    if (!esSocioAutenticado()) {
        redirigir('acceso.php');
    }
}

$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
