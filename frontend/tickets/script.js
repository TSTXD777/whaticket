// Ajusta si tu carpeta no se llama "whaticket"
const API = "/whaticket/backend/tickets.php";

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("ticketForm");
  const clearBtn = document.getElementById("clearBtn");
  const refreshBtn = document.getElementById("refreshBtn");

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const categoria = document.getElementById("categoria").value.trim();
    const prioridad = document.getElementById("prioridad").value.trim();
    const descripcion = document.getElementById("descripcion").value.trim();

    // Validaciones frontend
    if (!categoria || !prioridad || descripcion === "") {
      alert("Todos los campos son obligatorios.");
      return;
    }
    // Permitir letras, números y signos comunes en español
    const invalid = /[^a-zA-Z0-9\s.,()\-_:\u00BF?\u00A1!áéíóúÁÉÍÓÚñÑ]/;
    if (invalid.test(descripcion)) {
      alert("La descripción contiene caracteres inválidos.");
      return;
    }

    // Mostrar loading
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Creando...';
    submitBtn.disabled = true;

    try {
      // Crear ticket
      await fetch(API, {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({ action: "create", categoria, prioridad, descripcion })
      });

      form.reset();
      cargarTickets();
    } catch (error) {
      alert("Error al crear el ticket. Intente nuevamente.");
    }

    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
  });

  clearBtn.addEventListener("click", () => form.reset());
  refreshBtn.addEventListener("click", () => cargarTickets());

  cargarTickets();
});

// Cargar y renderizar tickets
async function cargarTickets(){
  const res = await fetch(API + "?action=list");
  const tickets = await res.json();
  const cont = document.getElementById("tickets");
  cont.innerHTML = "";

  if (!tickets.length){
    cont.innerHTML = `<div class="alert alert-info">
      <i class="bi bi-info-circle me-2"></i>No hay tickets registrados aún.
    </div>`;
    return;
  }

  tickets.reverse().forEach(t => {
    const priorityClass = t.prioridad === "Urgente" ? "priority-alta" :
                         t.prioridad === "Alta" ? "priority-alta" :
                         t.prioridad === "Media" ? "priority-media" : "priority-baja";

    const statusClass = t.estado === "Pendiente" ? "status-pendiente" :
                       t.estado === "En Progreso" ? "status-progreso" :
                       t.estado === "Resuelto" ? "status-resuelto" : "status-cerrado";

    const statusText = t.estado === "En Progreso" ? "En Progreso" : t.estado;

    const card = document.createElement("div");
    card.className = "ticket-item";
    card.innerHTML = `
      <div class="d-flex justify-content-between align-items-start mb-2">
        <div>
          <h6 class="mb-1">
            <i class="bi bi-hash me-1 text-muted"></i>Ticket #${t.id}
            <span class="badge ${priorityClass} ms-2">${t.prioridad}</span>
          </h6>
          <small class="text-muted">
            <i class="bi bi-tag me-1"></i>${t.categoria} •
            <i class="bi bi-calendar me-1"></i>${new Date(t.created_at).toLocaleDateString('es-ES')}
          </small>
        </div>
        <span class="badge ${statusClass}">${statusText}</span>
      </div>

      <p class="mb-2">${escapeHtml(t.descripcion)}</p>

      <div class="comments-section mb-2" id="comments-${t.id}">
        ${Array.isArray(t.comentarios) && t.comentarios.length
          ? t.comentarios.map(c => `<div class="alert alert-light py-2 px-3 mb-1">
              <small><i class="bi bi-chat-quote me-1"></i>${escapeHtml(c)}</small>
            </div>`).join('')
          : ''
        }
      </div>

      <div class="d-flex gap-2 align-items-center">
        <input id="inputc-${t.id}" class="form-control form-control-sm"
               placeholder="Agregar comentario..." style="flex: 1;">
        <button data-id="${t.id}" class="btn btn-outline-primary btn-sm add-comment">
          <i class="bi bi-plus-circle me-1"></i>Comentar
        </button>
      </div>

      <div class="mt-2 d-flex gap-1 flex-wrap">
        ${t.estado !== "En Progreso" ?
          `<button class="btn btn-outline-warning btn-sm" data-id="${t.id}" data-action="set" data-value="En Progreso">
            <i class="bi bi-play-circle me-1"></i>En Proceso
          </button>` : ''
        }
        ${t.estado !== "Resuelto" ?
          `<button class="btn btn-outline-success btn-sm" data-id="${t.id}" data-action="set" data-value="Resuelto">
            <i class="bi bi-check-circle me-1"></i>Resolver
          </button>` : ''
        }
        ${t.estado !== "Cerrado" ?
          `<button class="btn btn-outline-secondary btn-sm" data-id="${t.id}" data-action="set" data-value="Cerrado">
            <i class="bi bi-x-circle me-1"></i>Cerrar
          </button>` : ''
        }
        ${t.estado !== "Pendiente" ?
          `<button class="btn btn-outline-info btn-sm" data-id="${t.id}" data-action="set" data-value="Pendiente">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Reabrir
          </button>` : ''
        }
      </div>
    `;

    cont.appendChild(card);
  });

  // Listeners para cambiar estado
  document.querySelectorAll('.controls button, .ticket-item button[data-action]').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      const id = Number(e.currentTarget.getAttribute('data-id'));
      const value = e.currentTarget.getAttribute('data-value');
      if (!value) return;

      await fetch(API, {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({ action: "update", id, field: "estado", value })
      });
      cargarTickets();
    });
  });

  // Listeners para comentarios
  document.querySelectorAll('.add-comment').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      const id = Number(e.currentTarget.getAttribute('data-id'));
      const input = document.getElementById(`inputc-${id}`);
      const text = input.value.trim();
      if (!text) {
        alert("Escribe un comentario.");
        return;
      }

      await fetch(API, {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({ action: "comment", id, comment: text })
      });
      input.value = "";
      cargarTickets();
    });
  });
}

// Escapar HTML
function escapeHtml(str){
  return String(str).replace(/[&<>\"'\/]/g, s => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#47;'
  }[s]));
}
