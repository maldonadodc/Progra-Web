<?php
require __DIR__ . '/sesion.php';
require __DIR__ . '/conexion.php';
require __DIR__ . '/funcionesddbb.php';
exigirSocio();

$claseId = (int)($_GET['clase'] ?? $_POST['clase'] ?? 0);
$clase = obtenerClase($cnn, $claseId);
$error = '';
$ok = false;
if (!$clase) { http_response_code(404); $error = 'La clase seleccionada no existe.'; }
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    if (inscribirSocioEnClase($cnn, $claseId, (int)$_SESSION['socio_id'])) $ok = true;
    else $error = 'No se pudo completar la inscripción. Debes tener una membresía activa y no estar ya inscrito.';
}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Inscripción a clase</title><link rel="stylesheet" href="../css/estilos.css"></head>
<body><header><div class="logo marca"></div><nav><a href="paginaprincipal.php#clases">Volver a clases</a></nav></header><main><section class="centro"><div class="caja">
<?php if ($ok): ?><h2>Inscripción confirmada</h2><p>Te inscribiste a <?= escapar($clase['nombre']) ?>.</p><br><a class="btn" href="paginaprincipal.php#clases">Volver a clases</a>
<?php else: ?><h2>Inscribirme a clase</h2><p class="err"><?= escapar($error) ?></p><?php if ($clase): ?><h3><?= escapar($clase['nombre']) ?></h3><p><?= escapar($clase['dias']) ?> · <?= escapar($clase['hora']) ?></p><p><?= escapar($clase['descripcion']) ?></p><form method="post"><input type="hidden" name="csrf" value="<?= escapar($_SESSION['csrf']) ?>"><input type="hidden" name="clase" value="<?= $claseId ?>"><button class="btn" type="submit">Confirmar inscripción</button></form><?php endif; ?><?php endif; ?>
</div></section></main><script src="../js/datos.js"></script></body></html>
