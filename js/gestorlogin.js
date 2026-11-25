window.addEventListener("DOMContentLoaded", function () {
  aplicarMarca();
  var formulario = document.getElementById("form-login");
  var usuario = document.getElementById("l-usuario");
  var recordar = document.getElementById("l-recordar");

  try {
    var recordado = localStorage.getItem("gm_recordar");
    if (recordado) {
      if (!usuario.value) usuario.value = recordado;
      recordar.checked = true;
    }
  } catch (error) {
    // El acceso sigue funcionando si el navegador bloquea el almacenamiento.
  }

  formulario.addEventListener("submit", function () {
    try {
      if (recordar.checked) localStorage.setItem("gm_recordar", usuario.value.trim());
      else localStorage.removeItem("gm_recordar");
    } catch (error) {
      // PHP procesa el formulario aunque no se pueda recordar el usuario.
    }
  });
});
