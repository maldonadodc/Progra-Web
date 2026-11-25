var $ = function (id) { return document.getElementById(id); };
var borrador = {};
var okUsuario = false, okPlan = false, facturaId = null;
var planes = [];
var sedes = [];
var socios = [];
var facturaSocio = null;

function consultarApi(url, datos) {
  var opciones = datos ? {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-Token": $("csrf-token").value
    },
    body: JSON.stringify(datos)
  } : {};
  return fetch(url, opciones).then(function (respuesta) {
    return respuesta.json().then(function (resultado) {
      if (!respuesta.ok) throw new Error(resultado.error || "No se pudo completar la operación.");
      return resultado;
    });
  });
}

function ver(v) {
  ["socios", "registro", "factura", "editar"].forEach(function (x) {
    $("vista-" + x).classList.toggle("oculto", x !== v);
  });
  if (v === "socios") pintarSocios();
  if (v === "registro") reiniciarRegistro();
}

function escaparHtml(valor) {
  var texto = document.createElement("span");
  texto.textContent = valor;
  return texto.innerHTML;
}

/* ---------- READ ---------- */
function pintarSocios() {
  var q = $("buscar").value.toLowerCase();
  var sede = $("filtro-sede").value;
  consultarApi("api.php?accion=socios" + (sede ? "&sede=" + encodeURIComponent(sede) : "")).then(function (todos) {
  socios = todos;
  var pagados = todos.filter(function (s) { return s.pagado; });
  $("stats").innerHTML =
    '<div class="card"><small>Socios</small><div class="num">' + todos.length + '</div></div>' +
    '<div class="card"><small>Pagados</small><div class="num">' + pagados.length + '</div></div>' +
    '<div class="card"><small>Ingresos del mes</small><div class="num">$' +
    pagados.reduce(function (a, s) { return a + s.monto; }, 0).toFixed(2) + '</div></div>';

  var filas = todos.filter(function (s) { return (s.nombre + s.dni).toLowerCase().indexOf(q) >= 0; })
    .map(function (s) {
      var plan = planes.filter(function (p) { return p.id === s.plan; })[0];
      var renovacion = !s.activo
        ? '<span class="err">Se activa al cobrar</span>'
        : s.proxima_mensualidad_creada
        ? '<span class="ok">Próximo mes creada</span>'
        : '<span>En ' + s.dias_para_renovar + ' día' + (s.dias_para_renovar === 1 ? '' : 's') + '</span><br><button class="btn peq" onclick="renovarSocio(' + Number(s.id) + ')">Renovar</button>';
      var cobro = s.pagado ? '' : '<button class="btn peq" onclick="cobrarSocio(' + Number(s.id) + ')">' + (s.activo ? 'Cobrar' : 'Cobrar y activar') + '</button> ';
      return '<tr><td>' + escaparHtml(s.nombre) + '</td><td>' + escaparHtml(s.dni) + '</td><td>' + escaparHtml(s.correo) + '</td>' +
        '<td>' + escaparHtml(plan ? plan.nombre : s.plan) + '<br><small>' + escaparHtml(s.sede || "Sin sede") + '</small></td>' +
        '<td>$' + s.monto.toFixed(2) + '</td>' +
        '<td class="' + (s.pagado ? "ok" : "err") + '">' + (s.pagado ? "Pagado" : "Pendiente") + '</td>' +
        '<td class="' + (s.activo ? "ok" : "err") + '">' + (s.activo ? "Activo" : "Inactivo") + '</td>' +
        '<td>' + renovacion + '</td>' +
        '<td>' + cobro + '<button class="btn peq" onclick="abrirEdicion(' + Number(s.id) + ')">Editar</button> <button class="btn peq sec" onclick="eliminarSocio(' + Number(s.id) + ')">Eliminar</button></td></tr>';
    }).join("");
  $("tabla-socios").innerHTML = filas || '<tr><td colspan="9">Sin socios registrados.</td></tr>';
  }).catch(function (error) {
    $("tabla-socios").innerHTML = '<tr><td colspan="9">' + escaparHtml(error.message) + '</td></tr>';
  });
}

/* ---------- UPDATE / DELETE ---------- */
function abrirEdicion(id) {
  var socio = socios.filter(function (s) { return s.id === id; })[0];
  if (!socio) return;
  $("e-id").value = socio.id;
  $("e-nombre").textContent = socio.nombre + " · " + socio.dni;
  $("e-correo").value = socio.correo;
  $("e-plan").innerHTML = planes.map(function (p) {
    return '<option value="' + p.id + '"' + (p.id === socio.plan ? " selected" : "") + '>' + escaparHtml(p.nombre) + ' – $' + p.precio.toFixed(2) + '</option>';
  }).join("");
  $("msg-editar").textContent = "";
  ver("editar");
}
function guardarEdicion() {
  var correo = $("e-correo").value.trim(), plan = $("e-plan").value, mensaje = $("msg-editar");
  mensaje.className = "err";
  if (!correoValido(correo)) { mensaje.textContent = "Ingresa un correo válido."; return; }
  if (!planes.some(function (p) { return p.id === plan; })) { mensaje.textContent = "Selecciona un plan válido."; return; }
  consultarApi("api.php?accion=editar", { id: Number($("e-id").value), correo: correo, plan: plan }).then(function () {
    ver("socios");
  }).catch(function (error) { mensaje.textContent = error.message; });
}
function renovarSocio(id) {
  if (!confirm("¿Crear la factura pendiente del próximo mes para este socio?")) return;
  consultarApi("api.php?accion=renovar", { id: id }).then(pintarSocios)
    .catch(function (error) { alert(error.message); });
}
function cobrarSocio(id) {
  if (!confirm("¿Registrar el pago de la mensualidad actual? Si estaba inactivo, el socio quedará activo.")) return;
  consultarApi("api.php?accion=pagar", { id: id }).then(pintarSocios)
    .catch(function (error) { alert(error.message); });
}
function eliminarSocio(id) {
  if (!confirm("¿Eliminar este socio?")) return;
  consultarApi("api.php?accion=eliminar", { id: id }).then(pintarSocios)
    .catch(function (error) { alert(error.message); });
}

/* ---------- CREATE (flujo en 3 pasos) ---------- */
function reiniciarRegistro() {
  borrador = {}; okUsuario = false; okPlan = false;
  $("form-registro").reset();
  $("msg-usuario").textContent = ""; $("msg-plan").textContent = ""; $("msg-final").textContent = "";
  $("r-plan").innerHTML = '<option value="">Cargando planes...</option>';
  consultarApi("api.php?accion=planes").then(function (lista) {
    planes = lista;
    $("r-plan").innerHTML = '<option value="">Elige un plan</option>' + planes.map(function (p) {
    return '<option value="' + p.id + '">' + p.nombre + ' – $' + p.precio.toFixed(2) + '</option>';
  }).join("");
    $("r-sede").innerHTML = '<option value="">Elige una sede</option>' + sedes.map(function (s) {
      return '<option value="' + s.id + '">' + escaparHtml(s.nombre) + '</option>';
    }).join("");
  }).catch(function (error) { $("r-plan").innerHTML = '<option value="">' + escaparHtml(error.message) + '</option>'; });
}

function guardarUsuario() {
  var n = $("r-nombre").value.trim(), d = $("r-dni").value.trim(), c = $("r-correo").value.trim();
  var m = $("msg-usuario");
  m.className = "err"; okUsuario = false;
  if (!nombreValido(n)) { m.textContent = "Ingresa un nombre válido con al menos 3 letras."; return; }
  if (!cedulaEcuatorianaValida(d)) { m.textContent = "Ingresa una cédula ecuatoriana válida."; return; }
  if (!correoValido(c) || c.length > 120) { m.textContent = "Correo inválido o mayor a 120 caracteres."; return; }
  var telefono = $("r-tel").value.trim();
  if (!telefonoEcuatorianoValido(telefono)) {
    m.textContent = "Ingresa un teléfono ecuatoriano válido o deja el campo vacío."; return;
  }
  borrador.nombre = n; borrador.dni = d; borrador.correo = c; borrador.telefono = telefono;
  okUsuario = true; m.className = "ok"; m.textContent = "Datos de usuario guardados.";
}

function guardarPlan() {
  var m = $("msg-plan"), p = $("r-plan").value, razon = $("r-razon").value.trim();
  m.className = "err"; okPlan = false;
  if (!razonSocialValida(razon)) {
    m.textContent = "Ingresa un nombre o razón social válidos."; return;
  }
  var direccion = $("r-dir").value.trim();
  if (!direccionValida(direccion)) {
    m.textContent = "Incluye calle o avenida y número en la dirección."; return;
  }
  if (!planes.some(function (plan) { return plan.id === p; })) { m.textContent = "Selecciona un plan válido."; return; }
  var sede = $("r-sede").value;
  if (!sedes.some(function (item) { return String(item.id) === sede; })) { m.textContent = "Selecciona una sede válida."; return; }
  borrador.razon = razon; borrador.direccion = direccion; borrador.plan = p; borrador.sede_id = sede;
  okPlan = true; m.className = "ok"; m.textContent = "Datos del plan guardados.";
}

function irFactura() {
  var m = $("msg-final");
  if (!okUsuario || !okPlan) { m.className = "err"; m.textContent = "Completa y guarda ambas secciones."; return; }
  consultarApi("api.php?accion=crear", borrador).then(function (socio) {
    okUsuario = false; okPlan = false;
    facturaId = socio.id;
    facturaSocio = socio;
    alert("Correo de bienvenida enviado a " + socio.correo + " (simulado).");
    pintarFactura(); ver("factura");
  }).catch(function (error) {
    m.className = "err";
    m.textContent = error.message;
  });
}

/* ---------- FACTURACIÓN ---------- */
function pintarFactura() {
  var s = facturaSocio;
  if (!s) return;
  var plan = planes.filter(function (p) { return p.id === s.plan; })[0];
  $("resumen-factura").innerHTML =
    "<p><b>Socio:</b> " + escaparHtml(s.nombre) + " (" + escaparHtml(s.dni) + ")</p>" +
    "<p><b>Facturar a:</b> " + escaparHtml(s.razon) + "</p>" +
    "<p><b>Plan:</b> " + escaparHtml(plan ? plan.nombre : "") + "</p>" +
    '<p class="precio">Total: $' + s.monto.toFixed(2) + "</p>";
}
function pagar() {
  consultarApi("api.php?accion=pagar", { id: facturaId }).then(function () {
    alert("Pago registrado. Membresía activada.");
    ver("socios");
  }).catch(function (error) { alert(error.message); });
}

window.addEventListener("DOMContentLoaded", function () {
  aplicarMarca();
  Promise.all([consultarApi("api.php?accion=planes"), consultarApi("api.php?accion=sedes")]).then(function (resultados) {
    planes = resultados[0]; sedes = resultados[1];
    $("filtro-sede").innerHTML = '<option value="">Todas las sedes</option>' + sedes.map(function (s) { return '<option value="' + s.id + '">' + escaparHtml(s.nombre) + '</option>'; }).join("");
    ver("socios");
  });
  $("buscar").addEventListener("input", pintarSocios);
  $("filtro-sede").addEventListener("change", pintarSocios);
  $("form-registro").addEventListener("input", function (evento) {
    if (["r-nombre", "r-dni", "r-correo", "r-tel"].includes(evento.target.id)) {
      okUsuario = false;
      $("msg-usuario").textContent = "";
    } else {
      okPlan = false;
      $("msg-plan").textContent = "";
    }
    $("msg-final").textContent = "";
  });
});
