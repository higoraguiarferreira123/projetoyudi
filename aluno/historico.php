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

if (
    isset($_SESSION['tipo']) &&
    $_SESSION['tipo'] !== 'aluno'
) {
    header("Location: ../auth/login.php");
    exit();
}

$aluno_id = (int) $_SESSION['id'];


/*
|--------------------------------------------------------------------------
| ESTATÍSTICAS
|--------------------------------------------------------------------------
*/

$stmt_estatisticas = mysqli_prepare(
    $conexao,
    "
    SELECT
        COUNT(*) AS total,
        IFNULL(AVG(nota), 0) AS media,
        IFNULL(MAX(nota), 0) AS maior
    FROM resultados
    WHERE aluno_id = ?
    "
);


if (!$stmt_estatisticas) {
    die("Erro ao consultar as estatísticas.");
}


mysqli_stmt_bind_param(
    $stmt_estatisticas,
    "i",
    $aluno_id
);


mysqli_stmt_execute(
    $stmt_estatisticas
);


$resultado_estatisticas =
    mysqli_stmt_get_result(
        $stmt_estatisticas
    );


$estatisticas =
    mysqli_fetch_assoc(
        $resultado_estatisticas
    );


mysqli_stmt_close(
    $stmt_estatisticas
);


/*
|--------------------------------------------------------------------------
| BUSCAR HISTÓRICO
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        r.id AS resultado_id,
        p.titulo,
        p.disciplina,
        r.nota,
        r.data_realizacao
    FROM resultados r
    INNER JOIN provas p
        ON r.prova_id = p.id
    WHERE r.aluno_id = ?
    ORDER BY r.data_realizacao DESC
";


$stmt = mysqli_prepare(
    $conexao,
    $sql
);


if (!$stmt) {
    die("Erro ao consultar o histórico.");
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $aluno_id
);


mysqli_stmt_execute(
    $stmt
);


$resultado =
    mysqli_stmt_get_result(
        $stmt
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
        Meu Histórico | QuestSystem
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/questsystem.css"
    >

</head>


<body class="qs-app">


    <?php include_once __DIR__ . "/../includes/sidebar.php"; ?>


    <div class="com-sidebar">

        <main class="main-content">


            <!-- =====================================================
                 TOPO
            ====================================================== -->

            <div class="topbar">

                <div>

                    <h1>
                        Meu Histórico
                    </h1>

                    <p>
                        Acompanhe suas provas, notas e evolução.
                    </p>

                </div>


                <div class="topbar-user">

                    <div class="avatar">

                        <?php

                        echo strtoupper(
                            substr(
                                $_SESSION['nome'] ?? 'A',
                                0,
                                1
                            )
                        );

                        ?>

                    </div>


                    <div>

                        <strong>

                            <?php

                            echo htmlspecialchars(
                                $_SESSION['nome'] ?? 'Aluno'
                            );

                            ?>

                        </strong>

                        <span>
                            Aluno
                        </span>

                    </div>

                </div>

            </div>


            <!-- =====================================================
                 CABEÇALHO
            ====================================================== -->

            <section class="page-header">

                <div>

                    <span class="page-kicker">
                        DESEMPENHO
                    </span>

                    <h2>
                        Meu histórico de avaliações
                    </h2>

                    <p>
                        Veja todas as provas que você já realizou
                        e acompanhe suas notas.
                    </p>

                </div>


                <a
                    href="provas.php"
                    class="btn btn-primary"
                >
                    Ver Provas
                </a>

            </section>


            <!-- =====================================================
                 ESTATÍSTICAS
            ====================================================== -->

            <section class="stats-grid">


                <div class="stat-card">

                    <div class="stat-icon">
                        📝
                    </div>

                    <div class="stat-content">

                        <span class="stat-label">
                            Provas realizadas
                        </span>

                        <strong class="stat-value">
                            <?php
                            echo (int) $estatisticas['total'];
                            ?>
                        </strong>

                        <span class="stat-description">
                            Total de avaliações
                        </span>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        📊
                    </div>

                    <div class="stat-content">

                        <span class="stat-label">
                            Média geral
                        </span>

                        <strong class="stat-value">

                            <?php

                            echo number_format(
                                (float)
                                $estatisticas['media'],
                                1,
                                ',',
                                '.'
                            );

                            ?>

                        </strong>

                        <span class="stat-description">
                            Média das suas notas
                        </span>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        ⭐
                    </div>

                    <div class="stat-content">

                        <span class="stat-label">
                            Maior nota
                        </span>

                        <strong class="stat-value">

                            <?php

                            echo number_format(
                                (float)
                                $estatisticas['maior'],
                                1,
                                ',',
                                '.'
                            );

                            ?>

                        </strong>

                        <span class="stat-description">
                            Sua melhor nota
                        </span>

                    </div>

                </div>


            </section>


            <!-- =====================================================
                 HISTÓRICO
            ====================================================== -->

            <section class="content-card">


                <div class="section-heading">

                    <div>

                        <span class="section-kicker">
                            HISTÓRICO
                        </span>

                        <h2>
                            Provas realizadas
                        </h2>

                        <p>
                            Consulte suas avaliações anteriores.
                        </p>

                    </div>

                </div>


                <?php

                if (
                    $resultado &&
                    mysqli_num_rows($resultado) > 0
                ):

                ?>


                    <div class="table-wrapper">


                        <table class="qs-table">


                            <thead>

                                <tr>

                                    <th>
                                        Prova
                                    </th>

                                    <th>
                                        Disciplina
                                    </th>

                                    <th>
                                        Nota
                                    </th>

                                    <th>
                                        Data
                                    </th>

                                    <th>
                                        Resultado
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php while (
                                    $linha =
                                    mysqli_fetch_assoc($resultado)
                                ): ?>


                                    <tr>


                                        <!-- PROVA -->

                                        <td>

                                            <div class="table-primary-text">

                                                <?php

                                                echo htmlspecialchars(
                                                    $linha['titulo'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                );

                                                ?>

                                            </div>

                                        </td>


                                        <!-- DISCIPLINA -->

                                        <td>

                                            <span class="tag tag-primary">

                                                <?php

                                                echo htmlspecialchars(
                                                    $linha['disciplina'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                );

                                                ?>

                                            </span>

                                        </td>


                                        <!-- NOTA -->

                                        <td>

                                            <span class="grade-value">

                                                <?php

                                                echo number_format(
                                                    (float)
                                                    $linha['nota'],
                                                    1,
                                                    ',',
                                                    '.'
                                                );

                                                ?>

                                            </span>

                                        </td>


                                        <!-- DATA -->

                                        <td>

                                            <span class="table-date">

                                                <?php

                                                if (
                                                    !empty(
                                                        $linha[
                                                            'data_realizacao'
                                                        ]
                                                    )
                                                ) {

                                                    echo date(
                                                        "d/m/Y H:i",
                                                        strtotime(
                                                            $linha[
                                                                'data_realizacao'
                                                            ]
                                                        )
                                                    );

                                                } else {

                                                    echo "Não informada";
                                                }

                                                ?>

                                            </span>

                                        </td>


                                        <!-- RESULTADO -->

                                        <td>

                                            <a
                                                href="resultado_prova.php?id=<?php echo (int) $linha['resultado_id']; ?>"
                                                class="btn btn-primary btn-small"
                                            >
                                                Ver Resultado
                                            </a>

                                        </td>


                                    </tr>


                                <?php endwhile; ?>


                            </tbody>


                        </table>


                    </div>


                <?php else: ?>


                    <!-- =================================================
                         NENHUM RESULTADO
                    ================================================== -->

                    <div class="empty-state">

                        <div class="empty-icon">
                            📚
                        </div>

                        <h3>
                            Você ainda não realizou nenhuma prova
                        </h3>

                        <p>
                            Quando realizar uma avaliação,
                            ela aparecerá neste histórico.
                        </p>


                        <a
                            href="provas.php"
                            class="btn btn-primary"
                        >
                            Ver Provas Disponíveis
                        </a>

                    </div>


                <?php endif; ?>


            </section>


            <!-- =====================================================
                 ORIENTAÇÃO
            ====================================================== -->

            <?php

            if (
                (int) $estatisticas['total'] > 0
            ):

            ?>

                <section class="info-box">

                    <div class="info-box-icon">
                        ℹ
                    </div>

                    <div>

                        <strong>
                            Acompanhe sua evolução
                        </strong>

                        <p>
                            Abra o resultado de uma prova para conferir
                            seus acertos, erros, respostas dadas e
                            respostas corretas.
                        </p>

                    </div>

                </section>

            <?php endif; ?>


            <!-- =====================================================
                 AÇÕES
            ====================================================== -->

            <div class="bottom-actions">


                <a
                    href="dashboard.php"
                    class="btn btn-secondary"
                >
                    ← Voltar ao Dashboard
                </a>


                <a
                    href="provas.php"
                    class="btn btn-primary"
                >
                    Ver Provas
                </a>


            </div>


            <!-- =====================================================
                 RODAPÉ
            ====================================================== -->

            <div class="page-footer">

                <span>
                    QuestSystem
                </span>

                <span>
                    Histórico de avaliações
                </span>

            </div>


        </main>

    </div>

</body>

</html>