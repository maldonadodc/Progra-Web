<?php
require __DIR__ . '/sesion.php';
exigirAdministrador();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel de administración</title>
  <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
  <header>
    <div class="logo"><span class="marca"></span> · Panel</div>
    <nav>
      <a href="paginaprincipal.php">Ver sitio</a>
      <form class="form-salir" action="salir.php" method="post">
        <input type="hidden" name="csrf" value="<?= escapar($_SESSION['csrf']) ?>">
        <button type="submit">Salir</button>
      </form>
    </nav>
  </header>

  <input id="csrf-token" type="hidden" value="<?= escapar($_SESSION['csrf']) ?>">

  <div class="tabs">
    <button class="btn" onclick="ver('socios')">Socios</button>
    <button class="btn" onclick="ver('registro')">Nuevo socio</button>
  </div>

  <main>
    <section id="vista-socios">
      <h2>Dashboard de socios</h2>
      <div class="grid" id="stats"></div><br>
      <input id="buscar" type="text" placeholder="Buscar por nombre o cédula...">
      <label for="filtro-sede">Gestionar socios de la sede</label><select id="filtro-sede"><option value="">Todas las sedes</option></select>
      <div class="tabla-scroll"><table>
        <thead><tr><th>Nombre</th><th>Cédula</th><th>Correo</th><th>Plan</th><th>Monto</th><th>Pago</th><th>Membresía</th><th>Renovación</th><th>Acciones</th></tr></thead>
        <tbody id="tabla-socios"></tbody>
      </table></div>
    </section>

    <section id="vista-editar" class="oculto">
      <h2>Editar socio</h2>
      <form id="form-editar" onsubmit="return false">
        <input id="e-id" type="hidden">
        <p id="e-nombre"></p>
        <label for="e-correo">Correo electrónico</label><input id="e-correo" type="email" maxlength="120" required>
        <label for="e-plan">Plan mensual</label><select id="e-plan" required></select>
        <button class="btn" type="button" onclick="guardarEdicion()">Guardar cambios</button>
        <button class="btn sec" type="button" onclick="ver('socios')">Cancelar</button>
        <p id="msg-editar" role="status"></p>
      </form>
    </section>

    <section id="vista-registro" class="oculto">
      <h2>Registro de nuevo socio</h2>
      <form id="form-registro" onsubmit="return false">
        <h3>1. Datos de usuario</h3>
        <label for="r-nombre">Nombre completo</label><input id="r-nombre" type="text" maxlength="100" placeholder="Ana María Pérez" title="Incluye al menos un nombre y un apellido, con iniciales en mayúscula." required>
        <label for="r-dni">Cédula ecuatoriana</label><input id="r-dni" type="text" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" required>
        <label for="r-correo">Correo</label><input id="r-correo" type="email" maxlength="120" required>
        <label for="r-tel">Teléfono (opcional)</label><input id="r-tel" type="tel" maxlength="20" placeholder="0984581008">
        <button class="btn" type="button" onclick="guardarUsuario()">Guardar datos de usuario</button>
        <p id="msg-usuario" role="status"></p><br>

        <h3>2. Datos de factura y plan mensual</h3>
        <label for="r-razon">Nombre / razón social</label><input id="r-razon" type="text" maxlength="100" placeholder="NovaForge S.A." required>
        <label for="r-dir">Dirección de facturación</label><input id="r-dir" type="text" maxlength="150" placeholder="Av. Principal 123, sector Centro" title="Incluye calle o avenida y número; la referencia es opcional." required>
        <label for="r-plan">Plan mensual</label><select id="r-plan" required></select>
        <label for="r-sede">Sede principal</label><select id="r-sede" required></select>
        <button class="btn" type="button" onclick="guardarPlan()">Guardar datos del plan</button>
        <p id="msg-plan" role="status"></p><br>

        <button class="btn" type="button" onclick="irFactura()">Ir a facturación</button>
        <p id="msg-final" role="status"></p>
      </form>
    </section>

    <section id="vista-factura" class="oculto">
      <h2>Facturación</h2>
      <div class="caja">
        <div id="resumen-factura"></div>
        <button class="btn" onclick="pagar()">Cobrar y activar</button>
        <button class="btn sec" onclick="ver('socios')">Volver a socios</button>
      </div>
    </section>
  </main>

  <script src="../js/datos.js?v=20261124-2"></script>
  <script src="../js/gestorpanel.js?v=20261124-2"></script>
</body>
</html>
