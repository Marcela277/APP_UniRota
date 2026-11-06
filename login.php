<?php

require_once __DIR__ . "/php/funcoes.php";

if (usuarioLogado()) {
    header("Location: index.php");
    exit;
}

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!validarToken()) {
        $erro = "Solicitação inválida. Atualize a página.";
    } else {

        $email = trim($_POST["email"] ?? "");
        $senha = $_POST["senha"] ?? "";

        $stmt = $pdo->prepare("
            SELECT id_usuario, senha
            FROM usuarios
            WHERE email = ?
        ");

        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario["senha"])) {

            session_regenerate_id(true);

            $_SESSION["id_usuario"] = $usuario["id_usuario"];

            header("Location: index.php");
            exit;

        } else {
            $erro = "E-mail ou senha incorretos.";
        }
    }
}

$token = gerarToken();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | UniRota</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="pagina-acesso">

    <main class="acesso-container">

        <a class="logo">
            <img src="img/logo.png" alt="UniRota">
        </a>

        <h1>Bem-vindo de volta!</h1>
        <p>Entre para encontrar ou oferecer uma carona.</p>

        <?php if (isset($_GET["cadastro"])): ?>
            <div class="mensagem-sistema sucesso">
                Cadastro realizado! Faça seu login.
            </div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="mensagem-sistema erro">
                <?= escapar($erro) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="formulario-acesso">

            <input type="hidden" name="csrf_token"
                   value="<?= escapar($token) ?>">

            <label>
                E-mail
                <input type="email" name="email" required>
            </label>

            <label>
                Senha
                <input type="password" name="senha" required>
            </label>

            <button type="submit" class="btn sun">
                Entrar
            </button>

        </form>

        <p class="texto-acesso">
            Ainda não possui uma conta?
            <a href="cadastro.php">Cadastre-se</a>
        </p>

    </main>

</body>
</html>