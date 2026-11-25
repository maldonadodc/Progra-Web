<?php
require __DIR__ . '/sesion.php';
$rutinas = [
 'empuje' => ['Empuje', 'Press de pecho, press militar, elevaciones laterales, fondos y tríceps.', '4 ejercicios principales · 3 a 4 series · descanso de 90 segundos.'],
 'traccion' => ['Tracción', 'Jalón al pecho, remo sentado, remo con mancuerna, curl de bíceps y face pulls.', 'Empieza con técnica controlada · descanso de 90 segundos.'],
 'pierna' => ['Pierna Forge', 'Sentadilla, peso muerto rumano, prensa, zancadas y elevaciones de pantorrilla.', '4 series en básicos · descanso de 120 segundos.'],
 'cardio' => ['Cardio y Core', 'Intervalos en bicicleta, plancha, dead bug, pallof press y caminata inclinada.', 'Bloques de 8 minutos · descanso activo de 60 segundos.'],
 'fullbody' => ['Full Body', 'Sentadilla goblet, press con mancuernas, remo, bisagra de cadera y core.', '3 rondas · descanso de 90 segundos entre rondas.'],
 'libre' => ['Libre guiado', 'Práctica de técnica, movilidad específica y trabajo de puntos débiles.', 'Pide una revisión al entrenador antes de aumentar carga.']
];
$imagenes = [
 'empuje' => 'ruta-empuje.webp',
 'traccion' => 'ruta-traccion.webp',
 'pierna' => 'ruta-pierna.webp',
 'cardio' => 'ruta-cardio.jpg',
 'fullbody' => 'ruta-fullbody.webp',
 'libre' => 'ruta-libre.webp'
];
$seleccion = $_GET['rutina'] ?? $_POST['rutina'] ?? 'empuje';
if (!isset($rutinas[$seleccion])) $seleccion = 'empuje';
$rutina = $rutinas[$seleccion];
$imagen = $imagenes[$seleccion] ?? 'funcional.jpg';
if (!is_file(__DIR__ . '/../img/' . $imagen)) $imagen = 'funcional.jpg';
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>NovaRuta</title><link rel="stylesheet" href="../css/estilos.css"><style>img.imagen-ruta{aspect-ratio:auto;height:auto;max-height:520px;object-fit:contain;background:#0c0d10}</style></head>
<body><header><div class="logo marca"></div><nav><a href="paginaprincipal.php#ruta">Volver a NovaRuta</a></nav></header><main><section class="centro"><form method="get"><h2>Planifica tu NovaRuta</h2><label for="rutina">Rutina</label><select id="rutina" name="rutina" onchange="this.form.submit()"><?php foreach ($rutinas as $id => $datos): ?><option value="<?= escapar($id) ?>"<?= $id === $seleccion ? ' selected' : '' ?>><?= escapar($datos[0]) ?></option><?php endforeach; ?></select><h3><?= escapar($rutina[0]) ?></h3><img class="imagen-clase imagen-ruta" src="../img/<?= escapar($imagen) ?>" alt="Rutina <?= escapar($rutina[0]) ?>"><p><strong>Ejercicios:</strong> <?= escapar($rutina[1]) ?></p><p><strong>Estructura y descansos:</strong> <?= escapar($rutina[2]) ?></p></form></section></main><script src="../js/datos.js"></script></body></html>