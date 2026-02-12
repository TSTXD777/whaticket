const API_CAT = "../backend/categorias.php";
const API_CFG = "../backend/config.php";

document.addEventListener("DOMContentLoaded", () => {
  cargarCategorias();
  cargarConfig();

  document.getElementById("addCat").addEventListener("click", agregarCategoria);
  document.getElementById("saveChatbot").addEventListener("click", guardarConfig);
});

async function cargarCategorias(){
  const res = await fetch(API_CAT);
  const categorias = await res.json();
  const ul = document.getElementById("listaCategorias");
  ul.innerHTML = "";

  categorias.forEach(cat => {
    const li = document.createElement("li");
    li.innerHTML = `
      ${cat}
      <button onclick="eliminarCategoria('${cat}')" class="btn-sm danger">Eliminar</button>
    `;
    ul.appendChild(li);
  });
}

async function agregarCategoria(){
  const nombre = document.getElementById("catInput").value.trim();
  if (!nombre){ alert("Ingrese un nombre"); return; }

  await fetch(API_CAT, {
    method: "POST",
    headers: {"Content-Type": "application/json"},
    body: JSON.stringify({ action: "add", nombre })
  });
  document.getElementById("catInput").value = "";
  cargarCategorias();
}

async function eliminarCategoria(nombre){
  await fetch(API_CAT, {
    method: "POST",
    headers: {"Content-Type": "application/json"},
    body: JSON.stringify({ action: "delete", nombre })
  });
  cargarCategorias();
}

async function cargarConfig(){
  const res = await fetch(API_CFG);
  const cfg = await res.json();
  document.getElementById("chatbotMsg").value = cfg.mensaje;
}

async function guardarConfig(){
  const mensaje = document.getElementById("chatbotMsg").value.trim();
  await fetch(API_CFG, {
    method: "POST",
    headers: {"Content-Type": "application/json"},
    body: JSON.stringify({ mensaje })
  });
  alert("Configuración guardada");
}