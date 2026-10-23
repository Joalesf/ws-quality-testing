<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$databaseError = false;
try {
    $categoryList = categories();
    $initialServices = search_services();
} catch (PDOException $exception) {
    $databaseError = true;
    $categoryList = [];
    $initialServices = [];
    error_log($exception->getMessage());
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Bus académico para localizar servicios web REST y SOAP.">
    <title><?= e(APP_NAME) ?> | Motor de búsqueda</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="index.php" aria-label="Inicio de WS Bus">
        <img src="assets/img/logo.svg" alt="" width="42" height="42">
        <span><strong>WS</strong> BUS</span>
    </a>
    <nav aria-label="Navegación principal">
        <a href="#catalogo">Catálogo</a>
        <a href="api/services.php" target="_blank" rel="noopener">API JSON</a>
        <a href="casos-prueba.php">Casos de prueba</a>
        <a class="admin-link" href="admin.php">Administración</a>
    </nav>
</header>

<main>
    <section class="hero">
        <div class="hero-copy">
            <p class="eyebrow">MOTOR DE BÚSQUEDA DE WEB SERVICES</p>
            <h1>Encuentra, compara y consume servicios web</h1>
            <p>Un bus centralizado para localizar endpoints REST y SOAP por necesidad, categoría o protocolo.</p>
            <div class="hero-stats" aria-label="Resumen del catálogo">
                <span><strong><?= count($initialServices) ?></strong> servicios indexados</span>
                <span><strong>2</strong> protocolos</span>
                <span><strong>JSON</strong> respuesta del bus</span>
            </div>
        </div>
        <div class="protocol-visual" aria-hidden="true">
            <span>Necesidad</span><i></i><b>BUS</b><i></i><span>Endpoint</span>
        </div>
    </section>

    <section class="search-panel" aria-labelledby="search-title">
        <div>
            <p class="eyebrow">CONSULTA DINÁMICA</p>
            <h2 id="search-title">¿Qué web service necesitas?</h2>
        </div>
        <form id="search-form" class="search-form" autocomplete="off">
            <label class="search-input">
                <span class="sr-only">Término de búsqueda</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
                <input id="query" name="q" type="search" maxlength="120" placeholder="Buscar por nombre, función o categoría...">
            </label>
            <label>
                <span>Categoría</span>
                <select id="category" name="category">
                    <option value="">Todas</option>
                    <?php foreach ($categoryList as $category): ?>
                        <option value="<?= e($category) ?>"><?= e($category) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Protocolo</span>
                <select id="protocol" name="protocol">
                    <option value="">Todos</option>
                    <option value="REST">REST</option>
                    <option value="SOAP">SOAP</option>
                </select>
            </label>
            <button class="primary-button" type="submit">Buscar</button>
        </form>
        <div class="quick-searches" aria-label="Búsquedas rápidas">
            <span>Prueba con</span>
            <button type="button" data-query="clima">Clima</button>
            <button type="button" data-query="países">Países</button>
            <button type="button" data-query="libros">Libros</button>
            <button type="button" data-query="números">Números</button>
        </div>
        <?php if ($databaseError): ?>
            <div class="alert error">No hay conexión con MySQL. Importe <strong>database/bus_web_services.sql</strong> y revise config/config.php.</div>
        <?php endif; ?>
    </section>

    <section id="catalogo" class="catalog-section" aria-live="polite">
        <div class="section-heading">
            <div>
                <p class="eyebrow">RESULTADOS</p>
                <h2>Catálogo disponible</h2>
            </div>
            <p id="result-count"><?= count($initialServices) ?> servicio(s)</p>
        </div>
        <div id="loading" class="loading" hidden>Cargando servicios...</div>
        <div id="service-grid" class="service-grid">
            <?php foreach ($initialServices as $service): ?>
                <article class="service-card">
                    <div class="card-top"><span class="protocol <?= strtolower(e($service['protocol'])) ?>"><?= e($service['protocol']) ?></span><span><?= e($service['category']) ?></span></div>
                    <h3><?= e($service['name']) ?></h3>
                    <p><?= e($service['description']) ?></p>
                    <code><?= e($service['http_method']) ?> <?= e($service['endpoint']) ?></code>
                    <div class="card-actions"><a href="<?= e($service['documentation_url']) ?>" target="_blank" rel="noopener">Documentación</a><a href="api/services.php?id=<?= (int) $service['id'] ?>" target="_blank" rel="noopener">Ver JSON</a></div>
                </article>
            <?php endforeach; ?>
        </div>
        <div id="empty-state" class="empty-state" <?= $initialServices ? 'hidden' : '' ?>>
            <strong>No encontramos coincidencias</strong>
            <span>Prueba otra palabra o elimina uno de los filtros.</span>
        </div>
    </section>
</main>

<footer>
    <span>WS BUS v<?= e(APP_VERSION) ?></span>
    <span>José Sánchez · Abdias Ruedas</span>
</footer>
<script src="assets/js/app.js" defer></script>
</body>
</html>
