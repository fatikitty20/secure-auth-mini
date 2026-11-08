# 🔐 Secure Auth Mini

Mini proyecto de portfolio: autenticación PHP/MySQL con controles de seguridad básicos y una estructura pequeña, separada por responsabilidades.

## Estructura

```text
app/
├── AuthController.php   # acciones HTTP y mensajes de flujo
├── AuthRateLimiter.php  # intentos fallidos
├── AuthService.php      # registro, login y logout
├── bootstrap.php        # configuración, DB, CSRF y sesión
└── views/
    ├── auth.php         # layout de la interfaz
    ├── forms.php        # login y registro
    └── authenticated.php
public/
├── index.php            # punto de entrada
└── styles.css
 database/schema.sql
 config.example.php
```

## Antes → después

- Contraseñas en texto plano → `password_hash()` / `password_verify()`.
- SQL concatenado → consultas preparadas con MySQLi.
- Sin protección CSRF ni límite de intentos → token CSRF y throttling por email/IP.
- Sesión acoplada a la interfaz → sesión PHP con cookies HttpOnly, SameSite y Secure bajo HTTPS.

## Ejecutar

1. Crea una base MySQL y ejecuta [`database/schema.sql`](database/schema.sql).
2. Copia `config.example.php` como `config.php`, completa la conexión y genera un `APP_KEY` aleatorio:
   `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`
3. Mantén `app/`, `database/` y `config.php` fuera del document root. Publica `public/`.
4. Activa HTTPS para producción. En local: `php -S localhost:8000 -t public`.

## Seguridad

Demo educativa; no es una auditoría ni un sistema para cuentas sensibles. Requiere HTTPS y configuración segura del servidor. El limitador de intentos usa MySQL. Para producción, añade monitoreo, recuperación de contraseñas y pruebas automatizadas.
