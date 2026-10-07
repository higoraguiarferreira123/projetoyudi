<?php

session_start();
include("../config/conexao.php");

if(!isset($_SESSION['id'])){
    header("Location: ../auth/login.php");
    exit();
}

$pesquisa = "";

if(isset($_GET['pesquisa'])){
    $pesquisa = mysqli_real_escape_string(
        $conexao,
        $_GET['pesquisa']
    );
}

$professor_id = (int) $_SESSION['id'];

$sqlContadorStmt = mysqli_prepare($conexao, "SELECT COUNT(*) AS total FROM provas WHERE professor_id = ?");
mysqli_stmt_bind_param($sqlContadorStmt, 'i', $professor_id);
mysqli_stmt_execute($sqlContadorStmt);
$sqlContador = mysqli_stmt_get_result($sqlContadorStmt);

$totalProvas = mysqli_fetch_assoc($sqlContador);

$sql = "SELECT * FROM provas WHERE professor_id = $professor_id";

if($pesquisa != ""){
    $sql .= " AND (titulo LIKE '%$pesquisa%'
              OR disciplina LIKE '%$pesquisa%')";
}

$sql .= " ORDER BY id DESC";

$resultado = mysqli_query($conexao, $sql);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Gerenciar Provas</title>

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
    max-width:1300px;
    margin:30px auto;
}

.card{
    background:white;
    border-radius:15px;
    padding:20px;
    box-shadow:0 5px 15px rgba(0,0,0,.1);
    margin-bottom:20px;
}

.topo{
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:15px;
}

.contador{
    font-size:22px;
    color:#6F3D62;
    font-weight:bold;
}

input[type=text]{
    width:300px;
    padding:12px;
    border:1px solid #ddd;
    border-radius:10px;
}

.btn{
    background:#6F3D62;
    color:white;
    border:none;
    padding:12px 20px;
    border-radius:10px;
    cursor:pointer;
    font-weight:bold;
}

.btn:hover{
    background:#4F2C46;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#6F3D62;
    color:white;
}

th,
td{
    padding:14px;
    border:1px solid #ddd;
    text-align:center;
}

tr:nth-child(even){
    background:#f8f8f8;
}

.visualizar{
    background:#167A72;
    color:white;
    padding:8px 12px;
    border-radius:8px;
    text-decoration:none;
}

.visualizar:hover{
    background:#0F5E58;
}

.pdf{
    background:#17a2b8;
    color:white;
    padding:8px 12px;
    border-radius:8px;
    text-decoration:none;
}

.pdf:hover{
    background:#117a8b;
}

.excluir{
    background:#D96C55;
    color:white;
    padding:8px 12px;
    border-radius:8px;
    text-decoration:none;
}

.excluir:hover{
    background:#b52a37;
}

.voltar{
    display:inline-block;
    background:#596170;
    color:white;
    text-decoration:none;
    padding:12px 20px;
    border-radius:10px;
}

.voltar:hover{
    background:#454B57;
}

.sem-registros{
    text-align:center;
    padding:30px;
    color:#666;
}

</style>

    <link rel="stylesheet" href="../assets/css/questsystem.css">
</head>
<body class="qs-app">
<?php include_once __DIR__ . "/../includes/sidebar.php"; ?>

<div class="header">

<h1>Gerenciamento de Provas</h1>

<p>
Visualize, pesquise e administre todas as provas criadas
</p>

</div>

<div class="container">

<div class="card">

<div class="topo">

<div class="contador">
Total de Provas:
<?php echo $totalProvas['total']; ?>
</div>

<form method="GET">

<input
type="text"
name="pesquisa"
placeholder="Pesquisar prova..."
value="<?php echo $pesquisa; ?>">

<button class="btn" type="submit">
Pesquisar
</button>

</form>

</div>

</div>

<div class="card">

<table>

<tr>
    <th>ID</th>
    <th>Título</th>
    <th>Disciplina</th>
    <th>Data</th>
    <th>Ações</th>
</tr>

<?php

if(mysqli_num_rows($resultado) > 0){

while($prova = mysqli_fetch_assoc($resultado)){

?>

<tr>

<td>
<?php echo $prova['id']; ?>
</td>

<td>
<?php echo $prova['titulo']; ?>
</td>

<td>
<?php echo $prova['disciplina']; ?>
</td>

<td>
<?php echo date(
'd/m/Y',
strtotime($prova['data_criacao'])
); ?>
</td>

<td>

<a
class="visualizar"
href="visualizar_prova.php?id=<?php echo $prova['id']; ?>">
Visualizar
</a>

<a
class="pdf"
href="gerar_pdf.php?id=<?php echo $prova['id']; ?>">
PDF
</a>

<a
class="excluir"
href="excluir_prova.php?id=<?php echo $prova['id']; ?>"
onclick="return confirm('Deseja excluir esta prova?')">
Excluir
</a>

</td>

</tr>

<?php

}

}else{

?>

<tr>

<td colspan="5" class="sem-registros">

Nenhuma prova encontrada.

</td>

</tr>

<?php

}

?>

</table>

</div>

<a class="voltar" href="dashboard.php">
Voltar ao Painel
</a>

</div>

</body>
</html>