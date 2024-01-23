<?php
/*comprueba que el usuario haya abierto sesión o redirige*/
require 'sesiones.php';
require_once 'bd.php';
comprobar_sesion();

if (isset($_POST['anadirCategoria'])) {
	$nombreCategoria = $_POST['nombreCategoria'];
	$descripcionCategoria = $_POST['descripcionCategoria'];
	if ($nombreCategoria == false || $descripcionCategoria == false) {
		echo "<p>Debe rellenar todos los campos</p>";
	} else {
		$anadirCategoria = insertar_categoria($nombreCategoria, $descripcionCategoria);
		if ($anadirCategoria === false) {
			echo "<p>Error al conectar con la base datos</p>";
		} else {
			echo "<p>Categoría añadida correctamente</p>";
		}
	}
}
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8">
	<title>Lista de categorías</title>
</head>

<body>
	<?php
	require 'cabecera.php'; ?>
	<h1>Lista de categorías</h1>
	<?php
	$categorias = cargar_categorias();
	//$categorias tiene el nombre de las categorias y su código
	if ($categorias === false) {
		echo "<p class='error'>Error al conectar con la base datos</p>";
	} else if ($categorias === 0) {
		echo "<p>No hay categorías</p>";
	} else {
		echo "<ul>"; //abrir la lista
		foreach ($categorias as $cat) {
			$url = "productos.php?categoria=" . $cat['CodCategoria'];
			echo "<li><a href='$url'>" . $cat['NombreCategoria'] . "</a></li>";
		}
		echo "</ul>";
	}
	if ($cliente['Rol'] === 0) {
	?>
		<h2>Añadir categoría</h2>
		<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="POST">
			<label for="nombreCategoria">Nombre de la categoría</label>
			<input id="nombreCategoria" type="text" name="nombreCategoria" minlength="3" maxlength="25" required>
			<label for="descripcionCategoria">Descripción de la categoría</label>
			<input id="descripcionCategoria" type="text" name="descripcionCategoria" minlength="10" maxlength="500" required>
			<input type="submit" name="anadirCategoria" value="Añadir categoría">
		<?php
	}
		?>
</body>

</html>