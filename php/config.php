<?php
session_start();
ini_set('display_errors', 1);              // Muestra los errores en pantalla
ini_set('display_startup_errors', 1);      // Muestra errores durante el arranque de PHP
error_reporting(E_ALL);                    // Reporta todos los errores y advertencias
$host = "sql308.infinityfree.com";
$username = "if0_38936794";
$password = "piC1QS3kFHb";
$database = "if0_38936794_caminosdelsol";

// Crear conexión
$conn = new mysqli($host, $username, $password, $database);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Función para limpiar datos de entrada
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>

