<?php

session_start();
include("../config/conexao.php");

if(!isset($_SESSION['id'])){
    header("Location: ../auth/login.php");
    exit();
}

$id = $_GET['id'];

mysqli_query($conexao, "DELETE FROM questoes WHERE id = $id");

header("Location: questoes.php");
exit();

?>