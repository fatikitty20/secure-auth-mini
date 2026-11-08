# 🔐 Secure Auth Mini

Mini proyecto de portfolio: registro e inicio de sesión en PHP con controles básicos de seguridad.

## Antes → después

- Contraseñas en texto plano → `password_hash()` / `password_verify()`.
- SQL concatenado → consultas preparadas con MySQLi.
- Sin protección CSRF ni límite de intentos → token CSRF y throttling por email/IP.
- Identidad controlada por el navegador → sesión PHP con cookies HttpOnly y SameSite.

**Stack:** PHP 8+ · MySQL · HTML

## Ejecutar

1. Crea una base de datos MySQL y ejecuta [`database/schema.sql`](database/schema.sql).
2. Copia `config.example.php` como `config.php`, completa la conexión y genera un `APP_KEY` aleatorio con `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`.
3. Configura el document root del servidor en `public/`; conserva `app/`, `config.php` y `database/` fuera del directorio público.
4. Sirve la app con HTTPS. Para desarrollo local, el servidor integrado de PHP funciona desde la raíz: `php -S localhost:8000 -t public`.

## Notas de seguridad

Es una demo educativa, no una auditoría ni un sistema listo para cuentas sensibles. Usa HTTPS en producción, protege los archivos privados, limita los privilegios de MySQL y haz copias de seguridad. Las contraseñas deben tener al menos 12 caracteres. El limitador utiliza MySQL y requiere las tablas del schema.
