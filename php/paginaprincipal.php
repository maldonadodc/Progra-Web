<?php
require __DIR__ . '/sesion.php';
header('Cache-Control: no-store, max-age=0');

$archivoSalir = 'salir.php'; // archivo que cierra la sesión (cámbialo si se llama distinto)
$sesionSocio = esSocioAutenticado();
$cuenta = null;
$clasesSocio = [];
$reservasSocio = [];

if ($sesionSocio) {
    require __DIR__ . '/conexion.php';
    require __DIR__ . '/funcionesddbb.php';
    $socioId = (int)$_SESSION['socio_id'];
    // Si la membresía ya venció, se desactiva antes de mostrar "Mi cuenta".
    desactivarSociosVencidos($cnn);
    $cuenta = obtenerMembresiaSocio($cnn, $socioId);
    if ($cuenta) {
        $clasesSocio = listarClasesDeSocio($cnn, $socioId);
        $reservasSocio = listarReservasDeSocio($cnn, $socioId);
    }
}
$estadosReserva = ['reservado' => 'Reservado', 'retirado' => 'Retirado', 'cancelado' => 'Cancelado'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gimnasio | Inicio</title>
  <link rel="stylesheet" href="../css/estilos.css?v=20261124-3">
</head>
<body>
  <header>
    <div class="logo marca"></div>
    <nav>
      <?php if ($cuenta): ?><a href="#mi-cuenta">Mi cuenta</a><?php endif; ?>
      <a href="#planes">Planes</a>
      <a href="#sedes">Sedes</a>
      <a href="#horarios">Horarios</a>
      <a href="#clases">Clases</a>
      <a href="#ruta">NovaRuta</a>
      <a href="#nutricion">Nutrición</a>
      <a href="#suplementos">Suplementos</a>
      <a href="#galeria">Galería</a>
      <a href="#contacto">Contacto</a>
      <?php if ($sesionSocio): ?>
        <form class="form-salir" method="post" action="<?= escapar($archivoSalir) ?>"><input type="hidden" name="csrf" value="<?= escapar($_SESSION['csrf']) ?>"><button type="submit">Cerrar sesión</button></form>
      <?php else: ?>
        <a href="acceso.php">Iniciar sesión</a>
      <?php endif; ?>
      <a href="login.php">Administración</a>
    </nav>
  </header>

  <main>
    <section class="hero">
      <h1>Tu cuerpo, tu <span style="color:var(--c)">método</span>.</h1>
      <p id="lema"></p>
      <div><a class="btn" href="#planes">Inscríbete hoy</a> <a class="btn sec" href="#sedes">Ver sedes</a></div>
    </section>

    <?php if ($cuenta): ?>
    <?php
      $dias = (int)$cuenta['dias_restantes'];
      $vencida = $cuenta['estado'] !== 'activo' && $cuenta['ha_pagado'];
    ?>
    <section id="mi-cuenta">
      <h2>Mi cuenta</h2>
      <p>Hola, <?= escapar((string)$cuenta['nombre']) ?>.</p>
      <br>
      <?php if ($vencida): ?>
        <div class="caja">
          <p class="err"><strong>Tu membresía venció el <?= escapar((string)$cuenta['vence_texto']) ?>.</strong></p>
          <p>Para volver a inscribirte a clases y reservar suplementos, renueva tu membresía en cualquiera de nuestras sedes.</p>
          <br><a class="btn peq" href="#sedes">Ver sedes</a>
        </div>
        <br>
      <?php endif; ?>
      <div class="grid">
        <div class="card">
          <h3>Mi membresía</h3>
          <p><strong>Plan:</strong> <?= escapar((string)$cuenta['plan']) ?></p>
          <?php if ($vencida): ?>
            <p><strong>Venció el:</strong> <?= escapar((string)$cuenta['vence_texto']) ?></p>
            <p class="err">Membresía inactiva. Renueva en tu sede.</p>
          <?php elseif ($cuenta['estado'] !== 'activo'): ?>
            <p class="err">Membresía inactiva: pendiente de confirmación de pago.</p>
          <?php else: ?>
            <p><strong>Vence el:</strong> <?= escapar((string)$cuenta['vence_texto']) ?></p>
            <?php if ($dias > 5): ?>
              <p class="ok">Te quedan <?= $dias ?> días.</p>
            <?php elseif ($dias > 1): ?>
              <p class="err">Te quedan <?= $dias ?> días. Renueva pronto.</p>
            <?php elseif ($dias === 1): ?>
              <p class="err">Te queda 1 día. Renueva pronto.</p>
            <?php elseif ($dias === 0): ?>
              <p class="err">Tu membresía vence hoy.</p>
            <?php else: ?>
              <p class="err">Venció hace <?= abs($dias) ?> <?= abs($dias) === 1 ? 'día' : 'días' ?>. Renueva en tu sede.</p>
            <?php endif; ?>
          <?php endif; ?>
        </div>

        <div class="card">
          <h3>Mis clases</h3>
          <?php if ($clasesSocio): ?>
            <ul>
              <?php foreach ($clasesSocio as $clase): ?>
                <li><strong><?= escapar((string)$clase['nombre']) ?></strong> · <?= escapar((string)$clase['dias']) ?> · <?= escapar((string)$clase['hora']) ?> · <?= escapar((string)$clase['sede']) ?><br><small>Inscrita el <?= escapar((string)$clase['inscrita']) ?></small></li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p>Aún no te has inscrito a ninguna clase.</p>
            <a class="btn peq" href="#clases">Ver clases</a>
          <?php endif; ?>
        </div>

        <div class="card">
          <h3>Mis suplementos</h3>
          <?php if ($reservasSocio): ?>
            <ul>
              <?php foreach ($reservasSocio as $reserva): ?>
                <li><strong><?= escapar((string)$reserva['nombre']) ?></strong> · $<?= number_format($reserva['precio'], 2) ?> · <?= escapar($estadosReserva[$reserva['estado']] ?? (string)$reserva['estado']) ?><br><small><?= $reserva['sede'] !== '' ? 'Sede ' . escapar((string)$reserva['sede']) . ' · ' : '' ?>Reservado el <?= escapar((string)$reserva['reservada']) ?></small></li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p>Aún no has reservado ningún suplemento.</p>
            <a class="btn peq" href="#suplementos">Ver suplementos</a>
          <?php endif; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <section id="planes">
      <h2>Planes y precios</h2>
      <img class="imagen-seccion" src="../img/planes.jpg" alt="Mancuernas en la sala de pesas" loading="lazy">
      <div class="grid" id="lista-planes"></div>
    </section>
    <section id="sedes">
      <h2>Nuestras sedes</h2>
      <div class="grid" id="lista-sedes"></div>
    </section>

    <section id="horarios">
      <h2>Horarios</h2>
      <label for="horario-sede">Escoge una sede</label><select id="horario-sede"></select>
      <img id="imagen-sede-horario" class="imagen-seccion" src="../img/sede-exterior.png" alt="Fachada de una sede de gimnasio" loading="lazy">
      
      <div class="tabla-scroll"><table>
        <thead><tr><th>Días</th><th>Apertura – Cierre</th></tr></thead>
        <tbody id="tabla-horarios"></tbody>
      </table></div>
    </section>

    <section id="clases">
      <h2>Clases grupales</h2>
      <div class="carrusel" id="carrusel-clases"></div>
    </section>

    <section id="ruta"><h2>NovaRuta: itinerario semanal del gimnasio</h2><p>Selecciona una ruta para ver ejercicios, descansos e imágenes.</p><div class="grid" id="lista-ruta"></div></section>
    <section id="nutricion"><h2>NovaPlato: planes nutricionales recomendados</h2><div class="grid" id="lista-nutricion"></div></section>

    <section id="suplementos"><h2>NovaSupp: suplementación recomendada</h2><div class="grid" id="lista-supl"></div></section>
    <section id="galeria"><h2>Galería</h2><div class="carrusel" id="carrusel-galeria"></div></section>

    <section id="contacto">
      <h2>Contacto</h2>
      <form id="form-contacto">
        <label for="c-nombre">Nombre</label><input id="c-nombre" type="text" autocomplete="name" maxlength="100" required>
        <label for="c-correo">Correo</label><input id="c-correo" type="email" autocomplete="email" maxlength="254" required>
        <label for="c-msg">Mensaje</label><textarea id="c-msg" rows="4" minlength="10" maxlength="1000" required></textarea>
        <button class="btn" type="submit">Enviar por WhatsApp</button>
        <p id="c-resultado" role="status"></p>
      </form>
    </section>
  </main>

  <footer>&copy; 2026 <span class="marca"></span> · Sistema GymManager Lite</footer>

  <script src="../js/datos.js?v=20261124-2"></script>
  <script src="../js/paginaprincipal.js?v=20261124-6"></script>
</body>
</html>