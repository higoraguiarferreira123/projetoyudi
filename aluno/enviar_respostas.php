<?php
/*
 * Este arquivo mantém compatibilidade com versões anteriores do sistema.
 * O processamento principal agora fica em realizar_prova.php.
 */
session_start();
include("../config/conexao.php");

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_POST['prova_id'])) {
    header("Location: dashboard.php");
    exit();
}

$prova_id = (int) $_POST['prova_id'];
$respostas = $_POST['respostas'] ?? $_POST['resposta'] ?? [];

/* Reaproveita o mesmo processamento através de um POST interno simples. */
$_GET['id'] = $prova_id;
$_POST['resposta'] = is_array($respostas) ? $respostas : [];
$_POST['finalizar'] = 1;

require __DIR__ . '/realizar_prova.php';
