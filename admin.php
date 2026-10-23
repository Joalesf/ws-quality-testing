<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/quality.php';
start_secure_session();

$message = '';
$messageType = 'success';

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = (string) ($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $role = '';

    if (ADMIN_PASSWORD !== '' && hash_equals(ADMIN_USER, $username) && hash_equals(ADMIN_PASSWORD, $password)) {
        $role = 'admin';
    } elseif (TESTER_PASSWORD !== '' && hash_equals(TESTER_USER, $username) && hash_equals(TESTER_PASSWORD, $password)) {
        $role = 'tester';
    }

    if ($role === '') {
        try {
            quality_schema();
            $userQuery = db()->prepare('SELECT password_hash, role FROM app_users WHERE username=?');
            $userQuery->execute([$username]);
            $user = $userQuery->fetch();
            if ($user && password_verify($password, $user['password_hash'])) $role = $user['role'];
        } catch (Throwable $exception) { error_log($exception->getMessage()); }
    }

    if ($role !== '') {
        session_regenerate_id(true);
        $_SESSION['role'] = $role;
        $_SESSION['username'] = $username;
        header('Location: ' . ($role === 'admin' ? 'admin.php' : 'testing.php'));
        exit;
    }
    $message = 'Usuario o contraseña incorrectos.';
    $messageType = 'error';
}

if (is_tester()) {
    header('Location: testing.php');
    exit;
}

if (isset($_GET['forbidden']) && $_GET['forbidden'] === 'tester') {
    $message = 'El módulo de casos de prueba está reservado para el rol Tester.';
    $messageType = 'error';
}

if (is_admin() && isset($_SESSION['flash_message'])) {
    $message = (string) $_SESSION['flash_message'];
    $messageType = (string) ($_SESSION['flash_type'] ?? 'success');
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

if (is_admin() && $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['login'])) {
    if (!valid_csrf($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        $message = 'La solicitud caducó. Recarga la página e inténtalo otra vez.';
        $messageType = 'error';
    } else {
        try {
            $action = (string) ($_POST['action'] ?? '');
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if ($action === 'delete_search' && $id) {
                $stmt = db()->prepare('DELETE FROM search_history WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $message = 'La búsqueda seleccionada fue eliminada.';
            } elseif ($action === 'clear_searches') {
                db()->exec('DELETE FROM search_history');
                $message = 'El historial de búsquedas quedó vacío.';
            } elseif ($action === 'delete_service' && $id) {
                $stmt = db()->prepare('DELETE FROM services WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $message = 'El servicio fue eliminado del catálogo.';
            } elseif ($action === 'add_service') {
                $protocol = normalize_protocol((string) ($_POST['protocol'] ?? ''));
                $required = ['name', 'description', 'category', 'endpoint', 'documentation_url', 'http_method'];
                foreach ($required as $field) {
                    if (trim((string) ($_POST[$field] ?? '')) === '') {
                        throw new InvalidArgumentException('Completa todos los campos del servicio.');
                    }
                }
                if (!$protocol) {
                    throw new InvalidArgumentException('Selecciona REST o SOAP.');
                }
                $stmt = db()->prepare(
                    'INSERT INTO services (name, description, category, protocol, endpoint, documentation_url, http_method)
                     VALUES (:name, :description, :category, :protocol, :endpoint, :documentation_url, :http_method)'
                );
                $stmt->execute([
                    'name' => trim((string) $_POST['name']),
                    'description' => trim((string) $_POST['description']),
                    'category' => trim((string) $_POST['category']),
                    'protocol' => $protocol,
                    'endpoint' => trim((string) $_POST['endpoint']),
                    'documentation_url' => trim((string) $_POST['documentation_url']),
                    'http_method' => strtoupper(trim((string) $_POST['http_method'])),
                ]);
                $message = 'El servicio fue agregado al catálogo.';
            }

            if ($message !== '' && $messageType === 'success') {
                $_SESSION['flash_message'] = $message;
                $_SESSION['flash_type'] = $messageType;
                header('Location: admin.php');
                exit;
            }
        } catch (Throwable $exception) {
            $message = $exception instanceof InvalidArgumentException
                ? $exception->getMessage()
                : 'No se pudo completar la operación.';
            $messageType = 'error';
            error_log($exception->getMessage());
        }
    }
}

$services = [];
$searches = [];
$stats = ['services' => 0, 'searches' => 0, 'rest' => 0, 'soap' => 0];
if (is_admin()) {
    try {
        $services = db()->query('SELECT * FROM services ORDER BY created_at DESC')->fetchAll();
        $searches = db()->query('SELECT * FROM search_history ORDER BY searched_at DESC LIMIT 100')->fetchAll();
        $stats['services'] = count($services);
        $stats['searches'] = (int) db()->query('SELECT COUNT(*) FROM search_history')->fetchColumn();
        foreach ($services as $service) {
            $stats[strtolower($service['protocol'])]++;
        }
    } catch (PDOException $exception) {
        $message = 'No se pudo conectar con MySQL. Importa el archivo SQL primero.';
        $messageType = 'error';
        error_log($exception->getMessage());
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administración | <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="admin-page">
<header class="topbar">
    <a class="brand" href="index.php"><img src="assets/img/logo.svg" alt="" width="42" height="42"><span><strong>WS</strong> BUS</span></a>
    <nav><a href="index.php">Volver al buscador</a><?php if (is_admin()): ?><a href="cuentas.php">Cuentas</a><a href="admin.php?logout=1">Cerrar sesión</a><?php endif; ?></nav>
</header>
<main class="admin-main">
<?php if (!is_admin()): ?>
    <section class="login-panel">
        <p class="eyebrow">ACCESO RESTRINGIDO</p>
        <h1>Acceso al sistema</h1>
        <p>La pantalla disponible se asignará automáticamente de acuerdo con tu rol.</p>
        <?php if ($message): ?><div class="alert <?= e($messageType) ?>"><?= e($message) ?></div><?php endif; ?>
        <form method="post" class="stack-form">
            <input type="hidden" name="login" value="1">
            <label><span>Usuario</span><input name="username" required autocomplete="username"></label>
            <label><span>Contraseña</span><input type="password" name="password" required autocomplete="current-password"></label>
            <button class="primary-button" type="submit">Iniciar sesión</button>
        </form>
        <div class="login-roles">
            <p><strong>Administrador</strong><span>Catálogo y cuentas</span></p>
            <p><strong>Tester</strong><span>Herramientas de pruebas</span></p>
        </div>
    </section>
<?php else: ?>
    <section class="admin-header">
        <div><p class="eyebrow">CONTROL DEL BUS</p><h1>Administración</h1><p>Alta y eliminación controlada de datos del catálogo.</p></div>
        <div class="stats"><div><strong><?= $stats['services'] ?></strong><span>Servicios</span></div><div><strong><?= $stats['searches'] ?></strong><span>Búsquedas</span></div><div><strong><?= $stats['rest'] ?>/<?= $stats['soap'] ?></strong><span>REST / SOAP</span></div></div>
    </section>
    <?php if ($message): ?><div class="alert <?= e($messageType) ?>"><?= e($message) ?></div><?php endif; ?>

    <section class="admin-section">
        <div class="section-heading"><div><p class="eyebrow">CATÁLOGO</p><h2>Agregar servicio</h2></div></div>
        <form method="post" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="add_service">
            <label><span>Nombre</span><input name="name" maxlength="100" required></label>
            <label><span>Categoría</span><input name="category" maxlength="60" required></label>
            <label><span>Protocolo</span><select name="protocol" required><option value="REST">REST</option><option value="SOAP">SOAP</option></select></label>
            <label><span>Método</span><input name="http_method" value="GET" maxlength="20" required></label>
            <label class="wide"><span>Descripción</span><textarea name="description" rows="2" maxlength="500" required></textarea></label>
            <label class="wide"><span>Endpoint</span><input type="url" name="endpoint" maxlength="255" required></label>
            <label class="wide"><span>URL de documentación</span><input type="url" name="documentation_url" maxlength="255" required></label>
            <button class="primary-button" type="submit">Guardar servicio</button>
        </form>
    </section>

    <section class="admin-section">
        <div class="section-heading"><div><p class="eyebrow">SERVICIOS</p><h2>Catálogo registrado</h2></div><span><?= count($services) ?> registros</span></div>
        <div class="table-wrap"><table><thead><tr><th>Servicio</th><th>Tipo</th><th>Categoría</th><th>Endpoint</th><th>Acción</th></tr></thead><tbody>
        <?php foreach ($services as $service): ?><tr><td><strong><?= e($service['name']) ?></strong></td><td><span class="protocol <?= strtolower(e($service['protocol'])) ?>"><?= e($service['protocol']) ?></span></td><td><?= e($service['category']) ?></td><td><code><?= e($service['endpoint']) ?></code></td><td><form method="post" class="confirm-form" data-confirm="¿Eliminar este servicio?"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_service"><input type="hidden" name="id" value="<?= (int) $service['id'] ?>"><button class="danger-button" type="submit" data-busy-text="Eliminando…">Eliminar</button></form></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </section>

    <section class="admin-section">
        <div class="section-heading"><div><p class="eyebrow">TRAZABILIDAD</p><h2>Historial de búsquedas</h2></div>
            <form method="post" class="confirm-form" data-confirm="¿Borrar todo el historial? Esta acción no se puede deshacer."><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="clear_searches"><button class="danger-button" type="submit" data-busy-text="Borrando…">Borrar todas las búsquedas</button></form>
        </div>
        <div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Término</th><th>Filtros</th><th>Resultados</th><th>Acción</th></tr></thead><tbody>
        <?php if (!$searches): ?><tr><td colspan="5" class="muted">Aún no hay búsquedas registradas.</td></tr><?php endif; ?>
        <?php foreach ($searches as $search): ?><tr><td><?= e($search['searched_at']) ?></td><td><?= e($search['search_term'] ?: '(sin texto)') ?></td><td><?= e(trim($search['category'] . ' ' . $search['protocol']) ?: 'Todos') ?></td><td><?= (int) $search['result_count'] ?></td><td><form method="post" class="confirm-form" data-confirm="¿Eliminar esta búsqueda?"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_search"><input type="hidden" name="id" value="<?= (int) $search['id'] ?>"><button class="text-button" type="submit" data-busy-text="Eliminando…">Eliminar</button></form></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </section>
<?php endif; ?>
</main>
<script>
document.querySelectorAll('.confirm-form').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm || '¿Continuar?')) {
            event.preventDefault();
            return;
        }
        const button = form.querySelector('button[type="submit"]');
        if (button) {
            button.disabled = true;
            button.textContent = button.dataset.busyText || 'Procesando…';
        }
    });
});
</script>
</body>
</html>
