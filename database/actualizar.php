<?php
// Ejecutar desde consola: C:\xampp\php\php.exe database\actualizar.php
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once __DIR__.'/../includes/quality.php';
quality_schema();
echo "Tablas de calidad y cuentas disponibles.\n";
