<?php
session_start();
include("../config/conexao.php");

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$professor_id = (int) $_SESSION['id'];
$erro = "";

/* =====================================================
   RESULTADOS SOMENTE DAS PROVAS DO PROFESSOR LOGADO
===================================================== */
$sql = "
    SELECT
        r.id AS resultado_id,
        u.nome AS aluno_nome,
        p.id AS prova_id,
        p.titulo,
        p.disciplina,
        r.nota,
        r.data_realizacao,
        COUNT(ra.id) AS total_respostas,
        COALESCE(SUM(CASE WHEN ra.correta = 1 THEN 1 ELSE 0 END), 0) AS acertos,
        COALESCE(SUM(CASE WHEN ra.correta = 0 THEN 1 ELSE 0 END), 0) AS erros
    FROM resultados r
    INNER JOIN usuarios u ON u.id = r.aluno_id
    INNER JOIN provas p ON p.id = r.prova_id
    LEFT JOIN respostas_alunos ra ON ra.resultado_id = r.id
    WHERE p.professor_id = ?
    GROUP BY
        r.id,
        u.nome,
        p.id,
        p.titulo,
        p.disciplina,
        r.nota,
        r.data_realizacao
    ORDER BY r.id DESC
";

$stmt = mysqli_prepare($conexao, $sql);
$lista = [];

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $professor_id);
    mysqli_stmt_execute($stmt);

    $res = mysqli_stmt_get_result($stmt);

    while ($linha = mysqli_fetch_assoc($res)) {
        $lista[] = $linha;
    }

    mysqli_stmt_close($stmt);
} else {
    $erro = mysqli_error($conexao);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Resultados dos Alunos</title>

<link rel="stylesheet" href="../assets/css/questsystem.css">

<style>
/* =====================================================
   AJUSTES ESPECÍFICOS DA PÁGINA
===================================================== */

.resultados-container{
    width:92%;
    max-width:1250px;
    margin:30px auto 60px;
}

.resultados-card{
    background:#fff;
    border-radius:18px;
    padding:24px;
    box-shadow:0 8px 30px rgba(0,0,0,.08);
    overflow:hidden;
}

.resultados-header{
    background:linear-gradient(135deg,#6d315d,#8c4d77);
    color:#fff;
    padding:28px 5%;
}

.resultados-header h1{
    margin:0 0 6px;
    font-size:28px;
}

.resultados-header p{
    margin:0;
    opacity:.92;
}

/* TABELA */

.tabela-resultados{
    width:100%;
    border-collapse:collapse;
}

.tabela-resultados th{
    background:#6d315d;
    color:#fff;
    text-align:left;
    padding:14px 12px;
    font-size:14px;
    white-space:nowrap;
}

.tabela-resultados td{
    padding:14px 12px;
    border-bottom:1px solid #e8e6ea;
    vertical-align:middle;
}

.tabela-resultados tbody tr{
    transition:.2s ease;
}

.tabela-resultados tbody tr:hover{
    background:#faf8fb;
}

/* BADGES */

.badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:45px;
    padding:6px 10px;
    border-radius:8px;
    font-weight:700;
    font-size:13px;
}

.badge-ok{
    background:#dff4e9;
    color:#167044;
}

.badge-erro{
    background:#fde4e5;
    color:#a12732;
}

/* AÇÕES */

.acoes-resultados{
    display:flex;
    flex-wrap:wrap;
    gap:7px;
    min-width:200px;
}

.btn-acao{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    text-decoration:none;
    padding:9px 13px;
    border-radius:9px;
    font-weight:700;
    font-size:13px;
    transition:.2s ease;
    white-space:nowrap;
}

.btn-gabarito{
    background:#6d315d;
    color:#fff;
}

.btn-gabarito:hover{
    background:#542346;
    transform:translateY(-1px);
}

.btn-ai{
    background:linear-gradient(135deg,#1f7a6b,#279889);
    color:#fff;
}

.btn-ai:hover{
    background:linear-gradient(135deg,#185f54,#218170);
    transform:translateY(-1px);
    box-shadow:0 5px 15px rgba(31,122,107,.20);
}

.btn-voltar{
    display:inline-flex;
    align-items:center;
    gap:7px;
    text-decoration:none;
    background:#495057;
    color:#fff;
    padding:10px 15px;
    border-radius:9px;
    font-weight:700;
    margin-top:20px;
}

.btn-voltar:hover{
    background:#343a40;
}

/* MENSAGENS */

.vazio{
    text-align:center;
    padding:55px 20px;
    color:#64748b;
}

.vazio h2{
    margin-bottom:8px;
    color:#334155;
}

.erro{
    background:#fde4e5;
    color:#842029;
    padding:15px 18px;
    border-radius:12px;
    margin-bottom:20px;
    border:1px solid #f5c2c7;
}

/* NOTA */

.nota{
    font-weight:800;
    color:#6d315d;
    font-size:15px;
}

/* RESPONSIVO */

@media(max-width:950px){

    .resultados-card{
        padding:16px;
    }

    .tabela-resultados{
        min-width:900px;
    }

    .tabela-scroll{
        overflow-x:auto;
        width:100%;
    }
}

@media(max-width:600px){

    .resultados-header{
        padding:22px 20px;
    }

    .resultados-header h1{
        font-size:23px;
    }

    .resultados-container{
        width:94%;
    }
}
</style>

</head>

<body class="qs-app">

<?php include_once __DIR__ . "/../includes/sidebar.php"; ?>

<!-- =====================================================
     CABEÇALHO
===================================================== -->

<div class="resultados-header">

    <h1>📊 Resultados dos Alunos</h1>

    <p>
        Veja o desempenho dos alunos, consulte o gabarito
        e utilize a Inteligência Artificial para analisar cada resultado.
    </p>

</div>

<!-- =====================================================
     CONTEÚDO
===================================================== -->

<div class="resultados-container">

<?php if ($erro !== "") { ?>

    <div class="erro">
        ❌
        <?php
        echo htmlspecialchars(
            $erro,
            ENT_QUOTES,
            'UTF-8'
        );
        ?>
    </div>

<?php } ?>

<div class="resultados-card">

<?php if (count($lista) === 0) { ?>

    <div class="vazio">

        <h2>Nenhum resultado encontrado</h2>

        <p>
            Quando um aluno realizar uma das suas provas,
            o resultado aparecerá aqui.
        </p>

    </div>

<?php } else { ?>

    <div class="tabela-scroll">

        <table class="tabela-resultados">

            <thead>

                <tr>

                    <th>Aluno</th>

                    <th>Prova</th>

                    <th>Disciplina</th>

                    <th>Acertos</th>

                    <th>Erros</th>

                    <th>Nota</th>

                    <th>Data</th>

                    <th>Ações</th>

                </tr>

            </thead>

            <tbody>

            <?php foreach ($lista as $linha) { ?>

                <tr>

                    <!-- ALUNO -->

                    <td>

                        <?php
                        echo htmlspecialchars(
                            $linha['aluno_nome'],
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>

                    </td>

                    <!-- PROVA -->

                    <td>

                        <?php
                        echo htmlspecialchars(
                            $linha['titulo'],
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>

                    </td>

                    <!-- DISCIPLINA -->

                    <td>

                        <?php
                        echo htmlspecialchars(
                            $linha['disciplina'],
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>

                    </td>

                    <!-- ACERTOS -->

                    <td>

                        <span class="badge badge-ok">

                            ✓

                            <?php
                            echo (int) $linha['acertos'];
                            ?>

                        </span>

                    </td>

                    <!-- ERROS -->

                    <td>

                        <span class="badge badge-erro">

                            ✗

                            <?php
                            echo (int) $linha['erros'];
                            ?>

                        </span>

                    </td>

                    <!-- NOTA -->

                    <td>

                        <span class="nota">

                            <?php
                            echo number_format(
                                (float) $linha['nota'],
                                2,
                                ',',
                                '.'
                            );
                            ?>

                        </span>

                    </td>

                    <!-- DATA -->

                    <td>

                        <?php

                        if (!empty($linha['data_realizacao'])) {

                            echo date(
                                'd/m/Y H:i',
                                strtotime(
                                    $linha['data_realizacao']
                                )
                            );

                        } else {

                            echo '-';

                        }

                        ?>

                    </td>

                    <!-- AÇÕES -->

                    <td>

                        <div class="acoes-resultados">

                            <!-- VER GABARITO -->

                            <a
                                class="btn-acao btn-gabarito"
                                href="gabarito_aluno.php?id=<?php echo (int)$linha['resultado_id']; ?>"
                            >
                                📋 Ver gabarito
                            </a>

                            <!-- ANÁLISE COM IA -->

                            <a
                                class="btn-acao btn-ai"
                                href="analise_ia.php?id=<?php echo (int)$linha['resultado_id']; ?>"
                            >
                                ✦ Analisar com IA
                            </a>

                        </div>

                    </td>

                </tr>

            <?php } ?>

            </tbody>

        </table>

    </div>

<?php } ?>

</div>

<!-- VOLTAR -->

<a
    class="btn-voltar"
    href="dashboard.php"
>
    ← Voltar
</a>

</div>

</body>
</html>
