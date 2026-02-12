// Estadísticas globales
let usuariosData = [];

// Cargar tabla de usuarios
async function cargarUsuarios() {
    try {
        let res = await fetch("../../backend/obtener_usuarios.php");
        let usuarios = await res.json();

        usuariosData = usuarios; // Guardar para estadísticas
        actualizarEstadisticas(usuarios);

        let tabla = document.getElementById("tablaUsuarios");
        tabla.innerHTML = "";

        if (usuarios.length === 0) {
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
            const estadoTexto = u.activo ? "Activo" : "Inactivo";
            const estadoClass = u.activo ? "status-activo" : "status-inactivo";
            const rolTexto = u.rol.charAt(0).toUpperCase() + u.rol.slice(1);
            const fechaRegistro = new Date(u.created_at).toLocaleDateString('es-ES');

            tabla.innerHTML += `
                <tr>
                    <td><strong>${u.id}</strong></td>
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
                        <span class="role-badge">${rolTexto}</span>
                    </td>
                    <td>
                        <span class="badge ${estadoClass}">${estadoTexto}</span>
                    </td>
                    <td>
                        <small class="text-muted">
                            <i class="bi bi-calendar me-1"></i>${fechaRegistro}
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
        console.error("Error al cargar usuarios:", error);
        const tabla = document.getElementById("tablaUsuarios");
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

// Actualizar estadísticas
function actualizarEstadisticas(usuarios) {
    const totalUsers = usuarios.length;
    const activeUsers = usuarios.filter(u => u.activo).length;
    const adminUsers = usuarios.filter(u => u.rol === 'admin').length;
    const technicianUsers = usuarios.filter(u => u.rol === 'tecnico').length;

    // Actualizar contadores si existen
    const totalUsersEl = document.getElementById("totalUsers");
    const activeUsersEl = document.getElementById("activeUsers");
    const adminUsersEl = document.getElementById("adminUsers");
    const technicianUsersEl = document.getElementById("technicianUsers");

    if (totalUsersEl) totalUsersEl.textContent = totalUsers;
    if (activeUsersEl) activeUsersEl.textContent = activeUsers;
    if (adminUsersEl) adminUsersEl.textContent = adminUsers;
    if (technicianUsersEl) technicianUsersEl.textContent = technicianUsers;
}

// Ir a editar.html con ID
function editarUsuario(id) {
    window.location.href = `editar.html?id=${id}`;
}

// Desactivar usuario
async function desactivarUsuario(id) {
    const usuario = usuariosData.find(u => u.id == id);
    if (!usuario) {
        alert("Usuario no encontrado");
        return;
    }

    const accion = usuario.activo ? "desactivar" : "activar";
    const mensaje = `¿Seguro que desea ${accion} al usuario "${usuario.nombre}"?`;

    if (!confirm(mensaje)) return;

    try {
        const res = await fetch("../../backend/desactivar.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id })
        });

        const data = await res.json();
        if (data.ok) {
            alert(data.mensaje);
            cargarUsuarios(); // refrescar tabla
        } else {
            alert(data.mensaje || "Error al procesar la solicitud");
        }
    } catch (error) {
        alert("Error de conexión. Intente nuevamente.");
    }
}

// Manejar registro de usuario
document.getElementById("formRegistro")?.addEventListener("submit", async (e) => {
    e.preventDefault();

    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Registrando...';
    submitBtn.disabled = true;

    let nombre = document.getElementById("nombre").value;
    let correo = document.getElementById("correo").value;
    let password = document.getElementById("password").value;
    let rol = document.getElementById("rol").value;

    try {
        let res = await fetch("../../backend/registrar.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ nombre, correo, password, rol })
        });

        let data = await res.json();

        const alertDiv = document.getElementById("respuesta");
        if (data.ok) {
            alertDiv.innerHTML = `<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>${data.mensaje}</div>`;
            document.getElementById("formRegistro").reset();
        } else {
            alertDiv.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${data.mensaje}</div>`;
        }
        alertDiv.style.display = "block";

    } catch (error) {
        const alertDiv = document.getElementById("respuesta");
        alertDiv.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Error de conexión. Intente nuevamente.</div>`;
        alertDiv.style.display = "block";
    }

    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
});

// Inicializar tabla
cargarUsuarios();
