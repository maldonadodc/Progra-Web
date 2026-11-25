<?php
require __DIR__ . '/sesion.php';
require __DIR__ . '/conexion.php';
require __DIR__ . '/funcionesddbb.php';

if (esAdministrador()) {
    redirigir('panel.php');
}

$error = '';
$usuario = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $usuario = is_string($_POST['usuario'] ?? null) ? trim($_POST['usuario']) : '';
    $clave = $_POST['clave'] ?? null;

    if ($usuario === '' || strlen($usuario) > 40 || !is_string($clave) ||
        $clave === '' || strlen($clave) > 72) {
        $error = 'Ingresa un usuario y una clave válidos.';
    } else {
        $cuenta = consultarUsuario($cnn, $usuario);
        $claveCorrecta = $cuenta && password_verify($clave, $cuenta['clave_hash']);

        // Compatibilidad temporal con claves guardadas como texto plano.
        if ($cuenta && !$claveCorrecta && hash_equals($cuenta['clave_hash'], $clave)) {
            $claveCorrecta = true;
            actualizarClaveHash($cnn, $cuenta['id'], password_hash($clave, PASSWORD_DEFAULT));
        }

        if ($claveCorrecta) {
            session_regenerate_id(true);
            $_SESSION['usuario'] = $cuenta['usuario'];
            $_SESSION['rol'] = 'administrador';
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            redirigir('panel.php');
        }
        $error = 'Usuario o clave incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Administración | NovaForge Gym</title>
  <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
  <main class="centro">
    <form id="form-login" action="login.php" method="post">
      <h2 class="marca">NovaForge Gym</h2>
      <p>Administración</p><br>
      <input type="hidden" name="csrf" value="<?= escapar($_SESSION['csrf']) ?>">
      <label for="l-usuario">Usuario</label>
      <input id="l-usuario" name="usuario" type="text" value="<?= escapar(substr($usuario, 0, 40)) ?>"
             autocomplete="username" maxlength="40" required>
      <label for="l-clave">Clave</label>
      <input id="l-clave" name="clave" type="password" autocomplete="current-password" maxlength="72" required>
      <label><input id="l-recordar" type="checkbox" style="width:auto"> Recordar usuario</label><br><br>
      <button class="btn" type="submit">Ingresar</button>
      <a class="btn sec" href="acceso.php">Acceso de socio</a>
      <a class="btn sec" href="paginaprincipal.php">Volver</a>
      <p id="l-resultado" class="err" role="status"><?= escapar($error) ?></p>
    </form>
  </main>
  <script src="../js/datos.js?v=20261124-2"></script>
  <script src="../js/gestorlogin.js?v=20261124-2"></script>
</body>
</html>
