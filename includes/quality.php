<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

function quality_schema(): void {
    ensure_test_cases_table();
    db()->exec("CREATE TABLE IF NOT EXISTS quality_records (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, kind VARCHAR(30) NOT NULL, title VARCHAR(150) NOT NULL, project VARCHAR(100) NOT NULL, responsible VARCHAR(100) NOT NULL, record_date DATE NOT NULL, status VARCHAR(30) NOT NULL, case_id BIGINT UNSIGNED NULL, payload LONGTEXT NOT NULL, created_by VARCHAR(100) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX(kind), FOREIGN KEY(case_id) REFERENCES test_cases(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    db()->exec("CREATE TABLE IF NOT EXISTS quality_files (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, record_id BIGINT UNSIGNED NULL, case_id BIGINT UNSIGNED NULL, original_name VARCHAR(255) NOT NULL, stored_name VARCHAR(100) NOT NULL, mime VARCHAR(100) NOT NULL, size_bytes INT NOT NULL, uploaded_by VARCHAR(100) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(record_id) REFERENCES quality_records(id), FOREIGN KEY(case_id) REFERENCES test_cases(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    db()->exec("CREATE TABLE IF NOT EXISTS app_users (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, username VARCHAR(60) UNIQUE NOT NULL, password_hash VARCHAR(255) NOT NULL, role ENUM('admin','tester') NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function quality_modules(): array {
    return [
      'equivalence'=>['Matriz de clases de equivalencia','Agrupa entradas válidas e inválidas para comprobar el comportamiento del sistema.','Caja negra', ['variable'=>'Campo o variable','domain'=>'Dominio y reglas de validación'], ['Clase','Condición o intervalo','Tipo (válida / inválida)','Dato representativo','Resultado esperado']],
      'boundary'=>['Análisis de valor límite','Calcula los valores de frontera y registra lo que esperas de cada entrada.','Caja negra',['variable'=>'Variable numérica','minimum'=>'Valor mínimo','maximum'=>'Valor máximo','step'=>'Incremento mínimo'],['Valor de prueba','Posición respecto al límite','Resultado esperado','Resultado obtenido']],
      'decision'=>['Tabla de decisión','Relaciona condiciones, combinaciones y acciones esperadas.','Caja negra',['business_rule'=>'Regla de negocio','conditions'=>'Condiciones que intervienen'],['Regla','Combinación de condiciones (Sí / No)','Acción esperada','Resultado obtenido']],
      'coverage'=>['Cobertura de caja blanca','Registra elementos ejecutados y calcula el porcentaje de cobertura.','Caja blanca',['source'=>'Archivo y función','code'=>'Fragmento de código','metric'=>'Tipo de cobertura','total'=>'Elementos totales','covered'=>'Elementos ejecutados'],['Ruta o elemento','Condición / entrada','Caso que lo ejecuta','Resultado']],
      'plan'=>['Plan de pruebas del proyecto','Organiza alcance, calendario, recursos y criterios de aceptación.','Planificación',['scope'=>'Alcance','excluded'=>'Fuera de alcance','strategy'=>'Estrategia y técnicas','environment'=>'Ambiente y herramientas','start'=>'Fecha de inicio','end'=>'Fecha de finalización','entry'=>'Criterios de entrada','exit'=>'Criterios de salida','risks'=>'Riesgos y mitigación'],['Actividad','Responsable','Fecha prevista','Entregable']],
      'rubric'=>['Rúbrica de evaluación','Define criterios con ponderación y calcula la calificación final.','Evaluación',['evaluated'=>'Persona o equipo evaluado','scale'=>'Descripción de niveles (1 a 5)'],['Criterio','Peso (%)','Nivel obtenido (1–5)','Retroalimentación']],
      'assessment'=>['Autoevaluación y coevaluación','Valora aportes y registra compromisos de mejora.','Evaluación',['mode'=>'Modalidad (Autoevaluación / Coevaluación)','evaluated'=>'Persona evaluada','strengths'=>'Fortalezas','improvements'=>'Aspectos por mejorar','commitments'=>'Compromisos'],['Criterio','Puntuación (1–5)','Justificación']],
      'portfolio'=>['Portafolio de evidencias','Organiza archivos y relaciona cada evidencia con su propósito.','Evidencias',['description'=>'Descripción de las evidencias','category'=>'Categoría','version'=>'Versión o entrega','reflection'=>'Qué demuestra esta evidencia'],['Evidencia','Actividad relacionada','Descripción','Referencia']],
      'incident'=>['Registro de incidentes','Reporta defectos reproducibles y da seguimiento a su resolución.','Incidentes',['severity'=>'Severidad','priority'=>'Prioridad','environment'=>'Ambiente y versión','steps'=>'Pasos para reproducir','expected'=>'Resultado esperado','actual'=>'Resultado obtenido','assigned'=>'Asignado a','resolution'=>'Resolución y verificación'],['Paso','Entrada o acción','Comportamiento observado','Notas']],
    ];
}
function quality_uploads(): array {
    $prepared=[];
    foreach (($_FILES['attachments']['error'] ?? []) as $i=>$error) {
        if ($error===UPLOAD_ERR_NO_FILE) continue;
        if ($error!==UPLOAD_ERR_OK) throw new InvalidArgumentException('No se pudo recibir un archivo. Revisa el límite de carga del servidor.');
        if (count($prepared)>=5 || $_FILES['attachments']['size'][$i]>5*1024*1024) throw new InvalidArgumentException('Adjunta hasta 5 archivos de máximo 5 MB cada uno.');
        $tmp=$_FILES['attachments']['tmp_name'][$i];
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $ext=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp','application/pdf'=>'pdf','text/plain'=>'txt'][$mime]??null;
        if (!$ext) throw new InvalidArgumentException('Formato no permitido. Usa PNG, JPG, WebP, PDF o TXT.');
        $prepared[]=['tmp'=>$tmp,'name'=>mb_substr(basename($_FILES['attachments']['name'][$i]),0,255),'mime'=>$mime,'size'=>$_FILES['attachments']['size'][$i],'stored'=>bin2hex(random_bytes(20)).'.'.$ext];
    }
    return $prepared;
}
function quality_save_files(array $files, ?int $recordId, ?int $caseId): void {
    $dir=__DIR__.'/../storage/evidence';
    if (!is_dir($dir) && !mkdir($dir,0755,true)) throw new RuntimeException('No se pudo preparar el almacenamiento.');
    foreach($files as $f) {
        if (!move_uploaded_file($f['tmp'],$dir.'/'.$f['stored'])) throw new RuntimeException('No se pudo guardar el archivo.');
        $s=db()->prepare('INSERT INTO quality_files(record_id,case_id,original_name,stored_name,mime,size_bytes,uploaded_by) VALUES(?,?,?,?,?,?,?)');
        $s->execute([$recordId,$caseId,$f['name'],$f['stored'],$f['mime'],$f['size'],$_SESSION['username']]);
    }
}
