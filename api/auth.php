<?php
/**
 * API de Autenticación
 * POST /api/auth.php?action=login
 * POST /api/auth.php?action=logout
 */

header('Content-Type: application/json; charset=utf-8');

// Iniciar sesión de forma segura
if (session_status() === PHP_SESSION_NONE) {
    // Configurar sesión antes de iniciar
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$pdo = getPDO();

// ============ LOGIN ============
if ($method === 'POST' && $action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    // Validar entrada
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 4) {
        http_response_code(400);
        echo json_encode(['error' => 'Email o contraseña inválidos']);
        exit;
    }

    try {
        $stmt = $pdo->prepare('SELECT id, email, password, nombre FROM administradores WHERE email = ? AND activo = 1');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if (!$admin) {
            http_response_code(401);
            echo json_encode(['error' => 'Credenciales inválidas']);
            exit;
        }

        // Verificar contraseña
        if (!password_verify($password, $admin['password'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Credenciales inválidas']);
            exit;
        }

        // Crear sesión
        session_regenerate_id(true);
        $_SESSION['admin'] = [
            'id' => $admin['id'],
            'email' => $admin['email'],
            'nombre' => $admin['nombre'],
            'login_time' => time()
        ];

        echo json_encode([
            'success' => true,
            'mensaje' => 'Login exitoso',
            'admin' => $_SESSION['admin']
        ]);
        exit;

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error en el servidor']);
        exit;
    }
}

// ============ LOGOUT ============
if ($method === 'POST' && $action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true, 'mensaje' => 'Sesión cerrada']);
    exit;
}

// ============ REGISTRAR NUEVO ADMIN ============
if ($method === 'POST' && $action === 'registrar') {
    // Aquí podrías añadir lógica para que solo admins registren nuevos admins
    // Por ahora, permitimos registro público para primera configuración
    
    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $nombre = trim($data['nombre'] ?? '');

    // Validar entrada
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6 || $nombre === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Datos inválidos']);
        exit;
    }

    try {
        // Verificar si ya existe
        $stmt = $pdo->prepare('SELECT id FROM administradores WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'El email ya está registrado']);
            exit;
        }

        // Hashear contraseña y crear
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('INSERT INTO administradores (email, password, nombre) VALUES (?, ?, ?)');
        $stmt->execute([$email, $hash, $nombre]);

        http_response_code(201);
        echo json_encode(['success' => true, 'mensaje' => 'Administrador creado exitosamente']);
        exit;

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear administrador']);
        exit;
    }
}

// ============ VERIFICAR SESIÓN ============
if ($method === 'GET' && $action === 'check') {
    if (!empty($_SESSION['admin'])) {
        echo json_encode(['authenticated' => true, 'admin' => $_SESSION['admin']]);
    } else {
        http_response_code(401);
        echo json_encode(['authenticated' => false]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
