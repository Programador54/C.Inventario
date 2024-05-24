<?php
include 'db.php';

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = 'PaPeleriACT';
    $password = 'rZLV9cKI78';

    // Prevenir inyecciones SQL
    $username = $conn->real_escape_string($username);
    $password = $conn->real_escape_string($password);

    $sql = "SELECT * FROM usuario WHERE username='$username' AND password='$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $_SESSION['username'] = $username;
        header("Location: productos.php");
    } else {
        echo "Usuario o contraseña incorrectos";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <link href="style.css" rel="stylesheet" type="text/css">
    <title>Inicio de Sesión</title>
</head>
<body>
    <form method="post" action="index.php">
    <h1>Inicio de Sesión</h1>
        Usuario: <input type="text" name="username" required><br>
        Contraseña: <input type="password" name="password" required><br>
        <input type="submit" value="Iniciar Sesión">
    </form>
</body>
</html>
