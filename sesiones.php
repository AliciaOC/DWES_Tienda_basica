<?php
function comprobar_sesion()
{
	session_start();
	if (!isset($_SESSION['usuario'])) {
		header("Location: login.php?redirigido=true");
	}
}

function comprobar_cliente()
{
	$cliente = $_SESSION['usuario'];
	if ($cliente['Rol'] === 0) {
		header("Location: login.php?redirigido=true");
	}
}

function comprobar_admin()
{
	$cliente = $_SESSION['usuario'];
	if ($cliente['Rol'] === 1) {
		header("Location: login.php?redirigido=true");
	}
}
