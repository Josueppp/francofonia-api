<?php

/**
 * Clase Database
 * Implementa un patrón similar a Singleton para la conexión a MySQL.
 * Aunque en PHP el ciclo de vida de la petición destruye y recrea la instancia,
 * centralizar la lógica de `new PDO()` asegura que todas las consultas 
 * compartan el mismo método de acceso y se gestionen los errores en un solo lugar.
 */
class Database
{
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct()
    {
        // Detectar si estamos en un entorno con variables de entorno (Render/Railway/etc)
        if (getenv('DB_HOST')) {
            $this->host = getenv('DB_HOST');
            $this->db_name = getenv('DB_NAME');
            $this->username = getenv('DB_USER');
            $this->password = getenv('DB_PASS');
        }
        // Detectar si estamos en local (localhost o 127.0.0.1)
        else if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1' || $_SERVER['SERVER_ADDR'] === '127.0.0.1') {
            $this->host = '127.0.0.1';
            $this->db_name = 'francofonia';
            $this->username = 'root';
            $this->password = '';
        }
        else {
            // CONFIGURACIÓN PARA INFINITYFREE (Rellenar con tus datos reales)
            $this->host = 'sql211.infinityfree.com';
            $this->db_name = 'if0_41477145_francofonia';
            $this->username = 'if0_41477145';
            $this->password = 'tvHePBZsTH0jT'; // CAMBIAR ESTO
        }
    }

    /**
     * Obtiene y retorna la conexión a la base de datos usando PDO.
     * PDO (PHP Data Objects) es una capa de abstracción de acceso a datos que
     * previene inyecciones SQL mediante el uso de Prepared Statements.
     */
    public function getConnection()
    {
        $this->conn = null;
        try {
            $this->conn = new PDO('mysql:host=' . $this->host . ';dbname=' . $this->db_name, $this->username, $this->password);

            // Configurar PDO para que lance excepciones (Exception) en caso de error SQL.
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Garantizar que la conexión devuelva los datos en formato UTF-8, por ejemplo para acentos (Francofonía).
            $this->conn->exec("set names utf8");
        }
        catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]);
            exit;
        }
        return $this->conn;
    }
}
