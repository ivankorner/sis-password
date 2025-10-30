<?php
/**
 * API de Usuarios por PC
 * GET    /api/usuarios.php              - Listar todos
 * GET    /api/usuarios.php?pc_id=N     - Usuarios de un PC
 * POST   /api/usuarios.php              - Crear usuario
 * PUT    /api/usuarios.php?id=N         - Actualizar
 * DELETE /api/usuarios.php?id=N         - Eliminar
 */

header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Verificar autenticación
if (empty($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getPDO();

// ============ GET - LISTAR ============
if ($method === 'GET') {
    $pc_id = isset($_GET['pc_id']) ? intval($_GET['pc_id']) : 0;

    try {
        if ($pc_id > 0) {
            // Usuarios de un PC específico
            $stmt = $pdo->prepare('SELECT * FROM usuarios_pc WHERE pc_id = ? ORDER BY nombre_usuario ASC');
            $stmt->execute([$pc_id]);
            $usuarios = $stmt->fetchAll();
            echo json_encode(['usuarios' => $usuarios]);
            exit;
        }

        // Listar todos los usuarios con info del PC
        $sql = "SELECT u.*, p.nombre as pc_nombre, p.oficina
                FROM usuarios_pc u
                JOIN pcs p ON u.pc_id = p.id
                ORDER BY p.oficina ASC, p.nombre ASC, u.nombre_usuario ASC";
        $stmt = $pdo->query($sql);
        $usuarios = $stmt->fetchAll();

        echo json_encode(['usuarios' => $usuarios]);
        exit;

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error en el servidor']);
        exit;
    }
}

// ============ POST - CREAR ============
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $pc_id = intval($data['pc_id'] ?? 0);
    $operario = trim($data['operario'] ?? '');
    $nombre_usuario = trim($data['nombre_usuario'] ?? '');
    $password = $data['password'] ?? '';
    $id_control = trim($data['id_control_remoto'] ?? '');
    $pw_control = trim($data['password_control_remoto'] ?? '');
    $notas = trim($data['notas'] ?? '');

    // Validar entrada
    if ($pc_id <= 0 || $operario === '' || $nombre_usuario === '' || $password === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Campos requeridos: pc_id, operario, nombre_usuario, password']);
        exit;
    }

    try {
        // Verificar que el PC existe
        $stmt = $pdo->prepare('SELECT id FROM pcs WHERE id = ?');
        $stmt->execute([$pc_id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'PC no encontrado']);
            exit;
        }

        // Insertar usuario
        $stmt = $pdo->prepare(
            'INSERT INTO usuarios_pc (pc_id, operario, nombre_usuario, password, id_control_remoto, password_control_remoto, notas)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$pc_id, $operario, $nombre_usuario, $password, $id_control, $pw_control, $notas]);

        echo json_encode([
            'success' => true,
            'id' => $pdo->lastInsertId(),
            'mensaje' => 'Usuario creado exitosamente'
        ]);
        exit;

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear usuario']);
        exit;
    }
}

// ============ PUT - ACTUALIZAR ============
if ($method === 'PUT') {
    parse_str(file_get_contents('php://input'), $input);
    $id = intval($input['id'] ?? 0);
    $operario = trim($input['operario'] ?? '');
    $nombre_usuario = trim($input['nombre_usuario'] ?? '');
    $password = $input['password'] ?? '';
    $id_control = trim($input['id_control_remoto'] ?? '');
    $pw_control = trim($input['password_control_remoto'] ?? '');
    $notas = trim($input['notas'] ?? '');

    if ($id <= 0 || $operario === '' || $nombre_usuario === '' || $password === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Datos inválidos']);
        exit;
    }

    try {
        // Verificar que existe
        $stmt = $pdo->prepare('SELECT id FROM usuarios_pc WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Usuario no encontrado']);
            exit;
        }

        // Actualizar
        $stmt = $pdo->prepare(
            'UPDATE usuarios_pc SET operario = ?, nombre_usuario = ?, password = ?, 
             id_control_remoto = ?, password_control_remoto = ?, notas = ?, fecha_actualizacion = NOW() WHERE id = ?'
        );
        $stmt->execute([$operario, $nombre_usuario, $password, $id_control, $pw_control, $notas, $id]);

        echo json_encode(['success' => true, 'mensaje' => 'Usuario actualizado']);
        exit;

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar usuario']);
        exit;
    }
}

// ============ DELETE - ELIMINAR ============
if ($method === 'DELETE') {
    parse_str(file_get_contents('php://input'), $input);
    $id = intval($input['id'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID inválido']);
        exit;
    }

    try {
        $stmt = $pdo->prepare('SELECT id FROM usuarios_pc WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Usuario no encontrado']);
            exit;
        }

        $stmt = $pdo->prepare('DELETE FROM usuarios_pc WHERE id = ?');
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'mensaje' => 'Usuario eliminado']);
        exit;

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar usuario']);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
