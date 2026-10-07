<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

$erro = '';

if (isset($_POST['entrar'])) {
    $email = trim($_POST['email'] ?? '');
    $senha = (string)($_POST['senha'] ?? '');

    if ($email === '' || $senha === '') {
        $erro = 'Informe seu e-mail e sua senha.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } else {
        $stmt = mysqli_prepare($conexao, 'SELECT id, nome, email, senha, tipo FROM usuarios WHERE email = ? LIMIT 1');

        if (!$stmt) {
            $erro = 'Não foi possível preparar o login.';
        } else {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $resultado = mysqli_stmt_get_result($stmt);

            if ($resultado && ($usuario = mysqli_fetch_assoc($resultado))) {
                if (password_verify($senha, $usuario['senha'])) {
                    session_regenerate_id(true);
                    $_SESSION['id'] = (int)$usuario['id'];
                    $_SESSION['nome'] = $usuario['nome'];
                    $_SESSION['tipo'] = $usuario['tipo'];

                    if ($usuario['tipo'] === 'professor') {
                        header('Location: ../professor/dashboard.php');
                    } elseif ($usuario['tipo'] === 'aluno') {
                        header('Location: ../aluno/dashboard.php');
                    } else {
                        $erro = 'Tipo de usuário inválido.';
                    }

                    if ($erro === '') {
                        exit();
                    }
                } else {
                    $erro = 'E-mail ou senha inválidos.';
                }
            } else {
                $erro = 'E-mail ou senha inválidos.';
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Entrar | QuestSystem</title>
<link rel="stylesheet" href="../assets/css/auth-modern.css">
</head>
<body class="qs-auth">
<div class="qs-auth-wrap">
    <section class="qs-auth-visual">
        <div class="qs-auth-kicker">Plataforma educacional</div>
        <div class="qs-auth-brand"><div class="qs-auth-mark">QS</div><strong>QuestSystem</strong></div>
        <h1>Seu banco de questões, organizado de um jeito simples.</h1>
        <p>Crie avaliações, organize questões por disciplina e acompanhe os resultados dos alunos em um só lugar.</p>
        <div class="qs-auth-points">
            <div class="qs-auth-point"><span>▣</span> Banco de questões por professor</div>
            <div class="qs-auth-point"><span>✦</span> Geração de provas com IA</div>
            <div class="qs-auth-point"><span>▥</span> Resultados e gabaritos</div>
        </div>
    </section>

    <section class="qs-auth-form">
        <span class="qs-auth-kicker-mobile">QUESTSYSTEM</span>
        <h2>Entrar</h2>
        <div class="subtitle">Acesse sua conta para continuar.</div>

        <?php if ($erro !== ''): ?>
            <div class="qs-alert qs-alert-error"><span>!</span><div><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div></div>
        <?php endif; ?>

        <form method="POST">
            <div class="qs-field">
                <label for="email">E-mail</label>
                <input id="email" type="email" name="email" placeholder="voce@exemplo.com" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="email" required>
            </div>

            <div class="qs-field">
                <label for="senha">Senha</label>
                <input id="senha" type="password" name="senha" placeholder="Digite sua senha" autocomplete="current-password" required>
            </div>

            <button class="qs-auth-button" type="submit" name="entrar">Entrar no QuestSystem</button>
        </form>

        <div class="qs-auth-link">Ainda não tem conta? <a href="cadastro.php">Criar conta</a></div>
        <div class="qs-note">A estrutura técnica continua usando a pasta e o banco <strong>projetoyudi</strong>. A identidade visual do sistema é QuestSystem.</div>
    </section>
</div>
</body>
</html>
