const API = "/whaticket/backend/tickets.php";

document.addEventListener("DOMContentLoaded", async () => {
  const res = await fetch(API + "?action=list");
  const tickets = await res.json();

  generarTabla(tickets);
  generarGrafico(tickets);
});

function generarTabla(tickets){
  const tbody = document.querySelector("#tablaTickets tbody");
  tbody.innerHTML = tickets.map(t => `
    <tr>
      <td>${t.id}</td>
      <td>${t.categoria}</td>
      <td>${t.prioridad}</td>
      <td>${t.estado}</td>
      <td>${t.descripcion}</td>
    </tr>
  `).join("");
}

function generarGrafico(tickets){
  const cont = document.getElementById("chart-categorias");

  const conteo = {};
  tickets.forEach(t => conteo[t.categoria] = (conteo[t.categoria] || 0) + 1);

  cont.innerHTML = Object.entries(conteo).map(([cat, count]) => `
    <div class="bar">
      <div class="bar-fill" style="width:${count * 40}px"></div>
      <span>${cat}: ${count}</span>
    </div>
  `).join("");
}