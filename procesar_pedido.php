<?php
/*comprueba que el cliente haya abierto sesión o redirige*/

require 'sesiones.php';
require_once 'bd.php';
comprobar_sesion();
comprobar_cliente();
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8">
	<title>Pedidos</title>
</head>

<body>
	<?php
	require 'cabecera.php';

	if ($_SERVER["REQUEST_METHOD"] == "POST") {

		//Si la contraseña es correcta se procesa el pedido
		// Verificar la contraseña utilizando password_verify
		if (password_verify($_POST['clave'], $cliente['ContrasenaCliente'])) {

			//Comprueba que hay suficiente stock para todos los productos del carrito, si no lo hay muestra un mensaje de error y no procesa el pedido (es útil si hay varios clientes a la vez)
			$productos = cargar_productos(array_keys($_SESSION['carrito']));
			foreach ($productos as $producto) {
				$codProducto = $producto['CodProducto'];
				$unidades = $_SESSION['carrito'][$codProducto];
				$stock = consultarStockProducto($codProducto);
				if ($stock && $stock < $unidades) {
					echo "No hay suficiente stock de $codProducto, por favor, reduzca las unidades de este producto en el carrito";
					exit;
				} elseif ($stock == false) {
					echo "No se ha podido consultar el stock del producto con código $codProducto, por favor, vuelva a intentarlo más tarde";
					exit;
				}
			}

			//Si llega aquí es que hay suficiente stock para todos los productos del carrito, por lo que se procesa el pedido
			$resul = insertar_pedido($_SESSION['carrito'], $_SESSION['usuario']['CodCliente'], $_SESSION['totalArticulos']);
			if ($resul === FALSE) {
				echo "No se ha podido realizar el pedido<br>";
			} else {
				$compra = $_SESSION['carrito'];

				echo "Pedido realizado con exito. Resumen del pedido: </BR>";
				echo "<h1>Pedido nº $resul</h1>";
				$productos = cargar_productos(array_keys($_SESSION['carrito']));
				echo "<table>"; //abrir la tabla
				echo "<tr>
						<th>Nombre</th>
						<th>Descripción</th>
						<th>Precio Recomendado</th>
						<th>Unidades</th>
						</tr>";
				foreach ($productos as $producto) {
					$codProducto = $producto['CodProducto'];
					$nombreProducto = $producto['NombreProducto'];
					$descripcionproducto = $producto['DescripcionProducto'];
					$precio = $producto['Precio'];
					$unidades = $_SESSION['carrito'][$codProducto];

					echo "<tr>
							<td>$nombreProducto</td>
							<td>$descripcionproducto</td>
							<td>$precio</td>
							<td>$unidades</td>
						</tr>";
				}
				echo "Artículos totales: " . $_SESSION['totalArticulos'] . "<br>";
				echo "</table>";

				$repetir = false;

				$_SESSION['carrito'] = [];
			}
		} else {
			echo "Contraseña erronea, vuelva a intentarlo para procesar el pedido";
			$repetir = true;
		}
	}
	if (!isset($_POST['clave']) || $repetir) {
	?>
		<h1>Por favor, para realizar el pedido introduzca su contraseña</h1>
		<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="POST">
			<label for="clave">Contraseña</label>
			<input id="clave" name="clave" type="password">
			<input type="submit" value="Aceptar">
		</form>
	<?php } ?>
</body>

</html>