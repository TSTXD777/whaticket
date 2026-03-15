let usuariosData = [];

function escapeHtml(texto) {
    if (texto === null || texto === undefined) return "";
    return String(texto)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

async function cargarUsuarios() {
    const tabla = document.getElementById("tablaUsuarios");
    if (!tabla) return;

    try {
        let res = await fetch("./backend/registrar.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ nombre, correo, password, rol })
});
        usuariosData = usuarios;
        actualizarEstadisticas(usuarios);

        tabla.innerHTML = "";

        if (!Array.isArray(usuarios) || usuarios.length === 0) {
            tabla.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <i class="bi bi-info-circle text-muted" style="font-size: 2em;"></i>
                        <p class="text-muted mt-2 mb-0">No hay usuarios registrados aún.</p>
                    </td>
                </tr>
            `;
            return;
        }

        usuarios.forEach((u) => {
            const estadoTexto = Number(u.activo) === 1 ? "Activo" : "Inactivo";
            const estadoClass = Number(u.activo) === 1 ? "status-activo" : "status-inactivo";
            const rolTexto = u.rol ? u.rol.charAt(0).toUpperCase() + u.rol.slice(1) : "";
            const fechaRegistro = u.created_at
                ? new Date(u.created_at).toLocaleDateString('es-ES')
                : "Sin fecha";

            tabla.innerHTML += `
                <tr>
                    <td><strong>${escapeHtml(u.id)}</strong></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-person-circle text-primary me-2"></i>
                            ${escapeHtml(u.nombre)}
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-envelope text-secondary me-2"></i>
                            <small>${escapeHtml(u.correo)}</small>
                        </div>
                    </td>
                    <td>
                        <span class="role-badge">${escapeHtml(rolTexto)}</span>
                    </td>
                    <td>
                        <span class="badge ${estadoClass}">${estadoTexto}</span>
                    </td>
                    <td>
                        <small class="text-muted">
                            <i class="bi bi-calendar me-1"></i>${escapeHtml(fechaRegistro)}
                        </small>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button onclick="editarUsuario(${u.id})" class="btn btn-custom" title="Editar usuario">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button onclick="desactivarUsuario(${u.id})" class="btn btn-danger-custom" title="Desactivar usuario">
                                <i class="bi bi-person-dash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
    } catch (error) {
        tabla.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4">
                    <i class="bi bi-exclamation-triangle text-danger" style="font-size: 2em;"></i>
                    <p class="text-danger mt-2 mb-0">Error al cargar usuarios. Intente nuevamente.</p>
                </td>
            </tr>
        `;
    }
}

function actualizarEstadisticas(usuarios) {
    const totalUsers = usuarios.length;
    const activeUsers = usuarios.filter(u => Number(u.activo) === 1).length;
    const adminUsers = usuarios.filter(u => u.rol === 'admin').length;
    const technicianUsers = usuarios.filter(u => u.rol === 'tecnico').length;

    const totalUsersEl = document.getElementById("totalUsers");
    const activeUsersEl = document.getElementById("activeUsers");
    const adminUsersEl = document.getElementById("adminUsers");
    const technicianUsersEl = document.getElementById("technicianUsers");

    if (totalUsersEl) totalUsersEl.textContent = totalUsers;
    if (activeUsersEl) activeUsersEl.textContent = activeUsers;
    if (adminUsersEl) adminUsersEl.textContent = adminUsers;
    if (technicianUsersEl) technicianUsersEl.textContent = technicianUsers;
}

function editarUsuario(id) {
    window.location.href = `editar.html?id=${id}`;
}

async function desactivarUsuario(id) {
    const usuario = usuariosData.find(u => u.id == id);
    if (!usuario) {
        alert("Usuario no encontrado");
        return;
    }

    const accion = Number(usuario.activo) === 1 ? "desactivar" : "activar";
    const mensaje = `¿Seguro que desea ${accion} al usuario "${usuario.nombre}"?`;

    if (!confirm(mensaje)) return;

    try {
        const res = await fetch("../backend/desactivar.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id })
        });

        const data = await res.json();

        if (data.ok) {
            alert(data.mensaje);
            cargarUsuarios();
        } else {
            alert(data.mensaje || "Error al procesar la solicitud");
        }
    } catch (error) {
        alert("Error de conexión. Intente nuevamente.");
    }
}

document.getElementById("formRegistro")?.addEventListener("submit", async (e) => {
    e.preventDefault();

    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    const alertDiv = document.getElementById("respuesta");

    submitBtn.innerHTML = 'Registrando...';
    submitBtn.disabled = true;
    alertDiv.style.display = "none";

    let nombre = document.getElementById("nombre").value.trim();
    let correo = document.getElementById("correo").value.trim();
    let password = document.getElementById("password").value.trim();
    let rol = document.getElementById("rol").value;

    try {
        let res = await fetch("./backend/registrar.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ nombre, correo, password, rol })
        });

        let data = await res.json();

        if (data.ok) {
            alertDiv.innerHTML = `<div class="alert alert-success">${data.mensaje}</div>`;
            alertDiv.style.display = "block";
            document.getElementById("formRegistro").reset();
        } else {
            alertDiv.innerHTML = `<div class="alert alert-danger">${data.mensaje}</div>`;
            alertDiv.style.display = "block";
        }

    } catch (error) {
        alertDiv.innerHTML = `<div class="alert alert-danger">Error de conexión</div>`;
        alertDiv.style.display = "block";
    }

    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
});

cargarUsuarios();
