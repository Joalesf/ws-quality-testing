# Restaurar el proyecto

Requisitos: XAMPP con PHP 8.2 y MySQL/MariaDB. No requiere instalar paquetes.

1. Copiar `bus-web-services` en `C:\xampp\htdocs`.
2. Iniciar Apache y MySQL en XAMPP.
3. En phpMyAdmin, importar `database/bus_web_services.sql` para instalar una base nueva con catálogo de ejemplo. Ejecutar `C:\xampp\php\php.exe database\actualizar.php` para preparar las tablas de calidad y cuentas.
4. Revisar los puertos y la conexión en `config/config.php`.
5. Abrir `http://localhost:8012/bus-web-services/admin.php`.

Configure los accesos copiando `config/local.example.php` a `config/local.php` y definiendo contraseñas propias. El RAR privado conserva la configuración local de la instalación original. El repositorio público contiene el código; GitHub Pages no ejecuta PHP ni MySQL.

## Restauración exacta desde el RAR local

El RAR incluye `respaldo-local/base-completa.sql`, que contiene los registros actuales, cuentas y metadatos de evidencias. Importarlo en lugar del SQL inicial para recuperar el estado guardado. Los archivos adjuntos están en `storage/evidence`. Esta carpeta de respaldo se excluye de Git para no publicar información privada.

El administrador gestiona catálogo, historial y creación de cuentas. El tester accede al dashboard y a los diez formularios desde `testing.php`.
