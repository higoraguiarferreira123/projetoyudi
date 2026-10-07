<?php

session_start();

require_once __DIR__ . '/../config/conexao.php';


/*
|--------------------------------------------------------------------------
| SEGURANÇA
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['id'])) {
    header('Location: ../auth/login.php');
    exit;
}


$professor_id = (int) $_SESSION['id'];

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


/*
|--------------------------------------------------------------------------
| VERIFICA ID
|--------------------------------------------------------------------------
*/

if (!$id) {

    header('Location: questoes.php');
    exit;
}


$erro = '';
$mensagem = '';



/*
|--------------------------------------------------------------------------
| BUSCAR QUESTÃO
|--------------------------------------------------------------------------
|
| IMPORTANTE:
| A questão precisa pertencer ao professor logado.
|
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
        assunto
    FROM questoes
    WHERE id = ?
      AND professor_id = ?
    LIMIT 1
";


$stmt = mysqli_prepare(
    $conexao,
    $sql
);


if (!$stmt) {

    die(
        'Erro ao preparar consulta: ' .
        mysqli_error($conexao)
    );
}


mysqli_stmt_bind_param(
    $stmt,
    'ii',
    $id,
    $professor_id
);


mysqli_stmt_execute($stmt);


$resultado =
    mysqli_stmt_get_result($stmt);


$questao =
    mysqli_fetch_assoc($resultado);


mysqli_stmt_close($stmt);



/*
|--------------------------------------------------------------------------
| QUESTÃO NÃO ENCONTRADA
|--------------------------------------------------------------------------
*/

if (!$questao) {

    header('Location: questoes.php');
    exit;
}



/*
|--------------------------------------------------------------------------
| ATUALIZAR QUESTÃO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pergunta =
        trim($_POST['pergunta'] ?? '');

    $alternativa_a =
        trim($_POST['alternativa_a'] ?? '');

    $alternativa_b =
        trim($_POST['alternativa_b'] ?? '');

    $alternativa_c =
        trim($_POST['alternativa_c'] ?? '');

    $alternativa_d =
        trim($_POST['alternativa_d'] ?? '');

    $correta =
        strtoupper(
            trim($_POST['correta'] ?? '')
        );

    $disciplina =
        trim($_POST['disciplina'] ?? '');

    $assunto =
        trim($_POST['assunto'] ?? '');



    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÃO
    |--------------------------------------------------------------------------
    */

    if (
        $pergunta === '' ||
        $alternativa_a === '' ||
        $alternativa_b === '' ||
        $alternativa_c === '' ||
        $alternativa_d === '' ||
        $correta === '' ||
        $disciplina === '' ||
        $assunto === ''
    ) {

        $erro =
            'Preencha todos os campos.';

    } elseif (
        !in_array(
            $correta,
            ['A', 'B', 'C', 'D'],
            true
        )
    ) {

        $erro =
            'A alternativa correta deve ser A, B, C ou D.';

    } else {


        /*
        |--------------------------------------------------------------------------
        | ATUALIZA
        |--------------------------------------------------------------------------
        |
        | Novamente usamos professor_id.
        |
        */

        $sql_update = "
            UPDATE questoes
            SET
                pergunta = ?,
                alternativa_a = ?,
                alternativa_b = ?,
                alternativa_c = ?,
                alternativa_d = ?,
                correta = ?,
                disciplina = ?,
                assunto = ?
            WHERE id = ?
              AND professor_id = ?
        ";


        $stmt_update =
            mysqli_prepare(
                $conexao,
                $sql_update
            );


        if (!$stmt_update) {

            $erro =
                'Erro ao preparar atualização: ' .
                mysqli_error($conexao);

        } else {

            mysqli_stmt_bind_param(
                $stmt_update,
                'ssssssssii',
                $pergunta,
                $alternativa_a,
                $alternativa_b,
                $alternativa_c,
                $alternativa_d,
                $correta,
                $disciplina,
                $assunto,
                $id,
                $professor_id
            );


            if (
                mysqli_stmt_execute(
                    $stmt_update
                )
            ) {

                mysqli_stmt_close(
                    $stmt_update
                );


                header(
                    'Location: questoes.php?editado=1'
                );

                exit;

            } else {

                $erro =
                    'Erro ao atualizar questão: ' .
                    mysqli_stmt_error(
                        $stmt_update
                    );

                mysqli_stmt_close(
                    $stmt_update
                );
            }
        }
    }



    /*
    |--------------------------------------------------------------------------
    | ATUALIZA DADOS DA TELA
    |--------------------------------------------------------------------------
    */

    $questao['pergunta'] =
        $pergunta;

    $questao['alternativa_a'] =
        $alternativa_a;

    $questao['alternativa_b'] =
        $alternativa_b;

    $questao['alternativa_c'] =
        $alternativa_c;

    $questao['alternativa_d'] =
        $alternativa_d;

    $questao['correta'] =
        $correta;

    $questao['disciplina'] =
        $disciplina;

    $questao['assunto'] =
        $assunto;
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

    <title>Editar Questão</title>


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #eef2ff,
                    #f8fafc
                );

            min-height: 100vh;

            color: #1e293b;

        }


        .topo {

            background:
                linear-gradient(
                    135deg,
                    #1e3a8a,
                    #2563eb
                );

            color: white;

            padding:
                22px 30px;

            box-shadow:
                0 5px 20px
                rgba(15,23,42,.15);

        }


        .topo-conteudo {

            width:
                min(
                    900px,
                    calc(100% - 20px)
                );

            margin: auto;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

        }


        .topo h1 {

            font-size: 22px;

            margin-bottom: 5px;

        }


        .topo p {

            font-size: 13px;

            opacity: .85;

        }


        .voltar {

            color: white;

            text-decoration: none;

            background:
                rgba(255,255,255,.15);

            padding:
                10px 15px;

            border-radius: 9px;

        }


        .container {

            width:
                min(
                    900px,
                    calc(100% - 30px)
                );

            margin:
                35px auto 60px;

        }


        .card {

            background: white;

            border:
                1px solid
                #e2e8f0;

            border-radius: 18px;

            padding: 28px;

            box-shadow:
                0 10px 35px
                rgba(15,23,42,.06);

        }


        .erro {

            background: #fee2e2;

            color: #991b1b;

            padding: 13px 16px;

            border-radius: 10px;

            margin-bottom: 20px;

        }


        .campo {

            margin-bottom: 18px;

        }


        label {

            display: block;

            margin-bottom: 7px;

            font-size: 13px;

            font-weight: 700;

            color: #334155;

        }


        input,
        textarea {

            width: 100%;

            padding:
                12px 13px;

            border:
                1px solid
                #cbd5e1;

            border-radius: 10px;

            font-family: inherit;

            font-size: 14px;

            outline: none;

        }


        textarea {

            min-height: 120px;

            resize: vertical;

        }


        input:focus,
        textarea:focus {

            border-color:
                #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37,99,235,.1);

        }


        .alternativas {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 15px;

        }


        .corretas {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 10px;

        }


        .correta-item input {

            display: none;

        }


        .correta-item label {

            text-align: center;

            border:
                1px solid
                #cbd5e1;

            border-radius: 9px;

            padding: 11px;

            cursor: pointer;

        }


        .correta-item input:checked + label {

            background:
                #2563eb;

            color: white;

            border-color:
                #2563eb;

        }


        .botoes {

            display: flex;

            gap: 10px;

            margin-top: 25px;

        }


        .btn {

            border: none;

            padding:
                12px 20px;

            border-radius: 10px;

            font-weight: 700;

            cursor: pointer;

            text-decoration: none;

            font-size: 14px;

        }


        .btn-salvar {

            background:
                #2563eb;

            color: white;

        }


        .btn-cancelar {

            background:
                #f1f5f9;

            color:
                #475569;

        }


        @media(max-width:700px) {

            .alternativas {

                grid-template-columns: 1fr;

            }


            .corretas {

                grid-template-columns:
                    repeat(2, 1fr);

            }


            .topo-conteudo {

                align-items:
                    flex-start;

                flex-direction:
                    column;

            }

        }

    </style>

    <link rel="stylesheet" href="../assets/css/questsystem.css">
</head>


<body class="qs-app">
<?php include_once __DIR__ . "/../includes/sidebar.php"; ?>


<header class="topo">

    <div class="topo-conteudo">

        <div>

            <h1>
                ✏ Editar questão
            </h1>

            <p>
                Atualize os dados da questão selecionada.
            </p>

        </div>


        <a
            href="questoes.php"
            class="voltar"
        >
            ← Voltar
        </a>

    </div>

</header>



<main class="container">


    <?php if ($erro !== ''): ?>

        <div class="erro">

            ⚠
            <?= htmlspecialchars($erro) ?>

        </div>

    <?php endif; ?>


    <section class="card">


        <form
            method="POST"
            action=""
        >


            <div class="campo">

                <label for="disciplina">
                    Disciplina
                </label>

                <input
                    type="text"
                    id="disciplina"
                    name="disciplina"
                    value="<?= htmlspecialchars($questao['disciplina']) ?>"
                    required
                >

            </div>



            <div class="campo">

                <label for="assunto">
                    Assunto
                </label>

                <input
                    type="text"
                    id="assunto"
                    name="assunto"
                    value="<?= htmlspecialchars($questao['assunto']) ?>"
                    required
                >

            </div>



            <div class="campo">

                <label for="pergunta">
                    Pergunta
                </label>

                <textarea
                    id="pergunta"
                    name="pergunta"
                    required
                ><?= htmlspecialchars($questao['pergunta']) ?></textarea>

            </div>



            <div class="alternativas">


                <div class="campo">

                    <label for="alternativa_a">
                        Alternativa A
                    </label>

                    <input
                        type="text"
                        id="alternativa_a"
                        name="alternativa_a"
                        value="<?= htmlspecialchars($questao['alternativa_a']) ?>"
                        required
                    >

                </div>


                <div class="campo">

                    <label for="alternativa_b">
                        Alternativa B
                    </label>

                    <input
                        type="text"
                        id="alternativa_b"
                        name="alternativa_b"
                        value="<?= htmlspecialchars($questao['alternativa_b']) ?>"
                        required
                    >

                </div>


                <div class="campo">

                    <label for="alternativa_c">
                        Alternativa C
                    </label>

                    <input
                        type="text"
                        id="alternativa_c"
                        name="alternativa_c"
                        value="<?= htmlspecialchars($questao['alternativa_c']) ?>"
                        required
                    >

                </div>


                <div class="campo">

                    <label for="alternativa_d">
                        Alternativa D
                    </label>

                    <input
                        type="text"
                        id="alternativa_d"
                        name="alternativa_d"
                        value="<?= htmlspecialchars($questao['alternativa_d']) ?>"
                        required
                    >

                </div>


            </div>



            <div class="campo">

                <label>
                    Alternativa correta
                </label>


                <div class="corretas">


                    <div class="correta-item">

                        <input
                            type="radio"
                            name="correta"
                            id="edit_a"
                            value="A"
                            <?= $questao['correta'] === 'A' ? 'checked' : '' ?>
                            required
                        >

                        <label for="edit_a">
                            A
                        </label>

                    </div>


                    <div class="correta-item">

                        <input
                            type="radio"
                            name="correta"
                            id="edit_b"
                            value="B"
                            <?= $questao['correta'] === 'B' ? 'checked' : '' ?>
                        >

                        <label for="edit_b">
                            B
                        </label>

                    </div>


                    <div class="correta-item">

                        <input
                            type="radio"
                            name="correta"
                            id="edit_c"
                            value="C"
                            <?= $questao['correta'] === 'C' ? 'checked' : '' ?>
                        >

                        <label for="edit_c">
                            C
                        </label>

                    </div>


                    <div class="correta-item">

                        <input
                            type="radio"
                            name="correta"
                            id="edit_d"
                            value="D"
                            <?= $questao['correta'] === 'D' ? 'checked' : '' ?>
                        >

                        <label for="edit_d">
                            D
                        </label>

                    </div>


                </div>

            </div>



            <div class="botoes">


                <button
                    type="submit"
                    class="btn btn-salvar"
                >
                    💾 Salvar alterações
                </button>


                <a
                    href="questoes.php"
                    class="btn btn-cancelar"
                >
                    Cancelar
                </a>


            </div>


        </form>


    </section>


</main>


</body>

</html>