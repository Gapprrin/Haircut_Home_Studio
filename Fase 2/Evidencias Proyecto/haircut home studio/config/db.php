<?php
/**
 * Conexión a MySQL (XAMPP).
 * Si cambias usuario/clave de MySQL, edita estas constantes.
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // En XAMPP suele ir vacío
define('DB_NAME', 'haircut_studio');

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            $pagina = basename($_SERVER['PHP_SELF'] ?? '');
            if ($pagina !== 'instalar.php') {
                header('Location: instalar.php');
                exit;
            }
            throw $e;
        }
    }

    return $pdo;
}
