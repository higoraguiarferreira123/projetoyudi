<?php
session_start();
include("../config/conexao.php");

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$aluno_id = (int) $_SESSION['id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: provas.php");
    exit();
}

$prova_id = (int) $_GET['id'];
$erro = "";
$sucesso = false;
$nota = 0;
$acertos = 0;
$total = 0;
$resultado_id = 0;

/* =====================================================
   BUSCAR PROVA
===================================================== */
$stmt_prova = mysqli_prepare(
    $conexao,
    "SELECT id, titulo, disciplina FROM provas WHERE id = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt_prova, "i", $prova_id);
mysqli_stmt_execute($stmt_prova);
$result_prova = mysqli_stmt_get_result($stmt_prova);
$dadosProva = mysqli_fetch_assoc($result_prova);
mysqli_stmt_close($stmt_prova);

if (!$dadosProva) {
    die("Prova não encontrada.");
}

/* =====================================================
   FINALIZAR PROVA
===================================================== */
if (isset($_POST['finalizar'])) {

    $respostas = isset($_POST['resposta']) && is_array($_POST['resposta'])
        ? $_POST['resposta']
        : [];

    $stmt_questoes = mysqli_prepare(
        $conexao,
        "SELECT q.id, q.pergunta, q.correta
         FROM questoes q
         INNER JOIN prova_questoes pq ON pq.questao_id = q.id
         WHERE pq.prova_id = ?
         ORDER BY pq.id ASC"
    );
    mysqli_stmt_bind_param($stmt_questoes, "i", $prova_id);
    mysqli_stmt_execute($stmt_questoes);
    $resultado_questoes = mysqli_stmt_get_result($stmt_questoes);

    $questoes = [];
    while ($q = mysqli_fetch_assoc($resultado_questoes)) {
        $questoes[] = $q;
    }
    mysqli_stmt_close($stmt_questoes);

    $total = count($questoes);

    if ($total === 0) {
        $erro = "Esta prova não possui questões.";
    } else {

        foreach ($questoes as $q) {
            $questao_id = (int) $q['id'];
            $resposta = isset($respostas[$questao_id])
                ? strtoupper(trim((string)$respostas[$questao_id]))
                : "";
            $correta = strtoupper(trim((string)$q['correta']));

            if ($resposta !== "" && $resposta === $correta) {
                $acertos++;
            }
        }

        $nota = round(($acertos / $total) * 10, 2);

        mysqli_begin_transaction($conexao);

        try {
            /* Salvar resultado */
            $stmt_resultado = mysqli_prepare(
                $conexao,
                "INSERT INTO resultados (aluno_id, prova_id, nota, data_realizacao)
                 VALUES (?, ?, ?, NOW())"
            );

            mysqli_stmt_bind_param(
                $stmt_resultado,
                "iid",
                $aluno_id,
                $prova_id,
                $nota
            );

            if (!mysqli_stmt_execute($stmt_resultado)) {
                throw new Exception("Erro ao salvar o resultado: " . mysqli_stmt_error($stmt_resultado));
            }

            $resultado_id = mysqli_insert_id($conexao);
            mysqli_stmt_close($stmt_resultado);

            /* Salvar cada resposta e o gabarito */
            $stmt_resposta = mysqli_prepare(
                $conexao,
                "INSERT INTO respostas_alunos
                 (resultado_id, questao_id, resposta_aluno, resposta_correta, correta)
                 VALUES (?, ?, ?, ?, ?)"
            );

            if (!$stmt_resposta) {
                throw new Exception("Erro ao preparar o salvamento das respostas: " . mysqli_error($conexao));
            }

            foreach ($questoes as $q) {
                $questao_id = (int) $q['id'];
                $resposta_aluno = isset($respostas[$questao_id])
                    ? strtoupper(trim((string)$respostas[$questao_id]))
                    : "";
                $resposta_correta = strtoupper(trim((string)$q['correta']));
                $acertou = ($resposta_aluno !== "" && $resposta_aluno === $resposta_correta) ? 1 : 0;

                mysqli_stmt_bind_param(
                    $stmt_resposta,
                    "iissi",
                    $resultado_id,
                    $questao_id,
                    $resposta_aluno,
                    $resposta_correta,
                    $acertou
                );

                if (!mysqli_stmt_execute($stmt_resposta)) {
                    throw new Exception("Erro ao salvar a resposta da questão " . $questao_id . ": " . mysqli_stmt_error($stmt_resposta));
                }
            }

            mysqli_stmt_close($stmt_resposta);
            mysqli_commit($conexao);
            $sucesso = true;

        } catch (Exception $e) {
            mysqli_rollback($conexao);
            $erro = $e->getMessage();
        }
    }

    if ($sucesso) {
        header("Location: resultado_prova.php?id=" . $resultado_id);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Realizar Prova</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Segoe UI,Arial,sans-serif;background:#f4f6f9;color:#172033}
.header{background:#6F3D62;color:white;padding:25px}
.container{width:95%;max-width:1000px;margin:30px auto}
.prova{background:white;padding:25px;border-radius:15px;box-shadow:0 5px 15px rgba(0,0,0,.1);margin-bottom:20px}
.questao{background:white;border-left:5px solid #6F3D62;padding:20px;border-radius:10px;margin-bottom:20px;box-shadow:0 3px 10px rgba(0,0,0,.08)}
.questao h3{color:#6F3D62;margin-bottom:10px}
.alternativa{display:flex;align-items:flex-start;gap:12px;width:100%;margin:12px 0;padding:15px;background:#f8f9fa;border:2px solid #ddd;border-radius:10px;cursor:pointer;transition:.3s}
.alternativa:hover{background:#eef5ff;border-color:#6F3D62}
.alternativa input[type=radio]{margin-top:4px;transform:scale(1.2)}
.alternativa span{display:block;width:100%;line-height:1.6;word-break:break-word}
button{width:100%;padding:15px;font-size:18px;background:#167A72;color:white;border:none;border-radius:10px;cursor:pointer}
button:hover{background:#1f8a3a}
.erro{background:#f8d7da;color:#842029;padding:15px;border-radius:10px;margin-bottom:20px;font-weight:600}
</style>
    <link rel="stylesheet" href="../assets/css/questsystem.css">
</head>
<body class="qs-app">
<?php include_once __DIR__ . "/../includes/sidebar.php"; ?>
<div class="header">
<h1>📝 <?php echo htmlspecialchars($dadosProva['titulo'], ENT_QUOTES, 'UTF-8'); ?></h1>
<p>Disciplina: <?php echo htmlspecialchars($dadosProva['disciplina'], ENT_QUOTES, 'UTF-8'); ?></p>
</div>

<div class="container">

<?php if ($erro !== "") { ?>
<div class="erro">❌ <?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></div>
<?php } ?>

<form method="POST" action="">

<?php
$stmt = mysqli_prepare(
    $conexao,
    "SELECT q.*
     FROM questoes q
     INNER JOIN prova_questoes pq ON q.id = pq.questao_id
     WHERE pq.prova_id = ?
     ORDER BY pq.id ASC"
);
mysqli_stmt_bind_param($stmt, "i", $prova_id);
mysqli_stmt_execute($stmt);
$questoes_tela = mysqli_stmt_get_result($stmt);

$numero = 1;
while ($q = mysqli_fetch_assoc($questoes_tela)) {
?>

<div class="questao">
<h3>Questão <?php echo $numero; ?></h3>
<p><?php echo nl2br(htmlspecialchars($q['pergunta'], ENT_QUOTES, 'UTF-8')); ?></p>
<br>

<label class="alternativa">
<input type="radio" name="resposta[<?php echo (int)$q['id']; ?>]" value="A" required>
<span><strong>A)</strong> <?php echo htmlspecialchars($q['alternativa_a'], ENT_QUOTES, 'UTF-8'); ?></span>
</label>

<label class="alternativa">
<input type="radio" name="resposta[<?php echo (int)$q['id']; ?>]" value="B">
<span><strong>B)</strong> <?php echo htmlspecialchars($q['alternativa_b'], ENT_QUOTES, 'UTF-8'); ?></span>
</label>

<label class="alternativa">
<input type="radio" name="resposta[<?php echo (int)$q['id']; ?>]" value="C">
<span><strong>C)</strong> <?php echo htmlspecialchars($q['alternativa_c'], ENT_QUOTES, 'UTF-8'); ?></span>
</label>

<label class="alternativa">
<input type="radio" name="resposta[<?php echo (int)$q['id']; ?>]" value="D">
<span><strong>D)</strong> <?php echo htmlspecialchars($q['alternativa_d'], ENT_QUOTES, 'UTF-8'); ?></span>
</label>
</div>

<?php
$numero++;
}
mysqli_stmt_close($stmt);
?>

<button type="submit" name="finalizar">Finalizar Prova</button>
</form>
</div>
</body>
</html>
