const API = "/whaticket/backend/tickets.php";

document.addEventListener("DOMContentLoaded", async () => {
  const res = await fetch(API + "?action=list");
  const tickets = await res.json();

  generarEstadisticas(tickets);
  generarTabla(tickets);
  generarGraficoCategorias(tickets);
  generarGraficoPrioridad(tickets);
});


/* =========================
   ESTADISTICAS
=========================*/
function generarEstadisticas(tickets){

  const total = tickets.length;

  const pendientes = tickets.filter(t => t.estado === "Pendiente").length;
  const progreso = tickets.filter(t => t.estado === "En Progreso").length;
  const resueltos = tickets.filter(t => t.estado === "Resuelto").length;

  document.getElementById("totalTickets").textContent = total;
  document.getElementById("pendingTickets").textContent = pendientes;
  document.getElementById("inProgressTickets").textContent = progreso;
  document.getElementById("resolvedTickets").textContent = resueltos;

}


/* =========================
   TABLA
=========================*/
function generarTabla(tickets){

  const tbody = document.querySelector("#tablaTickets tbody");

  tbody.innerHTML = tickets.map(t => `
    <tr>
      <td>#${t.id}</td>
      <td>${t.categoria}</td>

      <td>
        <span class="priority-badge priority-${t.prioridad.toLowerCase()}">
          ${t.prioridad}
        </span>
      </td>

      <td>
        <span class="status-badge status-${estadoClass(t.estado)}">
          ${t.estado}
        </span>
      </td>

      <td>${new Date(t.created_at).toLocaleDateString()}</td>

      <td>${t.descripcion}</td>
    </tr>
  `).join("");

}


function estadoClass(estado){

  if(estado === "Pendiente") return "pendiente";
  if(estado === "En Progreso") return "progreso";
  if(estado === "Resuelto") return "resuelto";

  return "cerrado";
}



/* =========================
   GRAFICO POR CATEGORIA
=========================*/
function generarGraficoCategorias(tickets){

  const conteo = {};

  tickets.forEach(t=>{
    conteo[t.categoria] = (conteo[t.categoria] || 0) + 1;
  });

  const ctx = document.getElementById("categoriesChart");

  new Chart(ctx,{
    type:'pie',
    data:{
      labels:Object.keys(conteo),
      datasets:[{
        data:Object.values(conteo),
        backgroundColor:[
          "#3b82f6",
          "#10b981",
          "#f59e0b",
          "#ef4444",
          "#8b5cf6"
        ]
      }]
    }
  });

}


/* =========================
   GRAFICO POR PRIORIDAD
=========================*/
function generarGraficoPrioridad(tickets){

  const conteo = {};

  tickets.forEach(t=>{
    conteo[t.prioridad] = (conteo[t.prioridad] || 0) + 1;
  });

  const ctx = document.getElementById("prioritiesChart");

  new Chart(ctx,{
    type:'doughnut',
    data:{
      labels:Object.keys(conteo),
      datasets:[{
        data:Object.values(conteo),
        backgroundColor:[
          "#22c55e",
          "#eab308",
          "#ef4444",
          "#7c3aed"
        ]
      }]
    }
  });

}