<?php

session_start();

include("../config/conexao.php");

/* =========================================================
   VERIFICAÇÃO DE LOGIN
   ========================================================= */

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$professor_id = (int) $_SESSION['id'];

$mensagem = "";
$erro = "";

/*
 * IMPORTANTE:
 * Preserve os dados digitados quando o professor clicar em
 * "Carregar Questões". Sem isso, o título ficava vazio depois
 * do filtro e, ao clicar em "Criar Prova", aparecia:
 * "Informe o título da prova."
 */
$titulo = trim($_POST['titulo'] ?? "");
$disciplina = trim($_POST['disciplina'] ?? "");
$assunto = trim($_POST['assunto'] ?? "");


/* =========================================================
   DISCIPLINAS DISPONÍVEIS NO SISTEMA
   ========================================================= */

$disciplinas = [
    "Biologia",
    "Matemática",
    "Português",
    "História",
    "Geografia",
    "Informática"
];


/* =========================================================
   VERIFICAR SE QUESTOES POSSUI professor_id
   ========================================================= */

$tem_professor_id = false;

$verifica_coluna = mysqli_query(
    $conexao,
    "SHOW COLUMNS FROM questoes LIKE 'professor_id'"
);

if ($verifica_coluna && mysqli_num_rows($verifica_coluna) > 0) {
    $tem_professor_id = true;
}


/* =========================================================
   CRIAR PROVA
   ========================================================= */

if (isset($_POST['criar'])) {

    $titulo = trim($_POST['titulo'] ?? "");
    $disciplina = trim($_POST['disciplina'] ?? "");
    $assunto = trim($_POST['assunto'] ?? "");


    /* -----------------------------------------------------
       VALIDAÇÕES
       ----------------------------------------------------- */

    if ($titulo === "") {

        $erro = "Informe o título da prova.";

    } elseif ($disciplina === "") {

        $erro = "Selecione uma disciplina.";

    } elseif (!in_array($disciplina, $disciplinas, true)) {

        $erro = "Disciplina inválida.";

    } elseif (
        !isset($_POST['questoes']) ||
        !is_array($_POST['questoes'])
    ) {

        $erro = "Selecione pelo menos uma questão.";

    } else {

        $questoes_selecionadas = $_POST['questoes'];

        $questoes_validas = [];


        foreach ($questoes_selecionadas as $questao_id) {

            $questao_id = (int) $questao_id;

            if ($questao_id > 0) {
                $questoes_validas[] = $questao_id;
            }
        }


        $questoes_validas = array_unique(
            $questoes_validas
        );


        if (count($questoes_validas) === 0) {

            $erro = "Selecione pelo menos uma questão.";

        } else {

            /*
             * =================================================
             * VERIFICAR QUESTÕES
             *
             * A questão precisa:
             *
             * 1. Existir
             * 2. Pertencer à disciplina escolhida
             * 3. Pertencer ao professor logado
             *
             * Se o assunto foi digitado, ele também será
             * utilizado para localizar as questões.
             * =================================================
             */

            $questoes_permitidas = [];


            foreach ($questoes_validas as $questao_id) {


                if ($tem_professor_id) {


                    if ($assunto !== "") {

                        $sql_verifica = "
                            SELECT id
                            FROM questoes
                            WHERE id = ?
                            AND disciplina = ?
                            AND professor_id = ?
                            AND assunto LIKE ?
                            LIMIT 1
                        ";

                        $stmt_verifica = mysqli_prepare(
                            $conexao,
                            $sql_verifica
                        );


                        $assunto_busca = "%" . $assunto . "%";


                        mysqli_stmt_bind_param(
                            $stmt_verifica,
                            "isis",
                            $questao_id,
                            $disciplina,
                            $professor_id,
                            $assunto_busca
                        );

                    } else {

                        $sql_verifica = "
                            SELECT id
                            FROM questoes
                            WHERE id = ?
                            AND disciplina = ?
                            AND professor_id = ?
                            LIMIT 1
                        ";

                        $stmt_verifica = mysqli_prepare(
                            $conexao,
                            $sql_verifica
                        );


                        mysqli_stmt_bind_param(
                            $stmt_verifica,
                            "isi",
                            $questao_id,
                            $disciplina,
                            $professor_id
                        );
                    }


                } else {


                    /*
                     * Compatibilidade com banco antigo
                     */

                    if ($assunto !== "") {

                        $sql_verifica = "
                            SELECT id
                            FROM questoes
                            WHERE id = ?
                            AND disciplina = ?
                            AND assunto LIKE ?
                            LIMIT 1
                        ";

                        $stmt_verifica = mysqli_prepare(
                            $conexao,
                            $sql_verifica
                        );


                        $assunto_busca = "%" . $assunto . "%";


                        mysqli_stmt_bind_param(
                            $stmt_verifica,
                            "iss",
                            $questao_id,
                            $disciplina,
                            $assunto_busca
                        );

                    } else {

                        $sql_verifica = "
                            SELECT id
                            FROM questoes
                            WHERE id = ?
                            AND disciplina = ?
                            LIMIT 1
                        ";

                        $stmt_verifica = mysqli_prepare(
                            $conexao,
                            $sql_verifica
                        );


                        mysqli_stmt_bind_param(
                            $stmt_verifica,
                            "is",
                            $questao_id,
                            $disciplina
                        );
                    }
                }


                mysqli_stmt_execute(
                    $stmt_verifica
                );


                $resultado_verifica =
                    mysqli_stmt_get_result(
                        $stmt_verifica
                    );


                if (
                    $resultado_verifica &&
                    mysqli_num_rows(
                        $resultado_verifica
                    ) > 0
                ) {

                    $questoes_permitidas[] =
                        $questao_id;
                }


                mysqli_stmt_close(
                    $stmt_verifica
                );
            }


            if (count($questoes_permitidas) === 0) {

                $erro =
                    "Nenhuma das questões selecionadas pertence ao seu banco de questões.";

            } else {


                /* =================================================
                   TRANSAÇÃO
                   ================================================= */

                mysqli_begin_transaction(
                    $conexao
                );


                try {


                    /* =================================================
                       INSERIR PROVA
                       ================================================= */

                    $sql_prova = "
                        INSERT INTO provas
                        (
                            titulo,
                            disciplina,
                            professor_id
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?
                        )
                    ";


                    $stmt_prova =
                        mysqli_prepare(
                            $conexao,
                            $sql_prova
                        );


                    mysqli_stmt_bind_param(
                        $stmt_prova,
                        "ssi",
                        $titulo,
                        $disciplina,
                        $professor_id
                    );


                    if (
                        !mysqli_stmt_execute(
                            $stmt_prova
                        )
                    ) {

                        throw new Exception(
                            "Não foi possível criar a prova."
                        );
                    }


                    $prova_id =
                        mysqli_insert_id(
                            $conexao
                        );


                    mysqli_stmt_close(
                        $stmt_prova
                    );


                    /* =================================================
                       INSERIR QUESTÕES NA PROVA
                       ================================================= */

                    $sql_prova_questao = "
                        INSERT INTO prova_questoes
                        (
                            prova_id,
                            questao_id
                        )
                        VALUES
                        (
                            ?,
                            ?
                        )
                    ";


                    $stmt_pq =
                        mysqli_prepare(
                            $conexao,
                            $sql_prova_questao
                        );


                    foreach (
                        $questoes_permitidas
                        as $questao_id
                    ) {

                        mysqli_stmt_bind_param(
                            $stmt_pq,
                            "ii",
                            $prova_id,
                            $questao_id
                        );


                        if (
                            !mysqli_stmt_execute(
                                $stmt_pq
                            )
                        ) {

                            throw new Exception(
                                "Erro ao adicionar uma questão à prova."
                            );
                        }
                    }


                    mysqli_stmt_close(
                        $stmt_pq
                    );


                    /* =================================================
                       FINALIZAR
                       ================================================= */

                    mysqli_commit(
                        $conexao
                    );


                    $mensagem =
                        "Prova criada com sucesso! " .
                        count($questoes_permitidas) .
                        " questão(ões) adicionada(s).";


                    $titulo = "";
                    $disciplina = "";
                    $assunto = "";


                } catch (Exception $e) {

                    mysqli_rollback(
                        $conexao
                    );

                    $erro =
                        $e->getMessage();
                }
            }
        }
    }
}


/* =========================================================
   CARREGAR QUESTÕES
   ========================================================= */

$mostrar_questoes = false;

$questoes = [];


if (
    isset($_POST['filtrar']) ||
    isset($_POST['criar'])
) {

    $disciplina =
        trim(
            $_POST['disciplina'] ?? ""
        );

    $assunto =
        trim(
            $_POST['assunto'] ?? ""
        );


    if ($disciplina !== "") {

        $mostrar_questoes = true;


        /* =================================================
           PROFESSOR + ASSUNTO
           ================================================= */

        if ($tem_professor_id) {


            if ($assunto !== "") {

                $sql_questoes = "
                    SELECT *
                    FROM questoes
                    WHERE professor_id = ?
                    AND disciplina = ?
                    AND assunto LIKE ?
                    ORDER BY id DESC
                ";


                $stmt_questoes =
                    mysqli_prepare(
                        $conexao,
                        $sql_questoes
                    );


                $assunto_busca =
                    "%" . $assunto . "%";


                mysqli_stmt_bind_param(
                    $stmt_questoes,
                    "iss",
                    $professor_id,
                    $disciplina,
                    $assunto_busca
                );


            } else {

                $sql_questoes = "
                    SELECT *
                    FROM questoes
                    WHERE professor_id = ?
                    AND disciplina = ?
                    ORDER BY id DESC
                ";


                $stmt_questoes =
                    mysqli_prepare(
                        $conexao,
                        $sql_questoes
                    );


                mysqli_stmt_bind_param(
                    $stmt_questoes,
                    "is",
                    $professor_id,
                    $disciplina
                );
            }


        } else {


            /* =================================================
               BANCO ANTIGO
               ================================================= */

            if ($assunto !== "") {

                $sql_questoes = "
                    SELECT *
                    FROM questoes
                    WHERE disciplina = ?
                    AND assunto LIKE ?
                    ORDER BY id DESC
                ";


                $stmt_questoes =
                    mysqli_prepare(
                        $conexao,
                        $sql_questoes
                    );


                $assunto_busca =
                    "%" . $assunto . "%";


                mysqli_stmt_bind_param(
                    $stmt_questoes,
                    "ss",
                    $disciplina,
                    $assunto_busca
                );


            } else {

                $sql_questoes = "
                    SELECT *
                    FROM questoes
                    WHERE disciplina = ?
                    ORDER BY id DESC
                ";


                $stmt_questoes =
                    mysqli_prepare(
                        $conexao,
                        $sql_questoes
                    );


                mysqli_stmt_bind_param(
                    $stmt_questoes,
                    "s",
                    $disciplina
                );
            }
        }


        mysqli_stmt_execute(
            $stmt_questoes
        );


        $resultado_questoes =
            mysqli_stmt_get_result(
                $stmt_questoes
            );


        while (
            $resultado_questoes &&
            (
                $q =
                mysqli_fetch_assoc(
                    $resultado_questoes
                )
            )
        ) {

            $questoes[] = $q;
        }


        mysqli_stmt_close(
            $stmt_questoes
        );
    }
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

<title>Criar Nova Prova</title>


<style>

/* =========================================================
   RESET
   ========================================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


/* =========================================================
   BODY
   ========================================================= */

body {

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

    background:
        #eef3ff;

    color:
        #172033;

    min-height:
        100vh;
}


/* =========================================================
   HEADER
   ========================================================= */

.header {

    background:
        linear-gradient(
            135deg,
            #214397,
            #2864db
        );

    color:
        white;

    padding:
        25px;

    box-shadow:
        0 4px 15px
        rgba(0,0,0,.15);
}


.header-content {

    width:
        95%;

    max-width:
        1200px;

    margin:
        auto;
}


.header h1 {

    font-size:
        30px;

    margin-bottom:
        5px;
}


.header p {

    opacity:
        .95;

    font-size:
        15px;
}


/* =========================================================
   CONTAINER
   ========================================================= */

.container {

    width:
        95%;

    max-width:
        1200px;

    margin:
        30px auto;
}


/* =========================================================
   CARD
   ========================================================= */

.card {

    background:
        white;

    padding:
        28px;

    border-radius:
        18px;

    box-shadow:
        0 8px 30px
        rgba(26,55,100,.10);

    margin-bottom:
        22px;
}


/* =========================================================
   TÍTULOS
   ========================================================= */

h2 {

    color:
        #123c91;

    margin-bottom:
        22px;
}

h3 {

    color:
        #123c91;

    margin-bottom:
        15px;
}


/* =========================================================
   GRID
   ========================================================= */

.grid {

    display:
        grid;

    grid-template-columns:
        1fr 1fr;

    gap:
        20px;
}


/* =========================================================
   CAMPOS
   ========================================================= */

.campo {

    margin-bottom:
        18px;
}


.campo label {

    display:
        block;

    font-weight:
        600;

    margin-bottom:
        8px;

    color:
        #24324a;
}


input[type="text"],
select {

    width:
        100%;

    padding:
        13px 14px;

    border:
        1px solid #cbd5e1;

    border-radius:
        10px;

    background:
        white;

    font-size:
        15px;

    outline:
        none;

    transition:
        .2s;
}


input[type="text"]:focus,
select:focus {

    border-color:
        #2864db;

    box-shadow:
        0 0 0 3px
        rgba(40,100,219,.12);
}


/* =========================================================
   BOTÕES
   ========================================================= */

.botoes {

    display:
        flex;

    flex-wrap:
        wrap;

    gap:
        12px;

    margin-top:
        15px;
}


.btn {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        7px;

    padding:
        12px 20px;

    border:
        none;

    border-radius:
        10px;

    cursor:
        pointer;

    text-decoration:
        none;

    font-size:
        14px;

    font-weight:
        700;

    transition:
        .2s;
}


.btn:hover {

    transform:
        translateY(-1px);
}


.btn-primary {

    background:
        #2864db;

    color:
        white;
}


.btn-primary:hover {

    background:
        #174db9;
}


.btn-success {

    background:
        #198754;

    color:
        white;
}


.btn-success:hover {

    background:
        #126b40;
}


.btn-ia {

    background:
        linear-gradient(
            135deg,
            #7c3aed,
            #4f46e5
        );

    color:
        white;
}


.btn-ia:hover {

    background:
        linear-gradient(
            135deg,
            #6d28d9,
            #4338ca
        );
}


.btn-secondary {

    background:
        #526176;

    color:
        white;
}


.btn-secondary:hover {

    background:
        #3e4b5f;
}


/* =========================================================
   ALERTAS
   ========================================================= */

.sucesso {

    background:
        #d1fae5;

    color:
        #065f46;

    padding:
        15px 18px;

    border-radius:
        10px;

    margin-bottom:
        20px;

    font-weight:
        600;
}


.erro {

    background:
        #fee2e2;

    color:
        #991b1b;

    padding:
        15px 18px;

    border-radius:
        10px;

    margin-bottom:
        20px;

    font-weight:
        600;
}


/* =========================================================
   INFORMAÇÕES
   ========================================================= */

.info {

    background:
        #eff6ff;

    border-left:
        5px solid #2864db;

    padding:
        15px 18px;

    border-radius:
        8px;

    margin-bottom:
        20px;

    color:
        #334155;
}


/* =========================================================
   QUESTÕES
   ========================================================= */

.questoes-area {

    margin-top:
        25px;
}


.questao {

    background:
        #ffffff;

    border:
        1px solid #dce3ee;

    border-left:
        5px solid #2864db;

    padding:
        20px;

    border-radius:
        12px;

    margin-bottom:
        15px;

    box-shadow:
        0 3px 12px
        rgba(0,0,0,.05);

    transition:
        .2s;
}


.questao:hover {

    border-left-color:
        #7c3aed;

    box-shadow:
        0 6px 18px
        rgba(0,0,0,.08);
}


.questao label {

    display:
        flex;

    gap:
        13px;

    cursor:
        pointer;

    line-height:
        1.6;
}


.questao input[type="checkbox"] {

    width:
        20px;

    height:
        20px;

    margin-top:
        3px;

    cursor:
        pointer;
}


.questao-conteudo {

    flex:
        1;
}


.questao-numero {

    color:
        #2864db;

    font-weight:
        800;

    margin-bottom:
        8px;
}


.questao-pergunta {

    color:
        #1e293b;

    font-size:
        15px;
}


/* =========================================================
   ALTERNATIVAS
   ========================================================= */

.alternativas {

    margin-top:
        12px;

    padding-left:
        5px;

    color:
        #64748b;

    font-size:
        14px;
}


.alternativas div {

    margin:
        4px 0;
}


/* =========================================================
   NENHUMA QUESTÃO
   ========================================================= */

.vazio {

    text-align:
        center;

    padding:
        45px 20px;

    color:
        #64748b;

    background:
        #f8fafc;

    border:
        1px dashed #cbd5e1;

    border-radius:
        12px;
}


.vazio h3 {

    color:
        #475569;

    margin-bottom:
        8px;
}


/* =========================================================
   RODAPÉ
   ========================================================= */

.voltar {

    display:
        inline-flex;

    text-decoration:
        none;

    background:
        #526176;

    color:
        white;

    padding:
        11px 18px;

    border-radius:
        10px;

    font-weight:
        600;
}


.voltar:hover {

    background:
        #3e4b5f;
}


/* =========================================================
   RESPONSIVO
   ========================================================= */

@media (max-width: 750px) {

    .grid {

        grid-template-columns:
            1fr;
    }


    .header h1 {

        font-size:
            24px;
    }


    .card {

        padding:
            20px;
    }
}

</style>

    <link rel="stylesheet" href="../assets/css/questsystem.css">
</head>


<body class="qs-app">
<?php include_once __DIR__ . "/../includes/sidebar.php"; ?>


<!-- ======================================================
     HEADER
     ====================================================== -->

<div class="header">

    <div class="header-content">

        <h1>
            📝 Criar Nova Prova
        </h1>

        <p>
            Monte uma avaliação personalizada utilizando suas próprias questões.
        </p>

    </div>

</div>


<div class="container">


<!-- ======================================================
     MENSAGEM DE SUCESSO
     ====================================================== -->

<?php if ($mensagem !== ""): ?>

    <div class="sucesso">

        ✅

        <?php
        echo htmlspecialchars($mensagem);
        ?>

    </div>

<?php endif; ?>


<!-- ======================================================
     ERRO
     ====================================================== -->

<?php if ($erro !== ""): ?>

    <div class="erro">

        ❌

        <?php
        echo htmlspecialchars($erro);
        ?>

    </div>

<?php endif; ?>


<!-- ======================================================
     CONFIGURAÇÃO DA PROVA
     ====================================================== -->

<div class="card">

    <h2>
        Informações da Prova
    </h2>


    <form method="POST">


        <div class="grid">


            <!-- =================================================
                 TÍTULO
                 ================================================= -->

            <div class="campo">

                <label>
                    Título da Prova
                </label>

                <input
                    type="text"
                    name="titulo"
                    placeholder="Ex.: Avaliação de Biologia"
                    value="<?php echo htmlspecialchars($titulo); ?>"
                    required
                >

            </div>


            <!-- =================================================
                 DISCIPLINA
                 ================================================= -->

            <div class="campo">

                <label>
                    Disciplina
                </label>

                <select
                    name="disciplina"
                    required
                >

                    <option value="">
                        Selecione uma disciplina
                    </option>


                    <?php foreach ($disciplinas as $d): ?>

                        <option
                            value="<?php echo htmlspecialchars($d); ?>"
                            <?php
                            if ($disciplina === $d) {
                                echo "selected";
                            }
                            ?>
                        >

                            <?php
                            echo htmlspecialchars($d);
                            ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


        </div>


        <!-- =====================================================
             ASSUNTO — AGORA É DIGITADO
             ===================================================== -->

        <div class="campo">

            <label for="assunto">
                Assunto
            </label>

            <input
                type="text"
                name="assunto"
                id="assunto"
                placeholder="Digite o assunto da prova..."
                value="<?php echo htmlspecialchars($assunto); ?>"
            >

            <small
                style="
                    display:block;
                    margin-top:7px;
                    color:#64748b;
                "
            >
                Digite uma palavra ou parte do assunto.
                Deixe vazio para mostrar todas as questões da disciplina.
            </small>

        </div>


        <!-- =====================================================
             BOTÕES
             ===================================================== -->

        <div class="botoes">


            <button
                type="submit"
                name="filtrar"
                class="btn btn-primary"
            >

                🔎

                Carregar Questões

            </button>


            <!-- =================================================
                 CRIAR PROVA COM IA
                 ================================================= -->

            <a
                href="criar_prova_ia.php"
                class="btn btn-ia"
            >

                🤖

                Criar Prova com IA

            </a>


        </div>


    </form>

</div>



<!-- ======================================================
     QUESTÕES
     ====================================================== -->

<?php if ($mostrar_questoes): ?>


<div class="card questoes-area">


    <h2>
        Questões disponíveis
    </h2>


    <div class="info">

        📚

        Disciplina:

        <strong>
            <?php
            echo htmlspecialchars(
                $disciplina
            );
            ?>
        </strong>


        <?php if ($assunto !== ""): ?>

            &nbsp; | &nbsp;

            📖

            Assunto pesquisado:

            <strong>
                <?php
                echo htmlspecialchars(
                    $assunto
                );
                ?>
            </strong>

        <?php endif; ?>


        <br><br>


        🔐

        <strong>
            Apenas questões cadastradas por você são exibidas.
        </strong>

    </div>


    <?php if (count($questoes) > 0): ?>


        <form method="POST">


            <!-- =================================================
                 MANTER INFORMAÇÕES
                 ================================================= -->

            <input
                type="hidden"
                name="titulo"
                value="<?php
                echo htmlspecialchars($titulo);
                ?>"
            >


            <input
                type="hidden"
                name="disciplina"
                value="<?php
                echo htmlspecialchars($disciplina);
                ?>"
            >


            <input
                type="hidden"
                name="assunto"
                value="<?php
                echo htmlspecialchars($assunto);
                ?>"
            >


            <!-- =================================================
                 QUESTÕES
                 ================================================= -->

            <?php foreach ($questoes as $q): ?>


                <div class="questao">


                    <label>


                        <input
                            type="checkbox"
                            name="questoes[]"
                            value="<?php
                            echo (int)$q['id'];
                            ?>"
                        >


                        <div
                            class="questao-conteudo"
                        >


                            <div
                                class="questao-numero"
                            >

                                Questão

                                <?php
                                echo (int)$q['id'];
                                ?>

                            </div>


                            <div
                                class="questao-pergunta"
                            >

                                <?php

                                echo nl2br(
                                    htmlspecialchars(
                                        $q['pergunta'] ?? ''
                                    )
                                );

                                ?>

                            </div>


                            <!-- =================================================
                                 ALTERNATIVAS
                                 ================================================= -->

                            <?php

                            $tem_alternativas =
                                isset(
                                    $q['alternativa_a']
                                ) ||
                                isset(
                                    $q['alternativa_b']
                                ) ||
                                isset(
                                    $q['alternativa_c']
                                ) ||
                                isset(
                                    $q['alternativa_d']
                                );

                            ?>


                            <?php if ($tem_alternativas): ?>


                                <div
                                    class="alternativas"
                                >


                                    <?php
                                    if (
                                        !empty(
                                            $q['alternativa_a']
                                        )
                                    ):
                                    ?>

                                        <div>

                                            <strong>
                                                A)
                                            </strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $q['alternativa_a']
                                            );
                                            ?>

                                        </div>

                                    <?php endif; ?>


                                    <?php
                                    if (
                                        !empty(
                                            $q['alternativa_b']
                                        )
                                    ):
                                    ?>

                                        <div>

                                            <strong>
                                                B)
                                            </strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $q['alternativa_b']
                                            );
                                            ?>

                                        </div>

                                    <?php endif; ?>


                                    <?php
                                    if (
                                        !empty(
                                            $q['alternativa_c']
                                        )
                                    ):
                                    ?>

                                        <div>

                                            <strong>
                                                C)
                                            </strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $q['alternativa_c']
                                            );
                                            ?>

                                        </div>

                                    <?php endif; ?>


                                    <?php
                                    if (
                                        !empty(
                                            $q['alternativa_d']
                                        )
                                    ):
                                    ?>

                                        <div>

                                            <strong>
                                                D)
                                            </strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $q['alternativa_d']
                                            );
                                            ?>

                                        </div>

                                    <?php endif; ?>


                                </div>

                            <?php endif; ?>


                        </div>


                    </label>


                </div>


            <?php endforeach; ?>


            <!-- =================================================
                 BOTÕES FINAIS
                 ================================================= -->

            <div class="botoes">


                <button
                    type="submit"
                    name="criar"
                    class="btn btn-success"
                >

                    ✅

                    Criar Prova

                </button>


                <button
                    type="button"
                    class="btn btn-secondary"
                    onclick="marcarTodas()"
                >

                    ☑️

                    Selecionar Todas

                </button>


            </div>


        </form>


    <?php else: ?>


        <div class="vazio">


            <h3>
                📭 Nenhuma questão encontrada
            </h3>


            <p>

                Não foram encontradas questões suas para:

            </p>


            <br>


            <strong>

                <?php
                echo htmlspecialchars(
                    $disciplina
                );
                ?>

            </strong>


            <?php if ($assunto !== ""): ?>

                <br>

                Assunto contendo:

                <strong>

                    <?php
                    echo htmlspecialchars(
                        $assunto
                    );
                    ?>

                </strong>

            <?php endif; ?>


            <br><br>


            <a
                href="questoes.php"
                class="btn btn-primary"
            >

                ➕

                Cadastrar Questão

            </a>


        </div>


    <?php endif; ?>


</div>


<?php endif; ?>



<!-- ======================================================
     VOLTAR
     ====================================================== -->

<a
    href="dashboard.php"
    class="voltar"
>

    ← Voltar ao Painel

</a>


</div>



<script>

/* =========================================================
   SELECIONAR TODAS AS QUESTÕES
   ========================================================= */

function marcarTodas() {

    const caixas =
        document.querySelectorAll(
            'input[name="questoes[]"]'
        );


    let algumaDesmarcada = false;


    caixas.forEach(
        function(caixa) {

            if (!caixa.checked) {

                algumaDesmarcada = true;

            }

        }
    );


    caixas.forEach(
        function(caixa) {

            caixa.checked =
                algumaDesmarcada;

        }
    );

}

</script>


</body>

</html>