<header>
    <?php $cliente = $_SESSION['usuario']; ?>
    <p>Usuario: <?php echo $cliente['NombreCliente']; ?></p>
    <?php
    //Los enlaces de navegación son un poco distintos según el rol del cliente
    if ($cliente['Rol'] === 1) { ?>
        <a href="categorias.php">Categorías</a>
        <a href="carrito.php">Ver Carrito</a>
        <a href="pedidos.php">Ver Pedidos</a>
        <a href="logout.php">Cerrar sesión</a>
    <?php
    } else { ?>
        <a href="categorias.php">Categorías</a>
        <a href="clientes.php">Ver Clientes</a>
        <a href="pedidos.php">Ver Pedidos</a>
        <a href="logout.php">Cerrar sesión</a>
    <?php } ?>
</header>
<hr>