<?php

session_start();

include("../config/conexao.php");

/*
|--------------------------------------------------------------------------
| VERIFICAR LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$professor_id = (int) $_SESSION['id'];


/*
|--------------------------------------------------------------------------
| MENSAGEM
|--------------------------------------------------------------------------
*/

$mensagem = "";
$erro = "";


/*
|--------------------------------------------------------------------------
| DISCIPLINAS DISPONÍVEIS
|--------------------------------------------------------------------------
*/

$disciplinas = [
    "Biologia",
    "Matemática",
    "Português",
    "História",
    "Geografia",
    "Informática"
];


/*
|--------------------------------------------------------------------------
| PESQUISA E FILTRO
|--------------------------------------------------------------------------
*/

$pesquisa = isset($_GET['pesquisa'])
    ? trim($_GET['pesquisa'])
    : "";

$filtro = isset($_GET['disciplina'])
    ? trim($_GET['disciplina'])
    : "";


/*
|--------------------------------------------------------------------------
| CADASTRAR QUESTÃO
|--------------------------------------------------------------------------
*/

if (isset($_POST['salvar'])) {

    $pergunta = trim($_POST['pergunta'] ?? "");
    $a = trim($_POST['a'] ?? "");
    $b = trim($_POST['b'] ?? "");
    $c = trim($_POST['c'] ?? "");
    $d = trim($_POST['d'] ?? "");
    $correta = $_POST['correta'] ?? "";
    $disciplina = trim($_POST['disciplina'] ?? "");


    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÕES
    |--------------------------------------------------------------------------
    */

    if (
        $pergunta === "" ||
        $a === "" ||
        $b === "" ||
        $c === "" ||
        $d === "" ||
        $disciplina === ""
    ) {

        $erro = "Preencha todos os campos da questão.";

    } elseif (!in_array($disciplina, $disciplinas, true)) {

        $erro = "Disciplina inválida.";

    } elseif (!in_array($correta, ["A", "B", "C", "D"], true)) {

        $erro = "Alternativa correta inválida.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | INSERIR QUESTÃO
        |--------------------------------------------------------------------------
        |
        | IMPORTANTE:
        | O professor_id é salvo automaticamente.
        |
        */

        $sql = "
            INSERT INTO questoes
            (
                pergunta,
                alternativa_a,
                alternativa_b,
                alternativa_c,
                alternativa_d,
                correta,
                disciplina,
                professor_id
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = mysqli_prepare($conexao, $sql);

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "sssssssi",
                $pergunta,
                $a,
                $b,
                $c,
                $d,
                $correta,
                $disciplina,
                $professor_id
            );

            if (mysqli_stmt_execute($stmt)) {

                $mensagem = "Questão cadastrada com sucesso!";

            } else {

                $erro = "Erro ao cadastrar questão: " .
                        mysqli_stmt_error($stmt);
            }

            mysqli_stmt_close($stmt);

        } else {

            $erro = "Erro ao preparar o cadastro: " .
                    mysqli_error($conexao);
        }
    }
}


/*
|--------------------------------------------------------------------------
| CONTADOR DE QUESTÕES
|--------------------------------------------------------------------------
|
| SOMENTE QUESTÕES DO PROFESSOR LOGADO
|--------------------------------------------------------------------------
*/

$sqlTotal = "
    SELECT COUNT(*) AS total
    FROM questoes
    WHERE professor_id = ?
";

$stmtTotal = mysqli_prepare($conexao, $sqlTotal);

$total = 0;

if ($stmtTotal) {

    mysqli_stmt_bind_param(
        $stmtTotal,
        "i",
        $professor_id
    );

    mysqli_stmt_execute($stmtTotal);

    $resultadoTotal = mysqli_stmt_get_result($stmtTotal);

    if ($resultadoTotal) {

        $linhaTotal = mysqli_fetch_assoc($resultadoTotal);

        $total = (int) $linhaTotal['total'];
    }

    mysqli_stmt_close($stmtTotal);
}


/*
|--------------------------------------------------------------------------
| BUSCAR QUESTÕES
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        pergunta,
        alternativa_a,
        alternativa_b,
        alternativa_c,
        alternativa_d,
        correta,
        disciplina,
        professor_id
    FROM questoes
    WHERE professor_id = ?
";

$tipos = "i";
$valores = [$professor_id];


/*
|--------------------------------------------------------------------------
| FILTRO POR DISCIPLINA
|--------------------------------------------------------------------------
*/

if ($filtro !== "") {

    $sql .= " AND disciplina = ?";

    $tipos .= "s";

    $valores[] = $filtro;
}


/*
|--------------------------------------------------------------------------
| PESQUISA
|--------------------------------------------------------------------------
*/

if ($pesquisa !== "") {

    $sql .= " AND pergunta LIKE ?";

    $tipos .= "s";

    $valores[] = "%" . $pesquisa . "%";
}


$sql .= " ORDER BY id DESC";


/*
|--------------------------------------------------------------------------
| PREPARAR CONSULTA
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare($conexao, $sql);

$resultado = false;

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        $tipos,
        ...$valores
    );

    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);
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

<title>Banco de Questões</title>


<style>

/*
|--------------------------------------------------------------------------
| RESET
|--------------------------------------------------------------------------
*/

* {

    margin: 0;

    padding: 0;

    box-sizing: border-box;

    font-family: "Segoe UI", Arial, sans-serif;
}


/*
|--------------------------------------------------------------------------
| BODY
|--------------------------------------------------------------------------
*/

body {

    background: #eef2f7;

    color: #172033;
}


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

.header {

    background: linear-gradient(
        135deg,
        #173d91,
        #2864df
    );

    color: white;

    padding: 24px 40px;

    box-shadow: 0 4px 15px rgba(0,0,0,.15);
}

.header h1 {

    font-size: 30px;

    margin-bottom: 5px;
}

.header p {

    opacity: .9;

    font-size: 14px;
}


/*
|--------------------------------------------------------------------------
| CONTAINER
|--------------------------------------------------------------------------
*/

.container {

    width: 94%;

    max-width: 1500px;

    margin: 30px auto;
}


/*
|--------------------------------------------------------------------------
| CARD
|--------------------------------------------------------------------------
*/

.card {

    background: white;

    padding: 24px;

    border-radius: 16px;

    box-shadow:
        0 5px 20px rgba(0,0,0,.06);

    margin-bottom: 22px;
}


/*
|--------------------------------------------------------------------------
| TOPO
|--------------------------------------------------------------------------
*/

.topo {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;

    flex-wrap: wrap;
}


/*
|--------------------------------------------------------------------------
| CONTADOR
|--------------------------------------------------------------------------
*/

.contador {

    font-size: 20px;

    font-weight: bold;

    color: #1748a3;
}


/*
|--------------------------------------------------------------------------
| BOTÃO VOLTAR
|--------------------------------------------------------------------------
*/

.voltar {

    background: #46566f;

    color: white;

    padding: 11px 20px;

    border-radius: 9px;

    text-decoration: none;

    font-weight: 600;
}

.voltar:hover {

    background: #354359;
}


/*
|--------------------------------------------------------------------------
| FORMULÁRIOS
|--------------------------------------------------------------------------
*/

.form-filtro {

    display: grid;

    grid-template-columns:
        1.5fr
        1fr
        auto
        auto;

    gap: 10px;

    align-items: center;
}

.form-cadastro {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 15px;
}


/*
|--------------------------------------------------------------------------
| INPUTS
|--------------------------------------------------------------------------
*/

input,
select,
textarea {

    width: 100%;

    padding: 12px 14px;

    border: 1px solid #d2d9e5;

    border-radius: 9px;

    background: white;

    font-size: 15px;

    outline: none;

    transition: .2s;
}

input:focus,
select:focus,
textarea:focus {

    border-color: #2864df;

    box-shadow:
        0 0 0 3px rgba(40,100,223,.12);
}

textarea {

    min-height: 130px;

    resize: vertical;

    grid-column: 1 / -1;
}


/*
|--------------------------------------------------------------------------
| LABEL
|--------------------------------------------------------------------------
*/

label {

    font-weight: 600;

    font-size: 14px;

    color: #344054;
}


/*
|--------------------------------------------------------------------------
| CAMPOS DE CADASTRO
|--------------------------------------------------------------------------
*/

.campo {

    display: flex;

    flex-direction: column;

    gap: 7px;
}

.campo-pergunta {

    grid-column: 1 / -1;
}


/*
|--------------------------------------------------------------------------
| BOTÕES
|--------------------------------------------------------------------------
*/

button {

    border: none;

    padding: 12px 20px;

    border-radius: 9px;

    cursor: pointer;

    font-weight: 600;

    font-size: 14px;

    transition: .2s;
}

.btn-primary {

    background: #2864df;

    color: white;
}

.btn-primary:hover {

    background: #1d4fb5;
}

.btn-success {

    background: #20a85a;

    color: white;
}

.btn-success:hover {

    background: #178746;
}

.btn-limpar {

    background: #eef1f5;

    color: #344054;

    text-decoration: none;

    padding: 12px 18px;

    border-radius: 9px;

    font-weight: 600;
}

.btn-limpar:hover {

    background: #dfe4eb;
}


/*
|--------------------------------------------------------------------------
| TITULOS
|--------------------------------------------------------------------------
*/

h2 {

    color: #172033;

    margin-bottom: 5px;
}

.subtitulo {

    color: #667085;

    font-size: 14px;

    margin-bottom: 20px;
}


/*
|--------------------------------------------------------------------------
| MENSAGENS
|--------------------------------------------------------------------------
*/

.mensagem {

    padding: 14px 18px;

    border-radius: 10px;

    margin-bottom: 20px;

    font-weight: 600;
}

.sucesso {

    background: #dff6e7;

    color: #176b39;

    border: 1px solid #b9e8c9;
}

.erro {

    background: #ffe1e1;

    color: #a32121;

    border: 1px solid #ffbcbc;
}


/*
|--------------------------------------------------------------------------
| TABELA
|--------------------------------------------------------------------------
*/

.tabela-container {

    overflow-x: auto;
}

table {

    width: 100%;

    border-collapse: collapse;

    margin-top: 15px;
}

th {

    background: #1748a3;

    color: white;

    padding: 14px;

    text-align: left;

    white-space: nowrap;
}

td {

    padding: 14px;

    border-bottom: 1px solid #edf0f5;

    vertical-align: middle;
}

tr:hover {

    background: #f8fbff;
}


/*
|--------------------------------------------------------------------------
| BADGE
|--------------------------------------------------------------------------
*/

.badge {

    display: inline-block;

    padding: 6px 11px;

    border-radius: 20px;

    background: #e7efff;

    color: #1748a3;

    font-size: 13px;

    font-weight: 700;
}


/*
|--------------------------------------------------------------------------
| RESPOSTA CORRETA
|--------------------------------------------------------------------------
*/

.correta {

    color: #20a85a;

    font-weight: bold;
}


/*
|--------------------------------------------------------------------------
| AÇÕES
|--------------------------------------------------------------------------
*/

.acoes-coluna {

    width: 210px;

    text-align: center;
}

.acoes {

    display: flex;

    justify-content: center;

    gap: 8px;
}

.editar,
.excluir {

    display: inline-flex;

    justify-content: center;

    align-items: center;

    padding: 9px 13px;

    border-radius: 8px;

    text-decoration: none;

    font-weight: 600;

    font-size: 13px;
}

.editar {

    background: #fff0bd;

    color: #795d00;
}

.editar:hover {

    background: #ffe28a;
}

.excluir {

    background: #ffe0e0;

    color: #b42318;
}

.excluir:hover {

    background: #ffc8c8;
}


/*
|--------------------------------------------------------------------------
| RESPONSIVIDADE
|--------------------------------------------------------------------------
*/

@media(max-width: 900px) {

    .form-filtro {

        grid-template-columns: 1fr 1fr;
    }

    .form-cadastro {

        grid-template-columns: 1fr;
    }

    textarea {

        grid-column: auto;
    }
}


@media(max-width: 600px) {

    .header {

        padding: 20px;
    }

    .container {

        width: 94%;
    }

    .form-filtro {

        grid-template-columns: 1fr;
    }

    .acoes {

        flex-direction: column;
    }

    .editar,
    .excluir {

        width: 100%;
    }
}

</style>

    <link rel="stylesheet" href="../assets/css/questsystem.css">
</head>


<body class="qs-app">
<?php include_once __DIR__ . "/../includes/sidebar.php"; ?>


<!--
|--------------------------------------------------------------------------
| CABEÇALHO
|--------------------------------------------------------------------------
-->

<div class="header">

    <h1>📚 Banco de Questões</h1>

    <p>
        Organize suas questões por disciplina.
    </p>

</div>


<div class="container">


<!--
|--------------------------------------------------------------------------
| MENSAGENS
|--------------------------------------------------------------------------
-->

<?php if ($mensagem !== "") { ?>

    <div class="mensagem sucesso">

        ✅ <?php echo htmlspecialchars($mensagem); ?>

    </div>

<?php } ?>


<?php if ($erro !== "") { ?>

    <div class="mensagem erro">

        ❌ <?php echo htmlspecialchars($erro); ?>

    </div>

<?php } ?>


<!--
|--------------------------------------------------------------------------
| CABEÇALHO DO BANCO
|--------------------------------------------------------------------------
-->

<div class="card">

    <div class="topo">

        <div>

            <div class="contador">

                <?php echo $total; ?>

                <?php echo ($total == 1)
                    ? " questão cadastrada"
                    : " questões cadastradas";
                ?>

            </div>

        </div>


        <a
            href="dashboard.php"
            class="voltar"
        >

            ← Voltar ao Painel

        </a>

    </div>

</div>


<!--
|--------------------------------------------------------------------------
| FILTROS
|--------------------------------------------------------------------------
-->

<div class="card">

    <h2>🔎 Pesquisar questões</h2>

    <p class="subtitulo">

        Filtre suas questões por disciplina ou pesquise pelo enunciado.

    </p>


    <form
        method="GET"
        class="form-filtro"
    >

        <!-- PESQUISA -->

        <input
            type="text"
            name="pesquisa"
            placeholder="🔍 Buscar questão..."
            value="<?php echo htmlspecialchars($pesquisa); ?>"
        >


        <!-- DISCIPLINA -->

        <select name="disciplina">

            <option value="">
                Todas as disciplinas
            </option>


            <?php foreach ($disciplinas as $disciplina) { ?>

                <option
                    value="<?php echo htmlspecialchars($disciplina); ?>"
                    <?php
                    if ($filtro === $disciplina) {
                        echo "selected";
                    }
                    ?>
                >

                    <?php echo htmlspecialchars($disciplina); ?>

                </option>

            <?php } ?>

        </select>


        <button
            type="submit"
            class="btn-primary"
        >

            Filtrar

        </button>


        <a
            href="questoes.php"
            class="btn-limpar"
        >

            Limpar

        </a>

    </form>

</div>


<!--
|--------------------------------------------------------------------------
| CADASTRAR NOVA QUESTÃO
|--------------------------------------------------------------------------
-->

<div class="card" id="cadastro">

    <h2>➕ Nova Questão</h2>

    <p class="subtitulo">

        Cadastre uma questão para o seu banco.

    </p>


    <form
        method="POST"
        class="form-cadastro"
    >


        <!-- PERGUNTA -->

        <div class="campo campo-pergunta">

            <label>
                Pergunta
            </label>

            <textarea
                name="pergunta"
                placeholder="Digite o enunciado da questão..."
                required
            ></textarea>

        </div>


        <!-- ALTERNATIVA A -->

        <div class="campo">

            <label>
                Alternativa A
            </label>

            <input
                type="text"
                name="a"
                placeholder="Digite a alternativa A"
                required
            >

        </div>


        <!-- ALTERNATIVA B -->

        <div class="campo">

            <label>
                Alternativa B
            </label>

            <input
                type="text"
                name="b"
                placeholder="Digite a alternativa B"
                required
            >

        </div>


        <!-- ALTERNATIVA C -->

        <div class="campo">

            <label>
                Alternativa C
            </label>

            <input
                type="text"
                name="c"
                placeholder="Digite a alternativa C"
                required
            >

        </div>


        <!-- ALTERNATIVA D -->

        <div class="campo">

            <label>
                Alternativa D
            </label>

            <input
                type="text"
                name="d"
                placeholder="Digite a alternativa D"
                required
            >

        </div>


        <!-- DISCIPLINA -->

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


                <?php foreach ($disciplinas as $disciplina) { ?>

                    <option
                        value="<?php echo htmlspecialchars($disciplina); ?>"
                    >

                        <?php echo htmlspecialchars($disciplina); ?>

                    </option>

                <?php } ?>

            </select>

        </div>


        <!-- ALTERNATIVA CORRETA -->

        <div class="campo">

            <label>
                Alternativa correta
            </label>

            <select
                name="correta"
                required
            >

                <option value="A">
                    A
                </option>

                <option value="B">
                    B
                </option>

                <option value="C">
                    C
                </option>

                <option value="D">
                    D
                </option>

            </select>

        </div>


        <!-- BOTÃO -->

        <div
            style="
                grid-column:1/-1;
                display:flex;
                justify-content:flex-end;
            "
        >

            <button
                type="submit"
                name="salvar"
                class="btn-success"
            >

                💾 Cadastrar Questão

            </button>

        </div>

    </form>

</div>


<!--
|--------------------------------------------------------------------------
| QUESTÕES CADASTRADAS
|--------------------------------------------------------------------------
-->

<div class="card">

    <h2>📋 Minhas Questões</h2>

    <p class="subtitulo">

        Aqui aparecem somente as questões cadastradas por você.

    </p>


    <div class="tabela-container">

        <table>

            <thead>

                <tr>

                    <th>
                        ID
                    </th>

                    <th>
                        Disciplina
                    </th>

                    <th>
                        Pergunta
                    </th>

                    <th>
                        Correta
                    </th>

                    <th class="acoes-coluna">
                        Ações
                    </th>

                </tr>

            </thead>


            <tbody>


            <?php

            if ($resultado && mysqli_num_rows($resultado) > 0) {

                while ($linha = mysqli_fetch_assoc($resultado)) {

            ?>

                <tr>


                    <td>

                        <?php echo (int) $linha['id']; ?>

                    </td>


                    <td>

                        <span class="badge">

                            <?php
                            echo htmlspecialchars(
                                $linha['disciplina']
                            );
                            ?>

                        </span>

                    </td>


                    <td>

                        <strong>

                            <?php
                            echo htmlspecialchars(
                                $linha['pergunta']
                            );
                            ?>

                        </strong>

                    </td>


                    <td>

                        <span class="correta">

                            <?php
                            echo htmlspecialchars(
                                $linha['correta']
                            );
                            ?>

                        </span>

                    </td>


                    <td class="acoes-coluna">

                        <div class="acoes">


                            <a
                                class="editar"
                                href="editar_questao.php?id=<?php echo (int)$linha['id']; ?>"
                            >

                                ✏️ Editar

                            </a>


                            <a
                                class="excluir"
                                href="excluir_questao.php?id=<?php echo (int)$linha['id']; ?>"
                                onclick="return confirm('Deseja realmente excluir esta questão?');"
                            >

                                🗑️ Excluir

                            </a>


                        </div>

                    </td>


                </tr>

            <?php

                }

            } else {

            ?>

                <tr>

                    <td
                        colspan="5"
                        style="
                            text-align:center;
                            padding:45px;
                            color:#667085;
                        "
                    >

                        <div
                            style="
                                font-size:42px;
                                margin-bottom:10px;
                            "
                        >
                            📚
                        </div>

                        <strong
                            style="
                                display:block;
                                font-size:18px;
                                color:#344054;
                                margin-bottom:6px;
                            "
                        >

                            Nenhuma questão encontrada

                        </strong>

                        <span>

                            Cadastre uma questão ou altere os filtros.

                        </span>

                    </td>

                </tr>

            <?php

            }

            ?>


            </tbody>

        </table>

    </div>

</div>


</div>


</body>

</html>