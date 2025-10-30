<?php
/**
 * Página de índice - Redirige a setup, login o dashboard según estado
 */

// Iniciar sesión de forma segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Si ya hay sesión activa, ir al dashboard
if (!empty($_SESSION['admin'])) {
    header('Location: public/dashboard.php');
    exit;
}

// Verificar si hay administradores en la BD
try {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM administradores');
    $count = $stmt->fetch()['count'];
    
    // Si no hay administradores, mostrar setup
    if ($count === 0 || $count == 0) {
        header('Location: setup.php');
        exit;
    }
} catch (Exception $e) {
    // Si hay error de conexión, ir al login
    error_log('Error en index.php: ' . $e->getMessage());
}

// Si hay administradores, ir al login
header('Location: public/login.php');
exit;
