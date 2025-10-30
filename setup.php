<?php
/**
 * Página de Setup - Crear primer administrador
 * Accesible solo si no hay administradores registrados
 */
session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$pdo = getPDO();

// Verificar si ya hay administradores
try {
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM administradores');
    $count = $stmt->fetch()['count'];
    
    if ($count > 0) {
        header('Location: public/login.php');
        exit;
    }
} catch (PDOException $e) {
    // Si hay error, probablemente la BD no está creada
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validaciones
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor ingresa un email válido';
    } elseif (strlen($nombre) < 3) {
        $error = 'El nombre debe tener al menos 3 caracteres';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener mínimo 6 caracteres';
    } elseif ($password !== $password_confirm) {
        $error = 'Las contraseñas no coinciden';
    } else {
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO administradores (email, password, nombre) VALUES (?, ?, ?)');
            $stmt->execute([$email, $hash, $nombre]);
            
            $success = 'Administrador creado exitosamente. Redirigiendo...';
            header('refresh:2;url=public/login.php');
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $error = 'Este email ya está registrado';
            } else {
                $error = 'Error al crear administrador: ' . $e->getMessage();
            }
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup - SIS Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .setup-card {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            border: none;
            border-radius: 10px;
        }
        .setup-card .card-body {
            padding: 3rem 2rem;
        }
        .btn-setup {
            padding: 0.75rem;
            font-size: 1.1rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card setup-card">
                    <div class="card-body">
                        <h3 class="text-center mb-1">
                            <i class="bi bi-shield-lock"></i> SIS Password
                        </h3>
                        <p class="text-center text-muted mb-4">Configuración Inicial</p>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($success); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email del Administrador</label>
                                    <input type="email" class="form-control form-control-lg" id="email" name="email" required>
                                    <small class="text-muted">Ejemplo: admin@miempresa.com</small>
                                </div>

                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre Completo</label>
                                    <input type="text" class="form-control form-control-lg" id="nombre" name="nombre" required>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Contraseña</label>
                                    <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                                    <small class="text-muted">Mínimo 6 caracteres</small>
                                </div>

                                <div class="mb-3">
                                    <label for="password_confirm" class="form-label">Confirmar Contraseña</label>
                                    <input type="password" class="form-control form-control-lg" id="password_confirm" name="password_confirm" required>
                                </div>

                                <button type="submit" class="btn btn-primary btn-setup w-100">Crear Administrador</button>
                            </form>
                        <?php endif; ?>

                        <hr class="my-3">
                        <small class="text-muted d-block text-center">
                            <i class="bi bi-info-circle"></i> Esta página solo aparece si no hay administradores registrados
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
