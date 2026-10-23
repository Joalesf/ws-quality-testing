# ServiceHub Bus

## Espacio de calidad

El tester accede a `testing.php`: dashboard y diez formularios de testing. Los registros pueden guardarse, consultarse y editarse en los nueve módulos nuevos. El formulario original conserva su registro de casos. Incluye adjuntos PNG/JPG/WebP/PDF/TXT, hasta 5 MB por archivo y cinco por envío, accesibles solo con sesión de tester. El administrador crea cuentas desde `cuentas.php`.

Para actualizar una instalación existente sin borrar datos, ejecute `C:\xampp\php\php.exe database\actualizar.php` desde la carpeta del proyecto. Las tablas también se preparan automáticamente al abrir el espacio de calidad. Copie la carpeta completa, incluido `storage/.htaccess`. El usuario de PHP necesita permiso de escritura en `storage`. Configure `upload_max_filesize=5M` y `post_max_size=30M` en PHP si necesita aprovechar el máximo de adjuntos; reinicie Apache tras cambiar PHP.

Motor de búsqueda académico de web services construido con PHP 8, MySQL, HTML, CSS y JavaScript. Incluye catálogo REST y SOAP, consulta dinámica, API JSON, historial y administración de datos.

También incluye un módulo protegido para registrar casos de prueba de caja blanca y caja negra, relacionarlos con requerimientos funcionales o no funcionales y conservar su evidencia.

## Instalación en XAMPP

1. Copie la carpeta `bus-web-services` dentro de `C:\xampp\htdocs\`.
2. Inicie Apache y MySQL desde el panel de XAMPP.
3. Abra phpMyAdmin en `http://localhost:8012/phpmyadmin`.
4. Importe `database/bus_web_services.sql`.
5. Verifique `config/config.php`. La configuración inicial usa MySQL en el puerto `3306`, usuario `root` y contraseña vacía.
6. Abra `http://localhost:8012/bus-web-services/`.

Si Apache usa HTTPS en el puerto 1443, la ruta equivalente es `https://localhost:1443/bus-web-services/`.

## Acceso administrativo

- URL: `http://localhost:8012/bus-web-services/admin.php`
- Administrador: acceso al catálogo e historial.
- Tester: acceso a los casos de prueba.
- URL de casos de prueba: `http://localhost:8012/bus-web-services/casos-prueba.php`

Cada rol puede entrar únicamente en su propio módulo. Copie `config/local.example.php` a `config/local.php`, defina sus credenciales y mantenga ese archivo privado. También puede usar las variables de entorno indicadas en `config/config.php`.

## Endpoints del bus

- `GET /api/services.php`: lista el catálogo.
- `GET /api/services.php?id=1`: obtiene un servicio.
- `GET /api/search.php?q=clima&protocol=REST&category=Clima`: consulta dinámica.
- Agregue `log=true` a la búsqueda para registrar la consulta en el historial.

Ejemplo local:

```text
http://localhost:8012/bus-web-services/api/search.php?q=paises&protocol=REST&log=true
```

## Seguridad incluida

- Consultas preparadas con PDO.
- Escape de salida contra XSS.
- Token CSRF para altas y eliminaciones.
- Sesión administrativa y confirmación antes de destruir datos.
- Errores internos registrados sin exponer credenciales en la API.

## Estructura

```text
bus-web-services/
  api/                 Endpoints JSON
  assets/              CSS y JavaScript
  config/              Aplicación y conexión PDO
  database/            Esquema e información inicial
  includes/            Funciones compartidas
  admin.php            Panel de administración
  casos-prueba.php     Registro y seguimiento de casos de prueba
  index.php            Buscador público
```

