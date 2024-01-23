<?php
require_once 'sesiones.php';
require_once 'bd.php';
comprobar_sesion();

//Cuando el cliente indica que ha recibido su pedido
if (isset($_POST['pedidoRecibido'])) {
    $cambioEstado = pedido_recibido($_POST['codPedido']);
    if ($cambioEstado) {
        echo "Pedido marcado como recibido con éxito";
    }
}
//Cuando el administrador indica que el pedido ha sido enviado
if (isset($_POST['pedidoEnviado'])) {
    $cambioEstado = pedido_enviado($_POST['codPedido']);
    if ($cambioEstado) {
        echo "Pedido marcado como enviado con éxito";
    }
}

?>
<!DOCTYPE html>

<head>
    <meta charset="UTF-8">
    <title>Pedidos</title>
</head>

<body>
</body>
<?php
require 'cabecera.php';
//si es un cliente solo se muestran sus pedidos, si es admin se muestran todos.

if ($cliente['Rol'] === 1) {
    $admin = false;
    $codCliente = $cliente['CodCliente']; //la variable cliente sale de la cabecera
    $pedidos = cargar_pedidos_cliente($codCliente);
    if ($pedidos === FALSE) {
        echo "<p>No se han realizado pedidos</p>";
        exit;
    }
} else {
    $admin = true;
    $pedidos = cargar_pedidos();
    if ($pedidos === FALSE) {
        echo "<p>No se han realizado pedidos</p>";
        exit;
    }
}
?>
<h2>Pedidos</h2>
<table>
    <tr>
        <th>Código del Pedido</th>
        <th>Fecha del Pedido</th>
        <th>Artículos Totales</th>
        <th>Estado del Pedido</th>
        <th>Acciones que puede realizar</th>
    </tr>
    <?php
    foreach ($pedidos as $pedido) {
        $codPedido = $pedido['CodPedido'];
        $fecha = $pedido['FechaPedido'];
        $articulosTotales = $pedido['ArticulosTotales'];
        $estado = $pedido['Estado'];
    ?>
        <tr>
            <td><?php echo $codPedido; ?></td>
            <td><?php echo $fecha; ?></td>
            <td><?php echo $articulosTotales; ?></td>
            <td><?php echo $estado; ?> </td>

            <!--Dos posibles botones en esta columna según el tipo de usuario-->
            <td>
                <?php
                //el cliente puede marcarlo como recibido si está enviado
                if ($admin === false && $estado === 'enviado') {
                ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method='POST'>
                        <input type='submit' name='pedidoRecibido' value='Marcar como Recibido'>
                        <input name='codPedido' type='hidden' value='<?php echo $codPedido; ?>'>
                        <input name='codCliente' type='hidden' value='<?php echo $pedido['CodCliente']; ?>'>
                    </form>
                <?php
                }
                //el admin puede marcarlo como enviado si está pendiente
                if ($admin === true && $estado === 'pendiente') {
                ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method='POST'>
                        <input type='submit' name='pedidoEnviado' value='Marcar como Enviado'>
                        <input name='codPedido' type='hidden' value='<?php echo $codPedido; ?>'>
                        <input name="codCliente" type="hidden" value="<?php echo $pedido['CodCliente']; ?>">
                    </form>
                <?php } ?>

                <!--Ambos pueden ver los productos del pedido-->
                <form action='pedido_producto.php' method='POST'>
                    <input type='hidden' name='codPedido' value='<?php echo $codPedido; ?>'>
                    <input type='hidden' name='codCliente' value='<?php echo $pedido['CodCliente']; ?>'>
                    <input type='submit' name="pedido_producto" value='Ver Productos del Pedido'>
                </form>
                <hr>
            </td>

        </tr>
    <?php } //cierre de foreach
    ?>
</table>
</body>

</html>