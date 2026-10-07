```php
<?php

session_start();

include("../config/conexao.php");


/* =========================================================
   VERIFICAR LOGIN
========================================================= */

if (!isset($_SESSION['id'])) {

    header("Location: ../auth/login.php");

    exit();

}


/* =========================================================
   DADOS DO PROFESSOR
========================================================= */

$professor_id = (int) $_SESSION['id'];

$nome_professor = $_SESSION['nome'] ?? 'Professor';


/* =========================================================
   ESTATÍSTICAS

   Mantemos a lógica original do dashboard.
========================================================= */


/* ALUNOS */

$totalAlunos = mysqli_fetch_assoc(

    mysqli_query(

        $conexao,

        "SELECT COUNT(*) AS total
         FROM usuarios
         WHERE tipo='aluno'"

    )

);

$total_alunos = (int)($totalAlunos['total'] ?? 0);


/* PROVAS */

$totalProvas = mysqli_fetch_assoc(

    mysqli_query(

        $conexao,

        "SELECT COUNT(*) AS total
         FROM provas"

    )

);

$total_provas = (int)($totalProvas['total'] ?? 0);


/* QUESTÕES */

$totalQuestoes = mysqli_fetch_assoc(

    mysqli_query(

        $conexao,

        "SELECT COUNT(*) AS total
         FROM questoes"

    )

);

$total_questoes = (int)($totalQuestoes['total'] ?? 0);


/* MÉDIA */

$mediaNotas = mysqli_fetch_assoc(

    mysqli_query(

        $conexao,

        "SELECT AVG(nota) AS media
         FROM resultados"

    )

);

$media = $mediaNotas['media'] ?? 0;

$media = (float)$media;


/* =========================================================
   PERCENTUAL DA MÉDIA

   A nota do sistema é de 0 a 10.
========================================================= */

$percentual_media = ($media / 10) * 100;

if ($percentual_media < 0) {

    $percentual_media = 0;

}

if ($percentual_media > 100) {

    $percentual_media = 100;

}


/* =========================================================
   TEXTO DA MÉDIA
========================================================= */

if ($media >= 9) {

    $texto_desempenho = "Excelente desempenho";

} elseif ($media >= 7) {

    $texto_desempenho = "Bom desempenho";

} elseif ($media >= 5) {

    $texto_desempenho = "Desempenho regular";

} elseif ($media > 0) {

    $texto_desempenho = "Atenção ao desempenho";

} else {

    $texto_desempenho = "Ainda sem resultados";

}


/* =========================================================
   DATA ATUAL
========================================================= */

$data_atual = date(
    'd/m/Y'
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
    Dashboard do Professor — QuestSystem
</title>


<link
    rel="stylesheet"
    href="../assets/css/questsystem.css"
>


<style>

/* =========================================================
   DASHBOARD
========================================================= */

.dashboard{

    width:100%;

    min-height:100vh;

}


/* =========================================================
   CABEÇALHO
========================================================= */

.dashboard-hero{

    position:relative;

    overflow:hidden;

    padding:
        30px 36px;

    color:#fff;

    background:

        radial-gradient(
            circle at 90% 20%,
            rgba(255,255,255,.12),
            transparent 25%
        ),

        radial-gradient(
            circle at 65% 100%,
            rgba(49,145,176,.28),
            transparent 27%
        ),

        linear-gradient(
            135deg,
            #101a39 0%,
            #263f79 53%,
            #287d99 100%
        );

    box-shadow:
        0 12px 35px
        rgba(18,39,76,.15);

}


.dashboard-hero::after{

    content:"";

    position:absolute;

    width:260px;

    height:260px;

    right:-95px;

    top:-125px;

    border-radius:50%;

    border:
        1px solid
        rgba(255,255,255,.10);

    box-shadow:
        0 0 0 24px
        rgba(255,255,255,.025),
        0 0 0 48px
        rgba(255,255,255,.018);

}


.dashboard-hero-content{

    position:relative;

    z-index:2;

    display:flex;

    align-items:flex-start;

    justify-content:space-between;

    gap:30px;

}


.dashboard-kicker{

    display:inline-flex;

    align-items:center;

    gap:7px;

    padding:
        7px 10px;

    border:
        1px solid
        rgba(255,255,255,.13);

    border-radius:
        999px;

    color:
        rgba(255,255,255,.86);

    background:
        rgba(255,255,255,.08);

    font-size:10px;

    font-weight:900;

    letter-spacing:.8px;

    text-transform:uppercase;

}


.dashboard-hero h1{

    margin-top:13px;

    color:#fff;

    font-size:31px;

    line-height:1.12;

    font-weight:900;

    letter-spacing:-.7px;

}


.dashboard-hero h1 strong{

    color:#8fd8e9;

}


.dashboard-hero p{

    max-width:690px;

    margin-top:8px;

    color:
        rgba(255,255,255,.73);

    font-size:13px;

    line-height:1.65;

}


.dashboard-date{

    min-width:135px;

    padding:13px 15px;

    border:
        1px solid
        rgba(255,255,255,.12);

    border-radius:13px;

    text-align:right;

    background:
        rgba(255,255,255,.07);

    backdrop-filter:
        blur(10px);

}


.dashboard-date span{

    display:block;

    color:
        rgba(255,255,255,.52);

    font-size:9px;

    font-weight:800;

    text-transform:uppercase;

}


.dashboard-date strong{

    display:block;

    margin-top:3px;

    color:#fff;

    font-size:12px;

}


/* =========================================================
   ÁREA PRINCIPAL
========================================================= */

.dashboard-content{

    padding:
        25px 34px 45px;

}


/* =========================================================
   TÍTULO DE SEÇÃO
========================================================= */

.dashboard-section-head{

    display:flex;

    align-items:flex-end;

    justify-content:space-between;

    gap:20px;

    margin-bottom:13px;

}


.dashboard-section-head h2{

    color:#18233e;

    font-size:18px;

    font-weight:900;

    letter-spacing:-.2px;

}


.dashboard-section-head p{

    margin-top:3px;

    color:#8490a3;

    font-size:11px;

}


/* =========================================================
   ESTATÍSTICAS
========================================================= */

.dashboard-stats{

    display:grid;

    grid-template-columns:
        repeat(4,minmax(0,1fr));

    gap:14px;

    margin-bottom:25px;

}


.dashboard-stat{

    position:relative;

    overflow:hidden;

    min-height:127px;

    padding:19px;

    border-radius:16px;

    color:#fff;

    box-shadow:
        0 12px 28px
        rgba(27,49,87,.12);

}


.dashboard-stat::after{

    content:"";

    position:absolute;

    width:120px;

    height:120px;

    right:-43px;

    bottom:-55px;

    border-radius:50%;

    background:
        rgba(255,255,255,.11);

}


.dashboard-stat:nth-child(1){

    background:
        linear-gradient(
            135deg,
            #3158ac,
            #477fb2
        );

}


.dashboard-stat:nth-child(2){

    background:
        linear-gradient(
            135deg,
            #21816e,
            #3ca38e
        );

}


.dashboard-stat:nth-child(3){

    background:
        linear-gradient(
            135deg,
            #6a4ba4,
            #8b63c0
        );

}


.dashboard-stat:nth-child(4){

    background:
        linear-gradient(
            135deg,
            #d0763d,
            #e59a53
        );

}


.dashboard-stat-top{

    position:relative;

    z-index:2;

    display:flex;

    align-items:center;

    justify-content:space-between;

}


.dashboard-stat-icon{

    width:42px;

    height:42px;

    display:grid;

    place-items:center;

    border-radius:12px;

    background:
        rgba(255,255,255,.12);

    font-size:18px;

}


.dashboard-stat-tag{

    padding:
        5px 8px;

    border-radius:
        999px;

    color:
        rgba(255,255,255,.76);

    background:
        rgba(255,255,255,.09);

    font-size:9px;

    font-weight:800;

}


.dashboard-stat-value{

    position:relative;

    z-index:2;

    margin-top:15px;

    font-size:31px;

    line-height:1;

    font-weight:900;

}


.dashboard-stat-label{

    position:relative;

    z-index:2;

    margin-top:5px;

    color:
        rgba(255,255,255,.81);

    font-size:11px;

    font-weight:700;

}


/* =========================================================
   GRID PRINCIPAL
========================================================= */

.dashboard-grid{

    display:grid;

    grid-template-columns:
        minmax(0,1.55fr)
        minmax(290px,.75fr);

    gap:17px;

    margin-bottom:17px;

}


/* =========================================================
   CARD
========================================================= */

.dashboard-card{

    padding:22px;

    border:
        1px solid
        rgba(34,57,91,.08);

    border-radius:
        17px;

    background:
        rgba(255,255,255,.96);

    box-shadow:
        0 10px 30px
        rgba(25,48,84,.07);

}


.dashboard-card-header{

    display:flex;

    align-items:flex-start;

    justify-content:space-between;

    gap:15px;

    margin-bottom:18px;

}


.dashboard-card-title{

    display:flex;

    align-items:center;

    gap:10px;

}


.dashboard-card-icon{

    width:36px;

    height:36px;

    display:grid;

    place-items:center;

    border-radius:10px;

    background:#edf2fb;

    color:#3158ac;

}


.dashboard-card-title h3{

    color:#1c2945;

    font-size:15px;

    font-weight:900;

}


.dashboard-card-title p{

    margin-top:2px;

    color:#8994a6;

    font-size:10px;

}


/* =========================================================
   AÇÕES RÁPIDAS
========================================================= */

.quick-actions{

    display:grid;

    grid-template-columns:
        repeat(2,minmax(0,1fr));

    gap:11px;

}


.quick-action{

    position:relative;

    display:flex;

    align-items:center;

    gap:12px;

    min-height:76px;

    padding:13px;

    border:
        1px solid
        #e2e8f1;

    border-radius:13px;

    color:#26334d;

    text-decoration:none;

    background:#fafcfe;

    transition:
        .2s ease;

}


.quick-action:hover{

    transform:
        translateY(-2px);

    border-color:#cbd7e7;

    background:#fff;

    box-shadow:
        0 10px 24px
        rgba(36,57,92,.09);

}


.quick-action-icon{

    width:42px;

    height:42px;

    flex:0 0 42px;

    display:grid;

    place-items:center;

    border-radius:11px;

    font-size:17px;

}


.quick-action-content{

    min-width:0;

}


.quick-action strong{

    display:block;

    color:#24324d;

    font-size:12px;

    font-weight:900;

}


.quick-action span{

    display:block;

    margin-top:2px;

    color:#8994a6;

    font-size:10px;

    line-height:1.4;

}


.quick-arrow{

    margin-left:auto;

    color:#a1aaba;

    font-size:14px;

}


/* CORES */

.quick-action:nth-child(1)
.quick-action-icon{

    color:#3158ac;

    background:#edf2fb;

}


.quick-action:nth-child(2)
.quick-action-icon{

    color:#21816e;

    background:#e8f6f1;

}


.quick-action:nth-child(3)
.quick-action-icon{

    color:#6b4ba7;

    background:#f0eafb;

}


.quick-action:nth-child(4)
.quick-action-icon{

    color:#d07138;

    background:#fff0e4;

}


/* =========================================================
   RESUMO DE DESEMPENHO
========================================================= */

.performance-panel{

    min-height:100%;

}


.performance-content{

    display:flex;

    align-items:center;

    gap:21px;

}


.performance-ring{

    position:relative;

    width:126px;

    height:126px;

    flex:0 0 126px;

    display:grid;

    place-items:center;

    border-radius:50%;

    background:

        conic-gradient(
            #3158ac
            <?php
            echo number_format(
                $percentual_media,
                2,
                '.',
                ''
            );
            ?>%,

            #e8edf5
            <?php
            echo number_format(
                $percentual_media,
                2,
                '.',
                ''
            ); ?>%
        );

}


.performance-ring::before{

    content:"";

    position:absolute;

    inset:11px;

    border-radius:50%;

    background:#fff;

}


.performance-ring-content{

    position:relative;

    z-index:2;

    text-align:center;

}


.performance-ring-content strong{

    display:block;

    color:#1c2945;

    font-size:25px;

    line-height:1;

}


.performance-ring-content span{

    color:#8792a5;

    font-size:9px;

    font-weight:800;

}


.performance-details{

    flex:1;

}


.performance-details h4{

    color:#24324d;

    font-size:14px;

    font-weight:900;

}


.performance-details p{

    margin-top:5px;

    color:#8692a6;

    font-size:11px;

    line-height:1.55;

}


.performance-status{

    display:inline-flex;

    align-items:center;

    gap:5px;

    margin-top:11px;

    padding:6px 9px;

    border-radius:999px;

    color:#247458;

    background:#e8f7f0;

    font-size:9px;

    font-weight:900;

}


.performance-empty{

    color:#8692a6;

}


/* =========================================================
   INDICADORES DO SISTEMA
========================================================= */

.system-flow{

    display:grid;

    grid-template-columns:
        repeat(3,minmax(0,1fr));

    gap:11px;

}


.system-step{

    padding:16px;

    border:
        1px solid
        #e4e9f1;

    border-radius:13px;

    background:#fafcfe;

}


.system-step-number{

    width:28px;

    height:28px;

    display:grid;

    place-items:center;

    border-radius:9px;

    color:#fff;

    background:
        linear-gradient(
            135deg,
            #3158ac,
            #4180a9
        );

    font-size:10px;

    font-weight:900;

}


.system-step h4{

    margin-top:11px;

    color:#26334d;

    font-size:12px;

    font-weight:900;

}


.system-step p{

    margin-top:4px;

    color:#8894a7;

    font-size:10px;

    line-height:1.5;

}


/* =========================================================
   RECURSOS
========================================================= */

.resource-list{

    display:grid;

    grid-template-columns:
        1fr 1fr;

    gap:9px;

}


.resource-item{

    display:flex;

    align-items:center;

    gap:9px;

    min-height:45px;

    padding:9px 11px;

    border-radius:10px;

    background:#f7f9fc;

}


.resource-check{

    width:25px;

    height:25px;

    flex:0 0 25px;

    display:grid;

    place-items:center;

    border-radius:8px;

    color:#208061;

    background:#e4f5ee;

    font-size:11px;

}


.resource-item span{

    color:#536177;

    font-size:10px;

    font-weight:700;

}


/* =========================================================
   RODAPÉ DO DASHBOARD
========================================================= */

.dashboard-footer{

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:15px;

    margin-top:5px;

    padding-top:18px;

    border-top:
        1px solid
        #e4e9f0;

    color:#98a2b3;

    font-size:9px;

}


.dashboard-footer strong{

    color:#68758b;

}


/* =========================================================
   RESPONSIVO
========================================================= */

@media(max-width:1050px){

    .dashboard-stats{

        grid-template-columns:
            repeat(2,minmax(0,1fr));

    }


    .dashboard-grid{

        grid-template-columns:
            1fr;

    }

}


@media(max-width:760px){

    .dashboard-hero{

        padding:
            24px 22px;

    }


    .dashboard-hero-content{

        flex-direction:column;

    }


    .dashboard-date{

        text-align:left;

    }


    .dashboard-content{

        padding:
            20px 22px 35px;

    }


    .quick-actions{

        grid-template-columns:
            1fr;

    }


    .system-flow{

        grid-template-columns:
            1fr;

    }


    .resource-list{

        grid-template-columns:
            1fr;

    }

}


@media(max-width:560px){

    .dashboard-stats{

        grid-template-columns:
            1fr;

    }


    .dashboard-hero h1{

        font-size:25px;

    }


    .dashboard-card{

        padding:18px;

    }


    .performance-content{

        flex-direction:column;

        align-items:flex-start;

    }


    .performance-ring{

        align-self:center;

    }


    .dashboard-footer{

        flex-direction:column;

        align-items:flex-start;

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


<div class="dashboard">


    <!-- =====================================================
         CABEÇALHO
    ====================================================== -->

    <header class="dashboard-hero">

        <div class="dashboard-hero-content">


            <div>

                <span class="dashboard-kicker">

                    ✦ Área do professor

                </span>


                <h1>

                    Olá,

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $nome_professor,
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>
                    </strong>

                    👋

                </h1>


                <p>

                    Gerencie suas questões, monte avaliações
                    e acompanhe o desempenho dos alunos em
                    um único ambiente.

                </p>

            </div>


            <div class="dashboard-date">

                <span>
                    Acesso em
                </span>

                <strong>
                    <?php
                    echo $data_atual;
                    ?>
                </strong>

            </div>


        </div>

    </header>


    <!-- =====================================================
         CONTEÚDO
    ====================================================== -->

    <main class="dashboard-content">


        <!-- =================================================
             ESTATÍSTICAS
        ================================================== -->

        <div class="dashboard-section-head">

            <div>

                <h2>
                    Visão geral
                </h2>

                <p>
                    Acompanhe os principais números do sistema.
                </p>

            </div>

        </div>


        <section class="dashboard-stats">


            <!-- QUESTÕES -->

            <div class="dashboard-stat">

                <div class="dashboard-stat-top">

                    <div class="dashboard-stat-icon">
                        📚
                    </div>

                    <span class="dashboard-stat-tag">
                        Banco
                    </span>

                </div>


                <div class="dashboard-stat-value">

                    <?php
                    echo $total_questoes;
                    ?>

                </div>


                <div class="dashboard-stat-label">

                    Questões cadastradas

                </div>

            </div>


            <!-- PROVAS -->

            <div class="dashboard-stat">

                <div class="dashboard-stat-top">

                    <div class="dashboard-stat-icon">
                        📝
                    </div>

                    <span class="dashboard-stat-tag">
                        Avaliações
                    </span>

                </div>


                <div class="dashboard-stat-value">

                    <?php
                    echo $total_provas;
                    ?>

                </div>


                <div class="dashboard-stat-label">

                    Provas criadas

                </div>

            </div>


            <!-- ALUNOS -->

            <div class="dashboard-stat">

                <div class="dashboard-stat-top">

                    <div class="dashboard-stat-icon">
                        👥
                    </div>

                    <span class="dashboard-stat-tag">
                        Usuários
                    </span>

                </div>


                <div class="dashboard-stat-value">

                    <?php
                    echo $total_alunos;
                    ?>

                </div>


                <div class="dashboard-stat-label">

                    Alunos cadastrados

                </div>

            </div>


            <!-- MÉDIA -->

            <div class="dashboard-stat">

                <div class="dashboard-stat-top">

                    <div class="dashboard-stat-icon">
                        📈
                    </div>

                    <span class="dashboard-stat-tag">
                        Desempenho
                    </span>

                </div>


                <div class="dashboard-stat-value">

                    <?php

                    echo number_format(
                        $media,
                        1,
                        ',',
                        '.'
                    );

                    ?>

                </div>


                <div class="dashboard-stat-label">

                    Média geral

                </div>

            </div>


        </section>


        <!-- =================================================
             GRID PRINCIPAL
        ================================================== -->

        <section class="dashboard-grid">


            <!-- =============================================
                 AÇÕES RÁPIDAS
            ============================================== -->

            <div class="dashboard-card">


                <div class="dashboard-card-header">

                    <div class="dashboard-card-title">

                        <div class="dashboard-card-icon">
                            ⚡
                        </div>

                        <div>

                            <h3>
                                Acesso rápido
                            </h3>

                            <p>
                                Comece uma tarefa em poucos cliques.
                            </p>

                        </div>

                    </div>

                </div>


                <div class="quick-actions">


                    <!-- BANCO -->

                    <a
                        href="questoes.php"
                        class="quick-action"
                    >

                        <div class="quick-action-icon">
                            📚
                        </div>

                        <div class="quick-action-content">

                            <strong>
                                Banco de Questões
                            </strong>

                            <span>
                                Cadastre, pesquise e organize questões.
                            </span>

                        </div>

                        <div class="quick-arrow">
                            →
                        </div>

                    </a>


                    <!-- PROVA -->

                    <a
                        href="criar_prova.php"
                        class="quick-action"
                    >

                        <div class="quick-action-icon">
                            📝
                        </div>

                        <div class="quick-action-content">

                            <strong>
                                Criar Prova
                            </strong>

                            <span>
                                Monte uma avaliação com suas questões.
                            </span>

                        </div>

                        <div class="quick-arrow">
                            →
                        </div>

                    </a>


                    <!-- IA -->

                    <a
                        href="criar_prova_ia.php"
                        class="quick-action"
                    >

                        <div class="quick-action-icon">
                            ✦
                        </div>

                        <div class="quick-action-content">

                            <strong>
                                Criar Prova com IA
                            </strong>

                            <span>
                                Gere uma avaliação usando inteligência artificial.
                            </span>

                        </div>

                        <div class="quick-arrow">
                            →
                        </div>

                    </a>


                    <!-- RESULTADOS -->

                    <a
                        href="resultados.php"
                        class="quick-action"
                    >

                        <div class="quick-action-icon">
                            📊
                        </div>

                        <div class="quick-action-content">

                            <strong>
                                Resultados
                            </strong>

                            <span>
                                Consulte o desempenho dos alunos.
                            </span>

                        </div>

                        <div class="quick-arrow">
                            →
                        </div>

                    </a>


                </div>


            </div>


            <!-- =============================================
                 DESEMPENHO
            ============================================== -->

            <div class="dashboard-card performance-panel">


                <div class="dashboard-card-header">

                    <div class="dashboard-card-title">

                        <div class="dashboard-card-icon">
                            📈
                        </div>

                        <div>

                            <h3>
                                Desempenho geral
                            </h3>

                            <p>
                                Média registrada nas avaliações.
                            </p>

                        </div>

                    </div>

                </div>


                <div class="performance-content">


                    <!-- CÍRCULO -->

                    <div
                        class="performance-ring"
                        aria-label="Média geral"
                    >

                        <div class="performance-ring-content">

                            <strong>

                                <?php

                                echo number_format(
                                    $media,
                                    1,
                                    ',',
                                    '.'
                                );

                                ?>

                            </strong>

                            <span>
                                de 10
                            </span>

                        </div>

                    </div>


                    <!-- TEXTO -->

                    <div class="performance-details">

                        <h4>

                            <?php
                            echo $texto_desempenho;
                            ?>

                        </h4>


                        <?php if ($media > 0) { ?>

                            <p>

                                A média atual do sistema é de

                                <strong>
                                    <?php
                                    echo number_format(
                                        $media,
                                        1,
                                        ',',
                                        '.'
                                    );
                                    ?>
                                </strong>

                                pontos.

                            </p>


                            <span class="performance-status">

                                ✓ Dados disponíveis

                            </span>

                        <?php } else { ?>

                            <p class="performance-empty">

                                Ainda não existem resultados
                                suficientes para apresentar
                                uma média.

                            </p>

                            <span class="performance-status">

                                • Aguardando avaliações

                            </span>

                        <?php } ?>


                    </div>

                </div>

            </div>


        </section>


        <!-- =================================================
             SEGUNDA LINHA
        ================================================== -->

        <section class="dashboard-grid">


            <!-- =============================================
                 FLUXO
            ============================================== -->

            <div class="dashboard-card">


                <div class="dashboard-card-header">

                    <div class="dashboard-card-title">

                        <div class="dashboard-card-icon">
                            🚀
                        </div>

                        <div>

                            <h3>
                                Fluxo de trabalho
                            </h3>

                            <p>
                                Organize sua rotina dentro do sistema.
                            </p>

                        </div>

                    </div>

                </div>


                <div class="system-flow">


                    <div class="system-step">

                        <div class="system-step-number">
                            01
                        </div>

                        <h4>
                            Cadastre questões
                        </h4>

                        <p>
                            Crie e organize seu banco por disciplina
                            e assunto.
                        </p>

                    </div>


                    <div class="system-step">

                        <div class="system-step-number">
                            02
                        </div>

                        <h4>
                            Monte a avaliação
                        </h4>

                        <p>
                            Escolha suas questões ou utilize
                            a geração automática com IA.
                        </p>

                    </div>


                    <div class="system-step">

                        <div class="system-step-number">
                            03
                        </div>

                        <h4>
                            Acompanhe os resultados
                        </h4>

                        <p>
                            Consulte respostas, notas,
                            gabaritos e análises.
                        </p>

                    </div>


                </div>

            </div>


            <!-- =============================================
                 RECURSOS
            ============================================== -->

            <div class="dashboard-card">


                <div class="dashboard-card-header">

                    <div class="dashboard-card-title">

                        <div class="dashboard-card-icon">
                            ✓
                        </div>

                        <div>

                            <h3>
                                Recursos disponíveis
                            </h3>

                            <p>
                                Ferramentas do ambiente.
                            </p>

                        </div>

                    </div>

                </div>


                <div class="resource-list">


                    <div class="resource-item">

                        <div class="resource-check">
                            ✓
                        </div>

                        <span>
                            Cadastro de questões
                        </span>

                    </div>


                    <div class="resource-item">

                        <div class="resource-check">
                            ✓
                        </div>

                        <span>
                            Criação de provas
                        </span>

                    </div>


                    <div class="resource-item">

                        <div class="resource-check">
                            ✓
                        </div>

                        <span>
                            Geração de PDF
                        </span>

                    </div>


                    <div class="resource-item">

                        <div class="resource-check">
                            ✓
                        </div>

                        <span>
                            Correção automática
                        </span>

                    </div>


                    <div class="resource-item">

                        <div class="resource-check">
                            ✓
                        </div>

                        <span>
                            Resultados
                        </span>

                    </div>


                    <div class="resource-item">

                        <div class="resource-check">
                            ✓
                        </div>

                        <span>
                            Análise com IA
                        </span>

                    </div>


                </div>

            </div>


        </section>


        <!-- =================================================
             RODAPÉ
        ================================================== -->

        <footer class="dashboard-footer">

            <span>

                QuestSystem

                <strong>
                    • Área do Professor
                </strong>

            </span>


            <span>

                Banco de questões e avaliações

            </span>

        </footer>


    </main>


</div>


</body>

</html>
```
