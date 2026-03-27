<?php
/**
 * Endpoint: Surveys - Encuestas de satisfacción
 */
require_once __DIR__ . '/../config/database.php';

function handleSurveys($method, $id) {
    $db = (new Database())->getConnection();

    switch ($method) {
        case 'GET':
            if ($id) {
                $standId = $_GET['standId'] ?? null;
                if ($standId) {
                    getSurveysByStand($db, $standId);
                } else {
                    getSurveyById($db, $id);
                }
            } else {
                $standId = $_GET['standId'] ?? null;
                if ($standId) {
                    getSurveysByStand($db, $standId);
                } else {
                    getAllSurveys($db);
                }
            }
            break;
        case 'POST':
            createSurvey($db);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
}

function getAllSurveys($db) {
    $stmt = $db->query('SELECT * FROM surveys ORDER BY fecha DESC');
    echo json_encode($stmt->fetchAll());
}

function getSurveyById($db, $id) {
    $stmt = $db->prepare('SELECT * FROM surveys WHERE id = ?');
    $stmt->execute([$id]);
    $s = $stmt->fetch();
    if ($s) echo json_encode($s);
    else { http_response_code(404); echo json_encode(['error' => 'Encuesta no encontrada']); }
}

function getSurveysByStand($db, $standId) {
    $stmt = $db->prepare('SELECT * FROM surveys WHERE standId = ? ORDER BY fecha DESC');
    $stmt->execute([$standId]);
    echo json_encode($stmt->fetchAll());
}

function createSurvey($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = 's-' . uniqid();

    $stmt = $db->prepare('INSERT INTO surveys (id, participantId, standId, p1, p2, p3, p4, p5, fecha) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
    $stmt->execute([
        $id,
        $data['participantId'] ?? '',
        $data['standId'] ?? '',
        $data['p1'] ?? '0',
        $data['p2'] ?? '0',
        $data['p3'] ?? '0',
        $data['p4'] ?? '0',
        $data['p5'] ?? '0'
    ]);

    echo json_encode(['id' => $id, 'message' => 'Encuesta guardada']);
}
