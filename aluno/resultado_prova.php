<?php

session_start();

include("../config/conexao.php");

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$aluno_id = (int) $_SESSION['id'];

if (!isset($_GET['id'])) {
    header("Location: historico.php");
    exit();
}

$resultado_id =
    (int) $_GET['id'];


/* =====================================================
   BUSCAR RESULTADO
===================================================== */

$sql_resultado = "
    SELECT
        r.id,
        r.nota,
        r.data_realizacao,
        p.titulo,
        p.disciplina

    FROM resultados r

    INNER JOIN provas p
        ON p.id = r.prova_id

    WHERE r.id = $resultado_id

    AND r.aluno_id = $aluno_id

    LIMIT 1
";

$resultado =
    mysqli_query(
        $conexao,
        $sql_resultado
    );


if (
    !$resultado ||
    mysqli_num_rows($resultado) === 0
) {

    die(
        "Resultado não encontrado."
    );

}


$dados =
    mysqli_fetch_assoc(
        $resultado
    );


/* =====================================================
   BUSCAR RESPOSTAS
===================================================== */

$sql_respostas = "
    SELECT
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

    INNER JOIN questoes q
        ON q.id = ra.questao_id

    WHERE ra.resultado_id = $resultado_id

    ORDER BY ra.id ASC
";

$respostas =
    mysqli_query(
        $conexao,
        $sql_respostas
    );


$lista_respostas = [];

$acertos = 0;

$erros = 0;


while (
    $r = mysqli_fetch_assoc(
        $respostas
    )
) {

    $lista_respostas[] = $r;


    if (
        (int)$r['correta'] === 1
    ) {

        $acertos++;

    } else {

        $erros++;

    }

}


$total =
    count(
        $lista_respostas
    );

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
Resultado da Prova
</title>


<style>

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

    background:
        #f4f7fc;

    color:
        #172033;

}


.header {

    background:
        linear-gradient(
            135deg,
            #6F3D62,
            #8F527B
        );

    color: white;

    padding: 30px 0;

}


.container {

    width: 92%;

    max-width: 1100px;

    margin: 30px auto 60px;

}


.header-inner {

    width: 92%;

    max-width: 1100px;

    margin: auto;

}


.header h1 {

    font-size: 30px;

    margin-bottom: 5px;

}


.header p {

    opacity: .9;

}


.card {

    background: white;

    border-radius: 15px;

    padding: 25px;

    margin-bottom: 20px;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.08);

}


.info-prova h2 {

    color: #6F3D62;

    margin-bottom: 10px;

}


.info-prova p {

    margin-top: 5px;

    color: #64748b;

}


/* ===============================
   ESTATÍSTICAS
================================ */

.estatisticas {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 15px;

}


.estatistica {

    padding: 20px;

    border-radius: 12px;

    text-align: center;

    background: #f8fafc;

}


.estatistica strong {

    display: block;

    font-size: 30px;

    color: #6F3D62;

    margin-bottom: 5px;

}


.estatistica span {

    color: #64748b;

    font-weight: 600;

}


.acerto strong {

    color: #198754;

}


.erro strong {

    color: #D96C55;

}


.nota strong {

    color: #6938c5;

}


/* ===============================
   GABARITO
================================ */

.gabarito-titulo {

    display: flex;

    justify-content:
        space-between;

    align-items: center;

    margin-bottom: 20px;

}


.gabarito-titulo h2 {

    color: #6F3D62;

}


.questao {

    border: 1px solid #dce3ed;

    border-radius: 12px;

    padding: 20px;

    margin-bottom: 15px;

}


.questao.correta {

    border-left:
        5px solid #198754;

    background:
        #f0fff7;

}


.questao.incorreta {

    border-left:
        5px solid #D96C55;

    background:
        #fff5f5;

}


.questao h3 {

    margin-bottom: 12px;

}


.pergunta {

    line-height: 1.6;

    margin-bottom: 15px;

}


.resposta {

    padding: 10px 12px;

    border-radius: 8px;

    margin-top: 8px;

}


.resposta-aluno {

    background: #eef2f7;

}


.resposta-correta {

    background: #d1e7dd;

    color: #0f5132;

}


.resposta-errada {

    background: #f8d7da;

    color: #842029;

}


.status {

    display: inline-block;

    padding: 6px 10px;

    border-radius: 7px;

    font-weight: 700;

    margin-bottom: 12px;

}


.status-certo {

    background: #d1e7dd;

    color: #0f5132;

}


.status-errado {

    background: #f8d7da;

    color: #842029;

}


.btn {

    display: inline-block;

    padding: 11px 18px;

    border-radius: 9px;

    text-decoration: none;

    font-weight: 700;

}


.btn-voltar {

    background: #495057;

    color: white;

}


@media(max-width: 750px) {

    .estatisticas {

        grid-template-columns:
            1fr 1fr;

    }

}

</style>

    <link rel="stylesheet" href="../assets/css/questsystem.css">
</head>


<body class="qs-app">
<?php include_once __DIR__ . "/../includes/sidebar.php"; ?>


<div class="header">

    <div class="header-inner">

        <h1>
            📊 Resultado da Prova
        </h1>

        <p>
            Confira seu desempenho e o gabarito.
        </p>

    </div>

</div>


<div class="container">


<!-- =====================================================
     INFORMAÇÕES
===================================================== -->

<div class="card info-prova">

    <h2>

        <?php
        echo htmlspecialchars(
            $dados['titulo']
        );
        ?>

    </h2>

    <p>

        📚 Disciplina:
        <strong>
            <?php
            echo htmlspecialchars(
                $dados['disciplina']
            );
            ?>
        </strong>

    </p>

    <p>

        📅 Realizada em:
        <?php
        echo date(
            "d/m/Y H:i",
            strtotime(
                $dados['data_realizacao']
            )
        );
        ?>

    </p>

</div>


<!-- =====================================================
     ESTATÍSTICAS
===================================================== -->

<div class="card">

<div class="estatisticas">


<div class="estatistica nota">

    <strong>

        <?php
        echo number_format(
            $dados['nota'],
            2,
            ',',
            '.'
        );
        ?>

    </strong>

    <span>
        Nota
    </span>

</div>


<div class="estatistica">

    <strong>
        <?php echo $total; ?>
    </strong>

    <span>
        Questões
    </span>

</div>


<div class="estatistica acerto">

    <strong>
        <?php echo $acertos; ?>
    </strong>

    <span>
        Acertos
    </span>

</div>


<div class="estatistica erro">

    <strong>
        <?php echo $erros; ?>
    </strong>

    <span>
        Erros
    </span>

</div>


</div>

</div>


<!-- =====================================================
     GABARITO
===================================================== -->

<div class="card">

<div class="gabarito-titulo">

    <h2>
        📋 Gabarito da Prova
    </h2>

</div>


<?php foreach (
    $lista_respostas
    as $indice => $r
) { ?>


<div class="questao <?php

if (
    (int)$r['correta'] === 1
) {

    echo "correta";

} else {

    echo "incorreta";

}

?>">


<?php if (
    (int)$r['correta'] === 1
) { ?>

<span class="status status-certo">

    ✅ Você acertou

</span>

<?php } else { ?>

<span class="status status-errado">

    ❌ Você errou

</span>

<?php } ?>


<h3>

    Questão
    <?php echo $indice + 1; ?>

</h3>


<div class="pergunta">

<?php

echo nl2br(
    htmlspecialchars(
        $r['pergunta']
    )
);

?>

</div>


<?php

$alternativas = [

    'A' => $r['alternativa_a'],

    'B' => $r['alternativa_b'],

    'C' => $r['alternativa_c'],

    'D' => $r['alternativa_d']

];

?>


<?php

if (
    isset(
        $alternativas[
            $r['resposta_aluno']
        ]
    )
) {

?>

<div class="resposta resposta-aluno">

    <strong>
        Sua resposta:
    </strong>

    <?php
    echo htmlspecialchars(
        $r['resposta_aluno']
    );
    ?>

    -
    <?php
    echo htmlspecialchars(
        $alternativas[
            $r['resposta_aluno']
        ]
    );
    ?>

</div>

<?php

} else {

?>

<div class="resposta resposta-errada">

    <strong>
        Sua resposta:
    </strong>

    Não respondida

</div>

<?php

}

?>


<div class="resposta resposta-correta">

    <strong>
        Resposta correta:
    </strong>

    <?php
    echo htmlspecialchars(
        $r['resposta_correta']
    );
    ?>

    -
    <?php

    if (
        isset(
            $alternativas[
                $r['resposta_correta']
            ]
        )
    ) {

        echo htmlspecialchars(
            $alternativas[
                $r['resposta_correta']
            ]
        );

    }

    ?>

</div>


</div>


<?php } ?>


</div>


<a
    href="historico.php"
    class="btn btn-voltar"
>

    ← Voltar para o Histórico

</a>


</div>


</body>

</html>