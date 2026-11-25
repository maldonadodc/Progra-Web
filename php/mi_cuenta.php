<?php
require __DIR__ . '/sesion.php';
exigirSocio();
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Mi cuenta | NovaForge Gym</title><link rel="stylesheet" href="../css/estilos.css"></head>
<body><header><div class="logo marca"></div><nav><a href="paginaprincipal.php">Inicio</a><form class="form-salir" action="salir.php" method="post"><input type="hidden" name="csrf" value="<?= escapar($_SESSION['csrf']) ?>"><button type="submit">Salir</button></form></nav></header>
<main><section><h2>Sesión de socio activa</h2><p>Desde el sitio puedes reservar suplementos e inscribirte a las clases grupales usando esta cuenta.</p><br><a class="btn" href="paginaprincipal.php#clases">Ver clases</a> <a class="btn sec" href="paginaprincipal.php#suplementos">Ver suplementos</a></section></main><script src="../js/datos.js"></script></body></html>
