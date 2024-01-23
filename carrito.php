<?php
/*comprueba que el usuario haya abierto sesión y no sea admin o redirige a login*/
require_once 'sesiones.php';
require_once 'bd.php';
comprobar_sesion();
comprobar_cliente();

if (isset($_POST['eliminarProducto'])) {
	$codProducto = $_POST['codProducto'];
	$unidades = $_POST['unidadesEliminar'];

	/*si existe el código restamos las unidades, con mínimo de 0*/
	if (isset($_SESSION['carrito'][$codProducto])) {
		$_SESSION['carrito'][$codProducto] -= $unidades;
		if ($_SESSION['carrito'][$codProducto] <= 0) {
			unset($_SESSION['carrito'][$codProducto]);
		}
	}
}

if (isset($_POST['vaciarCarrito'])) {
	$_SESSION['carrito'] = [];
}

?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8">
	<title>Carrito de la compra</title>
</head>

<body>

	<?php
	require 'cabecera.php';
	$productos = cargar_productos(array_keys($_SESSION['carrito']));
	if ($productos === FALSE) {
		echo "<p>No hay productos en el carrito</p>";
		exit;
	}
	?>
	<h2>Carrito de la compra</h2>
	<table>
		<tr>
			<th>Nombre</th>
			<th>Descripción</th>
			<th>Unidades</th>
			<th>Eliminar</th>
		</tr>
		<?php
		$totalArticulos = 0;
		foreach ($productos as $producto) {
			$codProducto = $producto['CodProducto'];
			$nombreProducto = $producto['NombreProducto'];
			$descripcionProducto = $producto['DescripcionProducto'];
			$unidades = $_SESSION['carrito'][$codProducto];
			$totalArticulos += $unidades;
		?>
			<tr>
				<td><?php echo $nombreProducto; ?> </td>
				<td><?php echo $descripcionProducto; ?> </td>
				<td><?php echo $unidades; ?> </td>
				<td>
					<form action='#' method='POST'>
						<input type="number" name='unidadesEliminar' min='1' max='<?php echo $_SESSION['carrito'][$codProducto]; ?>' value='1'>
						<input type='submit' name="eliminarProducto" value='Eliminar'>
						<input name='codProducto' type='hidden' value='<?php echo $codProducto; ?>'>
					</form>

				</td>
			</tr>
		<?php }
		$_SESSION['totalArticulos'] = $totalArticulos;
		?>
		<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method='POST'>
			<input type='submit' name="vaciarCarrito" value='Vaciar Carrito'>
		</form>
	</table>

	<hr>
	<a href="procesar_pedido.php">Realizar pedido</a>
</body>

</html>