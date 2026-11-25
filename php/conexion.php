<?php

$servidor = "localhost";
$usuario = "root";
$clave = "";
$baseDatos = "novaforge_gym";

$cnn = mysqli_connect($servidor, $usuario, $clave, $baseDatos);

if (!$cnn) {
    die("No se pudo conectar con novaforge_gym. Revisa conexion.php y que base_datos.sql haya sido importado.");
}

mysqli_set_charset($cnn, "utf8mb4");

?>
