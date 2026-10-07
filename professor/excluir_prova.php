<?php

session_start();
include("../config/conexao.php");

if(!isset($_SESSION['id'])){
    header("Location: ../auth/login.php");
    exit();
}

if(isset($_GET['id'])){

    $id = $_GET['id'];

    mysqli_query(
        $conexao,
        "DELETE FROM prova_questoes WHERE prova_id = $id"
    );

    mysqli_query(
        $conexao,
        "DELETE FROM provas WHERE id = $id"
    );
}

header("Location: listar_provas.php");
exit();

?>