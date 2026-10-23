# Diagramas del sistema

Estos esquemas usan sintaxis Mermaid y pueden abrirse en GitHub, Mermaid Live Editor o extensiones compatibles de Visual Studio Code.

## Casos de uso

```mermaid
flowchart LR
    Usuario((Usuario)) --> Buscar[Buscar servicios]
    Usuario --> Filtrar[Filtrar por categoría y protocolo]
    Usuario --> VerJSON[Consultar respuesta JSON]
    Administrador((Administrador)) --> Iniciar[Iniciar sesión]
    Administrador --> Agregar[Agregar servicio]
    Administrador --> EliminarS[Eliminar servicio]
    Administrador --> ConsultarH[Consultar historial]
    Administrador --> EliminarH[Eliminar búsquedas]
```

## Diagrama de componentes

```mermaid
flowchart LR
    Navegador[Navegador web] -->|HTTP Fetch| Bus[Aplicación PHP y API JSON]
    Bus -->|PDO y SQL preparado| MySQL[(MySQL)]
    Bus -->|Enlaces de documentación| REST[Servicios REST]
    Bus -->|Enlaces WSDL| SOAP[Servicios SOAP]
    MySQL --> Servicios[(services)]
    MySQL --> Historial[(search_history)]
```

## Modelo de datos

```mermaid
erDiagram
    SERVICES {
        int id PK
        varchar name
        varchar description
        varchar category
        enum protocol
        varchar endpoint UK
        varchar documentation_url
        varchar http_method
        enum status
        timestamp created_at
    }
    SEARCH_HISTORY {
        bigint id PK
        varchar search_term
        varchar category
        varchar protocol
        int result_count
        varchar ip_address
        timestamp searched_at
    }
```

El historial no almacena una relación física con los servicios porque conserva el criterio ejecutado y la cantidad de coincidencias. Esta decisión evita duplicar resultados que pueden cambiar cuando se actualiza el catálogo.

