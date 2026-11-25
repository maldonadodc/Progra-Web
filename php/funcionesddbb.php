<?php

function listarPlanes($cnn)
{
    $lista = [];
    $sql = "SELECT id, nombre, precio, descripcion FROM planes WHERE activo = 1 ORDER BY id";
    $resultado = mysqli_query($cnn, $sql);

    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $fila['precio'] = (float)$fila['precio'];
            $lista[] = $fila;
        }
    }

    return $lista;
}

function listarSuplementos($cnn)
{
    $lista = [];
    $resultado = mysqli_query($cnn, "SELECT id, nombre, uso, precio FROM suplementos WHERE activo = 1 ORDER BY id");
    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $fila['id'] = (int)$fila['id'];
            $fila['precio'] = (float)$fila['precio'];
            $lista[] = $fila;
        }
    }
    return $lista;
}

function obtenerSuplementoActivo($cnn, $id)
{
    $id = (int)$id;
    $resultado = mysqli_query($cnn, "SELECT id, nombre, uso, precio FROM suplementos WHERE id = $id AND activo = 1 LIMIT 1");
    $suplemento = $resultado ? mysqli_fetch_assoc($resultado) : null;
    if ($suplemento) {
        $suplemento['id'] = (int)$suplemento['id'];
        $suplemento['precio'] = (float)$suplemento['precio'];
    }
    return $suplemento ?: null;
}

function listarSedesActivas($cnn)
{
    $lista = [];
    $resultado = mysqli_query($cnn, "SELECT id, nombre, direccion, telefono, mapa_url FROM sedes WHERE activa = 1 ORDER BY id");
    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $fila['id'] = (int)$fila['id'];
            $lista[] = $fila;
        }
    }
    return $lista;
}

function listarHorariosSede($cnn, $sedeId)
{
    $sedeId = (int)$sedeId;
    $lista = [];
    $resultado = mysqli_query($cnn, "SELECT dias, TIME_FORMAT(apertura, '%H:%i') AS apertura, TIME_FORMAT(cierre, '%H:%i') AS cierre FROM horarios WHERE sede_id = $sedeId ORDER BY id");
    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $lista[] = $fila;
        }
    }
    return $lista;
}

function listarClases($cnn)
{
    $lista = [];
    $sql = "SELECT c.id, c.nombre, c.dias, TIME_FORMAT(c.hora, '%H:%i') AS hora, c.descripcion, c.imagen,
                   COALESCE(s.nombre, 'Todas las sedes') AS sede
            FROM clases_grupales c LEFT JOIN sedes s ON s.id = c.sede_id ORDER BY c.id";
    $resultado = mysqli_query($cnn, $sql);
    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $fila['id'] = (int)$fila['id'];
            $lista[] = $fila;
        }
    }
    return $lista;
}

function obtenerClase($cnn, $id)
{
    $id = (int)$id;
    $resultado = mysqli_query($cnn, "SELECT id, nombre, dias, TIME_FORMAT(hora, '%H:%i') AS hora, descripcion, imagen FROM clases_grupales WHERE id = $id LIMIT 1");
    $clase = $resultado ? mysqli_fetch_assoc($resultado) : null;
    return $clase ?: null;
}

function obtenerPlanActivo($cnn, $id)
{
    $id = mysqli_real_escape_string($cnn, $id);
    $sql = "SELECT id, nombre, precio FROM planes WHERE id = '$id' AND activo = 1 LIMIT 1";
    $resultado = mysqli_query($cnn, $sql);

    if (!$resultado) {
        return null;
    }

    $plan = mysqli_fetch_assoc($resultado);
    if ($plan) {
        $plan['precio'] = (float)$plan['precio'];
    }

    return $plan ?: null;
}

function consultarUsuario($cnn, $usuario)
{
    $usuario = mysqli_real_escape_string($cnn, $usuario);
    $sql = "SELECT id, socio_id, usuario, clave_hash, es_administrador, activo FROM usuarios
            WHERE usuario = '$usuario' AND es_administrador = 1 AND activo = 1";
    $resultado = mysqli_query($cnn, $sql);

    if ($resultado) {
        return mysqli_fetch_assoc($resultado);
    }

    return null;
}

function consultarSocioUsuario($cnn, $usuario)
{
    $usuario = mysqli_real_escape_string($cnn, $usuario);
    $sql = "SELECT id, socio_id, usuario, clave_hash FROM usuarios
            WHERE usuario = '$usuario' AND es_administrador = 0 AND activo = 1";
    $resultado = mysqli_query($cnn, $sql);
    return $resultado ? (mysqli_fetch_assoc($resultado) ?: null) : null;
}

function actualizarClaveHash($cnn, $id, $hash)
{
    $id = (int)$id;
    $hash = mysqli_real_escape_string($cnn, $hash);
    $sql = "UPDATE usuarios SET clave_hash = '$hash' WHERE id = $id";

    return mysqli_query($cnn, $sql) !== false;
}

function listarSocios($cnn, $sedeId = 0)
{
    $lista = [];
    $sedeId = (int)$sedeId;
    $filtroSede = $sedeId > 0 ? " WHERE s.sede_id = $sedeId" : '';
    $sql = "SELECT v.id, v.nombre, v.dni, v.correo, s.telefono, s.estado AS estado_socio, s.sede_id, se.nombre AS sede,
                   s.razon_social AS razon, s.direccion, v.plan_id AS plan,
                   v.monto, v.estado_pago,
                   GREATEST(0, DATEDIFF(DATE_ADD(COALESCE((SELECT MAX(fp.pagada_en) FROM facturas fp WHERE fp.socio_id = v.id AND fp.estado = 'pagada'), s.fecha_registro), INTERVAL 30 DAY), CURDATE())) AS dias_para_renovar,
                   EXISTS(SELECT 1 FROM facturas fs WHERE fs.socio_id = v.id
                          AND fs.periodo = DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m')) AS proxima_mensualidad_creada
            FROM v_socios_panel v
            INNER JOIN socios s ON s.id = v.id
            LEFT JOIN sedes se ON se.id = s.sede_id
            $filtroSede
            ORDER BY v.id DESC";
    $resultado = mysqli_query($cnn, $sql);

    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $fila['telefono'] = $fila['telefono'] ?? '';
            $fila['direccion'] = $fila['direccion'] ?? '';
            $fila['id'] = (int)$fila['id'];
            $fila['monto'] = (float)$fila['monto'];
            $fila['pagado'] = $fila['estado_pago'] === 'Pagado';
            $fila['activo'] = $fila['estado_socio'] === 'activo';
            $fila['dias_para_renovar'] = (int)$fila['dias_para_renovar'];
            $fila['proxima_mensualidad_creada'] = (bool)$fila['proxima_mensualidad_creada'];
            unset($fila['estado_pago'], $fila['estado_socio']);
            $lista[] = $fila;
        }
    }

    return $lista;
}

function buscarSocio($cnn, $id)
{
    $id = (int)$id;
    $sql = "SELECT v.id, v.nombre, v.dni, v.correo, s.telefono, s.estado AS estado_socio,
                   s.razon_social AS razon, s.direccion, v.plan_id AS plan,
                   v.monto, v.estado_pago
            FROM v_socios_panel v
            INNER JOIN socios s ON s.id = v.id
            WHERE v.id = $id";
    $resultado = mysqli_query($cnn, $sql);

    if (!$resultado) {
        return null;
    }

    $fila = mysqli_fetch_assoc($resultado);
    if ($fila) {
        $fila['telefono'] = $fila['telefono'] ?? '';
        $fila['direccion'] = $fila['direccion'] ?? '';
        $fila['id'] = (int)$fila['id'];
        $fila['monto'] = (float)$fila['monto'];
        $fila['pagado'] = $fila['estado_pago'] === 'Pagado';
        $fila['activo'] = $fila['estado_socio'] === 'activo';
        unset($fila['estado_pago'], $fila['estado_socio']);
    }

    return $fila;
}

/**
 * Expresión SQL con la fecha de vencimiento de la membresía (alias de la tabla socios: s).
 * Regla del panel: 30 días desde el último pago, o desde el registro si aún no hay pagos.
 */
function sqlVencimientoSocio()
{
    return "DATE_ADD(COALESCE((SELECT MAX(fv.pagada_en) FROM facturas fv WHERE fv.socio_id = s.id AND fv.estado = 'pagada'), s.fecha_registro), INTERVAL 30 DAY)";
}

/**
 * Datos de la membresía de un socio para la sección "Mi cuenta". Solo lectura.
 * ha_pagado distingue a un socio vencido (ya pagó antes) de uno nuevo que aún no ha pagado.
 */
function obtenerMembresiaSocio($cnn, $socioId)
{
    $socioId = (int)$socioId;
    if ($socioId <= 0) {
        return null;
    }
    $vence = sqlVencimientoSocio();
    $sql = "SELECT s.id, s.nombre, s.estado, p.nombre AS plan,
                   DATE_FORMAT($vence, '%d/%m/%Y') AS vence_texto,
                   DATEDIFF($vence, CURDATE()) AS dias_restantes,
                   EXISTS(SELECT 1 FROM facturas fp WHERE fp.socio_id = s.id AND fp.estado = 'pagada') AS ha_pagado
            FROM socios s
            INNER JOIN planes p ON p.id = s.plan_id
            WHERE s.id = $socioId
            LIMIT 1";
    try {
        $resultado = mysqli_query($cnn, $sql);
        $fila = $resultado ? mysqli_fetch_assoc($resultado) : null;
        if ($fila) {
            $fila['id'] = (int)$fila['id'];
            $fila['dias_restantes'] = (int)$fila['dias_restantes'];
            $fila['ha_pagado'] = (bool)$fila['ha_pagado'];
        }
        return $fila ?: null;
    } catch (mysqli_sql_exception $error) {
        return null;
    }
}

/**
 * Pasa a "inactivo" a los socios activos cuya membresía ya venció y les crea la factura
 * pendiente del mes actual (si no la tienen) para poder cobrarlos y reactivarlos.
 * No toca usuarios.activo: así el socio puede iniciar sesión y ver el aviso de renovación.
 * Devuelve cuántos socios se desactivaron.
 */
function desactivarSociosVencidos($cnn)
{
    $vence = sqlVencimientoSocio();
    $periodo = "DATE_FORMAT(CURDATE(), '%Y-%m')";

    $sqlFactura = "INSERT IGNORE INTO facturas (socio_id, plan_id, monto, periodo)
                   SELECT s.id, p.id, p.precio, $periodo
                   FROM socios s
                   INNER JOIN planes p ON p.id = s.plan_id
                   WHERE s.estado = 'activo'
                     AND DATEDIFF($vence, CURDATE()) < 0
                     AND NOT EXISTS (SELECT 1 FROM facturas fx WHERE fx.socio_id = s.id AND fx.periodo = $periodo)";
    $sqlEstado = "UPDATE socios s
                  SET s.estado = 'inactivo'
                  WHERE s.estado = 'activo'
                    AND DATEDIFF($vence, CURDATE()) < 0";

    try {
        mysqli_begin_transaction($cnn);
        if (!mysqli_query($cnn, $sqlFactura) || !mysqli_query($cnn, $sqlEstado)) {
            mysqli_rollback($cnn);
            return 0;
        }
        $desactivados = (int)mysqli_affected_rows($cnn);
        mysqli_commit($cnn);
        return max(0, $desactivados);
    } catch (mysqli_sql_exception $error) {
        mysqli_rollback($cnn);
        return 0;
    }
}

function listarClasesDeSocio($cnn, $socioId)
{
    $socioId = (int)$socioId;
    $lista = [];
    if ($socioId <= 0) {
        return $lista;
    }
    $sql = "SELECT c.id, c.nombre, c.dias, TIME_FORMAT(c.hora, '%H:%i') AS hora,
                   COALESCE(se.nombre, 'Todas las sedes') AS sede,
                   DATE_FORMAT(i.creada_en, '%d/%m/%Y') AS inscrita
            FROM inscripciones_clases i
            INNER JOIN clases_grupales c ON c.id = i.clase_id
            LEFT JOIN sedes se ON se.id = c.sede_id
            WHERE i.socio_id = $socioId
            ORDER BY i.creada_en DESC, i.id DESC";
    try {
        $resultado = mysqli_query($cnn, $sql);
        if ($resultado) {
            while ($fila = mysqli_fetch_assoc($resultado)) {
                $lista[] = $fila;
            }
        }
    } catch (mysqli_sql_exception $error) {
        return [];
    }
    return $lista;
}

function listarReservasDeSocio($cnn, $socioId)
{
    $socioId = (int)$socioId;
    $lista = [];
    if ($socioId <= 0) {
        return $lista;
    }
    $sql = "SELECT r.id, sp.nombre, sp.uso, sp.precio, r.estado,
                   COALESCE(se.nombre, '') AS sede,
                   DATE_FORMAT(r.creada_en, '%d/%m/%Y') AS reservada
            FROM reservas_suplementos r
            INNER JOIN suplementos sp ON sp.id = r.suplemento_id
            LEFT JOIN sedes se ON se.id = r.sede_id
            WHERE r.socio_id = $socioId
            ORDER BY r.creada_en DESC, r.id DESC";
    try {
        $resultado = mysqli_query($cnn, $sql);
        if ($resultado) {
            while ($fila = mysqli_fetch_assoc($resultado)) {
                $fila['precio'] = (float)$fila['precio'];
                $lista[] = $fila;
            }
        }
    } catch (mysqli_sql_exception $error) {
        return [];
    }
    return $lista;
}

/**
 * Indica qué datos de una inscripción ya existen en el sistema.
 * Devuelve una lista con los campos repetidos: 'dni', 'correo' y/o 'usuario'.
 */
function camposSocioRepetidos($cnn, $dni, $correo, $usuario)
{
    $repetidos = [];
    $dniSql = mysqli_real_escape_string($cnn, (string)$dni);
    $correoSql = mysqli_real_escape_string($cnn, (string)$correo);
    $usuarioSql = mysqli_real_escape_string($cnn, (string)$usuario);

    $consultas = [];
    if ($dniSql !== '') {
        $consultas['dni'] = "SELECT 1 FROM socios WHERE dni = '$dniSql' LIMIT 1";
    }
    if ($correoSql !== '') {
        $consultas['correo'] = "SELECT 1 FROM socios WHERE correo = '$correoSql' LIMIT 1";
    }
    if ($usuarioSql !== '') {
        $consultas['usuario'] = "SELECT 1 FROM usuarios WHERE usuario = '$usuarioSql' LIMIT 1";
    }

    foreach ($consultas as $campo => $sql) {
        try {
            $resultado = mysqli_query($cnn, $sql);
            if ($resultado && mysqli_num_rows($resultado) > 0) {
                $repetidos[] = $campo;
            }
        } catch (mysqli_sql_exception $error) {
            // Si la consulta falla, se omite esa comprobación y se intenta guardar normalmente.
        }
    }

    return $repetidos;
}

function guardarSocio($cnn, $datos)
{
    $nombre = mysqli_real_escape_string($cnn, $datos['nombre']);
    $dni = mysqli_real_escape_string($cnn, $datos['dni']);
    $correo = mysqli_real_escape_string($cnn, $datos['correo']);
    $telefono = mysqli_real_escape_string($cnn, $datos['telefono']);
    $razon = mysqli_real_escape_string($cnn, $datos['razon']);
    $direccion = mysqli_real_escape_string($cnn, $datos['direccion']);
    $plan = mysqli_real_escape_string($cnn, $datos['plan']);
    $sedeId = (int)($datos['sede_id'] ?? 0);
    $usuario = isset($datos['usuario']) ? mysqli_real_escape_string($cnn, $datos['usuario']) : '';
    $claveHash = isset($datos['clave_hash']) ? mysqli_real_escape_string($cnn, $datos['clave_hash']) : '';

    if (!obtenerPlanActivo($cnn, $plan) || $sedeId <= 0) {
        return false;
    }

    try {
        mysqli_begin_transaction($cnn);
        $sql = "INSERT INTO socios
                (nombre, dni, correo, telefono, razon_social, direccion, plan_id, sede_id, estado)
                SELECT '$nombre', '$dni', '$correo', '$telefono', '$razon', '$direccion', '$plan', se.id, 'inactivo'
                FROM sedes se WHERE se.id = $sedeId AND se.activa = 1";
        mysqli_query($cnn, $sql);
        if (mysqli_affected_rows($cnn) !== 1) {
            mysqli_rollback($cnn);
            return false;
        }

        $id = mysqli_insert_id($cnn);
        if ($usuario !== '' && $claveHash !== '') {
            if (!mysqli_query($cnn, "INSERT INTO usuarios (socio_id, usuario, clave_hash, es_administrador, activo)
                                VALUES ($id, '$usuario', '$claveHash', 0, 0)")) {
                mysqli_rollback($cnn);
                return false;
            }
        }
        $sqlFactura = "INSERT INTO facturas (socio_id, plan_id, monto, periodo)
                       SELECT $id, id, precio, DATE_FORMAT(CURDATE(), '%Y-%m')
                       FROM planes WHERE id = '$plan'";
        if (!mysqli_query($cnn, $sqlFactura)) {
            mysqli_rollback($cnn);
            return false;
        }
        mysqli_commit($cnn);
        return $id;
    } catch (mysqli_sql_exception $error) {
        mysqli_rollback($cnn);
        return false;
    }
}

function cambiarPlanSocio($cnn, $id, $plan)
{
    $id = (int)$id;
    $plan = mysqli_real_escape_string($cnn, $plan);
    if (!obtenerPlanActivo($cnn, $plan)) {
        return false;
    }
    try {
        mysqli_begin_transaction($cnn);
        $sqlSocio = "UPDATE socios SET plan_id = '$plan' WHERE id = $id";
        if (!mysqli_query($cnn, $sqlSocio)) {
            mysqli_rollback($cnn);
            return false;
        }

        $sqlFactura = "UPDATE facturas
                       SET plan_id = '$plan',
                           monto = (SELECT precio FROM planes WHERE id = '$plan')
                       WHERE socio_id = $id
                         AND periodo = DATE_FORMAT(CURDATE(), '%Y-%m')
                         AND estado = 'pendiente'";
        if (!mysqli_query($cnn, $sqlFactura)) {
            mysqli_rollback($cnn);
            return false;
        }
        return mysqli_commit($cnn);
    } catch (mysqli_sql_exception $error) {
        mysqli_rollback($cnn);
        return false;
    }
}

function editarSocio($cnn, $id, $correo, $plan)
{
    $id = (int)$id;
    $correo = mysqli_real_escape_string($cnn, $correo);
    $plan = mysqli_real_escape_string($cnn, $plan);
    if ($id <= 0 || !obtenerPlanActivo($cnn, $plan)) {
        return false;
    }

    try {
        mysqli_begin_transaction($cnn);
        if (!mysqli_query($cnn, "UPDATE socios SET correo = '$correo', plan_id = '$plan' WHERE id = $id") || mysqli_affected_rows($cnn) < 0) {
            mysqli_rollback($cnn);
            return false;
        }

        $sqlFactura = "UPDATE facturas
                       SET plan_id = '$plan', monto = (SELECT precio FROM planes WHERE id = '$plan')
                       WHERE socio_id = $id AND periodo = DATE_FORMAT(CURDATE(), '%Y-%m') AND estado = 'pendiente'";
        if (!mysqli_query($cnn, $sqlFactura)) {
            mysqli_rollback($cnn);
            return false;
        }
        return mysqli_commit($cnn);
    } catch (mysqli_sql_exception $error) {
        mysqli_rollback($cnn);
        return false;
    }
}

function renovarMensualidadSocio($cnn, $id)
{
    $id = (int)$id;
    if ($id <= 0) {
        return false;
    }
    $sql = "INSERT INTO facturas (socio_id, plan_id, monto, periodo)
            SELECT s.id, p.id, p.precio, DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m')
            FROM socios s INNER JOIN planes p ON p.id = s.plan_id
            WHERE s.id = $id AND s.estado = 'activo' AND p.activo = 1";
    try {
        return mysqli_query($cnn, $sql) && mysqli_affected_rows($cnn) === 1;
    } catch (mysqli_sql_exception $error) {
        return false;
    }
}

function buscarSocioPorCedulaCorreo($cnn, $dni, $correo)
{
    desactivarSociosVencidos($cnn);
    $dni = mysqli_real_escape_string($cnn, $dni);
    $correo = mysqli_real_escape_string($cnn, $correo);
    $resultado = mysqli_query($cnn, "SELECT id, nombre FROM socios WHERE dni = '$dni' AND correo = '$correo' AND estado = 'activo' LIMIT 1");
    return $resultado ? (mysqli_fetch_assoc($resultado) ?: null) : null;
}

function inscribirSocioEnClase($cnn, $claseId, $socioId)
{
    $claseId = (int)$claseId;
    $socioId = (int)$socioId;
    if ($claseId <= 0 || $socioId <= 0) {
        return false;
    }
    desactivarSociosVencidos($cnn);
    try {
        $sql = "INSERT INTO inscripciones_clases (clase_id, socio_id)
                SELECT c.id, s.id FROM clases_grupales c INNER JOIN socios s ON s.id = $socioId
                WHERE c.id = $claseId AND s.estado = 'activo'";
        return mysqli_query($cnn, $sql) && mysqli_affected_rows($cnn) === 1;
    } catch (mysqli_sql_exception $error) {
        return false;
    }
}

function guardarReservaSuplemento($cnn, $suplementoId, $socioId, $sedeId)
{
    $suplementoId = (int)$suplementoId;
    $socioId = (int)$socioId;
    $sedeId = (int)$sedeId;
    if (!$suplementoId || !$socioId || !$sedeId) {
        return false;
    }
    desactivarSociosVencidos($cnn);
    $sql = "INSERT INTO reservas_suplementos (suplemento_id, socio_id, sede_id)
            SELECT $suplementoId, $socioId, $sedeId
            FROM suplementos sp, socios so, sedes se
            WHERE sp.id = $suplementoId AND sp.activo = 1
              AND so.id = $socioId AND so.estado = 'activo'
              AND se.id = $sedeId AND se.activa = 1";
    try {
        return mysqli_query($cnn, $sql) && mysqli_affected_rows($cnn) === 1;
    } catch (mysqli_sql_exception $error) {
        return false;
    }
}

function eliminarSocio($cnn, $id)
{
    $id = (int)$id;
    return mysqli_query($cnn, "DELETE FROM socios WHERE id = $id") !== false;
}

function registrarPagoSocio($cnn, $id)
{
    $id = (int)$id;
    if ($id <= 0) {
        return false;
    }

    try {
        mysqli_begin_transaction($cnn);
        $sqlFactura = "UPDATE facturas
                       SET estado = 'pagada', pagada_en = NOW()
                       WHERE socio_id = $id
                         AND periodo = DATE_FORMAT(CURDATE(), '%Y-%m')
                         AND estado = 'pendiente'";
        mysqli_query($cnn, $sqlFactura);
        if (mysqli_affected_rows($cnn) !== 1) {
            mysqli_rollback($cnn);
            return false;
        }

        mysqli_query($cnn, "UPDATE socios SET estado = 'activo' WHERE id = $id");
        mysqli_query($cnn, "UPDATE usuarios SET activo = 1 WHERE socio_id = $id AND es_administrador = 0");
        return mysqli_commit($cnn);
    } catch (mysqli_sql_exception $error) {
        mysqli_rollback($cnn);
        return false;
    }
}

?>