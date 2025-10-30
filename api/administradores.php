<?php
/**
 * API para gestión de Administradores
 * POST /api/administradores.php?action=toggle
 * POST /api/administradores.php?action=delete
 */

header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$pdo = getPDO();

// Verificar autenticación
if (empty($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// ============ TOGGLE ADMINISTRADOR (Activar/Desactivar) ============
if ($method === 'POST' && $action === 'toggle') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    $activo = $data['activo'] ?? 0;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de administrador requerido']);
        exit;
    }

    try {
        $stmt = $pdo->prepare('UPDATE administradores SET activo = ? WHERE id = ?');
        $stmt->execute([$activo, $id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Administrador no encontrado']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'mensaje' => 'Administrador actualizado'
        ]);
        exit;

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar administrador']);
        exit;
    }
}

// ============ ELIMINAR ADMINISTRADOR ============
if ($method === 'POST' && $action === 'delete') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de administrador requerido']);
        exit;
    }

    // Validación: No permitir eliminar al administrador actual (a sí mismo)
    if ((int)$id === (int)$_SESSION['admin']['id']) {
        http_response_code(400);
        echo json_encode(['error' => 'No puedes eliminarte a ti mismo']);
        exit;
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM administradores WHERE id = ?');
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Administrador no encontrado']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'mensaje' => 'Administrador eliminado exitosamente'
        ]);
        exit;

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar administrador']);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
