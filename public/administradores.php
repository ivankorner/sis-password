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
    // Si no hay sesión, redirigir a login
    header('Location: login.php');
    exit;
}

// Conectar a BD
try {
    $pdo = getPDO();
} catch (Exception $e) {
    die('Error de conexión a base de datos');
}

// Obtener lista de administradores
try {
    $stmt = $pdo->query('SELECT id, email, nombre, activo, fecha_creacion FROM administradores ORDER BY fecha_creacion DESC');
    $administradores = $stmt->fetchAll();
} catch (PDOException $e) {
    $administradores = [];
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administradores - SIS Password</title>
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
        .sidebar {
            background-color: #fff;
            border-right: 1px solid #dee2e6;
            padding: 2rem 1rem;
        }
        .sidebar .nav-link {
            color: #495057;
            margin-bottom: 0.5rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #f0f0f0;
            color: #667eea;
        }
        .main-content {
            padding: 2rem;
        }
        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }
        .btn-primary {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, #5568d3 0%, #6a3f8f 100%);
            border: none;
        }
        .status-badge {
            display: inline-block;
            padding: 0.35rem 0.65rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-activo {
            background-color: #d4edda;
            color: #155724;
        }
        .status-inactivo {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="bi bi-shield-lock"></i> SIS Password
            </span>
            <div class="ms-auto">
                <span class="text-white me-3">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($admin['nombre']); ?>
                </span>
                <button class="btn btn-outline-light btn-sm" onclick="logout()">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </button>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-house"></i> Inicio
                    </a>
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-pc-display"></i> Gestionar PCs
                    </a>
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-people"></i> Gestionar Usuarios
                    </a>
                    <a class="nav-link active" href="administradores.php">
                        <i class="bi bi-shield-check"></i> Administradores
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-shield-check"></i> Gestionar Administradores</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdmin">
                        <i class="bi bi-plus-circle"></i> Nuevo Administrador
                    </button>
                </div>

                <div id="alertContainer"></div>

                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Email</th>
                                    <th>Nombre</th>
                                    <th>Estado</th>
                                    <th>Fecha de Creación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="adminTableBody">
                                <?php if (empty($administradores)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            No hay administradores registrados
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($administradores as $adm): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($adm['email']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($adm['nombre']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $adm['activo'] ? 'status-activo' : 'status-inactivo'; ?>">
                                                <?php echo $adm['activo'] ? 'Activo' : 'Inactivo'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($adm['fecha_creacion'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="editarAdmin(<?php echo htmlspecialchars(json_encode($adm)); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-<?php echo $adm['activo'] ? 'warning' : 'success'; ?>" onclick="toggleAdmin(<?php echo $adm['id']; ?>, <?php echo $adm['activo'] ? 0 : 1; ?>)">
                                                <i class="bi bi-<?php echo $adm['activo'] ? 'lock' : 'unlock'; ?>"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="eliminarAdmin(<?php echo $adm['id']; ?>, '<?php echo htmlspecialchars($adm['email']); ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar Administrador -->
    <div class="modal fade" id="modalAdmin" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAdminTitle">Nuevo Administrador</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formAdmin">
                        <input type="hidden" id="adminId" value="">
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" id="adminEmail" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="adminNombre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contraseña *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="adminPassword" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">Mínimo 6 caracteres</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirmar Contraseña *</label>
                            <input type="password" class="form-control" id="adminPasswordConfirm" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnGuardarAdmin">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const form = document.getElementById('formAdmin');
        const alertContainer = document.getElementById('alertContainer');
        const togglePasswordBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('adminPassword');
        const modal = new bootstrap.Modal(document.getElementById('modalAdmin'));

        // Toggle visibilidad contraseña
        togglePasswordBtn.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            togglePasswordBtn.innerHTML = `<i class="bi bi-eye${type === 'password' ? '' : '-slash'}"></i>`;
        });

        // Guardar administrador
        document.getElementById('btnGuardarAdmin').addEventListener('click', async () => {
            alertContainer.innerHTML = '';
            
            const id = document.getElementById('adminId').value;
            const email = document.getElementById('adminEmail').value.trim();
            const nombre = document.getElementById('adminNombre').value.trim();
            const password = document.getElementById('adminPassword').value;
            const passwordConfirm = document.getElementById('adminPasswordConfirm').value;

            if (!email || !nombre || !password) {
                showAlert('danger', 'Por favor completa todos los campos requeridos');
                return;
            }

            if (password !== passwordConfirm) {
                showAlert('danger', 'Las contraseñas no coinciden');
                return;
            }

            if (password.length < 6) {
                showAlert('danger', 'La contraseña debe tener mínimo 6 caracteres');
                return;
            }

            try {
                const response = await fetch('../api/auth.php?action=registrar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ 
                        email, 
                        nombre, 
                        password 
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    showAlert('danger', data.error || 'Error al guardar');
                    return;
                }

                showAlert('success', data.mensaje || 'Administrador guardado exitosamente');
                form.reset();
                document.getElementById('adminId').value = '';
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } catch (error) {
                showAlert('danger', 'Error de conexión');
                console.error(error);
            }
        });

        function editarAdmin(admin) {
            document.getElementById('adminId').value = admin.id;
            document.getElementById('adminEmail').value = admin.email;
            document.getElementById('adminNombre').value = admin.nombre;
            document.getElementById('adminPassword').value = '';
            document.getElementById('adminPasswordConfirm').value = '';
            document.getElementById('modalAdminTitle').textContent = 'Editar Administrador';
            modal.show();
        }

        async function toggleAdmin(id, activo) {
            if (!confirm(`¿Estás seguro de que deseas ${activo ? 'activar' : 'desactivar'} este administrador?`)) {
                return;
            }

            try {
                const response = await fetch('../api/administradores.php?action=toggle', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ id, activo })
                });

                const data = await response.json();

                if (!response.ok) {
                    showAlert('danger', data.error || 'Error al actualizar');
                    return;
                }

                showAlert('success', 'Administrador actualizado');
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } catch (error) {
                showAlert('danger', 'Error de conexión');
                console.error(error);
            }
        }

        async function eliminarAdmin(id, email) {
            if (!confirm(`¿Estás seguro de que deseas eliminar al administrador ${email}? Esta acción no se puede deshacer.`)) {
                return;
            }

            try {
                const response = await fetch('../api/administradores.php?action=delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ id })
                });

                const data = await response.json();

                if (!response.ok) {
                    showAlert('danger', data.error || 'Error al eliminar');
                    return;
                }

                showAlert('success', 'Administrador eliminado exitosamente');
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } catch (error) {
                showAlert('danger', 'Error de conexión');
                console.error(error);
            }
        }

        function logout() {
            fetch('../api/auth.php?action=logout', {
                method: 'POST',
                credentials: 'same-origin'
            }).then(() => {
                window.location.href = 'login.php';
            });
        }

        function showAlert(type, message) {
            alertContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            window.scrollTo(0, 0);
        }

        // Limpiar modal al cerrar
        document.getElementById('modalAdmin').addEventListener('hidden.bs.modal', () => {
            form.reset();
            document.getElementById('adminId').value = '';
            document.getElementById('modalAdminTitle').textContent = 'Nuevo Administrador';
            document.getElementById('adminPassword').setAttribute('type', 'password');
        });
    </script>
</body>
</html>
