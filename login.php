<?php
require_once 'bd.php';
/*formulario de login habitual
si va bien abre sesión, guarda el nombre de usuario, filtra segun rol y redirige a la página de inicio correspondiente
si va mal, mensaje de error */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $usu = comprobar_usuario($_POST['usuario'], $_POST['clave']);
    if ($usu === false) {
        $err = true;
    } else {
        session_start();
        // $usu tiene campos codCliente, nombreCliente, contrasenaCliente y Rol
        $_SESSION['usuario'] = $usu;
        if ($usu['Rol'] == 1) {
            $_SESSION['carrito'] = [];
        }
        header("Location: categorias.php");
        return;
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Formulario de login</title>
    <meta charset="UTF-8">
</head>

<body>
    <?php if (isset($_GET["redirigido"])) {
        echo "<p>Haga login para continuar</p>";
    } ?>
    <?php if (isset($err) and $err == true) {
        echo "<p> Revise usuario y contraseña</p>";
    } ?>
    <form action="#" method="POST">
        <label for="usuario">Usuario</label>
        <input id="usuario" name="usuario" type="text">
        <br />
        <label for="clave">Contraseña</label>
        <input id="clave" name="clave" type="password">
        <input type="submit" value="Entrar">
    </form>
</body>

</html>