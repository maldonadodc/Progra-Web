const CONFIG = {
  nombre: "NovaForge Gym",
  lema: "Entrena con ruta. Come con plan. Crece con método.",
  color: "#ff5a1f"
};

const PLANES = [
  { id: "basico", nombre: "Básico", precio: 19.99, beneficios: ["Sala de pesas y cardio", "Horario estándar", "1 sede"] },
  { id: "estandar", nombre: "Estándar", precio: 29.99, beneficios: ["Todo lo del Básico", "Clases grupales", "NovaRuta semanal", "Todas las sedes"] },
  { id: "premium", nombre: "Premium", precio: 44.99, beneficios: ["Todo lo del Estándar", "Plan nutricional NovaPlato", "10% en suplementos", "Evaluación corporal mensual"] }
];

const SEDES = [
  { nombre: "Sede Centro", direccion: "Calle Larga y Borrero, Cuenca", telefono: "07 281 0001", mapa: "https://maps.google.com/maps?q=Calle+Larga+Cuenca+Ecuador&output=embed" },
  { nombre: "Sede Norte", direccion: "Av. de las Américas, Cuenca", telefono: "07 281 0002", mapa: "https://maps.google.com/maps?q=Av+de+las+Americas+Cuenca+Ecuador&output=embed" }
];

const HORARIOS = [
  { sede: "Centro", dia: "Lunes a Viernes", horario: "05:00 – 22:00" },
  { sede: "Centro", dia: "Sábados y Domingos", horario: "07:00 – 15:00" },
  { sede: "Norte", dia: "Lunes a Viernes", horario: "06:00 – 21:00" },
  { sede: "Norte", dia: "Sábados", horario: "08:00 – 14:00" }
];

const CLASES = [
  { clase: "Funcional Forge", dia: "Lun / Mié", hora: "18:30", foto: "funcional", alt: "Grupo entrenando con pesas rusas" },
  { clase: "Spinning", dia: "Mar / Jue", hora: "19:00", foto: "spinning", alt: "Clase grupal en bicicletas estáticas" },
  { clase: "Movilidad y Core", dia: "Viernes", hora: "07:00", foto: "movilidad", alt: "Grupo haciendo estiramientos sobre colchonetas" }
];

// Propio del gimnasio: NovaRuta (itinerario semanal de entrenamiento)
const ITINERARIO = [
  { id: "empuje", dia: "Lunes", ruta: "Empuje", foco: "Pecho, hombro y tríceps", duracion: "60 min" },
  { id: "traccion", dia: "Martes", ruta: "Tracción", foco: "Espalda y bíceps", duracion: "60 min" },
  { id: "pierna", dia: "Miércoles", ruta: "Pierna Forge", foco: "Cuádriceps, glúteo y femoral", duracion: "70 min" },
  { id: "cardio", dia: "Jueves", ruta: "Cardio y Core", foco: "Resistencia y abdomen", duracion: "45 min" },
  { id: "fullbody", dia: "Viernes", ruta: "Full Body", foco: "Circuito global", duracion: "60 min" },
  { id: "libre", dia: "Sábado", ruta: "Libre guiado", foco: "Técnica con entrenador", duracion: "50 min" }
];

// Propio del gimnasio: NovaPlato (planes nutricionales recomendados)
const NUTRICION = [
  { nombre: "NovaPlato Definición", objetivo: "Perder grasa", desc: "Déficit calórico moderado, proteína alta y carbohidratos en torno al entreno.", detalle: "Plato sugerido: pollo a la plancha, quinoa, vegetales asados y aguacate. Ajusta porciones con un profesional.", foto: "novaplato-definicion.png" },
  { nombre: "NovaPlato Volumen Limpio", objetivo: "Ganar músculo", desc: "Superávit leve, 5 comidas al día y refuerzo post-entreno.", detalle: "Plato sugerido: arroz integral, salmón, camote y ensalada fresca. Prioriza proteína, carbohidratos y descanso.", foto: "novaplato-volumen.png" },
  { nombre: "NovaPlato Mantenimiento", objetivo: "Equilibrio", desc: "Comidas balanceadas y flexibles para sostener tu rendimiento.", detalle: "Plato sugerido: tortilla de vegetales, pan integral, fruta y yogur. Mantén variedad durante la semana.", foto: "novaplato-mantenimiento.png" }
];

// Propio del gimnasio: NovaSupp (suplementación recomendada y vendida por el gym)
const SUPLEMENTOS = [
  { nombre: "Nova Whey Proteína", uso: "1 scoop post-entreno", precio: 34.90 },
  { nombre: "Nova Creatina Mono", uso: "5 g diarios", precio: 19.90 },
  { nombre: "Nova Pre-Forge", uso: "30 min antes de entrenar", precio: 24.50 },
  { nombre: "Nova Omega 3", uso: "2 cápsulas con comida", precio: 14.90 }
];

const GALERIA = [
  { t: "Sala de pesas", foto: "planes" }, { t: "Zona cardio", foto: "cardio" },
  { t: "Clase funcional", foto: "funcional" }, { t: "Spinning", foto: "spinning" },
  { t: "Zona de estiramiento", foto: "movilidad" }, { t: "Tienda NovaSupp", foto: "suplementos" }
];

function nombreValido(nombre) {
  return nombre.length >= 5 && nombre.length <= 100 &&
    /^\p{Lu}[\p{L}\p{M}’'-]{1,}(?:\s+\p{Lu}[\p{L}\p{M}’'-]{1,})+$/u.test(nombre);
}
function correoValido(correo) {
  return correo.length <= 120 && /^[A-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[A-Z0-9](?:[A-Z0-9-]{0,61}[A-Z0-9])?(?:\.[A-Z0-9](?:[A-Z0-9-]{0,61}[A-Z0-9])?)+$/i.test(correo);
}
function cedulaEcuatorianaValida(cedula) {
  if (!/^\d{10}$/.test(cedula)) return false;
  var provincia = Number(cedula.slice(0, 2));
  if (provincia < 1 || provincia > 24 || Number(cedula[2]) > 5) return false;
  var suma = 0;
  for (var i = 0; i < 9; i++) {
    var digito = Number(cedula[i]);
    if (i % 2 === 0) { digito *= 2; if (digito > 9) digito -= 9; }
    suma += digito;
  }
  return (10 - (suma % 10)) % 10 === Number(cedula[9]);
}
function razonSocialValida(razon) {
  return razon.length >= 3 && razon.length <= 100 && /^[\p{L}0-9][\p{L}0-9 .,&()’'-]*$/u.test(razon);
}
function direccionValida(direccion) {
  return direccion.length >= 8 && direccion.length <= 150 && /^[\p{L}0-9][\p{L}0-9 .,#º°\/-]*$/u.test(direccion) && /\d/.test(direccion);
}
function telefonoEcuatorianoValido(telefono) {
  return telefono === "" || /^(?:\+593[ -]?|0)\d(?:[ -]?\d){7,8}$/.test(telefono);
}
function aplicarMarca() {
  document.documentElement.style.setProperty("--c", CONFIG.color);
  document.querySelectorAll(".marca").forEach(function (el) { el.textContent = CONFIG.nombre; });
}
