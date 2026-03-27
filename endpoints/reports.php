<?php
/**
 * Endpoint: Reports - Analytics y métricas
 */
require_once __DIR__ . '/../config/database.php';

function handleReports($method, $subResource) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        return;
    }

    $db = (new Database())->getConnection();

    switch ($subResource) {
        case 'visits-per-stand':
            getVisitsPerStand($db);
            break;
        case 'most-visited':
            getMostVisitedStand($db);
            break;
        case 'stand-ratings':
            getStandRatings($db);
            break;
        case 'global-flow':
            getGlobalFlow15Min($db);
            break;
        case 'stand-flows':
            getStandFlows15Min($db);
            break;
        case 'summary':
            getFullSummary($db);
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Reporte no encontrado']);
    }
}

function getVisitsPerStand($db) {
    $stmt = $db->query('
        SELECT v.standId, s.nombre as standName, COUNT(*) as count
        FROM visits v
        LEFT JOIN stands s ON v.standId = s.id
        GROUP BY v.standId, s.nombre
        ORDER BY count DESC
    ');
    echo json_encode($stmt->fetchAll());
}

function getMostVisitedStand($db) {
    $stmt = $db->query('
        SELECT v.standId, s.nombre as standName, COUNT(*) as count
        FROM visits v
        LEFT JOIN stands s ON v.standId = s.id
        GROUP BY v.standId, s.nombre
        ORDER BY count DESC
        LIMIT 1
    ');
    $result = $stmt->fetch();
    echo json_encode($result ?: null);
}

function getStandRatings($db) {
    $stmt = $db->query('
        SELECT standId,
               ROUND(AVG((CAST(p1 AS DECIMAL) + CAST(p2 AS DECIMAL) + CAST(p3 AS DECIMAL) + CAST(p4 AS DECIMAL)) / 4), 2) as avgRating,
               COUNT(*) as totalSurveys
        FROM surveys
        GROUP BY standId
    ');
    echo json_encode($stmt->fetchAll());
}

function get15MinInterval($dateStr) {
    $date = new DateTime($dateStr);
    $h = $date->format('H');
    $m = (int) $date->format('i');

    if ($m >= 45) $minGroup = '45';
    elseif ($m >= 30) $minGroup = '30';
    elseif ($m >= 15) $minGroup = '15';
    else $minGroup = '00';

    return "$h:$minGroup";
}

function getGlobalFlow15Min($db) {
    $stmt = $db->query('SELECT fecha FROM visits ORDER BY fecha');
    $visits = $stmt->fetchAll();

    $peakMap = [];
    foreach ($visits as $v) {
        $interval = get15MinInterval($v['fecha']);
        $peakMap[$interval] = ($peakMap[$interval] ?? 0) + 1;
    }

    ksort($peakMap);
    $result = [];
    foreach ($peakMap as $time => $count) {
        $result[] = ['time' => $time, 'count' => $count];
    }
    echo json_encode($result);
}

function getStandFlows15Min($db) {
    $stmt = $db->query('SELECT standId, fecha FROM visits ORDER BY fecha');
    $visits = $stmt->fetchAll();

    $standMap = [];
    foreach ($visits as $v) {
        $interval = get15MinInterval($v['fecha']);
        $sid = $v['standId'];
        if (!isset($standMap[$sid])) $standMap[$sid] = [];
        $standMap[$sid][$interval] = ($standMap[$sid][$interval] ?? 0) + 1;
    }

    $result = [];
    foreach ($standMap as $standId => $peakMap) {
        ksort($peakMap);
        $flows = [];
        foreach ($peakMap as $time => $count) {
            $flows[] = ['time' => $time, 'count' => $count];
        }
        $result[] = ['standId' => $standId, 'flows' => $flows];
    }
    echo json_encode($result);
}

function getFullSummary($db) {
    $totalParticipants = $db->query('SELECT COUNT(*) as c FROM participants')->fetch()['c'];
    $totalVisits = $db->query('SELECT COUNT(*) as c FROM visits')->fetch()['c'];
    $totalSurveys = $db->query('SELECT COUNT(*) as c FROM surveys')->fetch()['c'];
    $totalStands = $db->query('SELECT COUNT(*) as c FROM stands WHERE activo = 1')->fetch()['c'];

    echo json_encode([
        'totalParticipants' => (int)$totalParticipants,
        'totalVisits' => (int)$totalVisits,
        'totalSurveys' => (int)$totalSurveys,
        'totalStands' => (int)$totalStands
    ]);
}
