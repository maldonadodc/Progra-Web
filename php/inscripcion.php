<?php
require __DIR__ . '/sesion.php';
require __DIR__ . '/conexion.php';
require __DIR__ . '/funcionesddbb.php';
require __DIR__ . '/validaciones.php';

function textoSeguro(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$archivoSalir = 'salir.php'; // archivo que cierra la sesión (cámbialo si se llama distinto)
$sesionActiva = esSocioAutenticado() || esAdministrador();

// Mensaje específico para cada dato que no puede repetirse
$mensajesRepetidos = [
    'dni'     => 'Esa cédula ya está registrada. Si ya tienes cuenta, inicia sesión.',
    'correo'  => 'Ese correo electrónico ya está registrado. Usa otro o inicia sesión.',
    'usuario' => 'Ese nombre de usuario ya está en uso. Elige otro.',
];

$planId = $_GET['plan'] ?? $_POST['plan'] ?? '';
$plan = is_string($planId) ? obtenerPlanActivo($cnn, $planId) : null;
$sedes = listarSedesActivas($cnn);
$errores = [];
$datos = [
    'nombre' => '', 'dni' => '', 'correo' => '', 'telefono' => '',
    'razon' => '', 'direccion' => '', 'sede_id' => '', 'usuario' => '',
];
$reservaCreada = null;

if ($sesionActiva) {
    // Con una sesión iniciada no se permite una nueva inscripción: se muestra el aviso.
} elseif (!$plan) {
    http_response_code(404);
    $errores[] = 'El plan seleccionado no está disponible.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();

    foreach ($datos as $campo => $_) {
        $valor = $_POST[$campo] ?? '';
        $datos[$campo] = textoLimpio($valor);
    }

    $errores = validarDatosSocio($datos);
    $clave = $_POST['clave'] ?? '';
    $confirmacion = $_POST['confirmar_clave'] ?? '';
    if (!validarUsuario($datos['usuario'])) {
        $errores[] = 'El usuario debe tener 4 a 40 caracteres: letras, números, punto, guion o guion bajo.';
    }
    if (!is_string($clave) || !validarClave($clave)) {
        $errores[] = 'La contraseña debe tener 8 caracteres o más e incluir mayúscula, minúscula, número y símbolo.';
    } elseif (!is_string($confirmacion) || !hash_equals($clave, $confirmacion)) {
        $errores[] = 'Las contraseñas no coinciden.';
    }
    $sedeValida = false;
    foreach ($sedes as $sede) {
        if ((string)$sede['id'] === $datos['sede_id']) { $sedeValida = true; break; }
    }
    if (!$sedeValida) { $errores[] = 'Selecciona una sede disponible.'; }

    // Datos que ya existen en el sistema: un mensaje específico por cada uno
    foreach (camposSocioRepetidos($cnn, $datos['dni'], $datos['correo'], $datos['usuario']) as $campo) {
        if (isset($mensajesRepetidos[$campo])) { $errores[] = $mensajesRepetidos[$campo]; }
    }

    if (!$errores) {
        $datos['plan'] = $plan['id'];
        $datos['clave_hash'] = password_hash($clave, PASSWORD_DEFAULT);
        $socioId = guardarSocio($cnn, $datos);
        if ($socioId) {
            $reservaCreada = buscarSocio($cnn, $socioId);
        } else {
            $errores[] = 'No fue posible registrar la reserva. Puede que la cédula, el correo o el usuario ya estén registrados; si el problema continúa, contacta al administrador.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reserva de inscripción | GymManager</title>
  <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
  <header>
    <div class="logo marca"></div>
    <nav><a href="paginaprincipal.php#planes">Volver a planes</a></nav>
  </header>
  <main>
    <section class="centro">
      <?php if ($sesionActiva): ?>
        <div class="caja">
          <h2>Ya hay una sesión iniciada</h2>
          <p>Para inscribirte de nuevo debes cerrar la sesión primero.</p>
          <br>
          <form class="form-salir" method="post" action="<?= textoSeguro($archivoSalir) ?>"><input type="hidden" name="csrf" value="<?= textoSeguro($_SESSION['csrf']) ?>"><button class="btn" type="submit">Cerrar sesión</button></form>
          <a class="btn sec" href="paginaprincipal.php">Volver al inicio</a>
        </div>
      <?php elseif ($reservaCreada): ?>
        <div class="caja">
          <h2>Reserva registrada</h2>
          <p>Tu inscripción al plan <strong><?= textoSeguro($plan['nombre']) ?></strong> fue guardada correctamente.</p>
          <p class="precio">Total mensual: $<?= number_format($plan['precio'], 2) ?></p>
          <p>La factura quedó pendiente de pago. Tu membresía permanecerá inactiva hasta que el administrador confirme el cobro.</p>
          <br><a class="btn" href="paginaprincipal.php">Volver al inicio</a>
        </div>
      <?php else: ?>
        <form method="post" action="inscripcion.php">
          <h2>Reserva tu inscripción</h2>
          <p>Completa tus datos para reservar el plan seleccionado.</p>
          <?php foreach ($errores as $error): ?>
            <p class="err" role="alert"><?= textoSeguro($error) ?></p>
          <?php endforeach; ?>
          <?php if ($plan): ?>
            <input type="hidden" name="csrf" value="<?= textoSeguro($_SESSION['csrf']) ?>">
            <input type="hidden" name="plan" value="<?= textoSeguro($plan['id']) ?>">
            <div class="resumen-plan">
              <strong><?= textoSeguro($plan['nombre']) ?></strong>
              <span>$<?= number_format($plan['precio'], 2) ?> / mes</span>
            </div>
            <label for="nombre">Nombre completo</label>
            <input id="nombre" name="nombre" type="text" autocomplete="name" maxlength="100" required title="Incluye al menos un nombre y un apellido, con iniciales en mayúscula." placeholder="Ana María Pérez" value="<?= textoSeguro($datos['nombre']) ?>">
            <label for="dni">Cédula ecuatoriana</label>
            <input id="dni" name="dni" type="text" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" required value="<?= textoSeguro($datos['dni']) ?>">
            <label for="correo">Correo electrónico</label>
            <input id="correo" name="correo" type="email" autocomplete="email" maxlength="120" required value="<?= textoSeguro($datos['correo']) ?>">
            <label for="telefono">Teléfono (opcional)</label>
            <input id="telefono" name="telefono" type="tel" autocomplete="tel" maxlength="20" pattern="(?:\\+593[ -]?|0)\\d(?:[ -]?\\d){7,8}" placeholder="0984581008" value="<?= textoSeguro($datos['telefono']) ?>">
            <label for="razon">Nombre o razón social</label>
            <input id="razon" name="razon" type="text" maxlength="100" required value="<?= textoSeguro($datos['razon']) ?>">
            <label for="direccion">Dirección de facturación</label>
            <input id="direccion" name="direccion" type="text" autocomplete="street-address" maxlength="150" required title="Incluye calle o avenida y número; la referencia es opcional." placeholder="Av. Principal 123, sector Centro" value="<?= textoSeguro($datos['direccion']) ?>">
            <label for="sede_id">Sede principal</label>
            <select id="sede_id" name="sede_id" required><option value="">Elige una sede</option><?php foreach ($sedes as $sede): ?><option value="<?= $sede['id'] ?>"<?= (string)$sede['id'] === $datos['sede_id'] ? ' selected' : '' ?>><?= textoSeguro($sede['nombre']) ?></option><?php endforeach; ?></select>
            <h3>Credenciales de acceso</h3>
            <label for="usuario">Usuario</label>
            <input id="usuario" name="usuario" type="text" autocomplete="username" maxlength="40" required value="<?= textoSeguro($datos['usuario']) ?>">
            <label for="clave">Contraseña</label>
            <input id="clave" name="clave" type="password" autocomplete="new-password" maxlength="72" required>
            <label for="confirmar_clave">Confirmar contraseña</label>
            <input id="confirmar_clave" name="confirmar_clave" type="password" autocomplete="new-password" maxlength="72" required>
            <button class="btn" type="submit">Confirmar reserva</button>
          <?php else: ?>
            <br><a class="btn" href="paginaprincipal.php#planes">Elegir un plan</a>
          <?php endif; ?>
        </form>
      <?php endif; ?>
    </section>
  </main>
  <footer>&copy; 2026 <span class="marca"></span> · Sistema GymManager Lite</footer>
  <script src="../js/datos.js"></script>
</body>
</html>