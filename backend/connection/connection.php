<?php
class Conexion {
    private static $host = 'localhost';    
    private static $dbname = 'topskin';
    private static $user = 'root';
    private static $pass = 'mipmopmap26PanQ';
    
    public static function conectar() {
        try {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=utf8";
            $conexion = new PDO($dsn, self::$user, self::$pass);
            $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conexion;
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
}
?>