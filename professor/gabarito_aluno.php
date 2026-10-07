<?php
session_start();
include("../config/conexao.php");

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$professor_id = (int) $_SESSION['id'];
$resultado_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($resultado_id <= 0) {
    header("Location: resultados.php");
    exit();
}

/* =====================================================
   BUSCAR RESULTADO
   GARANTINDO QUE A PROVA PERTENCE AO PROFESSOR
===================================================== */

$sql = "
    SELECT
        r.id AS resultado_id,
        r.nota,
        r.data_realizacao,
        u.nome AS aluno_nome,
        p.id AS prova_id,
        p.titulo,
        p.disciplina
    FROM resultados r
    INNER JOIN usuarios u ON u.id = r.aluno_id
    INNER JOIN provas p ON p.id = r.prova_id
    WHERE r.id = ?
      AND p.professor_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conexao, $sql);

if (!$stmt) {
    die("Erro ao preparar consulta.");
}

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $resultado_id,
    $professor_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$dados = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$dados) {
    die("Resultado não encontrado ou você não tem permissão para visualizá-lo.");
}

/* =====================================================
   BUSCAR RESPOSTAS DO ALUNO
===================================================== */

$sql_respostas = "
    SELECT
        ra.id,
        ra.questao_id,
        ra.resposta_aluno,
        ra.resposta_correta,
        ra.correta,
        q.pergunta,
        q.alternativa_a,
        q.alternativa_b,
        q.alternativa_c,
        q.alternativa_d
    FROM respostas_alunos ra
    INNER JOIN questoes q ON q.id = ra.questao_id
    WHERE ra.resultado_id = ?
    ORDER BY ra.id ASC
";

$stmt = mysqli_prepare($conexao, $sql_respostas);

if (!$stmt) {
    die("Erro ao preparar consulta das respostas.");
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $resultado_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$respostas = [];
$acertos = 0;
$erros = 0;

while ($linha = mysqli_fetch_assoc($result)) {

    $respostas[] = $linha;

    if ((int)$linha['correta'] === 1) {
        $acertos++;
    } else {
        $erros++;
    }
}

mysqli_stmt_close($stmt);

$total = count($respostas);

/* =====================================================
   PERCENTUAL
===================================================== */

$percentual = $total > 0
    ? ($acertos / $total) * 100
    : 0;

/* =====================================================
   CLASSIFICAÇÃO DA NOTA
===================================================== */

$nota = (float)$dados['nota'];

if ($nota >= 9) {

    $classificacao = "Excelente desempenho";
    $classe_nota = "nota-excelente";

} elseif ($nota >= 7) {

    $classificacao = "Bom desempenho";
    $classe_nota = "nota-bom";

} elseif ($nota >= 5) {

    $classificacao = "Desempenho regular";
    $classe_nota = "nota-regular";

} else {

    $classificacao = "Precisa melhorar";
    $classe_nota = "nota-baixa";
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Gabarito do Aluno</title>

<link
    rel="stylesheet"
    href="../assets/css/questsystem.css"
>

<style>

/* =====================================================
   PÁGINA
===================================================== */

.gabarito-header{

    background:
        linear-gradient(
            135deg,
            #6d315d,
            #8a4c76
        );

    color:#fff;

    padding:30px 5%;

}

.gabarito-header h1{

    margin:0 0 7px;

    font-size:29px;

}

.gabarito-header p{

    margin:0;

    opacity:.92;

}

.gabarito-container{

    width:92%;

    max-width:1100px;

    margin:30px auto 60px;

}


/* =====================================================
   CARDS
===================================================== */

.gabarito-card{

    background:#fff;

    border-radius:18px;

    padding:25px;

    margin-bottom:20px;

    box-shadow:
        0 8px 30px
        rgba(0,0,0,.07);

}


/* =====================================================
   INFORMAÇÕES DA PROVA
===================================================== */

.info-prova h2{

    color:#6d315d;

    margin-bottom:15px;

}

.info-item{

    margin:7px 0;

    color:#64748b;

}

.info-item strong{

    color:#344054;

}


/* =====================================================
   ESTATÍSTICAS
===================================================== */

.gabarito-stats{

    display:grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:15px;

}

.g-stat{

    border-radius:14px;

    padding:20px;

    text-align:center;

    background:#f8f7fa;

    border:1px solid #ece8ee;

}

.g-stat strong{

    display:block;

    font-size:30px;

    margin-bottom:5px;

}

.g-stat span{

    color:#64748b;

    font-weight:600;

}


/* CORES DOS INDICADORES */

.g-stat.nota{

    background:#f5edf3;

}

.g-stat.nota strong{

    color:#6d315d;

}

.g-stat.questoes strong{

    color:#7a4bb7;

}

.g-stat.acertos{

    background:#edf8f4;

}

.g-stat.acertos strong{

    color:#1f7a6b;

}

.g-stat.erros{

    background:#fff2ef;

}

.g-stat.erros strong{

    color:#d9825b;

}


/* =====================================================
   CLASSIFICAÇÃO
===================================================== */

.classificacao{

    margin-top:18px;

    padding:13px 16px;

    border-radius:10px;

    text-align:center;

    font-weight:700;

    background:#f7f4f7;

    color:#6d315d;

}

.nota-excelente{

    background:#e9f8f0;

    color:#167044;

}

.nota-bom{

    background:#edf8f4;

    color:#1f7a6b;

}

.nota-regular{

    background:#fff7df;

    color:#856404;

}

.nota-baixa{

    background:#fdeaea;

    color:#a12732;

}


/* =====================================================
   TÍTULO GABARITO
===================================================== */

.gabarito-titulo{

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:15px;

    flex-wrap:wrap;

    margin-bottom:22px;

}

.gabarito-titulo h2{

    margin:0;

    color:#6d315d;

}


/* =====================================================
   BOTÃO IA
===================================================== */

.btn-analise-ia{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    gap:7px;

    text-decoration:none;

    padding:11px 17px;

    border-radius:10px;

    background:
        linear-gradient(
            135deg,
            #1f7a6b,
            #299889
        );

    color:#fff;

    font-weight:700;

    transition:.2s ease;

}

.btn-analise-ia:hover{

    transform:translateY(-2px);

    box-shadow:
        0 7px 18px
        rgba(31,122,107,.22);

}


/* =====================================================
   QUESTÃO
===================================================== */

.questao-gabarito{

    padding:20px;

    border-radius:13px;

    margin-bottom:17px;

    border:1px solid #e3e0e5;

}

.questao-gabarito.ok{

    border-left:6px solid #1f7a6b;

    background:#f4fcf8;

}

.questao-gabarito.bad{

    border-left:6px solid #d9825b;

    background:#fff8f6;

}


/* =====================================================
   STATUS
===================================================== */

.status-resposta{

    display:inline-flex;

    padding:7px 11px;

    border-radius:8px;

    font-weight:700;

    margin-bottom:12px;

}

.status-resposta.ok{

    background:#dff4e9;

    color:#167044;

}

.status-resposta.bad{

    background:#fde4e5;

    color:#a12732;

}


/* =====================================================
   PERGUNTA
===================================================== */

.pergunta-gabarito{

    line-height:1.65;

    margin:

    12px 0 16px;

    color:#253044;

}


/* =====================================================
   RESPOSTAS
===================================================== */

.resposta-gabarito{

    padding:12px 14px;

    border-radius:9px;

    margin-top:9px;

    line-height:1.5;

}

.resposta-aluno{

    background:#f2f4f7;

    color:#344054;

}

.resposta-correta{

    background:#dff4e9;

    color:#14532d;

}

.resposta-errada{

    background:#fde4e5;

    color:#842029;

}


/* =====================================================
   BOTÕES
===================================================== */

.acoes-final{

    display:flex;

    gap:10px;

    flex-wrap:wrap;

    margin-top:20px;

}

.btn-voltar-gabarito{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    text-decoration:none;

    padding:11px 17px;

    border-radius:10px;

    background:#495057;

    color:#fff;

    font-weight:700;

}

.btn-voltar-gabarito:hover{

    background:#343a40;

}


/* =====================================================
   VAZIO
===================================================== */

.sem-respostas{

    text-align:center;

    padding:35px 20px;

    color:#64748b;

    background:#faf9fb;

    border-radius:12px;

}


/* =====================================================
   RESPONSIVO
===================================================== */

@media(max-width:850px){

    .gabarito-stats{

        grid-template-columns:
            repeat(2,1fr);

    }

}

@media(max-width:550px){

    .gabarito-header{

        padding:24px 20px;

    }

    .gabarito-header h1{

        font-size:24px;

    }

    .gabarito-container{

        width:94%;

    }

    .gabarito-stats{

        grid-template-columns:1fr;

    }

    .gabarito-card{

        padding:18px;

    }

}

</style>

</head>

<body class="qs-app">

<?php include_once __DIR__ . "/../includes/sidebar.php"; ?>


<!-- =====================================================
     CABEÇALHO
===================================================== -->

<div class="gabarito-header">

    <h1>📋 Gabarito do Aluno</h1>

    <p>
        Correção detalhada e análise do desempenho na prova.
    </p>

</div>


<!-- =====================================================
     CONTEÚDO
===================================================== -->

<div class="gabarito-container">


    <!-- =================================================
         INFORMAÇÕES
    ================================================== -->

    <div class="gabarito-card info-prova">

        <h2>

            <?php
            echo htmlspecialchars(
                $dados['titulo'],
                ENT_QUOTES,
                'UTF-8'
            );
            ?>

        </h2>

        <div class="info-item">

            <strong>Aluno:</strong>

            <?php
            echo htmlspecialchars(
                $dados['aluno_nome'],
                ENT_QUOTES,
                'UTF-8'
            );
            ?>

        </div>

        <div class="info-item">

            <strong>Disciplina:</strong>

            <?php
            echo htmlspecialchars(
                $dados['disciplina'],
                ENT_QUOTES,
                'UTF-8'
            );
            ?>

        </div>

        <div class="info-item">

            <strong>Realizada em:</strong>

            <?php

            if (!empty($dados['data_realizacao'])) {

                echo date(
                    'd/m/Y H:i',
                    strtotime(
                        $dados['data_realizacao']
                    )
                );

            } else {

                echo '-';

            }

            ?>

        </div>

    </div>


    <!-- =================================================
         ESTATÍSTICAS
    ================================================== -->

    <div class="gabarito-card">

        <div class="gabarito-stats">


            <!-- NOTA -->

            <div class="g-stat nota">

                <strong>

                    <?php

                    echo number_format(
                        $nota,
                        2,
                        ',',
                        '.'
                    );

                    ?>

                </strong>

                <span>Nota</span>

            </div>


            <!-- QUESTÕES -->

            <div class="g-stat questoes">

                <strong>

                    <?php
                    echo $total;
                    ?>

                </strong>

                <span>Questões</span>

            </div>


            <!-- ACERTOS -->

            <div class="g-stat acertos">

                <strong>

                    <?php
                    echo $acertos;
                    ?>

                </strong>

                <span>Acertos</span>

            </div>


            <!-- ERROS -->

            <div class="g-stat erros">

                <strong>

                    <?php
                    echo $erros;
                    ?>

                </strong>

                <span>Erros</span>

            </div>

        </div>


        <div class="classificacao <?php echo $classe_nota; ?>">

            <?php echo $classificacao; ?>

            — <?php echo number_format($percentual, 1, ',', '.'); ?>% de aproveitamento

        </div>

    </div>


    <!-- =================================================
         GABARITO
    ================================================== -->

    <div class="gabarito-card">


        <div class="gabarito-titulo">

            <h2>
                Gabarito detalhado
            </h2>


            <!-- IA -->

            <a
                href="analise_ia.php?id=<?php echo (int)$resultado_id; ?>"
                class="btn-analise-ia"
            >

                ✦ Analisar este resultado com IA

            </a>

        </div>


        <?php if ($total === 0) { ?>

            <div class="sem-respostas">

                <h3>Não existem respostas registradas.</h3>

                <p>

                    O aluno precisa realizar novamente a
                    prova com o código atualizado.

                </p>

            </div>

        <?php } ?>


        <?php foreach ($respostas as $i => $r) {

            $alternativas = [

                'A' => $r['alternativa_a'],

                'B' => $r['alternativa_b'],

                'C' => $r['alternativa_c'],

                'D' => $r['alternativa_d']

            ];

            $respostaAluno =
                strtoupper(
                    trim(
                        (string)$r['resposta_aluno']
                    )
                );

            $respostaCorreta =
                strtoupper(
                    trim(
                        (string)$r['resposta_correta']
                    )
                );

            $acertou =
                (int)$r['correta'] === 1;

        ?>


        <div
            class="questao-gabarito
            <?php echo $acertou ? 'ok' : 'bad'; ?>"
        >


            <!-- STATUS -->

            <span
                class="status-resposta
                <?php echo $acertou ? 'ok' : 'bad'; ?>"
            >

                <?php

                echo $acertou
                    ? '✅ Acertou'
                    : '❌ Errou';

                ?>

            </span>


            <!-- NÚMERO -->

            <h3>

                Questão
                <?php echo $i + 1; ?>

            </h3>


            <!-- PERGUNTA -->

            <div class="pergunta-gabarito">

                <?php

                echo nl2br(
                    htmlspecialchars(
                        $r['pergunta'],
                        ENT_QUOTES,
                        'UTF-8'
                    )
                );

                ?>

            </div>


            <!-- RESPOSTA DO ALUNO -->

            <?php if (

                $respostaAluno !== ''
                &&
                isset(
                    $alternativas[$respostaAluno]
                )

            ) { ?>

                <div
                    class="resposta-gabarito
                    <?php
                    echo $acertou
                        ? 'resposta-aluno'
                        : 'resposta-errada';
                    ?>"
                >

                    <strong>
                        Resposta do aluno:
                    </strong>

                    <?php

                    echo htmlspecialchars(
                        $respostaAluno,
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    ?>)

                    <?php

                    echo htmlspecialchars(
                        $alternativas[$respostaAluno],
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    ?>

                </div>

            <?php } else { ?>

                <div class="resposta-gabarito resposta-errada">

                    <strong>
                        Resposta do aluno:
                    </strong>

                    Não respondida.

                </div>

            <?php } ?>


            <!-- RESPOSTA CORRETA -->

            <div class="resposta-gabarito resposta-correta">

                <strong>
                    Resposta correta:
                </strong>

                <?php

                echo htmlspecialchars(
                    $respostaCorreta,
                    ENT_QUOTES,
                    'UTF-8'
                );

                ?>)

                <?php

                echo isset(
                    $alternativas[$respostaCorreta]
                )
                    ? htmlspecialchars(
                        $alternativas[$respostaCorreta],
                        ENT_QUOTES,
                        'UTF-8'
                    )
                    : '';

                ?>

            </div>


        </div>


        <?php } ?>


    </div>


    <!-- =================================================
         AÇÕES FINAIS
    ================================================== -->

    <div class="acoes-final">

        <a
            class="btn-voltar-gabarito"
            href="resultados.php"
        >

            ← Voltar aos resultados

        </a>


        <a
            class="btn-analise-ia"
            href="analise_ia.php?id=<?php echo (int)$resultado_id; ?>"
        >

            ✦ Analisar resultado com IA

        </a>

    </div>


</div>

</body>
</html>
```
