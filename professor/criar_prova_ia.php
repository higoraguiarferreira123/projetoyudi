<?php

session_start();

require_once "../config/conexao.php";
require_once "../config/gemini.php";


/* =========================================================
   VERIFICAR LOGIN
   ========================================================= */

if (
    !isset($_SESSION['id']) ||
    !isset($_SESSION['tipo']) ||
    $_SESSION['tipo'] !== 'professor'
) {
    header("Location: ../auth/login.php");
    exit;
}


$professor_id = (int) $_SESSION['id'];


/* =========================================================
   VARIÁVEIS
   ========================================================= */

$mensagem = "";
$erro = "";

$questoes_geradas = [];

$titulo = "";
$disciplina = "";
$quantidade = 5;
$nivel = "Médio";
$tema = "";
$prompt_personalizado = "";


/* =========================================================
   DISCIPLINAS
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
   GERAR PROVA COM IA
   ========================================================= */

if (isset($_POST['gerar'])) {

    $titulo = trim($_POST['titulo'] ?? '');

    $disciplina = trim(
        $_POST['disciplina'] ?? ''
    );

    $quantidade = (int) (
        $_POST['quantidade'] ?? 5
    );

    $nivel = trim(
        $_POST['nivel'] ?? 'Médio'
    );

    $tema = trim(
        $_POST['tema'] ?? ''
    );

    $prompt_personalizado = trim(
        $_POST['prompt_personalizado'] ?? ''
    );


    /* =====================================================
       VALIDAÇÕES
       ===================================================== */

    if ($titulo === '') {

        $erro =
            "Informe o título da prova.";

    } elseif ($disciplina === '') {

        $erro =
            "Selecione uma disciplina.";

    } elseif (
        !in_array(
            $disciplina,
            $disciplinas,
            true
        )
    ) {

        $erro =
            "A disciplina selecionada é inválida.";

    } elseif (
        $quantidade < 1 ||
        $quantidade > 30
    ) {

        $erro =
            "A quantidade de questões deve estar entre 1 e 30.";

    } elseif (
        $tema === '' &&
        $prompt_personalizado === ''
    ) {

        $erro =
            "Informe o tema ou escreva uma instrução para a IA.";
    }


    /* =====================================================
       PROMPT DA IA
       ===================================================== */

    if ($erro === '') {

        $prompt = "

Você é um especialista em elaboração de avaliações escolares.

Crie uma prova de {$disciplina}.

Título da prova:
{$titulo}

Quantidade de questões:
{$quantidade}

Nível de dificuldade:
{$nivel}

Tema / Conteúdo:
{$tema}

Instruções adicionais do professor:
{$prompt_personalizado}


REGRAS OBRIGATÓRIAS:

1. Crie exatamente {$quantidade} questões.

2. Todas as questões devem ser de múltipla escolha.

3. Cada questão deve possuir exatamente quatro alternativas.

4. As alternativas devem ser A, B, C e D.

5. Somente uma alternativa pode estar correta.

6. A resposta correta deve ser A, B, C ou D.

7. Todas as questões devem pertencer à disciplina {$disciplina}.

8. As questões devem estar relacionadas ao tema informado.

9. Respeite o nível {$nivel}.

10. Evite questões ambíguas.

11. Evite informações incorretas.

12. Não repita questões.

13. Utilize linguagem adequada aos alunos.

14. Misture questões conceituais e contextualizadas quando possível.

15. Não coloque explicações fora do JSON.

16. Não utilize Markdown.

17. Não utilize ```json.

18. Retorne SOMENTE JSON válido.

19. Utilize exatamente esta estrutura:

{
    \"questoes\": [
        {
            \"pergunta\": \"Texto da questão\",
            \"alternativa_a\": \"Texto da alternativa A\",
            \"alternativa_b\": \"Texto da alternativa B\",
            \"alternativa_c\": \"Texto da alternativa C\",
            \"alternativa_d\": \"Texto da alternativa D\",
            \"correta\": \"A\"
        }
    ]
}

A propriedade correta deve conter somente A, B, C ou D.

Não adicione outras propriedades.
";


        /* =================================================
           CHAMAR GEMINI
           ================================================= */

        $respostaIA =
            consultarGemini($prompt);


        if (
            !isset(
                $respostaIA['sucesso']
            ) ||
            !$respostaIA['sucesso']
        ) {

            $erro =
                $respostaIA['erro']
                ??
                "A Gemini retornou um erro.";

        } else {

            $textoIA = trim(
                $respostaIA['texto'] ?? ''
            );


            /* =============================================
               LIMPAR MARKDOWN
               ============================================= */

            $textoIA = preg_replace(
                '/^```json\s*/i',
                '',
                $textoIA
            );

            $textoIA = preg_replace(
                '/^```\s*/i',
                '',
                $textoIA
            );

            $textoIA = preg_replace(
                '/\s*```$/',
                '',
                $textoIA
            );

            $textoIA = trim($textoIA);


            /* =============================================
               DECODIFICAR JSON
               ============================================= */

            $dadosIA = json_decode(
                $textoIA,
                true
            );


            if (
                !is_array($dadosIA) ||
                !isset($dadosIA['questoes']) ||
                !is_array($dadosIA['questoes'])
            ) {

                $erro =
                    "A IA retornou uma resposta em formato inválido.";

            } else {

                $questoes_geradas =
                    array_slice(
                        $dadosIA['questoes'],
                        0,
                        $quantidade
                    );


                /* =========================================
                   VALIDAR QUESTÕES
                   ========================================= */

                foreach (
                    $questoes_geradas
                    as $indice => $questao
                ) {

                    $campos = [
                        'pergunta',
                        'alternativa_a',
                        'alternativa_b',
                        'alternativa_c',
                        'alternativa_d',
                        'correta'
                    ];


                    foreach (
                        $campos as $campo
                    ) {

                        if (
                            !isset(
                                $questao[$campo]
                            ) ||
                            trim(
                                (string)
                                $questao[$campo]
                            ) === ''
                        ) {

                            $erro =
                                "A questão " .
                                ($indice + 1) .
                                " foi gerada de forma incompleta.";

                            break 2;
                        }
                    }


                    $correta = strtoupper(
                        trim(
                            $questao['correta']
                        )
                    );


                    if (
                        !in_array(
                            $correta,
                            ['A', 'B', 'C', 'D'],
                            true
                        )
                    ) {

                        $erro =
                            "A questão " .
                            ($indice + 1) .
                            " possui uma resposta correta inválida.";

                        break;
                    }


                    $questoes_geradas[$indice]['correta'] =
                        $correta;
                }


                /* =========================================
                   VERIFICAR QUANTIDADE
                   ========================================= */

                if (
                    $erro === '' &&
                    count($questoes_geradas)
                    < $quantidade
                ) {

                    $erro =
                        "A IA retornou apenas " .
                        count($questoes_geradas) .
                        " questões, mas foram solicitadas " .
                        $quantidade .
                        ".";
                }


                if ($erro !== '') {

                    $questoes_geradas = [];
                }
            }
        }
    }
}


/* =========================================================
   SALVAR PROVA E QUESTÕES
   ========================================================= */

if (
    isset($_POST['salvar_questoes'])
) {

    $titulo = trim(
        $_POST['titulo'] ?? ''
    );

    $disciplina = trim(
        $_POST['disciplina'] ?? ''
    );

    $tema = trim(
        $_POST['tema'] ?? ''
    );

    $questoes_json =
        $_POST['questoes_json'] ?? '';


    /* =====================================================
       VALIDAÇÕES
       ===================================================== */

    if ($titulo === '') {

        $erro =
            "O título da prova não foi informado.";

    } elseif ($disciplina === '') {

        $erro =
            "A disciplina não foi informada.";

    } elseif ($questoes_json === '') {

        $erro =
            "Nenhuma questão foi enviada para salvar.";
    }


    /* =====================================================
       DECODIFICAR QUESTÕES
       ===================================================== */

    if ($erro === '') {

        $questoes_salvar =
            json_decode(
                $questoes_json,
                true
            );


        if (
            !is_array($questoes_salvar) ||
            count($questoes_salvar) === 0
        ) {

            $erro =
                "As questões recebidas são inválidas.";
        }
    }


    /* =====================================================
       SALVAMENTO
       ===================================================== */

    if ($erro === '') {

        mysqli_begin_transaction(
            $conexao
        );


        try {

            /* =============================================
               CRIAR PROVA
               ============================================= */

            $sqlProva = "
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


            $stmtProva =
                mysqli_prepare(
                    $conexao,
                    $sqlProva
                );


            if (!$stmtProva) {

                throw new Exception(
                    "Erro ao preparar a criação da prova: " .
                    mysqli_error($conexao)
                );
            }


            mysqli_stmt_bind_param(
                $stmtProva,
                "ssi",
                $titulo,
                $disciplina,
                $professor_id
            );


            if (
                !mysqli_stmt_execute(
                    $stmtProva
                )
            ) {

                throw new Exception(
                    "Erro ao criar a prova: " .
                    mysqli_stmt_error($stmtProva)
                );
            }


            $prova_id =
                mysqli_insert_id(
                    $conexao
                );


            mysqli_stmt_close(
                $stmtProva
            );


            /* =============================================
               PREPARAR INSERÇÃO DAS QUESTÕES
               ============================================= */

            $sqlQuestao = "
                INSERT INTO questoes
                (
                    pergunta,
                    alternativa_a,
                    alternativa_b,
                    alternativa_c,
                    alternativa_d,
                    correta,
                    disciplina,
                    assunto,
                    professor_id
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ";


            $stmtQuestao =
                mysqli_prepare(
                    $conexao,
                    $sqlQuestao
                );


            if (!$stmtQuestao) {

                throw new Exception(
                    "Erro ao preparar o cadastro das questões: " .
                    mysqli_error($conexao)
                );
            }


            /* =============================================
               PREPARAR RELACIONAMENTO
               ============================================= */

            $sqlRelacionamento = "
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


            $stmtRelacionamento =
                mysqli_prepare(
                    $conexao,
                    $sqlRelacionamento
                );


            if (!$stmtRelacionamento) {

                throw new Exception(
                    "Erro ao preparar o relacionamento da prova: " .
                    mysqli_error($conexao)
                );
            }


            /* =============================================
               SALVAR CADA QUESTÃO
               ============================================= */

            foreach (
                $questoes_salvar
                as $indice => $questao
            ) {

                if (
                    !isset($questao['pergunta']) ||
                    !isset($questao['alternativa_a']) ||
                    !isset($questao['alternativa_b']) ||
                    !isset($questao['alternativa_c']) ||
                    !isset($questao['alternativa_d']) ||
                    !isset($questao['correta'])
                ) {

                    throw new Exception(
                        "A questão " .
                        ($indice + 1) .
                        " está incompleta."
                    );
                }


                $pergunta =
                    trim(
                        (string)
                        $questao['pergunta']
                    );


                $alternativa_a =
                    trim(
                        (string)
                        $questao['alternativa_a']
                    );


                $alternativa_b =
                    trim(
                        (string)
                        $questao['alternativa_b']
                    );


                $alternativa_c =
                    trim(
                        (string)
                        $questao['alternativa_c']
                    );


                $alternativa_d =
                    trim(
                        (string)
                        $questao['alternativa_d']
                    );


                $correta =
                    strtoupper(
                        trim(
                            (string)
                            $questao['correta']
                        )
                    );


                /* =========================================
                   ASSUNTO
                   =========================================

                   O tema informado pelo professor será
                   salvo como assunto da questão.
                   ========================================= */

                $assunto = $tema;


                /* =========================================
                   VALIDAR ALTERNATIVA
                   ========================================= */

                if (
                    !in_array(
                        $correta,
                        ['A', 'B', 'C', 'D'],
                        true
                    )
                ) {

                    throw new Exception(
                        "A questão " .
                        ($indice + 1) .
                        " possui uma alternativa correta inválida."
                    );
                }


                /* =========================================
                   IMPORTANTE

                   AQUI A QUESTÃO É SALVA COM:

                   professor_id = professor logado

                   disciplina = disciplina escolhida

                   assunto = tema informado
                   ========================================= */

                mysqli_stmt_bind_param(
                    $stmtQuestao,
                    "ssssssssi",
                    $pergunta,
                    $alternativa_a,
                    $alternativa_b,
                    $alternativa_c,
                    $alternativa_d,
                    $correta,
                    $disciplina,
                    $assunto,
                    $professor_id
                );


                if (
                    !mysqli_stmt_execute(
                        $stmtQuestao
                    )
                ) {

                    throw new Exception(
                        "Erro ao salvar a questão " .
                        ($indice + 1) .
                        ": " .
                        mysqli_stmt_error(
                            $stmtQuestao
                        )
                    );
                }


                /* =========================================
                   PEGAR ID DA QUESTÃO
                   ========================================= */

                $questao_id =
                    mysqli_insert_id(
                        $conexao
                    );


                if (!$questao_id) {

                    throw new Exception(
                        "Não foi possível obter o ID da questão."
                    );
                }


                /* =========================================
                   VINCULAR QUESTÃO À PROVA
                   ========================================= */

                mysqli_stmt_bind_param(
                    $stmtRelacionamento,
                    "ii",
                    $prova_id,
                    $questao_id
                );


                if (
                    !mysqli_stmt_execute(
                        $stmtRelacionamento
                    )
                ) {

                    throw new Exception(
                        "Erro ao vincular a questão " .
                        ($indice + 1) .
                        " à prova: " .
                        mysqli_stmt_error(
                            $stmtRelacionamento
                        )
                    );
                }
            }


            mysqli_stmt_close(
                $stmtQuestao
            );


            mysqli_stmt_close(
                $stmtRelacionamento
            );


            /* =============================================
               CONFIRMAR TRANSAÇÃO
               ============================================= */

            mysqli_commit(
                $conexao
            );


            $mensagem =
                "Prova criada e salva com sucesso! " .
                count($questoes_salvar) .
                " questões foram adicionadas ao seu Banco de Questões.";


            $questoes_geradas = [];

            $titulo = "";
            $disciplina = "";
            $tema = "";
            $prompt_personalizado = "";

        } catch (Exception $e) {

            mysqli_rollback(
                $conexao
            );


            $erro =
                $e->getMessage();
        }
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

<title>Criar Prova com IA</title>


<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

    background:
        linear-gradient(
            135deg,
            #eef4ff,
            #f8faff
        );

    color: #1f2937;

    min-height: 100vh;
}


/* =========================================================
   HEADER
   ========================================================= */

.header {

    background:
        linear-gradient(
            135deg,
            #6F3D62,
            #0066d6
        );

    color: white;

    padding: 30px;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.12);
}

.header-content {

    width: 95%;

    max-width: 1200px;

    margin: auto;
}

.header h1 {

    font-size: 30px;

    margin-bottom: 8px;
}

.header p {

    opacity: .9;
}


/* =========================================================
   CONTAINER
   ========================================================= */

.container {

    width: 95%;

    max-width: 1200px;

    margin: 35px auto;
}


/* =========================================================
   CARD
   ========================================================= */

.card {

    background: white;

    padding: 30px;

    border-radius: 18px;

    box-shadow:
        0 8px 30px
        rgba(0,0,0,.08);

    margin-bottom: 25px;
}

.card h2 {

    color: #6F3D62;

    margin-bottom: 25px;
}


/* =========================================================
   FORM
   ========================================================= */

.form-grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 20px;
}

.form-group {

    display: flex;

    flex-direction: column;
}

.form-group.full {

    grid-column:
        1 / -1;
}

label {

    font-weight: 600;

    margin-bottom: 8px;

    color: #374151;
}

input,
select,
textarea {

    width: 100%;

    padding: 13px 15px;

    border:
        1px solid #d7dce5;

    border-radius: 10px;

    font-size: 15px;

    outline: none;

    transition: .2s;
}

textarea {

    min-height: 130px;

    resize: vertical;
}

input:focus,
select:focus,
textarea:focus {

    border-color: #6F3D62;

    box-shadow:
        0 0 0 3px
        rgba(0,71,171,.1);
}


/* =========================================================
   BOTÕES
   ========================================================= */

.botoes {

    margin-top: 25px;

    display: flex;

    gap: 12px;

    flex-wrap: wrap;
}

.btn {

    display: inline-block;

    border: none;

    padding: 13px 22px;

    border-radius: 10px;

    cursor: pointer;

    font-weight: 700;

    font-size: 15px;

    transition: .2s;

    text-decoration: none;
}

.btn:hover {

    transform:
        translateY(-1px);
}

.btn-primary {

    background: #6F3D62;

    color: white;
}

.btn-primary:hover {

    background: #003783;
}

.btn-success {

    background: #198754;

    color: white;
}

.btn-success:hover {

    background: #146c43;
}

.btn-secondary {

    background: #6b7280;

    color: white;
}

.btn-secondary:hover {

    background: #4b5563;
}


/* =========================================================
   ALERTAS
   ========================================================= */

.alert {

    padding: 16px 20px;

    border-radius: 12px;

    margin-bottom: 20px;

    font-weight: 600;
}

.alert-error {

    background: #fee2e2;

    color: #991b1b;

    border:
        1px solid #fecaca;
}

.alert-success {

    background: #dcfce7;

    color: #166534;

    border:
        1px solid #bbf7d0;
}


/* =========================================================
   INFO
   ========================================================= */

.info {

    background: #f1f5f9;

    border-radius: 12px;

    padding: 15px;

    margin-bottom: 20px;

    color: #475569;

    line-height: 1.6;
}


/* =========================================================
   BADGE
   ========================================================= */

.badge {

    display: inline-block;

    padding: 6px 10px;

    border-radius: 20px;

    background: #e8f1ff;

    color: #6F3D62;

    font-size: 13px;

    font-weight: 700;

    margin-bottom: 15px;
}


/* =========================================================
   QUESTÃO
   ========================================================= */

.questao {

    border:
        1px solid #e1e5eb;

    border-left:
        5px solid #6F3D62;

    border-radius: 12px;

    padding: 22px;

    margin-bottom: 18px;

    background: #fff;
}

.questao-titulo {

    font-weight: 700;

    color: #6F3D62;

    margin-bottom: 12px;

    font-size: 17px;
}

.questao-pergunta {

    font-size: 16px;

    line-height: 1.6;

    margin-bottom: 15px;
}

.alternativa {

    padding: 10px 13px;

    margin: 7px 0;

    background: #f7f9fc;

    border-radius: 8px;
}

.correta {

    background: #dcfce7;

    color: #166534;

    font-weight: 700;

    border:
        1px solid #bbf7d0;
}


/* =========================================================
   VOLTAR
   ========================================================= */

.voltar {

    display: inline-block;

    margin-top: 5px;

    text-decoration: none;

    color: #6F3D62;

    font-weight: 600;
}


/* =========================================================
   RESPONSIVO
   ========================================================= */

@media(max-width:700px) {

    .form-grid {

        grid-template-columns: 1fr;
    }

    .form-group.full {

        grid-column: auto;
    }

    .header h1 {

        font-size: 24px;
    }

    .card {

        padding: 20px;
    }
}

</style>

    <link rel="stylesheet" href="../assets/css/questsystem.css">
</head>


<body class="qs-app">
<?php include_once __DIR__ . "/../includes/sidebar.php"; ?>


<!-- =====================================================
     CABEÇALHO
     ===================================================== -->

<div class="header">

    <div class="header-content">

        <h1>
            🤖 Criar Prova com IA
        </h1>

        <p>
            Gere avaliações personalizadas utilizando inteligência artificial.
        </p>

    </div>

</div>


<div class="container">


<!-- =====================================================
     ERRO
     ===================================================== -->

<?php if ($erro !== ""): ?>

    <div class="alert alert-error">

        ❌

        <?php
        echo htmlspecialchars(
            $erro
        );
        ?>

    </div>

<?php endif; ?>


<!-- =====================================================
     SUCESSO
     ===================================================== -->

<?php if ($mensagem !== ""): ?>

    <div class="alert alert-success">

        ✅

        <?php
        echo htmlspecialchars(
            $mensagem
        );
        ?>

    </div>

<?php endif; ?>


<!-- =====================================================
     FORMULÁRIO
     ===================================================== -->

<?php if (
    count($questoes_geradas) === 0
): ?>


<div class="card">

    <h2>
        Configurações da Prova
    </h2>


    <div class="info">

        <strong>
            Como funciona?
        </strong>

        <br>

        Informe as características da prova.
        A inteligência artificial irá criar as questões.

        <br>

        Depois você poderá revisar tudo antes de salvar.

    </div>


    <form
        method="POST"
    >


        <div class="form-grid">


            <!-- =========================================
                 TÍTULO
                 ========================================= -->

            <div class="form-group">

                <label
                    for="titulo"
                >
                    Título da prova
                </label>

                <input
                    type="text"
                    id="titulo"
                    name="titulo"
                    placeholder="Ex.: Avaliação de Biologia"
                    value="<?php
                    echo htmlspecialchars(
                        $titulo
                    );
                    ?>"
                    required
                >

            </div>


            <!-- =========================================
                 DISCIPLINA
                 ========================================= -->

            <div class="form-group">

                <label
                    for="disciplina"
                >
                    Disciplina
                </label>

                <select
                    id="disciplina"
                    name="disciplina"
                    required
                >

                    <option value="">
                        Selecione a disciplina
                    </option>


                    <?php foreach (
                        $disciplinas
                        as $d
                    ): ?>

                        <option
                            value="<?php
                            echo htmlspecialchars(
                                $d
                            );
                            ?>"
                            <?php
                            if (
                                $disciplina === $d
                            ) {
                                echo "selected";
                            }
                            ?>
                        >

                            <?php
                            echo htmlspecialchars(
                                $d
                            );
                            ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- =========================================
                 QUANTIDADE
                 ========================================= -->

            <div class="form-group">

                <label
                    for="quantidade"
                >
                    Quantidade de questões
                </label>

                <input
                    type="number"
                    id="quantidade"
                    name="quantidade"
                    min="1"
                    max="30"
                    value="<?php
                    echo $quantidade;
                    ?>"
                    required
                >

            </div>


            <!-- =========================================
                 NÍVEL
                 ========================================= -->

            <div class="form-group">

                <label
                    for="nivel"
                >
                    Nível de dificuldade
                </label>

                <select
                    id="nivel"
                    name="nivel"
                >

                    <option
                        value="Fácil"
                        <?php
                        if (
                            $nivel === "Fácil"
                        ) {
                            echo "selected";
                        }
                        ?>
                    >
                        Fácil
                    </option>

                    <option
                        value="Médio"
                        <?php
                        if (
                            $nivel === "Médio"
                        ) {
                            echo "selected";
                        }
                        ?>
                    >
                        Médio
                    </option>

                    <option
                        value="Difícil"
                        <?php
                        if (
                            $nivel === "Difícil"
                        ) {
                            echo "selected";
                        }
                        ?>
                    >
                        Difícil
                    </option>

                </select>

            </div>


            <!-- =========================================
                 TEMA
                 ========================================= -->

            <div class="form-group full">

                <label
                    for="tema"
                >
                    Tema / Conteúdo
                </label>

                <input
                    type="text"
                    id="tema"
                    name="tema"
                    placeholder="Ex.: Sistema imunológico"
                    value="<?php
                    echo htmlspecialchars(
                        $tema
                    );
                    ?>"
                >

            </div>


            <!-- =========================================
                 INSTRUÇÕES
                 ========================================= -->

            <div class="form-group full">

                <label
                    for="prompt_personalizado"
                >
                    Instruções para a IA
                </label>

                <textarea
                    id="prompt_personalizado"
                    name="prompt_personalizado"
                    placeholder="Ex.: Crie questões contextualizadas para alunos do ensino médio."
                ><?php
                echo htmlspecialchars(
                    $prompt_personalizado
                );
                ?></textarea>

            </div>


        </div>


        <div class="botoes">

            <button
                type="submit"
                name="gerar"
                class="btn btn-primary"
            >

                🤖 Gerar Prova com IA

            </button>


            <a
                href="dashboard.php"
                class="btn btn-secondary"
            >

                Voltar

            </a>

        </div>


    </form>

</div>


<?php else: ?>


<!-- =====================================================
     QUESTÕES GERADAS
     ===================================================== -->

<div class="card">

    <h2>
        Questões geradas pela IA
    </h2>


    <span class="badge">

        <?php
        echo htmlspecialchars(
            $disciplina
        );
        ?>

    </span>


    <div class="info">

        <strong>

            <?php
            echo htmlspecialchars(
                $titulo
            );
            ?>

        </strong>

        <br>

        Tema:

        <strong>

            <?php
            echo htmlspecialchars(
                $tema
            );
            ?>

        </strong>

        <br>

        Foram geradas

        <strong>

            <?php
            echo count(
                $questoes_geradas
            );
            ?>

        </strong>

        questões.

        <br><br>

        <strong>
            Revise as questões antes de salvar.
        </strong>

    </div>


    <!-- =================================================
         MOSTRAR QUESTÕES
         ================================================= -->

    <?php foreach (
        $questoes_geradas
        as $indice => $questao
    ): ?>


        <div class="questao">


            <div class="questao-titulo">

                Questão

                <?php
                echo $indice + 1;
                ?>

            </div>


            <div class="questao-pergunta">

                <?php
                echo nl2br(
                    htmlspecialchars(
                        $questao['pergunta']
                    )
                );
                ?>

            </div>


            <div class="alternativa">

                <strong>A)</strong>

                <?php
                echo htmlspecialchars(
                    $questao['alternativa_a']
                );
                ?>

            </div>


            <div class="alternativa">

                <strong>B)</strong>

                <?php
                echo htmlspecialchars(
                    $questao['alternativa_b']
                );
                ?>

            </div>


            <div class="alternativa">

                <strong>C)</strong>

                <?php
                echo htmlspecialchars(
                    $questao['alternativa_c']
                );
                ?>

            </div>


            <div class="alternativa">

                <strong>D)</strong>

                <?php
                echo htmlspecialchars(
                    $questao['alternativa_d']
                );
                ?>

            </div>


            <div class="alternativa correta">

                ✓ Resposta correta:

                <?php
                echo htmlspecialchars(
                    strtoupper(
                        $questao['correta']
                    )
                );
                ?>

            </div>


        </div>


    <?php endforeach; ?>


    <!-- =================================================
         SALVAR
         ================================================= -->

    <form
        method="POST"
    >


        <input
            type="hidden"
            name="titulo"
            value="<?php
            echo htmlspecialchars(
                $titulo
            );
            ?>"
        >


        <input
            type="hidden"
            name="disciplina"
            value="<?php
            echo htmlspecialchars(
                $disciplina
            );
            ?>"
        >


        <input
            type="hidden"
            name="tema"
            value="<?php
            echo htmlspecialchars(
                $tema
            );
            ?>"
        >


        <input
            type="hidden"
            name="questoes_json"
            value="<?php

            echo htmlspecialchars(
                json_encode(
                    $questoes_geradas,
                    JSON_UNESCAPED_UNICODE
                ),
                ENT_QUOTES
            );

            ?>"
        >


        <div class="botoes">


            <button
                type="submit"
                name="salvar_questoes"
                class="btn btn-success"
            >

                ✓ Salvar Prova no Banco

            </button>


            <a
                href="criar_prova_ia.php"
                class="btn btn-primary"
            >

                🔄 Criar Outra Prova

            </a>


            <a
                href="dashboard.php"
                class="btn btn-secondary"
            >

                Voltar

            </a>


        </div>


    </form>


</div>


<?php endif; ?>


</div>

</body>

</html>