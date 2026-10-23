<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function normalize_protocol(string $protocol): string
{
    $protocol = strtoupper(trim($protocol));
    return in_array($protocol, ['REST', 'SOAP'], true) ? $protocol : '';
}

function search_services(string $query = '', string $category = '', string $protocol = ''): array
{
    $sql = 'SELECT id, name, description, category, protocol, endpoint, documentation_url, http_method, status
            FROM services WHERE status = :status';
    $params = ['status' => 'active'];

    if ($query !== '') {
        $sql .= ' AND (name LIKE :query_name OR description LIKE :query_description OR category LIKE :query_category)';
        $likeQuery = '%' . $query . '%';
        $params['query_name'] = $likeQuery;
        $params['query_description'] = $likeQuery;
        $params['query_category'] = $likeQuery;
    }
    if ($category !== '') {
        $sql .= ' AND category = :category';
        $params['category'] = $category;
    }
    if ($protocol !== '') {
        $sql .= ' AND protocol = :protocol';
        $params['protocol'] = $protocol;
    }

    $sql .= ' ORDER BY name ASC LIMIT 50';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function log_search(string $query, string $category, string $protocol, int $resultCount): void
{
    $stmt = db()->prepare(
        'INSERT INTO search_history (search_term, category, protocol, result_count, ip_address)
         VALUES (:term, :category, :protocol, :result_count, :ip_address)'
    );
    $stmt->execute([
        'term' => mb_substr($query, 0, 120),
        'category' => mb_substr($category, 0, 60),
        'protocol' => $protocol,
        'result_count' => $resultCount,
        'ip_address' => mb_substr($_SERVER['REMOTE_ADDR'] ?? 'local', 0, 45),
    ]);
}

function categories(): array
{
    return db()->query("SELECT DISTINCT category FROM services WHERE status = 'active' ORDER BY category")
        ->fetchAll(PDO::FETCH_COLUMN);
}

function start_secure_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => isset($_SERVER['HTTPS']),
        ]);
        session_start();
    }
}

function csrf_token(): string
{
    start_secure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function valid_csrf(?string $token): bool
{
    start_secure_session();
    return is_string($token) && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function is_admin(): bool
{
    start_secure_session();
    return ($_SESSION['role'] ?? '') === 'admin';
}

function is_tester(): bool
{
    start_secure_session();
    return ($_SESSION['role'] ?? '') === 'tester';
}

function current_role(): string
{
    start_secure_session();
    return in_array($_SESSION['role'] ?? '', ['admin', 'tester'], true)
        ? (string) $_SESSION['role']
        : '';
}

function ensure_test_cases_table(): void
{
    db()->exec(
        "CREATE TABLE IF NOT EXISTS test_cases (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(150) NOT NULL,
            module VARCHAR(100) NOT NULL,
            requirement_code VARCHAR(30) NOT NULL DEFAULT '',
            requirement_type ENUM('Funcional', 'No funcional') NOT NULL DEFAULT 'No funcional',
            technique ENUM('Caja blanca', 'Caja negra') NOT NULL,
            subtechnique VARCHAR(100) NOT NULL DEFAULT '',
            objective VARCHAR(800) NOT NULL,
            preconditions VARCHAR(1200) NOT NULL,
            input_data VARCHAR(1200) NOT NULL,
            steps TEXT NOT NULL,
            expected_result VARCHAR(1200) NOT NULL,
            actual_result VARCHAR(1200) NOT NULL DEFAULT '',
            status ENUM('Pendiente', 'Éxito', 'Fallo', 'Bloqueado') NOT NULL DEFAULT 'Pendiente',
            priority ENUM('Alta', 'Media', 'Baja') NOT NULL DEFAULT 'Media',
            responsible VARCHAR(100) NOT NULL,
            execution_date DATE NOT NULL,
            environment VARCHAR(150) NOT NULL DEFAULT '',
            evidence VARCHAR(500) NOT NULL DEFAULT '',
            observations VARCHAR(1200) NOT NULL DEFAULT '',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_test_case_date (execution_date),
            INDEX idx_test_case_status (status, technique)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    );
}

function test_case_code(int $id): string
{
    return 'CP-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
}

function status_slug(string $status): string
{
    return match ($status) {
        'Éxito' => 'success',
        'Fallo' => 'failure',
        'Bloqueado' => 'blocked',
        default => 'pending',
    };
}
