<?php
/**
 * Endpoint: Stands - CRUD + inicialización
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth_middleware.php';

// Proteger el endpoint - Detiene la ejecución si no hay token válido
verifyRequest();

function handleStands($method, $id) {
    $db = (new Database())->getConnection();

    switch ($method) {
        case 'GET':
            if ($id === 'initialize') {
                initializeStands($db);
            } elseif ($id) {
                // Check if it's a filter by usuarioId
                $usuarioId = $_GET['usuarioId'] ?? null;
                if ($usuarioId) {
                    getStandsByUsuarioId($db, $usuarioId);
                } else {
                    getStandById($db, $id);
                }
            } else {
                $usuarioId = $_GET['usuarioId'] ?? null;
                if ($usuarioId) {
                    getStandsByUsuarioId($db, $usuarioId);
                } else {
                    getAllStands($db);
                }
            }
            break;
        case 'POST':
            if ($id === 'initialize') {
                initializeStands($db);
            } else {
                createStand($db);
            }
            break;
        case 'PUT':
            if ($id) updateStand($db, $id);
            else { http_response_code(400); echo json_encode(['error' => 'ID requerido']); }
            break;
        case 'DELETE':
            if ($id) deleteStand($db, $id);
            else { http_response_code(400); echo json_encode(['error' => 'ID requerido']); }
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
}

function getAllStands($db) {
    $stmt = $db->query('SELECT * FROM stands');
    $stands = $stmt->fetchAll();
    foreach ($stands as &$s) {
        $s['activo'] = (bool) $s['activo'];
    }
    echo json_encode($stands);
}

function getStandById($db, $id) {
    $stmt = $db->prepare('SELECT * FROM stands WHERE id = ?');
    $stmt->execute([$id]);
    $s = $stmt->fetch();
    if ($s) {
        $s['activo'] = (bool) $s['activo'];
        echo json_encode($s);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Stand no encontrado']);
    }
}

function getStandsByUsuarioId($db, $usuarioId) {
    $stmt = $db->prepare('SELECT * FROM stands WHERE usuarioId = ?');
    $stmt->execute([$usuarioId]);
    $stands = $stmt->fetchAll();
    foreach ($stands as &$s) {
        $s['activo'] = (bool) $s['activo'];
    }
    echo json_encode($stands);
}

function createStand($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = 'stand-' . uniqid();

    $stmt = $db->prepare('INSERT INTO stands (id, nombre, descripcion, usuarioId, activo, responsable) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $id,
        $data['nombre'] ?? '',
        $data['descripcion'] ?? '',
        $data['usuarioId'] ?? '',
        isset($data['activo']) ? ($data['activo'] ? 1 : 0) : 1,
        $data['responsable'] ?? null
    ]);

    echo json_encode(['id' => $id, 'message' => 'Stand creado']);
}

function updateStand($db, $id) {
    $data = json_decode(file_get_contents('php://input'), true);

    $fields = [];
    $values = [];

    $allowed = ['nombre', 'descripcion', 'usuarioId', 'activo', 'responsable'];
    foreach ($allowed as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            $val = $data[$field];
            if ($field === 'activo') $val = $val ? 1 : 0;
            $values[] = $val;
        }
    }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No hay campos para actualizar']);
        return;
    }

    $values[] = $id;
    $sql = 'UPDATE stands SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $db->prepare($sql)->execute($values);

    echo json_encode(['message' => 'Stand actualizado']);
}

function deleteStand($db, $id) {
    $stmt = $db->prepare('DELETE FROM stands WHERE id = ?');
    $stmt->execute([$id]);
    echo json_encode(['message' => 'Stand eliminado']);
}

function initializeStands($db) {
    $stmt = $db->query('SELECT COUNT(*) as total FROM stands');
    $count = $stmt->fetch()['total'];

    if ($count == 0) {
        $defaultStands = [
            ['stand-001', 'Crepê', 'Adriana García', 'Deliciosas crepas francesas'],
            ['stand-002', 'Quiche Lorraine', 'Mildred Zoé', 'Clásico quiche de Lorena'],
            ['stand-003', 'Croquembouche', 'José Emilio', 'Torre de profitroles con caramelo'],
            ['stand-004', 'Crème Brûlée', 'Selina Maldonado', 'Postre de crema con azúcar quemada'],
            ['stand-005', 'Croissant', 'Ivan Atzin', 'Pan de hojaldre mantecoso'],
        ];

        $stmt = $db->prepare('INSERT INTO stands (id, nombre, responsable, descripcion, activo, usuarioId) VALUES (?, ?, ?, ?, 1, "")');
        foreach ($defaultStands as $s) {
            $stmt->execute($s);
        }
        echo json_encode(['initialized' => true, 'message' => 'Stands inicializados']);
    } else {
        echo json_encode(['initialized' => false, 'message' => 'Ya existen stands']);
    }
}
