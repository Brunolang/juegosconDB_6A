
# CRUD web de `usuarios`

La interfaz está en `index.html` (HTML, CSS y JavaScript). `api.php` realiza las operaciones del servidor y se conecta a MySQL; `config.php` contiene la configuración de conexión. HTML por sí solo no puede conectarse a MySQL de forma segura, por eso la página usa una API PHP local.

El CRUD usa la base de datos `juegos_6A` y la tabla `usuarios`. Se utilizan estas columnas (las mismas que indicaste para `usuarioz`):

- `usu_id` — identificador autoincremental y clave primaria.
- `usu_usuario` — nombre de usuario (hasta 50 caracteres).
- `usu_nombre` — nombre (hasta 50 caracteres).
- `usu_apellido` — apellido (hasta 50 caracteres).

## 1. Crear un usuario de MySQL para el CRUD

En Konsole, abre MySQL con `sudo mysql` y ejecuta estos comandos. Reemplaza `TU_CLAVE` por una contraseña propia y usa esa misma en las dos sentencias `CREATE USER`:

```sql
CREATE USER IF NOT EXISTS 'crud_app'@'localhost' IDENTIFIED BY 'TU_CLAVE';
CREATE USER IF NOT EXISTS 'crud_app'@'127.0.0.1' IDENTIFIED BY 'TU_CLAVE';
GRANT SELECT, INSERT, UPDATE, DELETE ON juegos_6A.usuarios TO 'crud_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON juegos_6A.usuarios TO 'crud_app'@'127.0.0.1';
EXIT;
```

No compartas esa contraseña. Si el usuario ya existía pero no recuerdas su contraseña, dentro de `sudo mysql` puedes restablecerla ejecutando `ALTER USER 'crud_app'@'localhost' IDENTIFIED BY 'TU_CLAVE';` y `ALTER USER 'crud_app'@'127.0.0.1' IDENTIFIED BY 'TU_CLAVE';` antes de los comandos `GRANT`.

## 2. Configurar el proyecto

La primera vez, copia `config.example.php` como `config.php` y edita ese archivo local. El host, puerto, base de datos y usuario ya están configurados; coloca la contraseña de `crud_app` en `password`. El archivo `config.php` está excluido de Git para evitar subir credenciales. No subas contraseñas ni uses la contraseña de `root` en la aplicación.

Si MySQL está en otro host o puerto, cambia `host` o `port` en `config.php`. También se aceptan las variables de entorno `MYSQL_HOST`, `MYSQL_PORT`, `MYSQL_DATABASE`, `MYSQL_USER` y `MYSQL_PASSWORD`.

## 3. Ejecutar en Kubuntu

Necesitas PHP con la extensión PDO MySQL (`php-mysql`). Desde Konsole, en la carpeta del proyecto, inicia el servidor local:

```sh
php -S 127.0.0.1:8000
```

Abre `http://127.0.0.1:8000` en el navegador. Para detener el servidor, vuelve a Konsole y pulsa `Ctrl+C`.

La página incluye listado, búsqueda, creación, edición y eliminación con confirmación. Si muestra error de conexión, verifica que MySQL esté iniciado, que `usuarios` tenga las columnas indicadas dentro de `juegos_6A` y que la contraseña y permisos de `crud_app` coincidan.
