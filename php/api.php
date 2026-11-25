<?php
require __DIR__ . '/conexion.php';
require __DIR__ . '/funcionesddbb.php';
require __DIR__ . '/validaciones.php';
require __DIR__ . '/sesion.php';

header('Content-Type: application/json; charset=utf-8');

function responder($datos, $codigo = 200)
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

$accion = $_GET['accion'] ?? '';

if ($accion === 'planes' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    responder(listarPlanes($cnn));
}
if ($accion === 'suplementos' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    responder(listarSuplementos($cnn));
}
if ($accion === 'sedes' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    responder(listarSedesActivas($cnn));
}
if ($accion === 'horarios' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    responder(listarHorariosSede($cnn, $_GET['sede'] ?? 0));
}
if ($accion === 'clases' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    responder(listarClases($cnn));
}
if ($accion === 'validar_correo' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $correo = textoLimpio($_GET['correo'] ?? '');
    responder(['valido' => validarCorreo($correo)]);
}

if (!esAdministrador()) {
    responder(['error' => 'Inicia sesión como administrador para continuar.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        responder(['error' => 'La solicitud caducó. Recarga la página e inténtalo de nuevo.'], 403);
    }
}

if ($accion === 'socios' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Antes de listar, se desactivan las membresías vencidas y se crea su factura pendiente.
    desactivarSociosVencidos($cnn);
    responder(listarSocios($cnn, $_GET['sede'] ?? 0));
}

$entrada = json_decode(file_get_contents('php://input'), true);
if (!is_array($entrada)) {
    responder(['error' => 'La solicitud no tiene datos válidos.'], 400);
}

if ($accion === 'crear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos = ['nombre', 'dni', 'correo', 'telefono', 'razon', 'direccion', 'plan', 'sede_id'];
    foreach ($campos as $campo) {
        if (!isset($entrada[$campo]) || !is_string($entrada[$campo])) {
            responder(['error' => 'Completa todos los datos del socio.'], 400);
        }
        $entrada[$campo] = trim($entrada[$campo]);
    }

    $errores = validarDatosSocio($entrada);
    if ($errores || !obtenerPlanActivo($cnn, $entrada['plan']) || !(int)$entrada['sede_id']) {
        responder(['error' => $errores[0] ?? 'Selecciona un plan disponible.'], 400);
    }

    $datos = $entrada;
    $id = guardarSocio($cnn, $datos);
    if (!$id) {
        responder(['error' => 'No se pudo guardar el socio. Comprueba que la cédula o el correo no estén registrados.'], 400);
    }
    responder(buscarSocio($cnn, $id), 201);
}

if ($accion === 'plan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_var($entrada['id'] ?? null, FILTER_VALIDATE_INT);
    $plan = $entrada['plan'] ?? '';
    if (!$id || !is_string($plan) || !cambiarPlanSocio($cnn, $id, $plan)) {
        responder(['error' => 'No se pudo actualizar el plan.'], 400);
    }
    responder(['ok' => true]);
}

if ($accion === 'editar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_var($entrada['id'] ?? null, FILTER_VALIDATE_INT);
    $correo = textoLimpio($entrada['correo'] ?? '');
    $plan = textoLimpio($entrada['plan'] ?? '');
    if (!$id || !validarCorreo($correo) || !editarSocio($cnn, $id, $correo, $plan)) {
        responder(['error' => 'No se pudo actualizar. Revisa el correo y el plan seleccionado.'], 400);
    }
    responder(buscarSocio($cnn, $id));
}

if ($accion === 'renovar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_var($entrada['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || !renovarMensualidadSocio($cnn, $id)) {
        responder(['error' => 'No se pudo renovar. Puede que la mensualidad del próximo mes ya exista.'], 400);
    }
    responder(['ok' => true]);
}

if ($accion === 'eliminar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_var($entrada['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || !eliminarSocio($cnn, $id)) {
        responder(['error' => 'No se pudo eliminar el socio.'], 400);
    }
    responder(['ok' => true]);
}

if ($accion === 'pagar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_var($entrada['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || !registrarPagoSocio($cnn, $id)) {
        responder(['error' => 'No se pudo registrar el pago.'], 400);
    }
    responder(['ok' => true]);
}

responder(['error' => 'Operación no válida.'], 404);
?>