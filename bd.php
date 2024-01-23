<?php

/**
 * Lee la configuración de los ficheros de configuración
 */
function leer_config($nombre, $esquema)
{
    $config = new DOMDocument();
    $config->load($nombre);
    $res = $config->schemaValidate($esquema);
    if ($res === FALSE) {
        throw new InvalidArgumentException("Revise fichero de configuración");
    }
    $datos = simplexml_load_file($nombre);
    $ip = $datos->xpath("//ip");
    $nombre = $datos->xpath("//nombre");
    $usu = $datos->xpath("//usuario");
    $clave = $datos->xpath("//clave");
    $cad = sprintf("mysql:dbname=%s;host=%s", $nombre[0], $ip[0]);
    $resul = [];
    $resul[] = $cad;
    $resul[] = $usu[0];
    $resul[] = $clave[0];
    return $resul;
}
/**
 * Crea una conexión a la base de datos
 */
function conexionBD()
{
    try {
        $res = leer_config(dirname(__FILE__) . "/configuracion.xml", dirname(__FILE__) . "/configuracion.xsd");
        $conexion = new PDO($res[0], $res[1], $res[2]);
        return $conexion;
    } catch (PDOException $e) {
        echo "Error al conectar con la base de datos: " . $e->getMessage();
        return FALSE;
    }
}

/**
 * Comprueba si el usuario existe en la base de datos
 * Devuelve un array con los datos del usuario si existe
 * Devuelve FALSE si no existe o si hay error
 * 
 * Esta función se utiliza en el fichero login.php
 */
function comprobar_usuario($nombre, $clave)
{
    $bd = conexionBD();
    try {
        $consulta = "SELECT CodCliente, NombreCliente, ContrasenaCliente, Rol FROM cliente WHERE NombreCliente = :nombre";
        $stmt = $bd->prepare($consulta);
        $stmt->execute([':nombre' => $nombre]);

        if ($stmt->rowCount() === 1) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificar la contraseña utilizando password_verify
            if (password_verify($clave, $usuario['ContrasenaCliente'])) {
                return $usuario;
            }
        }

        return FALSE;
    } catch (PDOException $e) {
        echo "Error al comprobar el usuario: " . $e->getMessage();
        return FALSE;
    } finally {
        $bd = null;
    }
}

/**
 * Carga el nombre de la categoría y su código
 * 
 * Esta función se utiliza en el fichero categorias.php
 */
function cargar_categorias()
{
    $bd = conexionBD();
    try {
        $ins = "select NombreCategoria, CodCategoria from categoria";
        $resul = $bd->query($ins);
        if (!$resul) {
            return FALSE;
        }
        if ($resul->rowCount() === 0) {
            return 0;
        }
        //si hay 1 o más
        return $resul;
    } catch (PDOException $e) {
        echo "Error al cargar las categorias: " . $e->getMessage();
        return FALSE;
    } finally {
        $bd = null;
    }
}

/**
 * Carga el nombre y la descripción de la categoría
 * 
 * Esta función se utiliza en el fichero productos.php
 */
function cargar_categoria($codCat)
{
    $bd = conexionBD();
    try {
        $ins = "select NombreCategoria, DescripcionCategoria from categoria where CodCategoria = $codCat";
        $resul = $bd->query($ins);
        if (!$resul) {
            return FALSE;
        }
        if ($resul->rowCount() === 0) {
            return false;
        }
        //si hay 
        return $resul->fetch();
    } catch (PDOException $e) {
        echo "Error al cargar la categoria: " . $e->getMessage();
        return FALSE;
    } finally {
        $bd = null;
    }
}

/**
 * Carga los productos de una categoría
 * 
 * Esta función se utiliza en el fichero productos.php
 */
function cargar_productos_categoria($codCat)
{
    $bd = conexionBD();
    try {
        $sql = "select CodProducto, NombreProducto, DescripcionProducto, Stock, Precio from producto where CodCategoria = $codCat and Stock >= 0"; //por si hubiera numero negativo de stock, aunque no deberia
        $resul = $bd->query($sql);
        if (!$resul) {
            return FALSE;
        }
        if ($resul->rowCount() === 0) {
            return 0;
        }
        //si hay 1 o más
        return $resul->fetchAll(PDO::FETCH_ASSOC); //He usado fetchAll porque quiero que devuelva más de una fila, me lo devuelve en un array asociativo
    } catch (PDOException $e) {
        echo "Error al cargar los productos de la categoria: " . $e->getMessage();
        return FALSE;
    } finally {
        $bd = null;
    }
}

/**
 * Recibe un array de códigos de productos (la de sesion de carrrito) devuelve un array bidimensional.
 * En el fichero donde se use se puede hacer un foreach para obtener los datos de cada producto
 * 
 * Esta función se utiliza en el fichero carrito.php
 */
function cargar_productos($arrayCodigosProductos)
{
    $bd = conexionBD();
    try {
        $texto_in = implode(",", $arrayCodigosProductos);
        if ($texto_in == NULL) return FALSE;
        $ins = "select * from producto where CodProducto in($texto_in)";
        $resul = $bd->query($ins);
        if (!$resul) {
            return FALSE;
        }
        if ($resul->rowCount() === 0) {
            return FALSE;
        }
        return $resul;
    } catch (PDOException $e) {
        echo "Error al cargar los productos: " . $e->getMessage();
        return FALSE;
    } finally {
        $bd = null;
    }
}

/**
 * Función para insertar un pedido en la base de datos
 * Afecta a las tablas pedido y pedido_producto
 * Actualiza los datos de la tabla producto restando las unidades compradas
 * Utiliza una transacción para asegurar que se hace todo o nada
 * Devuelve el código del pedido si todo ha ido bien
 * 
 * Esta función se utiliza en el fichero procesar_pedido.php
 */
function insertar_pedido($carrito, $codCliente, $totalArticulos)
{
    $bd = conexionBD();
    try {
        $bd->beginTransaction();
        $hora = date("Y-m-d H:i:s", time());
        // insertar el pedido
        $sql = "insert into pedido(FechaPedido, Estado, CodCliente, ArticulosTotales) 
			values('$hora','pendiente', $codCliente, $totalArticulos)";
        $resul = $bd->query($sql);
        if (!$resul) {
            return FALSE;
        }
        // coger el id del nuevo pedido
        $pedido = $bd->lastInsertId();
        // insertar las filas en pedido-producto

        foreach ($carrito as $codProd => $unidades) {

            $sql = "insert into pedido_producto(CodPedido, CodProducto, unidadesProducto) 
		             values( $pedido, $codProd, $unidades)";
            $resul = $bd->query($sql);


            $sql1 = "update producto set Stock=Stock-$unidades
		             where codProducto=$codProd";

            $resul1 = $bd->query($sql1);

            if (!$resul || !$resul1) {
                $bd->rollback(); //si falla alguna de las dos consultas, no se hace ninguna.
                return FALSE;
            }
        }
        $bd->commit(); //si no falla ninguna, se hace el commit.
        return $pedido;
    } catch (PDOException $e) {
        echo "Error al insertar el pedido: " . $e->getMessage();
        return FALSE;
    } finally {
        $bd = null;
    }
}

/**
 * Función para cargar los pedidos de un cliente
 * Devuelve un array con los pedidos si hay alguno
 * Devuelve FALSE si no hay pedidos o si hay error
 * 
 * Esta función se utiliza en el fichero pedidos.php cuando el que consulta es un cliente
 */
function cargar_pedidos_cliente($codCliente)
{
    $bd = conexionBD();
    try {
        $sql = "select * from pedido where CodCliente = $codCliente";
        $resul = $bd->query($sql);
        if (!$resul) {
            return FALSE;
        }
        if ($resul->rowCount() === 0) {
            return FALSE;
        }
        //si hay 1 o más
        return $resul->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error al cargar los pedidos del cliente: " . $e->getMessage();
        return FALSE;
    } finally {
        $bd = null;
    }
}

/**
 * Función para cargar los pedidos registrados
 * Devuelve un array con los pedidos si hay alguno
 * Devuelve FALSE si no hay pedidos o si hay error
 * 
 * Esta función se utiliza en el fichero pedidos.php cuando el que consulta es un admin
 */
function cargar_pedidos()
{
    $bd = conexionBD();
    try {
        $sql = "select * from pedido";
        $resul = $bd->query($sql);
        if (!$resul) {
            return FALSE;
        }
        if ($resul->rowCount() === 0) {
            return FALSE;
        }
        //si hay 1 o más
        return $resul->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error al cargar los pedidos: " . $e->getMessage();
        return FALSE;
    } finally {
        $bd = null;
    }
}

/**
 * Función para actualizar el estado de un pedido a recibido
 * 
 * Esta función se utiliza en el fichero pedidos.php cuando el que interactúa es un cliente
 */
function pedido_recibido($codPedido)
{
    $bd = conexionBD();
    try {
        $sql = "update pedido set Estado = :nuevoEstado where CodPedido = :codPedido";
        $stmt = $bd->prepare($sql);

        $stmt->execute([
            ':nuevoEstado' => "recibido",
            ':codPedido' => $codPedido
        ]);

        // Devuelve true si la actualización fue exitosa
        return true;
    } catch (PDOException $e) {
        echo "Error al cambiar el estado del pedido: " . $e->getMessage();
        return false;
    } finally {
        $bd = null;
    }
}

/**
 * Actualiza el estado de un pedido a enviado
 * 
 * Esta función se utiliza en el fichero pedidos.php cuando el que interactúa es un admin
 */
function pedido_enviado($codPedido)
{
    $bd = conexionBD();
    try {
        $sql = "update pedido set Estado = :nuevoEstado where CodPedido = :codPedido";
        $stmt = $bd->prepare($sql);

        $stmt->execute([
            ':nuevoEstado' => "enviado",
            ':codPedido' => $codPedido
        ]);

        // Devuelve true si la actualización fue exitosa
        return true;
    } catch (PDOException $e) {
        echo "Error al cambiar el estado del pedido: " . $e->getMessage();
        return false;
    } finally {
        $bd = null;
    }
}

/**
 * Función para obtener información de los productos de un pedido
 * Devuelve un array con los productos si hay alguno
 * 
 * Esta función se utiliza en el fichero pedido_producto.php
 */
function cargar_productos_pedido($codCliente, $codPedido)
{
    $bd = conexionBD();
    try {
        $sql = "SELECT pp.CodPedido, pe.CodCliente, pp.unidadesProducto, pp.CodProducto, p.NombreProducto, p.DescripcionProducto, p.Precio
                FROM pedido_producto pp
                JOIN producto p ON pp.CodProducto = p.CodProducto
                JOIN pedido pe ON pp.CodPedido = pe.CodPedido 
                WHERE pe.CodCliente = :codCliente AND pe.CodPedido = :codPedido
                GROUP BY pp.CodProducto";

        $stmt = $bd->prepare($sql);
        $stmt->execute([
            ':codCliente' => $codCliente,
            ':codPedido' => $codPedido
        ]);

        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $resultados;
    } catch (PDOException $e) {
        echo "Error al cargar el pedido: " . $e->getMessage();
        return FALSE;
    } finally {
        $bd = null;
    }
}

/**
 * Función para obtener información de un pedido
 * Devuelve un array con los datos del pedido si hay alguno
 * 
 * Esta función se utiliza en el fichero pedido_producto.php
 */
function cargar_datos_pedido($codCliente, $codPedido)
{
    $bd = conexionBD();
    try {
        $sql = "select * from pedido where CodCliente = $codCliente and CodPedido=$codPedido";
        $resul = $bd->query($sql);
        if (!$resul) {
            return FALSE;
        }
        if ($resul->rowCount() === 0) {
            return FALSE;
        }
        return $resul->fetch();
    } catch (PDOException $e) {
        echo "Error al cargar los datos del pedido: " . $e->getMessage();
        return FALSE;
    } finally {
        $bd = null;
    }
}

/**
 * Función para obtener información de un cliente
 * Devuelve un array con los datos del cliente si hay alguno
 * Se utiliza cuando el que consulta es un admin
 * 
 * Esta función se utiliza en el fichero pedido_producto.php
 */
function cargar_datos_cliente($codCliente)
{
    $bd = conexionBD();
    try {
        $sql = "select CodCliente, NombreCliente, DireccionCliente from cliente where CodCliente=$codCliente";
        $resul = $bd->query($sql);
        if (!$resul) {
            return FALSE;
        }
        if ($resul->rowCount() === 0) {
            return FALSE;
        }
        return $resul->fetch();
    } catch (PDOException $e) {
        echo "Error al cargar los datos del cliente: " . $e->getMessage();
        return FALSE;
    } finally {
        $bd = null;
    }
}

/**
 * Inseta una nueva categoría en la base de datos
 * Primero comprueba que no exista una categoría con el mismo nombre
 * 
 * Esta función se utiliza en el fichero categorias.php
 */
function insertar_categoria($nombre, $descripcion)
{
    $bd = conexionBD();
    try {
        // Verificar si ya existe una categoria con el mismo nombre
        $consultaExistencia = "select count(*) from categoria where NombreCategoria = :nombre";
        $stmtExistencia = $bd->prepare($consultaExistencia);
        $stmtExistencia->execute([':nombre' => $nombre]);

        if ($stmtExistencia->fetchColumn() > 0) {
            // Ya existe un usuario con ese nombre
            echo "Ya existe una categoría con ese nombre.";
            return false;
        }

        $sql = "insert into categoria(NombreCategoria, DescripcionCategoria) values(:nombre, :descripcion)";
        $stmt = $bd->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':descripcion' => $descripcion
        ]);
        return true;
    } catch (PDOException $e) {
        echo "Error al insertar la categoria: " . $e->getMessage();
        return FALSE;
    } finally {
        $bd = null;
    }
}

/**
 * Inseta un nuevo producto en la base de datos
 * Primero comprueba que no exista un producto con el mismo nombre
 * 
 * Esta función se utiliza en el fichero productos.php
 */
function insertar_producto($nombre, $descripcion, $stock, $precio, $codCategoria)
{
    $bd = conexionBD();
    try {
        // Verificar si ya existe un producto con el mismo nombre
        $consultaExistencia = "select count(*) from producto where NombreProducto = :nombre";
        $stmtExistencia = $bd->prepare($consultaExistencia);
        $stmtExistencia->execute([':nombre' => $nombre]);

        if ($stmtExistencia->fetchColumn() > 0) {
            // Ya existe un producto con ese nombre
            echo "Ya existe un producto con ese nombre.";
            return false;
        }

        $sql = "insert into producto(NombreProducto, DescripcionProducto, Stock, Precio, CodCategoria) 
        values(:nombre, :descripcion, :stock, :precio, :codCategoria)";
        $stmt = $bd->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':descripcion' => $descripcion,
            ':stock' => $stock,
            ':precio' => $precio,
            ':codCategoria' => $codCategoria
        ]);
        return true;
    } catch (PDOException $e) {
        echo "Error al insertar el producto: " . $e->getMessage();
        return FALSE;
    } finally {
        $bd = null;
    }
}

/**
 * Actualiza el stock de un producto
 * Esta función solo la utiliza el admin
 * 
 * Esta función se utiliza en el fichero productos.php
 */
function cambiar_stock($codProducto, $unidades)
{
    $bd = conexionBD();
    try {
        $sql = "update producto set Stock = :unidades where CodProducto = :codProducto";
        $stmt = $bd->prepare($sql);
        $stmt->execute([
            ':unidades' => $unidades,
            ':codProducto' => $codProducto
        ]);
        return true;
    } catch (PDOException $e) {
        echo "Error al cambiar el stock: " . $e->getMessage();
        return FALSE;
    } finally {
        $bd = null;
    }
}

/**
 * Carga todos los usuarios guardados en la base de datos
 * 
 * Esta función se utiliza en el fichero clientes.php
 */
function cargar_usuarios()
{
    $bd = conexionBD();
    try {
        $sql = "select * from cliente";
        $resul = $bd->query($sql);
        if (!$resul) {
            return FALSE;
        }
        if ($resul->rowCount() === 0) {
            return FALSE;
        }
        return $resul->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error al cargar los clientes: " . $e->getMessage();
        return FALSE;
    } finally {
        $bd = null;
    }
}

/**
 * Inserta un nuevo usuario en la base de datos
 * Primero comprueba que no exista un usuario con el mismo nombre
 * La contraseña se guarda encriptada
 * 
 * Esta función se utiliza en el fichero clientes.php
 */
function insertar_usuario($nombre, $contrasena, $direccion, $rol)
{
    $bd = conexionBD();
    try {
        // Verificar si ya existe un usuario con el mismo nombre
        $consultaExistencia = "select count(*) from cliente where NombreCliente = :nombre";
        $stmtExistencia = $bd->prepare($consultaExistencia);
        $stmtExistencia->execute([':nombre' => $nombre]);

        if ($stmtExistencia->fetchColumn() > 0) {
            // Ya existe un usuario con ese nombre
            echo "Ya existe un usuario con ese nombre.";
            return false;
        }

        // Hash de la contraseña antes de insertarla en la base de datos
        $hashContrasena = password_hash($contrasena, PASSWORD_DEFAULT);

        // Continuar con la inserción si no hay conflictos de nombre
        $sql = "insert into cliente(NombreCliente, ContrasenaCliente, DireccionCliente, Rol) 
                values(:nombre, :contrasena, :direccion, :rol)";
        $stmt = $bd->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':contrasena' => $hashContrasena,
            ':direccion' => $direccion,
            ':rol' => $rol
        ]);

        return true;
    } catch (PDOException $e) {
        echo "Error al insertar el usuario: " . $e->getMessage();
        return FALSE;
    } finally {
        $bd = null;
    }
}

/**
 * Elimina un usuario de la base de datos
 * Por cómo está construida la base de datos hay que tener en cuenta si el usuario tiene pedidos o no
 * Si es admin no tiene pedidos, si es cliente puede que si o no
 * Si tiene pedidos hay que borrar primero los registros de la tabla pedido_producto y luego los de pedido y después el cliente
 * Si no tiene pedidos se puede borrar directamente
 * Esta función solo la utiliza el admin
 * 
 * Esta función se utiliza en el fichero clientes.php
 */
function eliminar_usuario($codCliente)
{
    $bd = conexionBD();
    try {
        //no recordaba el cascade, y ya prefiero dejarlo así, perdón por el código extra
        // Obtener el rol del usuario
        $selectRol = "select Rol from cliente where CodCliente = :codCliente";
        $stmt = $bd->prepare($selectRol);
        $stmt->execute([
            ':codCliente' => $codCliente
        ]);

        $rol = $stmt->fetch(PDO::FETCH_ASSOC)['Rol'];

        // Admin
        if ($rol === 0) {
            $deleteCliente = "delete from cliente where CodCliente = :codCliente";
            $stmt = $bd->prepare($deleteCliente);
            $stmt->execute([
                ':codCliente' => $codCliente
            ]);
        }
        // Cliente
        elseif ($rol === 1) {
            // Comprobar si hay pedidos
            $selectCodPedido = "select CodPedido from pedido where CodCliente = :codCliente";
            $consultaPedido = $bd->prepare($selectCodPedido);
            $consultaPedido->execute([
                ':codCliente' => $codCliente
            ]);

            // Si no hay pedidos se puede borrar fácilmente
            $row = $consultaPedido->fetch(PDO::FETCH_ASSOC);
            $codPedido = $row ? $row['CodPedido'] : null; // Si no hay pedidos, $codPedido es null

            if ($codPedido === null) {
                // No hay pedidos, se puede borrar fácilmente
                $deleteCliente = "delete from cliente where CodCliente = :codCliente";
                $stmt = $bd->prepare($deleteCliente);
                $stmt->execute([
                    ':codCliente' => $codCliente
                ]);
            } else {
                // Si hay pedidos, borrar en pedido_producto y luego en pedido y cliente
                $deletePedidoProducto = "delete from pedido_producto where CodPedido = :codPedido";
                $stmt = $bd->prepare($deletePedidoProducto);
                $stmt->execute([
                    ':codPedido' => $codPedido
                ]);

                $deletePedido = "delete from pedido where CodCliente = :codCliente";
                $stmt = $bd->prepare($deletePedido);
                $stmt->execute([
                    ':codCliente' => $codCliente
                ]);

                $deleteCliente = "delete from cliente where CodCliente = :codCliente";
                $stmt = $bd->prepare($deleteCliente);
                $stmt->execute([
                    ':codCliente' => $codCliente
                ]);
            }
        }

        return true;
    } catch (PDOException $e) {
        echo "Error al eliminar el usuario: " . $e->getMessage();
        return false;
    } finally {
        $bd = null;
    }
}

function consultarStockProducto($codProducto)
{
    $bd = conexionBD();
    try {
        $sql = "select Stock from producto where CodProducto = $codProducto";
        $resul = $bd->query($sql);
        if (!$resul) {
            return FALSE;
        }
        if ($resul->rowCount() === 0) {
            return FALSE;
        }
        return $resul->fetch();
    } catch (PDOException $e) {
        echo "Error al consultar el stock del producto: " . $e->getMessage();
        return FALSE;
    } finally {
        $bd = null;
    }
}
