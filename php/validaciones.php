<?php

function textoLimpio($valor): string
{
    return is_string($valor) ? trim($valor) : '';
}

function validarNombrePersona(string $nombre): bool
{
    return mb_strlen($nombre) >= 5 && mb_strlen($nombre) <= 100
        && preg_match('/^\\p{Lu}[\\p{L}\\p{M}’\'-]{1,}(?:\\s+\\p{Lu}[\\p{L}\\p{M}’\'-]{1,})+$/u', $nombre) === 1;
}

function validarRazonSocial(string $razon): bool
{
    return mb_strlen($razon) >= 3 && mb_strlen($razon) <= 100
        && preg_match('/^[\\p{L}0-9][\\p{L}0-9 .,&()’\'-]*$/u', $razon) === 1;
}

function validarDireccion(string $direccion): bool
{
    return mb_strlen($direccion) >= 8 && mb_strlen($direccion) <= 150
        && preg_match('/^[\\p{L}0-9][\\p{L}0-9 .,#º°\/-]*$/u', $direccion) === 1
        && preg_match('/\\d/u', $direccion) === 1;
}

function validarCedulaEcuatoriana(string $cedula): bool
{
    if (!preg_match('/^\\d{10}$/', $cedula)) {
        return false;
    }

    $provincia = (int)substr($cedula, 0, 2);
    if ($provincia < 1 || $provincia > 24 || (int)$cedula[2] > 5) {
        return false;
    }

    $suma = 0;
    for ($i = 0; $i < 9; $i++) {
        $digito = (int)$cedula[$i];
        if ($i % 2 === 0) {
            $digito *= 2;
            if ($digito > 9) {
                $digito -= 9;
            }
        }
        $suma += $digito;
    }

    $verificador = (10 - ($suma % 10)) % 10;
    return $verificador === (int)$cedula[9];
}

function validarCorreo(string $correo): bool
{
    if (mb_strlen($correo) > 120 || preg_match('/[\\r\\n]/', $correo)) {
        return false;
    }

    if (filter_var($correo, FILTER_VALIDATE_EMAIL) === false
        || preg_match('/^[A-Z0-9.!#$%&\'*+\\/=?^_`{|}~-]+@[A-Z0-9](?:[A-Z0-9-]{0,61}[A-Z0-9])?(?:\\.[A-Z0-9](?:[A-Z0-9-]{0,61}[A-Z0-9])?)+$/i', $correo) !== 1) {
        return false;
    }

    $dominio = substr(strrchr($correo, '@'), 1);
    return $dominio !== '' && checkdnsrr($dominio, 'MX');
}

function validarTelefonoEcuatoriano(string $telefono): bool
{
    return $telefono === '' || (mb_strlen($telefono) <= 20
        && preg_match('/^(?:\\+593[ -]?|0)\\d(?:[ -]?\\d){7,8}$/', $telefono) === 1);
}

function validarUsuario(string $usuario): bool
{
    return preg_match('/^[a-zA-Z][a-zA-Z0-9_.-]{3,39}$/', $usuario) === 1;
}

function validarClave(string $clave): bool
{
    return strlen($clave) >= 8 && strlen($clave) <= 72
        && preg_match('/[a-z]/', $clave) === 1
        && preg_match('/[A-Z]/', $clave) === 1
        && preg_match('/\\d/', $clave) === 1
        && preg_match('/[^a-zA-Z0-9]/', $clave) === 1;
}

function validarDatosSocio(array $datos): array
{
    $errores = [];
    if (!validarNombrePersona($datos['nombre'] ?? '')) {
        $errores[] = 'Ingresa un nombre válido de entre 3 y 100 caracteres.';
    }
    if (!validarCedulaEcuatoriana($datos['dni'] ?? '')) {
        $errores[] = 'Ingresa una cédula ecuatoriana válida.';
    }
    if (!validarCorreo($datos['correo'] ?? '')) {
        $errores[] = 'Ingresa un correo válido (por ejemplo, nombre@dominio.com).';
    }
    if (!validarTelefonoEcuatoriano($datos['telefono'] ?? '')) {
        $errores[] = 'Ingresa un teléfono ecuatoriano válido o deja el campo vacío.';
    }
    if (!validarRazonSocial($datos['razon'] ?? '')) {
        $errores[] = 'Ingresa un nombre o razón social válidos.';
    }
    if (!validarDireccion($datos['direccion'] ?? '')) {
        $errores[] = 'Ingresa una dirección válida: calle, número y referencia opcional.';
    }

    return $errores;
}
