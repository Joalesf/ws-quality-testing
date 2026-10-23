<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    json_response(['ok' => false, 'message' => 'Método no permitido.'], 405);
}

try {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = db()->prepare('SELECT * FROM services WHERE id = :id AND status = :status');
        $stmt->execute(['id' => $id, 'status' => 'active']);
        $service = $stmt->fetch();
        if (!$service) {
            json_response(['ok' => false, 'message' => 'Servicio no encontrado.'], 404);
        }
        json_response(['ok' => true, 'data' => $service]);
    }

    json_response(['ok' => true, 'count' => count(search_services()), 'data' => search_services()]);
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    json_response(['ok' => false, 'message' => 'No se pudo consultar la base de datos.'], 500);
}

