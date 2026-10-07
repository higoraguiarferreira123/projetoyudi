<?php
session_start();
include("../config/conexao.php");
if (!isset($_SESSION['id']) || (isset($_SESSION['tipo']) && $_SESSION['tipo'] !== 'professor')) { header("Location: ../auth/login.php"); exit(); }
$professor_id=(int)$_SESSION['id'];
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) die("Prova não encontrada.");
$id=(int)$_GET['id'];
$stmt=mysqli_prepare($conexao,"SELECT * FROM provas WHERE id=? AND professor_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt,"ii",$id,$professor_id); mysqli_stmt_execute($stmt); $res=mysqli_stmt_get_result($stmt); $prova=$res?mysqli_fetch_assoc($res):null; mysqli_stmt_close($stmt);
if(!$prova) die("Prova não encontrada ou você não tem permissão para visualizá-la.");
$stmt=mysqli_prepare($conexao,"SELECT q.* FROM questoes q INNER JOIN prova_questoes pq ON q.id=pq.questao_id WHERE pq.prova_id=? ORDER BY pq.id ASC");
mysqli_stmt_bind_param($stmt,"i",$id); mysqli_stmt_execute($stmt); $resultado=mysqli_stmt_get_result($stmt);
$questoes=[]; while($resultado && ($q=mysqli_fetch_assoc($resultado))) $questoes[]=$q; mysqli_stmt_close($stmt);
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Visualizar Prova | QuestSystem</title><link rel="stylesheet" href="../assets/css/questsystem.css"></head><body class="qs-app">
<?php include_once __DIR__ . "/../includes/sidebar.php"; ?>
<div class="com-sidebar"><main class="main-content">
<div class="topbar"><div><h1>Visualizar Prova</h1><p>Confira o conteúdo completo da avaliação.</p></div><div class="topbar-user"><div class="avatar"><?= strtoupper(substr($_SESSION['nome']??'P',0,1)) ?></div><div><strong><?= htmlspecialchars($_SESSION['nome']??'Professor') ?></strong><span>Professor</span></div></div></div>
<section class="page-header"><div><span class="page-kicker">AVALIAÇÃO</span><h2><?= htmlspecialchars($prova['titulo']) ?></h2><p>Disciplina: <strong><?= htmlspecialchars($prova['disciplina']) ?></strong></p></div><div class="page-header-actions"><a class="btn btn-success" target="_blank" href="gerar_pdf.php?id=<?=$id?>">Gerar PDF</a><a class="btn btn-secondary" href="listar_provas.php">Voltar</a></div></section>
<section class="content-card"><div class="section-heading"><div><span class="section-kicker">CONTEÚDO</span><h2>Questões da prova</h2><p><?= count($questoes) ?> questão(ões) vinculada(s).</p></div></div>
<?php if($questoes): foreach($questoes as $i=>$q): ?>
<article class="questao"><div class="questao-numero">Questão <?= $i+1 ?></div><div class="questao-pergunta"><?= nl2br(htmlspecialchars($q['pergunta'])) ?></div><div class="alternativas"><div class="alternativa"><strong>A)</strong> <?= htmlspecialchars($q['alternativa_a']) ?></div><div class="alternativa"><strong>B)</strong> <?= htmlspecialchars($q['alternativa_b']) ?></div><div class="alternativa"><strong>C)</strong> <?= htmlspecialchars($q['alternativa_c']) ?></div><div class="alternativa"><strong>D)</strong> <?= htmlspecialchars($q['alternativa_d']) ?></div></div></article>
<?php endforeach; else: ?><div class="vazio"><div class="vazio-icone">📭</div><h3>Nenhuma questão encontrada</h3><p>Esta prova não possui questões vinculadas.</p></div><?php endif; ?>
</section>
<div class="bottom-actions"><a class="btn btn-success" target="_blank" href="gerar_pdf.php?id=<?=$id?>">Gerar PDF</a><a class="btn btn-secondary" href="listar_provas.php">← Voltar para Provas</a></div>
<div class="page-footer"><span>QuestSystem</span><span>Visualização de avaliações</span></div>
</main></div></body></html>