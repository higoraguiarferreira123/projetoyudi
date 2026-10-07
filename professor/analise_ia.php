````php
<?php

session_start();

include("../config/conexao.php");
require_once("../config/gemini.php");


/* =====================================================
   VERIFICAR LOGIN
===================================================== */

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$professor_id = (int) $_SESSION['id'];

$resultado_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


/* =====================================================
   VALIDAR ID
===================================================== */

if ($resultado_id <= 0) {
    header("Location: resultados.php");
    exit();
}


/* =====================================================
   VARIÁVEIS
===================================================== */

$erro = "";

$analise = null;

$dados = null;

$respostas = [];

$acertos = 0;

$erros = 0;

$total = 0;

$percentual = 0;

$nota = 0;


/* =====================================================
   BUSCAR RESULTADO
   SOMENTE SE A PROVA FOR DO PROFESSOR LOGADO
===================================================== */

$sql = "
    SELECT
        r.id AS resultado_id,
        r.nota,
        r.data_realizacao,

        u.id AS aluno_id,
        u.nome AS aluno_nome,

        p.id AS prova_id,
        p.titulo,
        p.disciplina

    FROM resultados r

    INNER JOIN usuarios u
        ON u.id = r.aluno_id

    INNER JOIN provas p
        ON p.id = r.prova_id

    WHERE r.id = ?
      AND p.professor_id = ?

    LIMIT 1
";


$stmt = mysqli_prepare($conexao, $sql);


if (!$stmt) {
    die("Erro ao preparar consulta do resultado.");
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


/* =====================================================
   RESULTADO NÃO ENCONTRADO
===================================================== */

if (!$dados) {

    die("
        <div style='
            font-family:Arial;
            padding:40px;
            text-align:center;
        '>
            <h2>Resultado não encontrado</h2>

            <p>
                Esse resultado não existe ou
                não pertence a uma das suas provas.
            </p>

            <a href='resultados.php'>
                Voltar para resultados
            </a>
        </div>
    ");

}


/* =====================================================
   NOTA
===================================================== */

$nota = (float) $dados['nota'];


/* =====================================================
   BUSCAR RESPOSTAS
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

        q.alternativa_d,

        q.disciplina,

        q.assunto

    FROM respostas_alunos ra

    INNER JOIN questoes q
        ON q.id = ra.questao_id

    WHERE ra.resultado_id = ?

    ORDER BY ra.id ASC
";


$stmt = mysqli_prepare(
    $conexao,
    $sql_respostas
);


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


while ($linha = mysqli_fetch_assoc($result)) {

    $respostas[] = $linha;

    if ((int)$linha['correta'] === 1) {

        $acertos++;

    } else {

        $erros++;

    }

}


mysqli_stmt_close($stmt);


/* =====================================================
   TOTAL E PERCENTUAL
===================================================== */

$total = count($respostas);


if ($total > 0) {

    $percentual =
        ($acertos / $total) * 100;

}


/* =====================================================
   PREPARAR DADOS PARA A IA
===================================================== */

$texto_questoes = "";

$numero = 1;


foreach ($respostas as $r) {

    $resposta_aluno =
        strtoupper(
            trim(
                (string)$r['resposta_aluno']
            )
        );


    $resposta_correta =
        strtoupper(
            trim(
                (string)$r['resposta_correta']
            )
        );


    $situacao =
        ((int)$r['correta'] === 1)
            ? "ACERTO"
            : "ERRO";


    $assunto =
        trim(
            (string)$r['assunto']
        );


    if ($assunto === "") {
        $assunto = "Não informado";
    }


    $texto_questoes .= "\n";

    $texto_questoes .=
        "QUESTÃO " . $numero . "\n";

    $texto_questoes .=
        "Assunto: " . $assunto . "\n";

    $texto_questoes .=
        "Pergunta: " .
        trim($r['pergunta']) .
        "\n";

    $texto_questoes .=
        "Resposta do aluno: " .
        ($resposta_aluno !== ""
            ? $resposta_aluno
            : "NÃO RESPONDIDA") .
        "\n";

    $texto_questoes .=
        "Resposta correta: " .
        $resposta_correta .
        "\n";

    $texto_questoes .=
        "Situação: " .
        $situacao .
        "\n";

    $numero++;

}


/* =====================================================
   PROMPT DA INTELIGÊNCIA ARTIFICIAL
===================================================== */

$prompt = "

Você é um assistente especializado em análise pedagógica
de avaliações escolares.

Faça uma análise exclusivamente com base nos dados
fornecidos abaixo.

Não invente informações.

Não faça diagnóstico médico ou psicológico.

Não atribua características pessoais ao aluno.

A análise deve ser educacional, objetiva e útil para
o professor.

DADOS DO RESULTADO

Aluno:
{$dados['aluno_nome']}

Prova:
{$dados['titulo']}

Disciplina:
{$dados['disciplina']}

Nota:
" . number_format($nota, 2, ',', '.') . "

Total de questões:
{$total}

Acertos:
{$acertos}

Erros:
{$erros}

Percentual de aproveitamento:
" . number_format($percentual, 1, ',', '.') . "%


QUESTÕES E RESPOSTAS

{$texto_questoes}


Com base nesses dados, produza uma análise pedagógica.

Retorne EXATAMENTE no formato JSON abaixo:

{
  \"resumo\": \"texto curto sobre o desempenho geral\",
  \"pontos_fortes\": [
    \"ponto forte 1\",
    \"ponto forte 2\",
    \"ponto forte 3\"
  ],
  \"pontos_melhorar\": [
    \"dificuldade 1\",
    \"dificuldade 2\",
    \"dificuldade 3\"
  ],
  \"questoes_atencao\": [
    {
      \"numero\": 1,
      \"motivo\": \"explicação objetiva\"
    }
  ],
  \"recomendacoes\": [
    \"recomendação 1\",
    \"recomendação 2\",
    \"recomendação 3\"
  ],
  \"conclusao\": \"conclusão pedagógica geral\"
}

REGRAS:

1. Não invente assuntos que não aparecem nas questões.

2. Considere como ponto forte os assuntos ou padrões
em que o aluno demonstrou bom desempenho.

3. Considere como dificuldade os assuntos relacionados
às questões erradas.

4. Em 'questoes_atencao', coloque somente questões erradas.

5. O campo 'numero' deve corresponder exatamente ao
número da questão apresentado nos dados.

6. As recomendações devem ser práticas para estudo.

7. Não dê recomendações médicas ou psicológicas.

8. Escreva tudo em português do Brasil.

9. Não use Markdown dentro do JSON.

10. Retorne somente o JSON.
";


/* =====================================================
   SOLICITAR ANÁLISE À GEMINI
===================================================== */

$respostaIA = consultarGemini($prompt);


/* =====================================================
   PROCESSAR RESPOSTA
===================================================== */

if (
    isset($respostaIA['sucesso'])
    &&
    $respostaIA['sucesso'] === true
) {

    $textoIA = trim(
        (string)$respostaIA['texto']
    );


    /* ---------------------------------------------
       REMOVER POSSÍVEIS CERCAS MARKDOWN
    --------------------------------------------- */

    $textoIA = preg_replace(
        '/^```(?:json)?\s*/i',
        '',
        $textoIA
    );

    $textoIA = preg_replace(
        '/\s*```$/',
        '',
        $textoIA
    );


    /* ---------------------------------------------
       LOCALIZAR JSON
    --------------------------------------------- */

    $inicio = strpos(
        $textoIA,
        '{'
    );

    $fim = strrpos(
        $textoIA,
        '}'
    );


    if (
        $inicio !== false
        &&
        $fim !== false
        &&
        $fim > $inicio
    ) {

        $textoJSON = substr(
            $textoIA,
            $inicio,
            $fim - $inicio + 1
        );


        $analise = json_decode(
            $textoJSON,
            true
        );


        if (!is_array($analise)) {

            $erro =
                "A IA retornou um formato que não pôde ser interpretado.";

        }

    } else {

        $erro =
            "A IA não retornou uma análise em formato válido.";

    }

} else {

    if (
        isset($respostaIA['mensagem'])
        &&
        $respostaIA['mensagem'] !== ""
    ) {

        $erro =
            (string)$respostaIA['mensagem'];

    } elseif (
        isset($respostaIA['erro'])
        &&
        $respostaIA['erro'] !== ""
    ) {

        $erro =
            (string)$respostaIA['erro'];

    } else {

        $erro =
            "Não foi possível gerar a análise com a IA.";

    }

}


/* =====================================================
   FUNÇÕES DE SEGURANÇA
===================================================== */

function escapar($texto)
{
    return htmlspecialchars(
        (string)$texto,
        ENT_QUOTES,
        'UTF-8'
    );
}


function mostrarLista($lista)
{
    if (!is_array($lista) || count($lista) === 0) {

        return;

    }

    foreach ($lista as $item) {

        echo "<li>";
        echo nl2br(
            escapar($item)
        );
        echo "</li>";

    }
}


/* =====================================================
   CLASSIFICAÇÃO DA NOTA
===================================================== */

if ($nota >= 9) {

    $classeNota = "excelente";

    $textoNota = "Excelente desempenho";

} elseif ($nota >= 7) {

    $classeNota = "bom";

    $textoNota = "Bom desempenho";

} elseif ($nota >= 5) {

    $classeNota = "regular";

    $textoNota = "Desempenho regular";

} else {

    $classeNota = "baixo";

    $textoNota = "Precisa melhorar";

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

<title>Análise com IA</title>

<link
    rel="stylesheet"
    href="../assets/css/questsystem.css"
>


<style>

/* =====================================================
   HEADER
===================================================== */

.ai-header{

    background:
        linear-gradient(
            135deg,
            #6d315d,
            #8a4c76
        );

    color:#fff;

    padding:32px 5%;

}


.ai-header h1{

    margin:0 0 8px;

    font-size:29px;

}


.ai-header p{

    margin:0;

    opacity:.93;

}


/* =====================================================
   CONTAINER
===================================================== */

.ai-container{

    width:92%;

    max-width:1150px;

    margin:30px auto 60px;

}


/* =====================================================
   CARDS
===================================================== */

.ai-card{

    background:#fff;

    border-radius:18px;

    padding:25px;

    margin-bottom:20px;

    box-shadow:
        0 8px 30px
        rgba(0,0,0,.07);

}


/* =====================================================
   CABEÇALHO DA PROVA
===================================================== */

.prova-info{

    display:flex;

    justify-content:space-between;

    align-items:flex-start;

    gap:20px;

    flex-wrap:wrap;

}


.prova-info h2{

    color:#6d315d;

    margin:0 0 10px;

}


.prova-info p{

    margin:6px 0;

    color:#64748b;

}


.prova-info strong{

    color:#344054;

}


/* =====================================================
   SELO IA
===================================================== */

.ai-badge{

    display:inline-flex;

    align-items:center;

    gap:7px;

    padding:10px 14px;

    border-radius:10px;

    background:#edf8f4;

    color:#1f7a6b;

    font-weight:800;

}


/* =====================================================
   ESTATÍSTICAS
===================================================== */

.ai-stats{

    display:grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:15px;

}


.ai-stat{

    text-align:center;

    padding:21px;

    border-radius:14px;

    background:#f8f7fa;

    border:1px solid #ece8ee;

}


.ai-stat strong{

    display:block;

    font-size:31px;

    margin-bottom:5px;

}


.ai-stat span{

    color:#64748b;

    font-weight:600;

}


.ai-stat.nota{

    background:#f5edf3;

}


.ai-stat.nota strong{

    color:#6d315d;

}


.ai-stat.questoes strong{

    color:#7a4bb7;

}


.ai-stat.acertos{

    background:#edf8f4;

}


.ai-stat.acertos strong{

    color:#1f7a6b;

}


.ai-stat.erros{

    background:#fff2ef;

}


.ai-stat.erros strong{

    color:#d9825b;

}


/* =====================================================
   CLASSIFICAÇÃO
===================================================== */

.ai-classificacao{

    text-align:center;

    margin-top:16px;

    padding:12px;

    border-radius:10px;

    font-weight:800;

}


.ai-classificacao.excelente{

    background:#e8f7ef;

    color:#167044;

}


.ai-classificacao.bom{

    background:#edf8f4;

    color:#1f7a6b;

}


.ai-classificacao.regular{

    background:#fff7df;

    color:#856404;

}


.ai-classificacao.baixo{

    background:#fdeaea;

    color:#a12732;

}


/* =====================================================
   TÍTULOS DAS SEÇÕES
===================================================== */

.ai-section-title{

    display:flex;

    align-items:center;

    gap:10px;

    margin-bottom:16px;

    color:#6d315d;

}


.ai-section-title span{

    width:38px;

    height:38px;

    border-radius:10px;

    display:flex;

    align-items:center;

    justify-content:center;

    background:#f5edf3;

}


/* =====================================================
   RESUMO
===================================================== */

.ai-resumo{

    font-size:16px;

    line-height:1.7;

    color:#344054;

    background:#faf8fb;

    padding:18px;

    border-radius:12px;

    border-left:5px solid #6d315d;

}


/* =====================================================
   DUAS COLUNAS
===================================================== */

.ai-grid{

    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:20px;

}


/* =====================================================
   LISTAS
===================================================== */

.ai-lista{

    margin:0;

    padding-left:22px;

}


.ai-lista li{

    margin:10px 0;

    line-height:1.55;

    color:#475467;

}


/* =====================================================
   PONTOS FORTES
===================================================== */

.pontos-fortes{

    background:#f3fbf7;

    border-radius:13px;

    padding:20px;

    border:1px solid #d9f0e5;

}


.pontos-fortes h3{

    color:#1f7a6b;

}


/* =====================================================
   PONTOS A MELHORAR
===================================================== */

.pontos-melhorar{

    background:#fff8f6;

    border-radius:13px;

    padding:20px;

    border:1px solid #f5dfd8;

}


.pontos-melhorar h3{

    color:#d9825b;

}


/* =====================================================
   QUESTÕES DE ATENÇÃO
===================================================== */

.questao-atencao{

    background:#fff8f6;

    border-left:5px solid #d9825b;

    padding:15px 17px;

    border-radius:10px;

    margin-bottom:10px;

}


.questao-atencao strong{

    color:#a04d36;

}


.questao-atencao p{

    margin:5px 0 0;

    color:#475467;

    line-height:1.5;

}


/* =====================================================
   RECOMENDAÇÕES
===================================================== */

.recomendacao{

    background:#f4f8ff;

    border-left:5px solid #7a4bb7;

    padding:15px 17px;

    border-radius:10px;

    margin-bottom:10px;

}


.recomendacao strong{

    color:#6941a5;

}


/* =====================================================
   CONCLUSÃO
===================================================== */

.ai-conclusao{

    background:
        linear-gradient(
            135deg,
            #faf6f9,
            #f4fbf9
        );

    border:1px solid #e8e0e7;

    border-radius:13px;

    padding:20px;

    line-height:1.7;

    color:#344054;

}


/* =====================================================
   ERRO
===================================================== */

.ai-erro{

    background:#fdeaea;

    color:#842029;

    border:1px solid #f5c2c7;

    padding:17px;

    border-radius:12px;

    margin-bottom:20px;

    line-height:1.5;

}


/* =====================================================
   BOTÕES
===================================================== */

.ai-acoes{

    display:flex;

    gap:10px;

    flex-wrap:wrap;

    margin-top:20px;

}


.btn-ai-voltar{

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


.btn-ai-voltar:hover{

    background:#343a40;

}


/* =====================================================
   BOTÃO NOVA ANÁLISE
===================================================== */

.btn-ai-nova{

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

}


/* =====================================================
   RESPONSIVO
===================================================== */

@media(max-width:850px){

    .ai-stats{

        grid-template-columns:
            repeat(2,1fr);

    }

    .ai-grid{

        grid-template-columns:1fr;

    }

}


@media(max-width:550px){

    .ai-header{

        padding:24px 20px;

    }

    .ai-header h1{

        font-size:24px;

    }

    .ai-container{

        width:94%;

    }

    .ai-card{

        padding:18px;

    }

    .ai-stats{

        grid-template-columns:1fr;

    }

}

</style>

</head>


<body class="qs-app">


<?php

include_once
    __DIR__ .
    "/../includes/sidebar.php";

?>


<!-- =====================================================
     HEADER
===================================================== -->

<div class="ai-header">

    <h1>✦ Análise de Desempenho com IA</h1>

    <p>
        Análise pedagógica automática baseada nas respostas
        do aluno nesta avaliação.
    </p>

</div>


<!-- =====================================================
     CONTAINER
===================================================== -->

<div class="ai-container">


    <!-- =================================================
         INFORMAÇÕES
    ================================================== -->

    <div class="ai-card">

        <div class="prova-info">

            <div>

                <h2>

                    <?php

                    echo escapar(
                        $dados['titulo']
                    );

                    ?>

                </h2>


                <p>

                    <strong>Aluno:</strong>

                    <?php

                    echo escapar(
                        $dados['aluno_nome']
                    );

                    ?>

                </p>


                <p>

                    <strong>Disciplina:</strong>

                    <?php

                    echo escapar(
                        $dados['disciplina']
                    );

                    ?>

                </p>


                <p>

                    <strong>Realizada em:</strong>

                    <?php

                    if (!empty(
                        $dados['data_realizacao']
                    )) {

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

                </p>

            </div>


            <div class="ai-badge">

                ✦

                Inteligência Artificial

            </div>

        </div>

    </div>


    <!-- =================================================
         ESTATÍSTICAS
    ================================================== -->

    <div class="ai-card">

        <div class="ai-stats">


            <!-- NOTA -->

            <div class="ai-stat nota">

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

            <div class="ai-stat questoes">

                <strong>

                    <?php echo $total; ?>

                </strong>

                <span>Questões</span>

            </div>


            <!-- ACERTOS -->

            <div class="ai-stat acertos">

                <strong>

                    <?php echo $acertos; ?>

                </strong>

                <span>Acertos</span>

            </div>


            <!-- ERROS -->

            <div class="ai-stat erros">

                <strong>

                    <?php echo $erros; ?>

                </strong>

                <span>Erros</span>

            </div>

        </div>


        <div class="
            ai-classificacao
            <?php echo $classeNota; ?>
        ">

            <?php echo $textoNota; ?>

            —
            
            <?php

            echo number_format(
                $percentual,
                1,
                ',',
                '.'
            );

            ?>%

            de aproveitamento

        </div>

    </div>


    <?php if ($erro !== "") { ?>


        <!-- =============================================
             ERRO DA IA
        ============================================== -->

        <div class="ai-card">

            <div class="ai-erro">

                ❌

                <strong>
                    Não foi possível gerar a análise.
                </strong>

                <br><br>

                <?php echo escapar($erro); ?>

            </div>


            <p>

                Verifique se o serviço configurado em
                <strong>config/gemini.php</strong>
                está funcionando e tente novamente.

            </p>

        </div>


    <?php } elseif (is_array($analise)) { ?>


        <!-- =============================================
             RESUMO
        ============================================== -->

        <div class="ai-card">

            <h2 class="ai-section-title">

                <span>📊</span>

                Resumo do desempenho

            </h2>


            <div class="ai-resumo">

                <?php

                echo nl2br(
                    escapar(
                        $analise['resumo']
                        ?? 'Resumo não informado pela IA.'
                    )
                );

                ?>

            </div>

        </div>


        <!-- =============================================
             PONTOS FORTES / MELHORAR
        ============================================== -->

        <div class="ai-grid">


            <!-- PONTOS FORTES -->

            <div class="ai-card pontos-fortes">

                <h2 class="ai-section-title">

                    <span>✅</span>

                    Pontos fortes

                </h2>


                <ul class="ai-lista">

                    <?php

                    mostrarLista(
                        $analise['pontos_fortes']
                        ?? []
                    );

                    ?>

                </ul>

            </div>


            <!-- PONTOS A MELHORAR -->

            <div class="ai-card pontos-melhorar">

                <h2 class="ai-section-title">

                    <span>🎯</span>

                    Pontos a melhorar

                </h2>


                <ul class="ai-lista">

                    <?php

                    mostrarLista(
                        $analise['pontos_melhorar']
                        ?? []
                    );

                    ?>

                </ul>

            </div>


        </div>


        <!-- =============================================
             QUESTÕES QUE MERECEM ATENÇÃO
        ============================================== -->

        <div class="ai-card">

            <h2 class="ai-section-title">

                <span>⚠️</span>

                Questões que merecem atenção

            </h2>


            <?php

            $questoesAtencao =
                $analise['questoes_atencao']
                ?? [];

            ?>


            <?php if (
                !is_array($questoesAtencao)
                ||
                count($questoesAtencao) === 0
            ) { ?>


                <div class="ai-resumo">

                    A IA não identificou questões
                    específicas para destacar.

                </div>


            <?php } else { ?>


                <?php foreach (
                    $questoesAtencao
                    as $questao
                ) { ?>


                    <div class="questao-atencao">

                        <strong>

                            ⚠️ Questão
                            <?php

                            echo escapar(
                                $questao['numero']
                                ?? '?'
                            );

                            ?>

                        </strong>


                        <p>

                            <?php

                            echo nl2br(
                                escapar(
                                    $questao['motivo']
                                    ?? ''
                                )
                            );

                            ?>

                        </p>

                    </div>


                <?php } ?>


            <?php } ?>


        </div>


        <!-- =============================================
             RECOMENDAÇÕES
        ============================================== -->

        <div class="ai-card">

            <h2 class="ai-section-title">

                <span>📚</span>

                Recomendações de estudo

            </h2>


            <ul class="ai-lista">

                <?php

                mostrarLista(
                    $analise['recomendacoes']
                    ?? []
                );

                ?>

            </ul>

        </div>


        <!-- =============================================
             CONCLUSÃO
        ============================================== -->

        <div class="ai-card">

            <h2 class="ai-section-title">

                <span>💡</span>

                Conclusão

            </h2>


            <div class="ai-conclusao">

                <?php

                echo nl2br(
                    escapar(
                        $analise['conclusao']
                        ?? 'Conclusão não informada pela IA.'
                    )
                );

                ?>

            </div>

        </div>


    <?php } ?>


    <!-- =================================================
         AÇÕES
    ================================================== -->

    <div class="ai-acoes">

        <a
            href="resultados.php"
            class="btn-ai-voltar"
        >

            ← Voltar aos resultados

        </a>


        <a
            href="gabarito_aluno.php?id=<?php echo $resultado_id; ?>"
            class="btn-ai-voltar"
        >

            📋 Ver gabarito

        </a>


        <a
            href="analise_ia.php?id=<?php echo $resultado_id; ?>"
            class="btn-ai-nova"
        >

            ✦ Gerar análise novamente

        </a>

    </div>


</div>


</body>

</html>
