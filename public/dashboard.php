<?php
// Iniciar sesión de forma segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Verificar autenticación - con manejo de errores
$admin = $_SESSION['admin'] ?? null;

if (empty($admin)) {
    header('Location: login.php');
    exit;
}

// Conectar a BD
try {
    $pdo = getPDO();
} catch (Exception $e) {
    die('Error de conexión a base de datos');
}

// Obtener estadísticas
try {
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM pcs');
    $totalPcs = $stmt->fetch()['total'];

    $stmt = $pdo->query('SELECT COUNT(*) as total FROM usuarios_pc');
    $totalUsuarios = $stmt->fetch()['total'];
} catch (PDOException $e) {
    $totalPcs = 0;
    $totalUsuarios = 0;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - SIS Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .sidebar-container {
            position: relative;
            transition: all 0.3s ease;
        }
        .sidebar {
            background-color: #fff;
            border-right: 1px solid #dee2e6;
            padding: 2rem 1rem;
            min-height: calc(100vh - 56px);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        .sidebar.collapsed {
            padding: 2rem 0.5rem;
        }
        .sidebar .nav-link {
            color: #495057;
            border-radius: 5px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .sidebar.collapsed .nav-link {
            justify-content: center;
        }
        .sidebar.collapsed .nav-link-text {
            display: none;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #667eea;
            color: white;
        }
        .sidebar-toggle {
            position: absolute;
            top: 10px;
            right: -15px;
            z-index: 1000;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .sidebar-toggle:hover {
            background: #5568d3;
            transform: scale(1.1);
        }
        .main-content {
            padding: 2rem;
            transition: all 0.3s ease;
        }
        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        .stat-card {
            text-align: center;
            padding: 2rem;
            color: white;
            border-radius: 8px;
        }
        .stat-card.pcs {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stat-card.usuarios {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        /* Responsive: ocultar toggle en móvil */
        @media (max-width: 768px) {
            .sidebar-toggle {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-shield-lock"></i> SIS Password
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-light">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($admin['nombre']); ?>
                </span>
                <button class="btn btn-outline-light btn-sm" id="logoutBtn">
                    <i class="bi bi-box-arrow-right"></i> Cerrar sesión
                </button>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar-container" id="sidebarContainer">
                <button class="sidebar-toggle" id="sidebarToggle" title="Plegar/Desplegar menú">
                    <i class="bi bi-chevron-left" id="toggleIcon"></i>
                </button>
                <div class="sidebar" id="sidebar">
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="#" data-page="inicio">
                            <i class="bi bi-house"></i>
                            <span class="nav-link-text">Inicio</span>
                        </a>
                        <a class="nav-link" href="#" data-page="pcs">
                            <i class="bi bi-pc-display"></i>
                            <span class="nav-link-text">Gestionar PCs</span>
                        </a>
                        <a class="nav-link" href="#" data-page="usuarios">
                            <i class="bi bi-people"></i>
                            <span class="nav-link-text">Gestionar Usuarios</span>
                        </a>
                        <a class="nav-link" href="administradores.php">
                            <i class="bi bi-shield-check"></i>
                            <span class="nav-link-text">Administradores</span>
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content" id="mainContent">
                <div id="content"></div>
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar PC -->
    <div class="modal fade" id="modalPC" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPCTitle">Nuevo PC</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formPC">
                        <input type="hidden" id="pcId" value="">
                        <div class="mb-3">
                            <label class="form-label">Oficina</label>
                            <input type="text" class="form-control" id="pcOficina" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre del PC</label>
                            <input type="text" class="form-control" id="pcNombre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción (opcional)</label>
                            <textarea class="form-control" id="pcDescripcion" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnGuardarPC">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar Usuario -->
    <div class="modal fade" id="modalUsuario" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalUsuarioTitle">Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formUsuario">
                        <input type="hidden" id="usuarioId" value="">
                        <div class="mb-3">
                            <label class="form-label">PC *</label>
                            <select class="form-select" id="usuarioPcId" required>
                                <option value="">Seleccionar PC</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Operario *</label>
                            <input type="text" class="form-control" id="usuarioOperario" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre de Usuario *</label>
                            <input type="text" class="form-control" id="usuarioNombreUsuario" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contraseña *</label>
                            <input type="text" class="form-control" id="usuarioPassword" required>
                        </div>
                        <hr>
                        <h6>Acceso Remoto (opcional)</h6>
                        <div class="mb-3">
                            <label class="form-label">ID Control Remoto</label>
                            <input type="text" class="form-control" id="usuarioIdControl">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contraseña Control Remoto</label>
                            <input type="text" class="form-control" id="usuarioPwControl">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notas</label>
                            <textarea class="form-control" id="usuarioNotas" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnGuardarUsuario">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const BASE_API = '../api';
        let modalPC = new bootstrap.Modal('#modalPC');
        let modalUsuario = new bootstrap.Modal('#modalUsuario');
        let pcsCache = [];
        let usuariosCache = [];

        // ===== TOGGLE SIDEBAR =====
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarContainer = document.getElementById('sidebarContainer');
        const mainContent = document.getElementById('mainContent');
        const toggleIcon = document.getElementById('toggleIcon');

        // Cargar estado del sidebar desde localStorage
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (sidebarCollapsed) {
            sidebar.classList.add('collapsed');
            sidebarContainer.classList.remove('col-md-3', 'col-lg-2');
            sidebarContainer.classList.add('col-md-1', 'col-lg-1');
            mainContent.classList.remove('col-md-9', 'col-lg-10');
            mainContent.classList.add('col-md-11', 'col-lg-11');
            toggleIcon.className = 'bi bi-chevron-right';
        }

        sidebarToggle.addEventListener('click', () => {
            const isCollapsed = sidebar.classList.toggle('collapsed');
            
            if (isCollapsed) {
                // Contraer
                sidebarContainer.classList.remove('col-md-3', 'col-lg-2');
                sidebarContainer.classList.add('col-md-1', 'col-lg-1');
                mainContent.classList.remove('col-md-9', 'col-lg-10');
                mainContent.classList.add('col-md-11', 'col-lg-11');
                toggleIcon.className = 'bi bi-chevron-right';
                localStorage.setItem('sidebarCollapsed', 'true');
            } else {
                // Expandir
                sidebarContainer.classList.remove('col-md-1', 'col-lg-1');
                sidebarContainer.classList.add('col-md-3', 'col-lg-2');
                mainContent.classList.remove('col-md-11', 'col-lg-11');
                mainContent.classList.add('col-md-9', 'col-lg-10');
                toggleIcon.className = 'bi bi-chevron-left';
                localStorage.setItem('sidebarCollapsed', 'false');
            }
        });

        // ===== NAVEGACIÓN =====
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                // Solo prevenir default si tiene data-page (navegación interna)
                if (link.dataset.page) {
                    e.preventDefault();
                    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                    link.classList.add('active');
                    const page = link.dataset.page;
                    cargarPagina(page);
                }
                // Si no tiene data-page (como administradores.php), permitir navegación normal
            });
        });

        async function cargarPagina(page) {
            const content = document.getElementById('content');
            
            try {
                if (page === 'inicio') {
                    content.innerHTML = `
                        <h2>Bienvenido</h2>
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <div class="stat-card pcs">
                                    <h3>${<?php echo $totalPcs; ?>}</h3>
                                    <p>Computadoras (PCs)</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="stat-card usuarios">
                                    <h3>${<?php echo $totalUsuarios; ?>}</h3>
                                    <p>Usuarios Registrados</p>
                                </div>
                            </div>
                        </div>
                    `;
                } else if (page === 'pcs') {
                    await cargarPCs();
                } else if (page === 'usuarios') {
                    await cargarUsuarios();
                }
            } catch (error) {
                content.innerHTML = `<div class="alert alert-danger">Error al cargar: ${error.message}</div>`;
            }
        }

        // ===== GESTIÓN DE PCs =====
        async function cargarPCs() {
            const content = document.getElementById('content');
            content.innerHTML = '<p class="text-muted">Cargando...</p>';

            try {
                const response = await fetch(BASE_API + '/pcs.php', {
                    credentials: 'same-origin'
                });

                if (response.status === 401) {
                    window.location.href = 'login.php';
                    return;
                }

                const data = await response.json();
                pcsCache = data.pcs || [];

                let html = `
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2>Gestión de PCs</h2>
                        <button class="btn btn-success" id="btnNuevoPC">
                            <i class="bi bi-plus-circle"></i> Nuevo PC
                        </button>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control" id="searchPC" placeholder="Buscar por oficina o nombre del PC...">
                                <button class="btn btn-outline-secondary" type="button" id="btnLimpiarPC">
                                    <i class="bi bi-x"></i> Limpiar
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                if (pcsCache.length === 0) {
                    html += '<div class="alert alert-info">No hay PCs registrados</div>';
                } else {
                    html += `
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="tablaPCs">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Oficina</th>
                                        <th>Nombre PC</th>
                                        <th>Usuarios</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="bodyPCs">
                    `;

                    pcsCache.forEach(pc => {
                        html += `
                            <tr class="fila-pc" data-oficina="${escapeHtml(pc.oficina).toLowerCase()}" data-nombre="${escapeHtml(pc.nombre).toLowerCase()}">
                                <td>${escapeHtml(pc.oficina)}</td>
                                <td>${escapeHtml(pc.nombre)}</td>
                                <td><span class="badge bg-info">${pc.cantidad_usuarios}</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editarPC(${pc.id})">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="eliminarPC(${pc.id})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });

                    html += `
                                </tbody>
                            </table>
                        </div>
                    `;
                }

                content.innerHTML = html;

                // Agregar funcionalidad de búsqueda para PCs
                const searchPC = document.getElementById('searchPC');
                const btnLimpiarPC = document.getElementById('btnLimpiarPC');
                
                if (searchPC) {
                    searchPC.addEventListener('keyup', () => {
                        const termino = searchPC.value.toLowerCase();
                        document.querySelectorAll('.fila-pc').forEach(fila => {
                            const oficina = fila.dataset.oficina;
                            const nombre = fila.dataset.nombre;
                            fila.style.display = (oficina.includes(termino) || nombre.includes(termino)) ? '' : 'none';
                        });
                    });
                }

                if (btnLimpiarPC) {
                    btnLimpiarPC.addEventListener('click', () => {
                        searchPC.value = '';
                        document.querySelectorAll('.fila-pc').forEach(fila => {
                            fila.style.display = '';
                        });
                        searchPC.focus();
                    });
                }

                document.getElementById('btnNuevoPC').addEventListener('click', () => {
                    limpiarFormPC();
                    document.getElementById('modalPCTitle').textContent = 'Nuevo PC';
                    modalPC.show();
                });

            } catch (error) {
                content.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
            }
        }

        function limpiarFormPC() {
            document.getElementById('pcId').value = '';
            document.getElementById('pcOficina').value = '';
            document.getElementById('pcNombre').value = '';
            document.getElementById('pcDescripcion').value = '';
        }

        function editarPC(id) {
            const pc = pcsCache.find(p => p.id === id);
            if (!pc) return;

            document.getElementById('pcId').value = pc.id;
            document.getElementById('pcOficina').value = pc.oficina;
            document.getElementById('pcNombre').value = pc.nombre;
            document.getElementById('pcDescripcion').value = pc.descripcion || '';
            document.getElementById('modalPCTitle').textContent = 'Editar PC';
            modalPC.show();
        }

        async function eliminarPC(id) {
            if (!confirm('¿Eliminar este PC y todos sus usuarios?')) return;

            try {
                const response = await fetch(BASE_API + '/pcs.php', {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    body: new URLSearchParams({ id })
                });

                const data = await response.json();
                alert(data.mensaje || data.error);
                cargarPCs();
            } catch (error) {
                alert('Error al eliminar: ' + error.message);
            }
        }

        document.getElementById('btnGuardarPC').addEventListener('click', async () => {
            const id = document.getElementById('pcId').value;
            const oficina = document.getElementById('pcOficina').value;
            const nombre = document.getElementById('pcNombre').value;
            const descripcion = document.getElementById('pcDescripcion').value;

            if (!oficina || !nombre) {
                alert('Completa los campos requeridos');
                return;
            }

            try {
                if (id) {
                    // Actualizar
                    const response = await fetch(BASE_API + '/pcs.php', {
                        method: 'PUT',
                        credentials: 'same-origin',
                        body: new URLSearchParams({ id, oficina, nombre, descripcion })
                    });
                    const data = await response.json();
                    alert(data.mensaje || data.error);
                } else {
                    // Crear
                    const response = await fetch(BASE_API + '/pcs.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ oficina, nombre, descripcion })
                    });
                    const data = await response.json();
                    alert(data.mensaje || data.error);
                }
                modalPC.hide();
                cargarPCs();
            } catch (error) {
                alert('Error: ' + error.message);
            }
        });

        // ===== GESTIÓN DE USUARIOS =====
        async function cargarUsuarios() {
            const content = document.getElementById('content');
            content.innerHTML = '<p class="text-muted">Cargando...</p>';

            try {
                const [respPCs, respUsuarios] = await Promise.all([
                    fetch(BASE_API + '/pcs.php', { credentials: 'same-origin' }),
                    fetch(BASE_API + '/usuarios.php', { credentials: 'same-origin' })
                ]);

                if (respPCs.status === 401 || respUsuarios.status === 401) {
                    window.location.href = 'login.php';
                    return;
                }

                const dataPCs = await respPCs.json();
                const dataUsuarios = await respUsuarios.json();

                pcsCache = dataPCs.pcs || [];
                usuariosCache = dataUsuarios.usuarios || [];

                // Llenar select de PCs
                let optionsPCs = '<option value="">Seleccionar PC</option>';
                pcsCache.forEach(pc => {
                    optionsPCs += `<option value="${pc.id}">${pc.oficina} - ${pc.nombre}</option>`;
                });
                document.getElementById('usuarioPcId').innerHTML = optionsPCs;

                let html = `
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2>Gestión de Usuarios</h2>
                        <button class="btn btn-success" id="btnNuevoUsuario">
                            <i class="bi bi-plus-circle"></i> Nuevo Usuario
                        </button>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control" id="searchUsuario" placeholder="Buscar por oficina, PC, operario, usuario...">
                                <button class="btn btn-outline-secondary" type="button" id="btnLimpiarUsuario">
                                    <i class="bi bi-x"></i> Limpiar
                                </button>
                            </div>
                            <small class="text-muted d-block mt-2">
                                💡 Búsqueda en: Oficina • PC • Operario • Usuario
                            </small>
                        </div>
                    </div>
                `;

                if (usuariosCache.length === 0) {
                    html += '<div class="alert alert-info">No hay usuarios registrados</div>';
                } else {
                    html += `
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm" id="tablaUsuarios">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Oficina</th>
                                        <th>PC</th>
                                        <th>Operario</th>
                                        <th>Usuario</th>
                                        <th>Contraseña</th>
                                        <th>Control Remoto</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="bodyUsuarios">
                    `;

                    usuariosCache.forEach(u => {
                        html += `
                            <tr class="fila-usuario" data-oficina="${escapeHtml(u.oficina).toLowerCase()}" data-pc="${escapeHtml(u.pc_nombre).toLowerCase()}" data-operario="${escapeHtml(u.operario).toLowerCase()}" data-usuario="${escapeHtml(u.nombre_usuario).toLowerCase()}">
                                <td>${escapeHtml(u.oficina)}</td>
                                <td>${escapeHtml(u.pc_nombre)}</td>
                                <td>${escapeHtml(u.operario)}</td>
                                <td><code>${escapeHtml(u.nombre_usuario)}</code></td>
                                <td><code>${escapeHtml(u.password)}</code></td>
                                <td>${u.id_control_remoto ? `${escapeHtml(u.id_control_remoto)}` : '-'}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editarUsuario(${u.id})">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="eliminarUsuario(${u.id})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });

                    html += `
                                </tbody>
                            </table>
                        </div>
                    `;
                }

                content.innerHTML = html;

                // Agregar funcionalidad de búsqueda para Usuarios
                const searchUsuario = document.getElementById('searchUsuario');
                const btnLimpiarUsuario = document.getElementById('btnLimpiarUsuario');

                if (searchUsuario) {
                    searchUsuario.addEventListener('keyup', () => {
                        const termino = searchUsuario.value.toLowerCase();
                        document.querySelectorAll('.fila-usuario').forEach(fila => {
                            const oficina = fila.dataset.oficina;
                            const pc = fila.dataset.pc;
                            const operario = fila.dataset.operario;
                            const usuario = fila.dataset.usuario;
                            fila.style.display = (oficina.includes(termino) || pc.includes(termino) || operario.includes(termino) || usuario.includes(termino)) ? '' : 'none';
                        });
                    });
                }

                if (btnLimpiarUsuario) {
                    btnLimpiarUsuario.addEventListener('click', () => {
                        searchUsuario.value = '';
                        document.querySelectorAll('.fila-usuario').forEach(fila => {
                            fila.style.display = '';
                        });
                        searchUsuario.focus();
                    });
                }

                document.getElementById('btnNuevoUsuario').addEventListener('click', () => {
                    limpiarFormUsuario();
                    document.getElementById('modalUsuarioTitle').textContent = 'Nuevo Usuario';
                    modalUsuario.show();
                });

            } catch (error) {
                content.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
            }
        }

        function limpiarFormUsuario() {
            document.getElementById('usuarioId').value = '';
            document.getElementById('usuarioPcId').value = '';
            document.getElementById('usuarioOperario').value = '';
            document.getElementById('usuarioNombreUsuario').value = '';
            document.getElementById('usuarioPassword').value = '';
            document.getElementById('usuarioIdControl').value = '';
            document.getElementById('usuarioPwControl').value = '';
            document.getElementById('usuarioNotas').value = '';
        }

        function editarUsuario(id) {
            const usuario = usuariosCache.find(u => u.id === id);
            if (!usuario) return;

            document.getElementById('usuarioId').value = usuario.id;
            document.getElementById('usuarioPcId').value = usuario.pc_id;
            document.getElementById('usuarioOperario').value = usuario.operario;
            document.getElementById('usuarioNombreUsuario').value = usuario.nombre_usuario;
            document.getElementById('usuarioPassword').value = usuario.password;
            document.getElementById('usuarioIdControl').value = usuario.id_control_remoto || '';
            document.getElementById('usuarioPwControl').value = usuario.password_control_remoto || '';
            document.getElementById('usuarioNotas').value = usuario.notas || '';
            document.getElementById('modalUsuarioTitle').textContent = 'Editar Usuario';
            modalUsuario.show();
        }

        async function eliminarUsuario(id) {
            if (!confirm('¿Eliminar este usuario?')) return;

            try {
                const response = await fetch(BASE_API + '/usuarios.php', {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    body: new URLSearchParams({ id })
                });

                const data = await response.json();
                alert(data.mensaje || data.error);
                cargarUsuarios();
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }

        document.getElementById('btnGuardarUsuario').addEventListener('click', async () => {
            const id = document.getElementById('usuarioId').value;
            const pc_id = document.getElementById('usuarioPcId').value;
            const operario = document.getElementById('usuarioOperario').value;
            const nombre_usuario = document.getElementById('usuarioNombreUsuario').value;
            const password = document.getElementById('usuarioPassword').value;
            const id_control_remoto = document.getElementById('usuarioIdControl').value;
            const password_control_remoto = document.getElementById('usuarioPwControl').value;
            const notas = document.getElementById('usuarioNotas').value;

            if (!pc_id || !operario || !nombre_usuario || !password) {
                alert('Completa los campos requeridos (*)');
                return;
            }

            try {
                if (id) {
                    // Actualizar
                    const response = await fetch(BASE_API + '/usuarios.php', {
                        method: 'PUT',
                        credentials: 'same-origin',
                        body: new URLSearchParams({
                            id, operario, nombre_usuario, password, 
                            id_control_remoto, password_control_remoto, notas
                        })
                    });
                    const data = await response.json();
                    alert(data.mensaje || data.error);
                } else {
                    // Crear
                    const response = await fetch(BASE_API + '/usuarios.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            pc_id, operario, nombre_usuario, password,
                            id_control_remoto, password_control_remoto, notas
                        })
                    });
                    const data = await response.json();
                    alert(data.mensaje || data.error);
                }
                modalUsuario.hide();
                cargarUsuarios();
            } catch (error) {
                alert('Error: ' + error.message);
            }
        });

        // ===== LOGOUT =====
        document.getElementById('logoutBtn').addEventListener('click', async () => {
            await fetch(BASE_API + '/auth.php?action=logout', {
                method: 'POST',
                credentials: 'same-origin'
            });
            window.location.href = 'login.php';
        });

        // ===== FUNCIONES AUXILIARES =====
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Cargar página inicial
        cargarPagina('inicio');
    </script>
</body>
</html>
