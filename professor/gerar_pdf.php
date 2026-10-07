<?php
session_start();
include("../config/conexao.php");
require("../fpdf19/fpdf.php");
if (!isset($_SESSION['id']) || (isset($_SESSION['tipo']) && $_SESSION['tipo'] !== 'professor')) { header("Location: ../auth/login.php"); exit(); }
$professor_id=(int)$_SESSION['id'];
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) die("Prova não encontrada.");
$id=(int)$_GET['id'];
$stmt=mysqli_prepare($conexao,"SELECT * FROM provas WHERE id=? AND professor_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt,"ii",$id,$professor_id); mysqli_stmt_execute($stmt); $res=mysqli_stmt_get_result($stmt); $prova=$res?mysqli_fetch_assoc($res):null; mysqli_stmt_close($stmt);
if(!$prova) die("Prova não encontrada ou você não tem permissão para gerar este PDF.");
$stmt=mysqli_prepare($conexao,"SELECT q.* FROM questoes q INNER JOIN prova_questoes pq ON q.id=pq.questao_id WHERE pq.prova_id=? ORDER BY pq.id ASC");
mysqli_stmt_bind_param($stmt,"i",$id); mysqli_stmt_execute($stmt); $resultado=mysqli_stmt_get_result($stmt);
$pdf=new FPDF(); $pdf->SetMargins(18,18,18); $pdf->SetAutoPageBreak(true,18); $pdf->AddPage();
$pdf->SetFont('Arial','B',18); $pdf->Cell(0,10,utf8_decode('QUESTSYSTEM'),0,1,'C');
$pdf->SetFont('Arial','B',14); $pdf->Cell(0,9,utf8_decode($prova['titulo']),0,1,'C');
$pdf->SetFont('Arial','',11); $pdf->Cell(0,7,utf8_decode('Disciplina: '.$prova['disciplina']),0,1,'C'); $pdf->Ln(5); $pdf->SetDrawColor(190,190,190); $pdf->Line(18,$pdf->GetY(),192,$pdf->GetY()); $pdf->Ln(8);
$pdf->SetFont('Arial','',11); $pdf->Cell(0,8,utf8_decode('Aluno: _________________________________________________'),0,1); $pdf->Cell(0,8,utf8_decode('Turma: __________________________    Data: ____/____/________'),0,1); $pdf->Ln(8);
$numero=1; while($q=mysqli_fetch_assoc($resultado)){ $pdf->SetFont('Arial','B',11); $pdf->MultiCell(0,7,utf8_decode('Questão '.$numero.': '.$q['pergunta'])); $pdf->Ln(2); $pdf->SetFont('Arial','',10); foreach(['A'=>$q['alternativa_a'],'B'=>$q['alternativa_b'],'C'=>$q['alternativa_c'],'D'=>$q['alternativa_d']] as $l=>$t){ $pdf->MultiCell(0,7,utf8_decode($l.') '.$t)); $pdf->Ln(1); } $pdf->Ln(5); $numero++; } mysqli_stmt_close($stmt);
if($numero===1){$pdf->SetFont('Arial','',11);$pdf->Cell(0,10,utf8_decode('Nenhuma questão foi encontrada para esta prova.'),0,1,'C');}
$pdf->SetTextColor(100,100,100); $pdf->SetFont('Arial','I',9); $pdf->Ln(5); $pdf->Cell(0,8,utf8_decode('QuestSystem - Sistema de Questões e Avaliações'),0,1,'C');
$pdf->Output('I','Prova_'.$prova['id'].'.pdf');
?>