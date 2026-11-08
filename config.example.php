<?php
// Copy to config.php. Keep config.php outside the public document root and out of Git.
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'secure_auth_mini');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'replace_with_a_unique_database_password');
// Generate a unique secret: php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
define('APP_KEY', 'replace-with-a-random-secret-of-at-least-32-characters');
