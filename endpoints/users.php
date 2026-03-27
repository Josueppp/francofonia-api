<?php
/**
 * Endpoint: Users - CRUD de usuarios del staff
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth_middleware.php';

// Proteger el endpoint - Detiene la ejecución si no hay token válido
verifyRequest();

function handleUsers($method, $id) {
    $db = (new Database())->getConnection();

    switch ($method) {
        case 'GET':
            if ($id) getUserById($db, $id);
            else getAllUsers($db);
            break;
        case 'POST':
            createUser($db);
            break;
        case 'PUT':
            if ($id) updateUser($db, $id);
            else { http_response_code(400); echo json_encode(['error' => 'ID requerido']); }
            break;
        case 'DELETE':
            if ($id) deleteUser($db, $id);
            else { http_response_code(400); echo json_encode(['error' => 'ID requerido']); }
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
}

function getAllUsers($db) {
    $stmt = $db->query('SELECT id, email, role, standId, createdAt FROM users ORDER BY createdAt DESC');
    echo json_encode($stmt->fetchAll());
}

function getUserById($db, $id) {
    $stmt = $db->prepare('SELECT id, email, role, standId, createdAt FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if ($u) echo json_encode($u);
    else { http_response_code(404); echo json_encode(['error' => 'Usuario no encontrado']); }
}

function createUser($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = 'u-' . uniqid();
    $password = password_hash($data['password'] ?? 'default123', PASSWORD_BCRYPT);

    $stmt = $db->prepare('INSERT INTO users (id, email, password, role, standId, createdAt) VALUES (?, ?, ?, ?, ?, NOW())');
    $stmt->execute([
        $id,
        $data['email'] ?? '',
        $password,
        $data['role'] ?? 'usuario',
        $data['standId'] ?? null
    ]);

    echo json_encode(['id' => $id, 'message' => 'Usuario creado']);
}

function updateUser($db, $id) {
    $data = json_decode(file_get_contents('php://input'), true);

    $fields = [];
    $values = [];

    if (isset($data['email'])) { $fields[] = 'email = ?'; $values[] = $data['email']; }
    if (isset($data['role'])) { $fields[] = 'role = ?'; $values[] = $data['role']; }
    if (isset($data['standId'])) { $fields[] = 'standId = ?'; $values[] = $data['standId']; }
    if (isset($data['password'])) {
        $fields[] = 'password = ?';
        $values[] = password_hash($data['password'], PASSWORD_BCRYPT);
    }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No hay campos para actualizar']);
        return;
    }

    $values[] = $id;
    $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $db->prepare($sql)->execute($values);

    echo json_encode(['message' => 'Usuario actualizado']);
}

function deleteUser($db, $id) {
    $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$id]);
    echo json_encode(['message' => 'Usuario eliminado']);
}
