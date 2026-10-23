<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

try {
    $query = trim((string) ($_GET['q'] ?? ''));
    $category = trim((string) ($_GET['category'] ?? ''));
    $protocol = normalize_protocol((string) ($_GET['protocol'] ?? ''));
    $shouldLog = filter_var($_GET['log'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if (mb_strlen($query) > 120 || mb_strlen($category) > 60) {
        json_response(['ok' => false, 'message' => 'Los parámetros exceden la longitud permitida.'], 422);
    }

    $services = search_services($query, $category, $protocol);
    if ($shouldLog) {
        log_search($query, $category, $protocol, count($services));
    }

    json_response([
        'ok' => true,
        'filters' => ['q' => $query, 'category' => $category, 'protocol' => $protocol],
        'count' => count($services),
        'data' => $services,
    ]);
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    json_response(['ok' => false, 'message' => 'No se pudo consultar la base de datos.'], 500);
}

