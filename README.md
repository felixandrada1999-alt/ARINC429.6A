# CRUD de instrumentos — ARINC429_6A

Aplicación sencilla en PHP y MySQL para crear, listar, editar y eliminar instrumentos.

## Requisitos

- PHP 8 o posterior con las extensiones `mysqli` y `mbstring`.
- MySQL.

## Base de datos

Desde una terminal, crea la base y la tabla:

```bash
sudo mysql < schema.sql
```

La tabla `instrumentos` contiene `ins_id` como clave primaria autoincremental y `ins_descripcion` como texto obligatorio de hasta 50 caracteres.

## Conexión

La aplicación lee estas variables de entorno (valores predeterminados indicados):

| Variable | Predeterminado |
|---|---|
| `MYSQL_HOST` | `127.0.0.1` |
| `MYSQL_USER` | `root` |
| `MYSQL_PASSWORD` | vacío |
| `MYSQL_DATABASE` | `ARINC429_6A` |

Si MySQL no permite que PHP se conecte con el usuario `root`, crea un usuario local para la aplicación desde `sudo mysql`:

```sql
CREATE USER 'arinc_app'@'127.0.0.1' IDENTIFIED BY 'elige_una_clave';
GRANT SELECT, INSERT, UPDATE, DELETE ON ARINC429_6A.instrumentos TO 'arinc_app'@'127.0.0.1';
```

Inicia PHP con ese usuario y su contraseña:

## Ejecutar

Desde la carpeta del proyecto:

```bash
MYSQL_USER=arinc_app MYSQL_PASSWORD='tu_clave' php -S 127.0.0.1:8000
```

Abre <http://127.0.0.1:8000> en el navegador. Prueba crear, editar y eliminar registros; los cambios se guardan en la tabla `instrumentos`.

## Vista previa visual

Abre [`demo.html`](./demo.html) para probar una vista alternativa con búsqueda y estadísticas. Esta demostración guarda sus datos en el almacenamiento local del navegador; no se conecta a MySQL. La aplicación conectada a la base de datos sigue siendo `index.php`.
