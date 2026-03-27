<?php
/**
 * Endpoint: Visits - Registro con reglas de negocio
 */
require_once __DIR__ . '/../config/database.php';

function handleVisits($method, $id) {
    $db = (new Database())->getConnection();

    switch ($method) {
        case 'GET':
            $standId = $_GET['standId'] ?? null;
            $participantId = $_GET['participantId'] ?? null;

            if ($participantId) {
                getVisitsByParticipant($db, $participantId);
            } elseif ($standId) {
                getVisitsByStand($db, $standId);
            } else {
                getAllVisits($db);
            }
            break;
        case 'POST':
            registerVisit($db);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
}

function getAllVisits($db) {
    $stmt = $db->query('SELECT * FROM visits ORDER BY fecha DESC');
    echo json_encode($stmt->fetchAll());
}

function getVisitsByStand($db, $standId) {
    $stmt = $db->prepare('SELECT * FROM visits WHERE standId = ? ORDER BY fecha DESC');
    $stmt->execute([$standId]);
    echo json_encode($stmt->fetchAll());
}

function getVisitsByParticipant($db, $participantId) {
    $stmt = $db->prepare('SELECT * FROM visits WHERE participantId = ? ORDER BY fecha DESC');
    $stmt->execute([$participantId]);
    echo json_encode($stmt->fetchAll());
}

function registerVisit($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    $participantId = $data['participantId'] ?? '';
    $standId = $data['standId'] ?? '';

    if (empty($participantId) || empty($standId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'participantId y standId son requeridos']);
        return;
    }

    // Consultar datos del participante antes de la visita
    $stmtPart = $db->prepare('SELECT nombre FROM participants WHERE id = ?');
    $stmtPart->execute([$participantId]);
    $participantData = $stmtPart->fetch();

    if (!$participantData) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Participante no encontrado']);
        return;
    }

    // Get all visits for this participant
    $stmt = $db->prepare('SELECT * FROM visits WHERE participantId = ? ORDER BY fecha DESC');
    $stmt->execute([$participantId]);
    $allVisits = $stmt->fetchAll();
    $totalVisits = count($allVisits);

    // Rule 1: Cannot repeat the same stand consecutively
    if ($totalVisits > 0) {
        $lastVisit = $allVisits[0];

        if ($lastVisit['standId'] === $standId) {
            echo json_encode([
                'success' => false,
                'message' => 'No se puede repetir el mismo stand consecutivamente.'
            ]);
            return;
        }

        // Rule 2: Every 5 stands, must wait 5 minutes
        if ($totalVisits % 5 === 0) {
            $lastTime = strtotime($lastVisit['fecha']);
            $now = time();
            $diffMinutes = ($now - $lastTime) / 60;

            if ($diffMinutes < 5) {
                $waitMinutes = ceil(5 - $diffMinutes);
                echo json_encode([
                    'success' => false,
                    'message' => "Has completado un ciclo de 5 stands. Debes esperar {$waitMinutes} minuto(s) para continuar."
                ]);
                return;
            }
        }
    }

    // Save visit
    $id = 'v-' . uniqid();
    $stmt = $db->prepare('INSERT INTO visits (id, participantId, standId, fecha) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$id, $participantId, $standId]);

    // Get updated visit count for notifications
    $newTotal = $totalVisits + 1;
    $response = [
        'success' => true,
        'message' => 'Visita registrada con éxito.',
        'visitId' => $id,
        'totalVisits' => $newTotal,
        'participantNombre' => $participantData['nombre']
    ];

    // Add recommendation data if they've visited more than 1 stand
    if ($newTotal > 1) {
        // Get stands they haven't visited
        $visitedStandIds = array_unique(array_column($allVisits, 'standId'));
        $visitedStandIds[] = $standId; // Include current
        $placeholders = implode(',', array_fill(0, count($visitedStandIds), '?'));

        $stmt = $db->prepare("SELECT * FROM stands WHERE id NOT IN ($placeholders) AND activo = 1");
        $stmt->execute($visitedStandIds);
        $unvisited = $stmt->fetchAll();

        if (!empty($unvisited)) {
            $suggestion = $unvisited[array_rand($unvisited)];
            $response['recommendation'] = [
                'standId' => $suggestion['id'],
                'nombre' => $suggestion['nombre'],
                'responsable' => $suggestion['responsable'],
                'message' => "¡Te recomendamos visitar el stand de {$suggestion['nombre']}!"
            ];
        }
    }

    echo json_encode($response);
}
