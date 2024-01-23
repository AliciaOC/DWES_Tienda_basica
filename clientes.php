<?php
require_once 'sesiones.php';
require_once 'bd.php';
comprobar_sesion();
comprobar_admin();

if (isset($_POST['eliminarCliente'])) {
    $codCliente = $_POST['codCliente'];
    $eliminado = eliminar_usuario($codCliente);
    if ($eliminado) {
        echo "<p>Cliente eliminado con éxito</p>";
    } else {
        echo "<p>Error al eliminar el cliente</p>";
    }
}

if (isset($_POST['anadirCliente'])) {
    $nombreCliente = $_POST['nombreCliente'];
    $contrasenaCliente = $_POST['contrasenaCliente'];
    $direccionCliente = $_POST['direccionCliente'];
    $rol = $_POST['Rol'];
    $anadido = insertar_usuario($nombreCliente, $contrasenaCliente, $direccionCliente, $rol);
    if ($anadido) {
        echo "<p>Cliente añadido con éxito</p>";
    } else {
        echo "<p>Error al añadir el cliente</p>";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Clientes</title>
</head>

<body>
    <?php
    require 'cabecera.php';

    //he intentado no llamar a las variables de este fichero como $cliente para que no hubiera confusión con la variable del fichero cabecera.php
    $usuarios = cargar_usuarios();
    if ($usuarios === FALSE) {
        echo "<p class='error'>Error al conectar con la base datos</p>";
        exit;
    } else {
    ?>
        <table>
            <tr>
                <th>Código del Usuario/a</th>
                <th>Nombre</th>
                <th>Dirección</th>
                <th>Rol</th>
                <th>Acciones de administración</th>
            </tr>
            <?php
            foreach ($usuarios as $usuario) {
                $codUsuario = $usuario['CodCliente'];
                $nombreUsuario = $usuario['NombreCliente'];
                $direccionUsuario = $usuario['DireccionCliente'];
                $rol = $usuario['Rol'];
            ?>
                <tr>
                    <td><?php echo $codUsuario; ?></td>
                    <td><?php echo $nombreUsuario; ?></td>
                    <td><?php echo $direccionUsuario; ?></td>
                    <td><?php
                        if ($rol === 0) {
                            echo "Administración";
                        } else {
                            echo "Cliente";
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if ($cliente['CodCliente'] !== $codUsuario) {
                        ?>
                            <form action="#" method='POST'>
                                <input name='codCliente' type='hidden' value='<?php echo $codUsuario; ?>'>
                                <input type='submit' name="eliminarCliente" value='Eliminar <?php echo $nombreUsuario; ?>'>
                            </form>
                        <?php } else echo "No aplica"; ?>
                    </td>
                </tr>
            <?php } ?>
        </table>
    <?php } ?>
    <h2>Añadir Clientes</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="POST">
        <label for="nombreCliente">Nombre de Usuario</label>
        <input id="nombreCliente" type="text" name="nombreCliente" minlength="4" maxlength="50" required>
        <label for="contrasenaCliente">Clave provisional asignada</label>
        <input id="contrasenaCliente" type="text" name="contrasenaCliente" minlength="1" maxlength="8" required>
        <label for="direccionCliente">Ubicación</label>
        <input id="direccionCliente" type="test" name="direccionCliente" min="4" maxlength="50" required>
        <label for="rol">Es cliente o administración</label>
        <input id="rol" type="number" name="Rol" min="0" max="1" value="1" required>
        <input type="submit" name="anadirCliente" value="Añadir Cliente">
    </form>
</body>

</html>