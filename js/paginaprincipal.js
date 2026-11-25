var indiceGaleria = 0;
var indiceClase = 0;
var detalleSuplemento = {
  "Nova Whey Proteína": "Aporta proteína de rápida absorción para complementar tu alimentación después del entrenamiento.",
  "Nova Creatina Mono": "Apoya esfuerzos de alta intensidad; úsala diariamente junto con buena hidratación.",
  "Nova Pre-Forge": "Fórmula previa al entrenamiento para días de alta demanda. Evita tomarla cerca de la hora de dormir.",
  "Nova Omega 3": "Ácidos grasos esenciales que complementan una alimentación balanceada."
};

var IMAGEN_SEDE_DEFECTO = "sede-exterior.png";
var imagenesSede = {
  "norte": "sede-norte.webp",
  "central": "sede-exterior.png"
};

function imagenDeSede(sede) {
  var nombre = String((sede && sede.nombre) || "").toLowerCase();
  for (var clave in imagenesSede) {
    if (nombre.indexOf(clave) !== -1) return imagenesSede[clave];
  }
  return IMAGEN_SEDE_DEFECTO;
}

function actualizarImagenSede(sede) {
  var img = document.getElementById("imagen-sede-horario");
  if (!img || !sede) return;
  img.src = "../img/" + imagenDeSede(sede);
  img.alt = "Fachada de " + sede.nombre;
}

function escaparHtmlPublico(valor) {
  var nodo = document.createElement("span");
  nodo.textContent = String(valor || "");
  return nodo.innerHTML;
}

function tarjetaCarrusel(item, tipo) {
  if (tipo === "galeria") {
    return '<figure class="foto foto-carrusel"><img src="../img/' + escaparHtmlPublico(item.foto) + '.jpg" alt="' + escaparHtmlPublico(item.t) + '" loading="lazy"><figcaption>' + escaparHtmlPublico(item.t) + '</figcaption></figure>';
  }
  return '<article class="card tarjeta-clase detalle-mousedown"><img class="imagen-clase" src="../img/' + escaparHtmlPublico(item.imagen) + '" alt="Clase ' + escaparHtmlPublico(item.nombre) + '" loading="lazy"><h3>' + escaparHtmlPublico(item.nombre) + '</h3><div class="detalle-oculto"><p>' + escaparHtmlPublico(item.dias) + ' · ' + escaparHtmlPublico(item.hora) + ' · ' + escaparHtmlPublico(item.sede) + '</p><p>' + escaparHtmlPublico(item.descripcion) + '</p><a class="btn peq" href="inscripcion_clase.php?clase=' + Number(item.id) + '">Inscribirme a esta clase</a></div></article>';
}

function renderCarrusel(elemento, items, indice, tipo) {
  if (!items.length) { elemento.innerHTML = '<p class="err">No hay contenido disponible.</p>'; return; }
  var actual = items[(indice + items.length) % items.length];
  elemento.innerHTML = '<button class="carrusel-control anterior" type="button" aria-label="Anterior">‹</button>' + tarjetaCarrusel(actual, tipo) + '<button class="carrusel-control siguiente" type="button" aria-label="Siguiente">›</button>';
  elemento.querySelector(".anterior").addEventListener("click", function () {
    if (tipo === "galeria") { indiceGaleria--; renderCarrusel(elemento, items, indiceGaleria, tipo); }
    else { indiceClase--; renderCarrusel(elemento, items, indiceClase, tipo); }
  });
  elemento.querySelector(".siguiente").addEventListener("click", function () {
    if (tipo === "galeria") { indiceGaleria++; renderCarrusel(elemento, items, indiceGaleria, tipo); }
    else { indiceClase++; renderCarrusel(elemento, items, indiceClase, tipo); }
  });
}

function cargarHorarios(sedeId) {
  fetch("api.php?accion=horarios&sede=" + encodeURIComponent(sedeId)).then(function (respuesta) { return respuesta.json(); }).then(function (horarios) {
    document.getElementById("tabla-horarios").innerHTML = horarios.map(function (h) {
      return '<tr><td>' + escaparHtmlPublico(h.dias) + '</td><td>' + escaparHtmlPublico(h.apertura) + ' – ' + escaparHtmlPublico(h.cierre) + '</td></tr>';
    }).join("") || '<tr><td colspan="2">No hay horarios configurados.</td></tr>';
  });
}

function pintarPublico() {
  aplicarMarca();
  document.getElementById("lema").textContent = CONFIG.lema;
  fetch("api.php?accion=planes").then(function (r) { if (!r.ok) throw new Error("planes"); return r.json(); }).then(function (planes) {
    document.getElementById("lista-planes").innerHTML = planes.map(function (p) {
      var detalle = PLANES.filter(function (plan) { return plan.id === p.id; })[0];
      var beneficios = detalle ? detalle.beneficios : [];
      return '<div class="card"><h3>' + escaparHtmlPublico(p.nombre) + '</h3><div class="precio">$' + Number(p.precio).toFixed(2) + '<small>/mes</small></div><p>' + escaparHtmlPublico(p.descripcion) + '</p><ul>' + beneficios.map(function (b) { return '<li>' + escaparHtmlPublico(b) + '</li>'; }).join('') + '</ul><a class="btn" href="inscripcion.php?plan=' + encodeURIComponent(p.id) + '">Inscribirme</a></div>';
    }).join("");
  }).catch(function () { document.getElementById("lista-planes").innerHTML = '<p class="err">No se pudieron cargar los planes.</p>'; });
  fetch("api.php?accion=sedes").then(function (r) { if (!r.ok) throw new Error("sedes"); return r.json(); }).then(function (sedes) {
    document.getElementById("lista-sedes").innerHTML = sedes.map(function (s) {
      return '<div class="card"><h3>' + escaparHtmlPublico(s.nombre) + '</h3><p>' + escaparHtmlPublico(s.direccion) + '</p><p>Tel: ' + escaparHtmlPublico(s.telefono) + '</p><a class="btn peq" target="_blank" rel="noopener" href="' + escaparHtmlPublico(s.mapa_url) + '">Abrir ubicación</a></div>';
    }).join("");
    var selector = document.getElementById("horario-sede");
    selector.innerHTML = sedes.map(function (s) { return '<option value="' + Number(s.id) + '">' + escaparHtmlPublico(s.nombre) + '</option>'; }).join("");
    if (sedes.length) { cargarHorarios(sedes[0].id); actualizarImagenSede(sedes[0]); }
    selector.addEventListener("change", function () {
      var id = this.value;
      cargarHorarios(id);
      actualizarImagenSede(sedes.filter(function (s) { return String(s.id) === String(id); })[0]);
    });
  }).catch(function () { document.getElementById("lista-sedes").innerHTML = '<p class="err">No se pudieron cargar las sedes.</p>'; });
  fetch("api.php?accion=clases").then(function (r) { if (!r.ok) throw new Error("clases"); return r.json(); }).then(function (clases) { renderCarrusel(document.getElementById("carrusel-clases"), clases, indiceClase, "clases"); }).catch(function () { document.getElementById("carrusel-clases").innerHTML = '<p class="err">No se pudieron cargar las clases.</p>'; });
  document.getElementById("lista-ruta").innerHTML = ITINERARIO.map(function (r) {
    return '<article class="card ruta-interactiva detalle-mousedown"><small>' + escaparHtmlPublico(r.dia) + '</small><h3>' + escaparHtmlPublico(r.ruta) + '</h3><div class="detalle-oculto"><p>' + escaparHtmlPublico(r.foco) + '</p><p class="ok">' + escaparHtmlPublico(r.duracion) + '</p><a class="btn peq" href="ruta.php?rutina=' + encodeURIComponent(r.id) + '">Ver rutina</a></div></article>';
  }).join("");
  document.getElementById("lista-nutricion").innerHTML = NUTRICION.map(function (n) {
    return '<article class="card nutricion detalle-mousedown"><img class="imagen-clase" src="../img/' + escaparHtmlPublico(n.foto) + '" alt="' + escaparHtmlPublico(n.nombre) + '" loading="lazy"><small>' + escaparHtmlPublico(n.objetivo) + '</small><h3>' + escaparHtmlPublico(n.nombre) + '</h3><div class="detalle-oculto"><p>' + escaparHtmlPublico(n.desc) + '</p><p>' + escaparHtmlPublico(n.detalle) + '</p></div></article>';
  }).join("");
  fetch("api.php?accion=suplementos").then(function (r) { if (!r.ok) throw new Error("suplementos"); return r.json(); }).then(function (suplementos) {
    document.getElementById("lista-supl").innerHTML = suplementos.map(function (s) {
      return '<article class="card suplemento-hover"><h3>' + escaparHtmlPublico(s.nombre) + '</h3><p>' + escaparHtmlPublico(s.uso) + '</p><div class="precio">$' + Number(s.precio).toFixed(2) + '</div><div class="detalle-oculto"><p>' + escaparHtmlPublico(detalleSuplemento[s.nombre] || 'Consulta disponibilidad y orientación de uso en sede.') + '</p></div><a class="btn peq" href="reserva_suplemento.php?suplemento=' + Number(s.id) + '">Reservar en sede</a></article>';
    }).join("");
  }).catch(function () { document.getElementById("lista-supl").innerHTML = '<p class="err">No se pudieron cargar los suplementos.</p>'; });
  renderCarrusel(document.getElementById("carrusel-galeria"), GALERIA, indiceGaleria, "galeria");
}

function validarContacto(e) {
  e.preventDefault();
  var n = document.getElementById("c-nombre").value.trim(), c = document.getElementById("c-correo").value.trim(), m = document.getElementById("c-msg").value.trim(), r = document.getElementById("c-resultado");
  if (!nombreValido(n)) { r.className = "err"; r.textContent = "Escribe nombre y apellido, con iniciales en mayúscula."; return; }
  if (!correoValido(c)) { r.className = "err"; r.textContent = "Correo no válido."; return; }
  if (m.length < 10 || m.length > 1000) { r.className = "err"; r.textContent = "El mensaje debe tener entre 10 y 1000 caracteres."; return; }
  fetch('api.php?accion=validar_correo&correo=' + encodeURIComponent(c)).then(function (respuesta) { return respuesta.json(); }).then(function (resultado) {
    if (!resultado.valido) { r.className = "err"; r.textContent = "El dominio del correo no puede recibir mensajes."; return; }
    window.open('https://wa.me/593984581008?text=' + encodeURIComponent('Hola, soy ' + n + '. Mi correo es ' + c + '. Mensaje: ' + m), '_blank', 'noopener');
    r.className = "ok"; r.textContent = "Se abrió WhatsApp con tu mensaje.";
  }).catch(function () { r.className = "err"; r.textContent = "No se pudo validar el correo. Inténtalo nuevamente."; });
}

window.addEventListener("DOMContentLoaded", function () { pintarPublico(); document.getElementById("form-contacto").addEventListener("submit", validarContacto); });