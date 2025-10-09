<?php
session_start();

function esAdmin()
{
    return isset($_SESSION['es_admin']) && $_SESSION['es_admin'] === true;
}

function requerirAdmin()
{
    if (!esAdmin()) {
        header("Location: acceso-denegado.php");
        exit();
    }
}
