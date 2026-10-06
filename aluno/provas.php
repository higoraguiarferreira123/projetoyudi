<?php

session_start();
include("../config/conexao.php");

if(!isset($_SESSION['id'])){
    header("Location: ../auth/login.php");
    exit();
}

$provas = mysqli_query(
    $conexao,
    "SELECT * FROM provas ORDER BY id DESC"
);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Provas Disponíveis</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:Segoe UI, Arial, sans-serif;
    background:#f4f6f9;
}

.header{
    background:#6F3D62;
    color:white;
    padding:25px;
}

.header h1{
    margin-bottom:5px;
}

.container{
    width:95%;
    max-width:1200px;
    margin:30px auto;
}

.grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(320px,1fr));
    gap:20px;
}

.card{
    background:white;
    border-radius:15px;
    padding:25px;
    box-shadow:0 5px 15px rgba(0,0,0,.1);
    transition:.3s;
}

.card:hover{
    transform:translateY(-5px);
}

.card h2{
    color:#6F3D62;
    margin-bottom:10px;
}

.card p{
    margin-bottom:8px;
    color:#555;
}

.botao{
    display:inline-block;
    background:#167A72;
    color:white;
    text-decoration:none;
    padding:12px 20px;
    border-radius:10px;
    font-weight:bold;
    margin-top:10px;
}

.botao:hover{
    background:#0F5E58;
}

.voltar{
    display:inline-block;
    margin-top:25px;
    background:#596170;
    color:white;
    text-decoration:none;
    padding:12px 20px;
    border-radius:10px;
}

.voltar:hover{
    background:#454B57;
}

.vazio{
    background:white;
    padding:30px;
    border-radius:15px;
    text-align:center;
    box-shadow:0 5px 15px rgba(0,0,0,.1);
}

</style>

    <link rel="stylesheet" href="../assets/css/questsystem.css">
</head>
<body class="qs-app">
<?php include_once __DIR__ . "/../includes/sidebar.php"; ?>

<div class="header">

<h1>Provas Disponíveis</h1>

<p>
Escolha uma prova para responder
</p>

</div>

<div class="container">

<?php if(mysqli_num_rows($provas) > 0){ ?>

<div class="grid">

<?php while($prova = mysqli_fetch_assoc($provas)){ ?>

<div class="card">

<h2>
<?php echo $prova['titulo']; ?>
</h2>

<p>
<strong>Disciplina:</strong>
<?php echo $prova['disciplina']; ?>
</p>

<p>
<strong>Data:</strong>
<?php echo date('d/m/Y', strtotime($prova['data_criacao'])); ?>
</p>

<a
class="botao"
href="realizar_prova.php?id=<?php echo $prova['id']; ?>">
📝 Realizar Prova
</a>

</div>

<?php } ?>

</div>

<?php } else { ?>

<div class="vazio">

<h2>Nenhuma prova disponível</h2>

<p>
Ainda não existem provas cadastradas.
</p>

</div>

<?php } ?>

<br>

<a class="voltar" href="dashboard.php">
← Voltar ao Painel
</a>

</div>

</body>
</html>