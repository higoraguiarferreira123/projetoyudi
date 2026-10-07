<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

$sucesso = '';
$erro = '';
$nome = '';
$email = '';
$tipo = '';

if (isset($_POST['cadastrar'])) {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = (string)($_POST['senha'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');

    if ($nome === '' || $email === '' || $senha === '' || $tipo === '') {
        $erro = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve possuir pelo menos 6 caracteres.';
    } elseif (!in_array($tipo, ['aluno', 'professor'], true)) {
        $erro = 'Selecione um tipo de acesso válido.';
    } else {
        $stmt = mysqli_prepare($conexao, 'SELECT id FROM usuarios WHERE email = ? LIMIT 1');

        if (!$stmt) {
            $erro = 'Erro ao verificar o e-mail.';
        } else {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $resultado = mysqli_stmt_get_result($stmt);
            $existe = $resultado && mysqli_num_rows($resultado) > 0;
            mysqli_stmt_close($stmt);

            if ($existe) {
                $erro = 'Este e-mail já está cadastrado.';
            }
        }
    }

    if ($erro === '') {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conexao, 'INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)');

        if (!$stmt) {
            $erro = 'Erro ao preparar o cadastro.';
        } else {
            mysqli_stmt_bind_param($stmt, 'ssss', $nome, $email, $senhaHash, $tipo);

            if (mysqli_stmt_execute($stmt)) {
                $sucesso = 'Cadastro realizado com sucesso! Agora você já pode entrar.';
                $nome = '';
                $email = '';
                $tipo = '';
            } else {
                $erro = 'Erro ao cadastrar usuário: ' . mysqli_stmt_error($stmt);
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
<title>Criar Conta | QuestSystem</title>
<link rel="stylesheet" href="../assets/css/auth-modern.css">
</head>
<body class="qs-auth">
<div class="qs-auth-wrap">
    <section class="qs-auth-visual">
        <div class="qs-auth-kicker">Banco de questões e avaliações</div>
        <div class="qs-auth-brand"><div class="qs-auth-mark">QS</div><strong>QuestSystem</strong></div>
        <h1>Crie seu acesso e faça parte da plataforma.</h1>
        <p>Professores podem criar questões e provas. Alunos podem realizar avaliações e acompanhar seus resultados.</p>
        <div class="qs-auth-points">
            <div class="qs-auth-point"><span>✓</span> Interface simples e moderna</div>
            <div class="qs-auth-point"><span>✦</span> Recursos de inteligência artificial</div>
            <div class="qs-auth-point"><span>📊</span> Acompanhamento de desempenho</div>
        </div>
    </section>

    <section class="qs-auth-form">
        <span class="qs-auth-kicker-mobile">QUESTSYSTEM</span>
        <h2>Criar conta</h2>
        <div class="subtitle">Preencha os dados para começar.</div>

        <?php if ($sucesso !== ''): ?>
            <div class="qs-alert qs-alert-success"><span>✓</span><div><?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?></div></div>
        <?php endif; ?>

        <?php if ($erro !== ''): ?>
            <div class="qs-alert qs-alert-error"><span>!</span><div><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div></div>
        <?php endif; ?>

        <form method="POST">
            <div class="qs-field">
                <label for="nome">Nome completo</label>
                <input id="nome" type="text" name="nome" placeholder="Seu nome completo" value="<?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>" autocomplete="name" required>
            </div>

            <div class="qs-field">
                <label for="email">E-mail</label>
                <input id="email" type="email" name="email" placeholder="voce@exemplo.com" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" autocomplete="email" required>
            </div>

            <div class="qs-field">
                <label for="senha">Senha</label>
                <input id="senha" type="password" name="senha" placeholder="Crie uma senha" autocomplete="new-password" minlength="6" required>
                <small class="qs-field-help">Use pelo menos 6 caracteres.</small>
            </div>

            <div class="qs-field">
                <label for="tipo">Tipo de acesso</label>
                <select id="tipo" name="tipo" required>
                    <option value="">Selecione seu perfil</option>
                    <option value="aluno" <?= $tipo === 'aluno' ? 'selected' : '' ?>>Aluno</option>
                    <option value="professor" <?= $tipo === 'professor' ? 'selected' : '' ?>>Professor</option>
                </select>
            </div>

            <button class="qs-auth-button" type="submit" name="cadastrar">Criar minha conta</button>
        </form>

        <div class="qs-auth-link">Já possui conta? <a href="login.php">Entrar</a></div>
    </section>
</div>
</body>
</html>
