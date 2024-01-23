<?php
/*comprueba que el usuario haya abierto sesión o redirige*/
require 'sesiones.php';
require_once 'bd.php';
comprobar_sesion();

$mostrarMensaje = false;
if (isset($_POST['anadirCarrito'])) {
	$codProducto = $_POST['codProducto'];
	$unidades = $_POST['unidadesProducto'];
	$categoria = $_POST['codCategoria'];
	$nombreProducto = $_POST['nomProducto'];
	/*si existe el código sumamos las unidades*/
	if (isset($_SESSION['carrito'][$codProducto])) {
		$_SESSION['carrito'][$codProducto] += $unidades;
	} else {
		//Si no existía el codigo del producto como key se crea y se le da el valor de las unidades
		$_SESSION['carrito'][$codProducto] = $unidades;
	}
	//Variable con el mensaje a mostrar
	$mostrarMensaje = true;
	$mensajeAniadir = "Se han añadido $unidades unidades de $nombreProducto al carrito";
}

//Esto solo se activa si un administrador rellena el formulario de añadir nuevos productos
if (isset($_POST['anadirProducto'])) {
	$codCategoria = $_POST['codCategoria'];
	$nombreProducto = $_POST['nombreProducto'];
	$descripcionProducto = $_POST['descripcionProducto'];
	$stockProducto = $_POST['stockProducto'];
	$precioProducto = $_POST['precioProducto'];
	if ($nombreProducto == false || $descripcionProducto == false || $stockProducto == false || $precioProducto == false) {
		echo "<p>Debe rellenar todos los campos</p>";
	} else {
		$anadirProducto = insertar_producto($nombreProducto, $descripcionProducto, $stockProducto, $precioProducto, $codCategoria);
		if ($anadirProducto === false) {
			echo "<p>Error al conectar con la base datos</p>";
		} else {
			echo "<p>Producto añadido correctamente</p>";
		}
	}
}
//Solo se activa si un administrador pulsa para actualizar el stock de un producto
if (isset($_POST['actualizarStock'])) {
	$codProducto = $_POST['codProducto'];
	$unidadesStock = $_POST['unidadesStock'];
	$actualizarStock = cambiar_stock($codProducto, $unidadesStock);
	if ($actualizarStock === false) {
		echo "<p>Error al conectar con la base datos</p>";
	} else {
		echo "<p>Stock actualizado correctamente</p>";
	}
}
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8">
	<title>Tabla de productos por categoría</title>
</head>

<body>
	<?php
	require 'cabecera.php';
	//En categoria.php añadía el codigo de categoria a la url de producto.php a la que redirigía, por lo que aquí está disponible con GET
	$codCategoria = $_GET['categoria'];

	$cat = cargar_categoria($codCategoria);
	//cat tiene el nombre y la descripcion de la categoria
	$productos = cargar_productos_categoria($codCategoria);
	//$productos tiene todo de la tabla producto(codigo, nombre, descripción, stock, codcategoria y precio)
	if ($cat === FALSE || $productos === FALSE) {
		echo "<p class='error'>Error al conectar con la base datos</p>";
		exit;
	} else if ($productos === 0) {
		//Esto pasa cuando la categoria es nueva y no se le han añadido productos (o que los productos hayan sido borrados desde base de datos)
		$mostrarTabla = false;
		echo "<p>No hay productos en esta categoría</p>";
	} else {
		$mostrarTabla = true;
	}
	echo "<h1>" . $cat['NombreCategoria'] . "</h1>";
	echo "<p>" . $cat['DescripcionCategoria'] . "</p>";

	//Si hay productos se muestran
	if ($mostrarTabla === true) {
	?>
		<table>
			<tr>
				<th>Nombre</th>
				<th>Descripción</th>
				<th>Stock en Almacén</th>
				<th>Precio de Venta</th>
				<?php
				if ($cliente['Rol'] === 1) echo "<th>Añadir al Carrito</th>"; ?>
			</tr>
			<?php
			foreach ($productos as $producto) {
				$codProducto = $producto['CodProducto'];
				$nomProducto = $producto['NombreProducto'];
				$descripcionProducto = $producto['DescripcionProducto'];
				$stockProducto = $producto['Stock'];
				$precioProducto = $producto['Precio'];
				////!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
				$hayEnCarrito = false;
				$ultimasUnidades = false;
				if (isset($_SESSION['carrito'][$codProducto])) {
					$hayEnCarrito = true;
					$unidadesCarrito = $_SESSION['carrito'][$codProducto];
					if ($unidadesCarrito == $stockProducto && $stockProducto > 0) {
						$ultimasUnidades = true;
					}
				}
			?>
				<tr>
					<td><?php echo $nomProducto; ?></td>
					<td><?php echo $descripcionProducto; ?></td>
					<td>
						<?php
						if ($stockProducto === 0) {
							echo "Producto agotado";
						} else {
							echo $stockProducto;
						}
						if ($cliente['Rol'] === 0) { ?>
							<br>
							<form action="#" method='POST'>
								<label for="stock">Indique la cantidad real de stock</label>
								<input id="stock" type='number' name='unidadesStock' min='0' value="<?php echo $stockProducto; ?>">
								<input name='codProducto' type='hidden' value='<?php echo $codProducto; ?>'>
								<input type='submit' name="actualizarStock" value='Actualizar Stock'>
							</form>
							<br>

						<?php } ?>
					</td>
					<td><?php echo $precioProducto; ?> </td>
					<?php if ($cliente['Rol'] === 1) {
					?>
						<td>
							<!--En POST se envía el codCategoria, $nomProducto, $codProducto y las unidades del producto que quiere añadir-->
							<form action="#" method='POST'>
								<input name='codCategoria' type='hidden' value='<?php echo $codCategoria; ?>'>
								<input name='nomProducto' type='hidden' value='<?php echo $nomProducto; ?>'>
								<input name='codProducto' type='hidden' value='<?php echo $codProducto; ?>'>
								<?php if ($stockProducto > 0 && $ultimasUnidades == false) { ?><input name='unidadesProducto' type='number' min='1' value='0' max='<?php if ($hayEnCarrito == false) {
																																										echo $stockProducto;
																																									} else {
																																										echo $stockProducto - $unidadesCarrito;
																																									} ?>'><?php } ?>
								<?php if ($stockProducto > 0 && $ultimasUnidades == false) { ?> <input name="anadirCarrito" type='submit' value='Añadir al carrito'><?php } ?>
							</form>
						</td>

					<?php }
					//se muestra mensaje de producto añadido al carrito. Solo confirma que se ha añadido el producto al carrito
					if ($mostrarMensaje) {
						echo $mensajeAniadir;
					} ?>

				</tr>
			<?php } ?>
		</table>
	<?php }
	if ($cliente['Rol'] === 0) { ?>
		<h2>Añadir producto</h2>
		<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="POST">
			<label for="nombreProducto">Nombre del producto</label>
			<input id="nombreProducto" type="text" name="nombreProducto" minlength="3" maxlength="25" required>
			<label for="descripcionProducto">Descripción del producto</label>
			<input id="descripcionProducto" type="text" name="descripcionProducto" minlength="10" maxlength="500" required>
			<label for="stockProducto">Stock del producto</label>
			<input id="stockProducto" type="number" name="stockProducto" min="0" required>
			<label for="precioProducto">Precio del producto</label>
			<input id="precioProducto" type="number" name="precioProducto" min="0.01" step="0.01" required>
			<input type="submit" name="anadirProducto" value="Añadir producto">
			<input name='codCategoria' type='hidden' value='<?php echo $codCategoria; ?>'>
		</form>

	<?php
	}
	?>
</body>

</html>