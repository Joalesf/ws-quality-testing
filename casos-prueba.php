<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/quality.php';
start_secure_session();

if (is_admin()) {
    header('Location: admin.php?forbidden=tester');
    exit;
}

if (!is_tester()) {
    header('Location: admin.php?next=casos-prueba.php');
    exit;
}

$message = '';
$messageType = 'success';
$savedCaseCode = '';

try {
    quality_schema();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!valid_csrf($_POST['csrf_token'] ?? null)) {
            throw new RuntimeException('La solicitud caducó. Recarga la página e inténtalo nuevamente.');
        }

        $required = [
            'title' => 'Título del caso',
            'module' => 'Módulo',
            'technique' => 'Técnica',
            'objective' => 'Objetivo',
            'preconditions' => 'Precondiciones',
            'input_data' => 'Datos de entrada',
            'steps' => 'Pasos',
            'expected_result' => 'Resultado esperado',
            'responsible' => 'Responsable',
            'execution_date' => 'Fecha de ejecución',
        ];

        foreach ($required as $field => $label) {
            if (trim((string) ($_POST[$field] ?? '')) === '') {
                throw new InvalidArgumentException("Completa el campo: {$label}.");
            }
        }

        $allowedTechniques = ['Caja blanca', 'Caja negra'];
        $allowedRequirementTypes = ['Funcional', 'No funcional'];
        $allowedPriorities = ['Alta', 'Media', 'Baja'];
        $allowedStatuses = ['Pendiente', 'Éxito', 'Fallo', 'Bloqueado'];

        $technique = (string) $_POST['technique'];
        $requirementType = (string) ($_POST['requirement_type'] ?? 'No funcional');
        $priority = (string) ($_POST['priority'] ?? 'Media');
        $status = (string) ($_POST['status'] ?? 'Pendiente');

        if (!in_array($technique, $allowedTechniques, true)
            || !in_array($requirementType, $allowedRequirementTypes, true)
            || !in_array($priority, $allowedPriorities, true)
            || !in_array($status, $allowedStatuses, true)) {
            throw new InvalidArgumentException('Uno de los valores seleccionados no es válido.');
        }

        $executionDate = DateTimeImmutable::createFromFormat('Y-m-d', (string) $_POST['execution_date']);
        if (!$executionDate || $executionDate->format('Y-m-d') !== (string) $_POST['execution_date']) {
            throw new InvalidArgumentException('La fecha de ejecución no es válida.');
        }

        $uploads = quality_uploads();
        $stmt = db()->prepare(
            'INSERT INTO test_cases (
                title, module, requirement_code, requirement_type, technique, subtechnique,
                objective, preconditions, input_data, steps, expected_result, actual_result,
                status, priority, responsible, execution_date, environment, evidence, observations
            ) VALUES (
                :title, :module, :requirement_code, :requirement_type, :technique, :subtechnique,
                :objective, :preconditions, :input_data, :steps, :expected_result, :actual_result,
                :status, :priority, :responsible, :execution_date, :environment, :evidence, :observations
            )'
        );
        $stmt->execute([
            'title' => trim((string) $_POST['title']),
            'module' => trim((string) $_POST['module']),
            'requirement_code' => trim((string) ($_POST['requirement_code'] ?? '')),
            'requirement_type' => $requirementType,
            'technique' => $technique,
            'subtechnique' => trim((string) ($_POST['subtechnique'] ?? '')),
            'objective' => trim((string) $_POST['objective']),
            'preconditions' => trim((string) $_POST['preconditions']),
            'input_data' => trim((string) $_POST['input_data']),
            'steps' => trim((string) $_POST['steps']),
            'expected_result' => trim((string) $_POST['expected_result']),
            'actual_result' => trim((string) ($_POST['actual_result'] ?? '')),
            'status' => $status,
            'priority' => $priority,
            'responsible' => trim((string) $_POST['responsible']),
            'execution_date' => $executionDate->format('Y-m-d'),
            'environment' => trim((string) ($_POST['environment'] ?? '')),
            'evidence' => trim((string) ($_POST['evidence'] ?? '')),
            'observations' => trim((string) ($_POST['observations'] ?? '')),
        ]);

        $caseId = (int) db()->lastInsertId();
        quality_save_files($uploads, null, $caseId);
        $savedCaseCode = test_case_code($caseId);
        $message = "Caso {$savedCaseCode} guardado correctamente.";
        $_POST = [];
    }

    $recentCases = db()->query(
        'SELECT id, title, module, technique, requirement_type, status, priority, responsible, execution_date
         FROM test_cases ORDER BY id DESC LIMIT 8'
    )->fetchAll();
} catch (Throwable $exception) {
    $recentCases = [];
    $message = $exception instanceof InvalidArgumentException || $exception instanceof RuntimeException
        ? $exception->getMessage()
        : 'No se pudo guardar el caso. Verifica que MySQL esté iniciado.';
    $messageType = 'error';
    error_log($exception->getMessage());
}

function old(string $field, string $default = ''): string
{
    return e((string) ($_POST[$field] ?? $default));
}

function selected(string $field, string $value, string $default = ''): string
{
    return (string) ($_POST[$field] ?? $default) === $value ? ' selected' : '';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Registro y control de casos de prueba del Bus de Web Services.">
    <title>Casos de prueba | <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="test-page">
<header class="topbar">
    <a class="brand" href="index.php" aria-label="Inicio de WS Bus">
        <img src="assets/img/logo.svg" alt="" width="42" height="42">
        <span><strong>WS</strong> BUS</span>
    </a>
    <nav aria-label="Navegación principal">
        <a href="index.php">Buscador</a>
        <a href="testing.php">Panel de pruebas</a>
        <a class="admin-link" href="admin.php?logout=1">Cerrar sesión</a>
    </nav>
</header>

<main class="test-main">
    <section class="test-intro">
        <div>
            <p class="eyebrow">CONTROL DE CALIDAD · CASOS DE PRUEBA</p>
            <h1>Registrar una prueba</h1>
            <p>Documenta qué se validará, cómo se ejecutará y cuál fue el resultado obtenido.</p>
        </div>
        <div class="test-intro-meta" aria-label="Información del registro">
            <span><b>ID</b> Automático</span>
            <span><b>Fecha</b> <?= e(date('d/m/Y')) ?></span>
            <span><b>Rol</b> Tester</span>
            <span><b>Proyecto</b> WS Bus</span>
        </div>
    </section>

    <?php if ($message): ?>
        <div class="alert <?= e($messageType) ?>" role="status"><?= e($message) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="test-form" id="test-case-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <section class="form-card">
            <div class="form-card-heading"><span>01</span><div><h2>Identificación</h2><p>Información general y relación con el requerimiento.</p></div></div>
            <div class="form-grid">
                <label class="span-2"><span>Título del caso *</span><input name="title" maxlength="150" required value="<?= old('title') ?>" placeholder="Ej.: Validar acceso al panel administrativo"></label>
                <label><span>Módulo *</span><input name="module" maxlength="100" required value="<?= old('module', 'Administración') ?>" placeholder="Ej.: Administración"></label>
                <label><span>Código del requerimiento</span><input name="requirement_code" maxlength="30" value="<?= old('requirement_code', 'RNF-01') ?>" placeholder="RNF-01"></label>
                <label><span>Tipo de requerimiento</span><select name="requirement_type"><option<?= selected('requirement_type', 'No funcional', 'No funcional') ?>>No funcional</option><option<?= selected('requirement_type', 'Funcional', 'No funcional') ?>>Funcional</option></select></label>
                <label><span>Prioridad</span><select name="priority"><option<?= selected('priority', 'Alta', 'Media') ?>>Alta</option><option<?= selected('priority', 'Media', 'Media') ?>>Media</option><option<?= selected('priority', 'Baja', 'Media') ?>>Baja</option></select></label>
                <label><span>Responsable *</span><input name="responsible" maxlength="100" required value="<?= old('responsible') ?>" placeholder="Nombre del evaluador"></label>
                <label><span>Fecha de ejecución *</span><input type="date" name="execution_date" required value="<?= old('execution_date', date('Y-m-d')) ?>"></label>
            </div>
        </section>

        <section class="form-card">
            <div class="form-card-heading"><span>02</span><div><h2>Diseño de la prueba</h2><p>Selecciona la técnica y define las condiciones de validación.</p></div></div>
            <div class="form-grid">
                <fieldset class="technique-choice span-2">
                    <legend>Técnica *</legend>
                    <label><input type="radio" name="technique" value="Caja blanca"<?= (($_POST['technique'] ?? 'Caja blanca') === 'Caja blanca') ? ' checked' : '' ?>><span><b>Caja blanca</b><small>Valida lógica, rutas, condiciones y código interno.</small></span></label>
                    <label><input type="radio" name="technique" value="Caja negra"<?= (($_POST['technique'] ?? '') === 'Caja negra') ? ' checked' : '' ?>><span><b>Caja negra</b><small>Valida entradas y salidas desde la vista del usuario.</small></span></label>
                </fieldset>
                <label><span>Subtécnica</span><select name="subtechnique"><option value="">Seleccionar…</option><option<?= selected('subtechnique', 'Caminos básicos') ?>>Caminos básicos</option><option<?= selected('subtechnique', 'Cobertura de decisiones') ?>>Cobertura de decisiones</option><option<?= selected('subtechnique', 'Cobertura de condiciones') ?>>Cobertura de condiciones</option><option<?= selected('subtechnique', 'Flujo de datos') ?>>Flujo de datos</option><option<?= selected('subtechnique', 'Partición de equivalencia') ?>>Partición de equivalencia</option><option<?= selected('subtechnique', 'Valores límite') ?>>Valores límite</option></select></label>
                <label><span>Ambiente</span><input name="environment" maxlength="150" value="<?= old('environment', 'XAMPP · PHP 8 · MySQL · Chrome') ?>" placeholder="Servidor, navegador y versión"></label>
                <label class="span-2"><span>Objetivo *</span><textarea name="objective" rows="3" maxlength="800" required placeholder="Indica exactamente qué comportamiento se comprobará."><?= old('objective') ?></textarea></label>
                <label><span>Precondiciones *</span><textarea name="preconditions" rows="4" maxlength="1200" required placeholder="Estado necesario antes de ejecutar la prueba."><?= old('preconditions') ?></textarea></label>
                <label><span>Datos de entrada *</span><textarea name="input_data" rows="4" maxlength="1200" required placeholder="Usuario, contraseña, filtros u otros datos."><?= old('input_data') ?></textarea></label>
            </div>
        </section>

        <section class="form-card">
            <div class="form-card-heading"><span>03</span><div><h2>Ejecución y resultados</h2><p>Describe los pasos reproducibles y compara el resultado.</p></div></div>
            <div class="form-grid">
                <label class="span-2"><span>Pasos de ejecución *</span><textarea name="steps" rows="6" maxlength="2500" required placeholder="1. Abrir la pantalla de administración.&#10;2. Ingresar las credenciales.&#10;3. Presionar Iniciar sesión."><?= old('steps') ?></textarea><small>Escribe un paso por línea y numéralos para facilitar la exposición.</small></label>
                <label><span>Resultado esperado *</span><textarea name="expected_result" rows="4" maxlength="1200" required placeholder="Comportamiento que debe producir el sistema."><?= old('expected_result') ?></textarea></label>
                <label><span>Resultado obtenido</span><textarea name="actual_result" rows="4" maxlength="1200" placeholder="Completa este campo después de ejecutar la prueba."><?= old('actual_result') ?></textarea></label>
                <label><span>Estado</span><select name="status"><option<?= selected('status', 'Pendiente', 'Pendiente') ?>>Pendiente</option><option<?= selected('status', 'Éxito', 'Pendiente') ?>>Éxito</option><option<?= selected('status', 'Fallo', 'Pendiente') ?>>Fallo</option><option<?= selected('status', 'Bloqueado', 'Pendiente') ?>>Bloqueado</option></select></label>
                <label><span>Evidencia</span><input name="evidence" maxlength="500" value="<?= old('evidence') ?>" placeholder="Nombre de captura, ruta o enlace"></label>
                <label class="span-2"><span>Observaciones</span><textarea name="observations" rows="3" maxlength="1200" placeholder="Notas, incidencias o recomendaciones adicionales."><?= old('observations') ?></textarea></label>
            </div>
        </section>

        <section class="form-card"><h2>Adjuntar evidencia</h2><p>Capturas, fotografías, PDF o registros de texto. Hasta 5 archivos de 5 MB cada uno.</p><input type="file" name="attachments[]" multiple accept="image/png,image/jpeg,image/webp,application/pdf,text/plain"></section>
        <div class="form-actions">
            <button type="reset" class="secondary-button">Limpiar formulario</button>
            <button type="submit" class="primary-button">Guardar caso de prueba</button>
        </div>
    </form>

    <section class="recent-tests" aria-labelledby="recent-title">
        <div class="section-heading"><div><p class="eyebrow">TRAZABILIDAD</p><h2 id="recent-title">Registros recientes</h2></div><span><?= count($recentCases) ?> mostrado(s)</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>ID</th><th>Caso</th><th>Técnica</th><th>Requerimiento</th><th>Responsable</th><th>Fecha</th><th>Estado</th></tr></thead>
                <tbody>
                <?php if (!$recentCases): ?><tr><td colspan="7" class="muted">Aún no hay casos registrados.</td></tr><?php endif; ?>
                <?php foreach ($recentCases as $case): ?>
                    <tr>
                        <td><strong><?= e(test_case_code((int) $case['id'])) ?></strong></td>
                        <td><strong><?= e($case['title']) ?></strong><small><?= e($case['module']) ?> · <?= e($case['priority']) ?></small></td>
                        <td><?= e($case['technique']) ?></td>
                        <td><?= e($case['requirement_type']) ?></td>
                        <td><?= e($case['responsible']) ?></td>
                        <td><?= e(date('d/m/Y', strtotime($case['execution_date']))) ?></td>
                        <td><span class="status-pill status-<?= e(status_slug($case['status'])) ?>"><?= e($case['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<section class="admin-main"><h2>Evidencias de casos</h2><?php $attachments=db()->query('SELECT f.id,f.original_name,f.case_id FROM quality_files f WHERE f.case_id IS NOT NULL ORDER BY f.id DESC LIMIT 30')->fetchAll();foreach($attachments as $attachment):?><p><a href="evidencia.php?id=<?= (int)$attachment['id'] ?>" target="_blank" rel="noopener"><?= e(test_case_code((int)$attachment['case_id']).' · '.$attachment['original_name']) ?></a></p><?php endforeach;?></section>

<footer><span>WS BUS v<?= e(APP_VERSION) ?></span><span>Registro de casos de prueba</span></footer>
</body>
</html>
