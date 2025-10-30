<?php
/**
 * API de PCs
 * GET    /api/pcs.php             - Listar todos
 * GET    /api/pcs.php?id=N        - Detalle de un PC
 * POST   /api/pcs.php             - Crear
 * PUT    /api/pcs.php?id=N        - Actualizar
 * DELETE /api/pcs.php?id=N        - Eliminar
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

// ============ GET - LISTAR O DETALLE ============
if ($method === 'GET') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    try {
        if ($id > 0) {
            // Obtener un PC específico con sus usuarios
            $stmt = $pdo->prepare('SELECT * FROM pcs WHERE id = ?');
            $stmt->execute([$id]);
            $pc = $stmt->fetch();

            if (!$pc) {
                http_response_code(404);
                echo json_encode(['error' => 'PC no encontrado']);
                exit;
            }

            $stmt = $pdo->prepare('SELECT * FROM usuarios_pc WHERE pc_id = ? ORDER BY nombre_usuario ASC');
            $stmt->execute([$id]);
            $usuarios = $stmt->fetchAll();

            echo json_encode([
                'pc' => $pc,
                'usuarios' => $usuarios
            ]);
            exit;
        }

        // Listar todos los PCs con cantidad de usuarios
        $sql = "SELECT p.*, COUNT(u.id) as cantidad_usuarios
                FROM pcs p
                LEFT JOIN usuarios_pc u ON p.id = u.pc_id
                GROUP BY p.id
                ORDER BY p.oficina ASC, p.nombre ASC";
        $stmt = $pdo->query($sql);
        $pcs = $stmt->fetchAll();

        echo json_encode(['pcs' => $pcs]);
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
    $oficina = trim($data['oficina'] ?? '');
    $nombre = trim($data['nombre'] ?? '');
    $descripcion = trim($data['descripcion'] ?? '');

    if ($oficina === '' || $nombre === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Oficina y Nombre son requeridos']);
        exit;
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO pcs (oficina, nombre, descripcion) VALUES (?, ?, ?)');
        $stmt->execute([$oficina, $nombre, $descripcion]);

        echo json_encode([
            'success' => true,
            'id' => $pdo->lastInsertId(),
            'mensaje' => 'PC creado exitosamente'
        ]);
        exit;

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear PC']);
        exit;
    }
}

// ============ PUT - ACTUALIZAR ============
if ($method === 'PUT') {
    parse_str(file_get_contents('php://input'), $input);
    $id = intval($input['id'] ?? 0);
    $oficina = trim($input['oficina'] ?? '');
    $nombre = trim($input['nombre'] ?? '');
    $descripcion = trim($input['descripcion'] ?? '');

    if ($id <= 0 || $oficina === '' || $nombre === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Datos inválidos']);
        exit;
    }

    try {
        $stmt = $pdo->prepare('SELECT id FROM pcs WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'PC no encontrado']);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE pcs SET oficina = ?, nombre = ?, descripcion = ?, fecha_actualizacion = NOW() WHERE id = ?');
        $stmt->execute([$oficina, $nombre, $descripcion, $id]);

        echo json_encode(['success' => true, 'mensaje' => 'PC actualizado']);
        exit;

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar PC']);
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
        $stmt = $pdo->prepare('SELECT id FROM pcs WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'PC no encontrado']);
            exit;
        }

        // Usar transacción para eliminar PC y sus usuarios
        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM usuarios_pc WHERE pc_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM pcs WHERE id = ?')->execute([$id]);
        $pdo->commit();

        echo json_encode(['success' => true, 'mensaje' => 'PC y sus usuarios eliminados']);
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar PC']);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
