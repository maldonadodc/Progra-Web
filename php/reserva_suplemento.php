<?php
require __DIR__ . '/sesion.php';
require __DIR__ . '/conexion.php';
require __DIR__ . '/funcionesddbb.php';
require __DIR__ . '/validaciones.php';
exigirSocio();

function escaparReserva(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$suplementoId = $_GET['suplemento'] ?? $_POST['suplemento'] ?? null;
$suplemento = obtenerSuplementoActivo($cnn, $suplementoId);
$sedes = listarSedesActivas($cnn);
$datos = ['sede_id' => ''];
$errores = [];
$reservaCreada = false;

if (!$suplemento) {
    http_response_code(404);
    $errores[] = 'El suplemento seleccionado no está disponible.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $datos['sede_id'] = textoLimpio($_POST['sede_id'] ?? '');
    $sedeValida = false;
    foreach ($sedes as $sede) {
        if ((string)$sede['id'] === $datos['sede_id']) {
            $sedeValida = true;
            break;
        }
    }
    if (!$sedeValida) {
        $errores[] = 'Selecciona una sede disponible.';
    }

    if (!$errores) {
        if (guardarReservaSuplemento($cnn, $suplemento['id'], (int)$_SESSION['socio_id'], (int)$datos['sede_id'])) {
            $reservaCreada = true;
        } else {
            $errores[] = 'No se pudo guardar la reserva. Inténtalo nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reserva de suplemento | GymManager</title>
  <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
  <header><div class="logo marca"></div><nav><a href="paginaprincipal.php#suplementos">Volver a suplementos</a></nav></header>
  <main><section class="centro">
    <?php if ($reservaCreada): ?>
      <div class="caja">
        <h2>Suplemento reservado</h2>
        <p>Tu reserva de <strong><?= escaparReserva($suplemento['nombre']) ?></strong> quedó registrada a nombre del socio.</p>
        <br><a class="btn" href="paginaprincipal.php">Volver al inicio</a>
      </div>
    <?php else: ?>
      <form method="post" action="reserva_suplemento.php">
        <h2>Reserva de suplemento</h2>
        <?php foreach ($errores as $error): ?><p class="err" role="alert"><?= escaparReserva($error) ?></p><?php endforeach; ?>
        <?php if ($suplemento): ?>
          <input type="hidden" name="csrf" value="<?= escaparReserva($_SESSION['csrf']) ?>">
          <input type="hidden" name="suplemento" value="<?= $suplemento['id'] ?>">
          <div class="resumen-plan"><strong><?= escaparReserva($suplemento['nombre']) ?></strong><span>$<?= number_format($suplemento['precio'], 2) ?></span></div>
          <p>La reserva se asociará automáticamente a tu cuenta de socio activa.</p>
          <label for="sede_id">Sede para retiro</label>
          <select id="sede_id" name="sede_id" required>
            <option value="">Elige una sede</option>
            <?php foreach ($sedes as $sede): ?>
              <option value="<?= $sede['id'] ?>"<?= (string)$sede['id'] === $datos['sede_id'] ? ' selected' : '' ?>><?= escaparReserva($sede['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn" type="submit">Confirmar reserva</button>
        <?php else: ?>
          <br><a class="btn" href="paginaprincipal.php#suplementos">Elegir suplemento</a>
        <?php endif; ?>
      </form>
    <?php endif; ?>
  </section></main>
  <footer>&copy; 2026 <span class="marca"></span> · Sistema GymManager Lite</footer>
  <script src="../js/datos.js"></script>
</body>
</html>
