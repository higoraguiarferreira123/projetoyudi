<?php

session_start();

if(!isset($_SESSION['id'])){
    header("Location: ../auth/login.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Painel do Aluno</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:Arial, sans-serif;
    background:#f4f6f9;
}

.header{
    background:#167A72;
    color:white;
    padding:20px;
}

.container{
    width:90%;
    max-width:1200px;
    margin:30px auto;
}

.menu{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:20px;
}

.card{
    background:white;
    padding:20px;
    border-radius:10px;
    box-shadow:0 2px 8px rgba(0,0,0,0.1);
}

.botao{
    display:block;
    text-decoration:none;
    text-align:center;
    background:#167A72;
    color:white;
    padding:20px;
    border-radius:10px;
    font-size:18px;
}

.botao:hover{
    background:#0F5E58;
}

.sair{
    background:#D96C55;
}

.sair:hover{
    background:#A4493A;
}

</style>

    <link rel="stylesheet" href="../assets/css/questsystem.css">
</head>
<body class="qs-app">
<?php include_once __DIR__ . "/../includes/sidebar.php"; ?>

<div class="header">

<h1>Painel do Aluno</h1>

<p>
Bem-vindo,
<strong><?php echo $_SESSION['nome']; ?></strong>
</p>

</div>

<div class="container">

<div class="menu">

<a class="botao" href="provas.php">
Provas Disponíveis
</a>

<a class="botao" href="historico.php">
Meu Histórico
</a>

<a class="botao sair" href="../auth/logout.php">
Sair
</a>

</div>

</div>

</body>
</html>