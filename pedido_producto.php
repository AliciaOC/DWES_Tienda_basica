<?php
require 'sesiones.php';
require_once 'bd.php';
comprobar_sesion();

if (isset($_POST['pedido_producto'])) {
    $mostrarTabla = true;

    $codPedido = $_POST['codPedido'];
    $codCliente = $_POST['codCliente'];
    $productosPedido = cargar_productos_pedido($codCliente, $codPedido);
    $datosPedido = cargar_datos_pedido($codCliente, $codPedido);

    if ($productosPedido === false || $datosPedido === false) {
        echo "<p>Se ha producido un error</p>";
        echo "<a href='pedidos.php'>Volver a la lista de pedidos</a>";
        exit;
    } else {
        if ($_SESSION['usuario']['Rol'] === 0) {
            $admin = true;
            $datosCliente = cargar_datos_cliente($_POST['codCliente']);
        } else {
            $admin = false;
        }
    }
} else {
    $mostrarTabla = false;
    header('Location: pedidos.php');
}

if ($mostrarTabla === true) {
?>
    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="UTF-8">
        <title>Productos del pedido</title>
    </head>

    <body>
        <?php require 'cabecera.php'; ?>
        <h1> Detalles del pedido nº <?php echo $codPedido; ?></h1>
        <p>Fecha del pedido: <?php echo $datosPedido['FechaPedido']; ?></p>
        <p>Estado del pedido: <?php echo $datosPedido['Estado']; ?></p>
        <?php
        if ($admin === true) {
            echo "<p>Código del cliente: " . $datosCliente['CodCliente'] . "</p>";
            echo "<p>Nombre del cliente: " . $datosCliente['NombreCliente'] . "</p>";
            echo "<p>Dirección del cliente: " . $datosCliente['DireccionCliente'] . "</p>";
        }
        //no muestro más información sobre el pedido si el usuario es un cliente porque solo puede ver sus pedidos, así que no es necesaria información extra como la dirección
        ?>
        <table>
            <tr>
                <th>Código del Producto</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Precio Recomendado</th>
                <th>Unidades</th>
            </tr>
            <?php
            foreach ($productosPedido as $producto) {
                $codProducto = $producto['CodProducto'];
                $nombreProducto = $producto['NombreProducto'];
                $descripcionproducto = $producto['DescripcionProducto'];
                $precio = $producto['Precio'];
                $unidades = $producto['unidadesProducto'];

                echo "<tr>
                            <td>$codProducto</td>
                            <td>$nombreProducto</td>
                            <td>$descripcionproducto</td>
                            <td>$precio</td>
                            <td>$unidades</td>
                        </tr>";
            }
            ?>
        </table>
        <br>
        <p>Artículos totales del pedido: <?php echo $datosPedido['ArticulosTotales']; ?></p><br>
        <a href="pedidos.php">Volver a la lista de pedidos</a>
    </body>

    </html>
<?php } //cierre if mostrarTabla
?>