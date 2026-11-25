<?php
require __DIR__ . '/sesion.php';
require __DIR__ . '/conexion.php';
require __DIR__ . '/funcionesddbb.php';
require __DIR__ . '/validaciones.php';

if (esSocioAutenticado()) {
    redirigir('mi_cuenta.php');
}

$error = '';
$usuario = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $usuario = textoLimpio($_POST['usuario'] ?? '');
    $clave = $_POST['clave'] ?? '';
    $cuenta = validarUsuario($usuario) && is_string($clave) ? consultarSocioUsuario($cnn, $usuario) : null;
    if ($cuenta && password_verify($clave, $cuenta['clave_hash'])) {
        session_regenerate_id(true);
        $_SESSION['usuario'] = $cuenta['usuario'];
        $_SESSION['socio_id'] = (int)$cuenta['socio_id'];
        $_SESSION['rol'] = 'socio';
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        redirigir('mi_cuenta.php');
    }
    $error = 'No se pudo iniciar sesión. La cuenta debe estar aprobada por administración.';
}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Acceso de socio</title><link rel="stylesheet" href="../css/estilos.css"></head>
<body><main class="centro"><form action="acceso.php" method="post"><h2 class="marca">NovaForge Gym</h2><p>Acceso de socio</p><br>
<input type="hidden" name="csrf" value="<?= escapar($_SESSION['csrf']) ?>">
<label for="usuario">Usuario</label><input id="usuario" name="usuario" autocomplete="username" maxlength="40" required value="<?= escapar($usuario) ?>">
<label for="clave">Contraseña</label><input id="clave" name="clave" type="password" autocomplete="current-password" maxlength="72" required>
<button class="btn" type="submit">Iniciar sesión</button> <a class="btn sec" href="login.php">Administración</a> <a class="btn sec" href="paginaprincipal.php">Volver</a>
<p class="err" role="status"><?= escapar($error) ?></p></form></main><script src="../js/datos.js"></script></body></html>
