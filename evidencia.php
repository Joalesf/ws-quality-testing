<?php
require_once __DIR__.'/includes/quality.php';
if (!is_tester()) { http_response_code(403); exit('Acceso restringido.'); }
quality_schema();
$s=db()->prepare('SELECT * FROM quality_files WHERE id=?'); $s->execute([(int)($_GET['id']??0)]); $f=$s->fetch();
if (!$f || !is_file(__DIR__.'/storage/evidence/'.$f['stored_name'])) { http_response_code(404); exit('Archivo no disponible.'); }
header('Content-Type: '.$f['mime']); header('X-Content-Type-Options: nosniff'); header('Cache-Control: private, no-store');
header("Content-Disposition: inline; filename*=UTF-8''".rawurlencode($f['original_name']));
readfile(__DIR__.'/storage/evidence/'.$f['stored_name']);
