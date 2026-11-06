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

        $nome = trim($_POST["nome"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $senha = $_POST["senha"] ?? "";
        $telefone = trim($_POST["telefone"] ?? "");
        $curso = trim($_POST["curso"] ?? "");
        $bairro = trim($_POST["bairro"] ?? "");
        $cidade = trim($_POST["cidade"] ?? "");
        $tipo = $_POST["tipo"] ?? "ambos";

        if (
            $nome === "" ||
            $email === "" ||
            $senha === "" ||
            $bairro === "" ||
            $cidade === ""
        ) {
            $erro = "Preencha todos os campos obrigatórios.";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = "Informe um e-mail válido.";

        } elseif (strlen($senha) < 8) {
            $erro = "A senha precisa ter pelo menos 8 caracteres.";

        } elseif (!in_array($tipo, ["passageiro", "motorista", "ambos"], true)) {
            $erro = "Selecione um tipo de conta válido.";

        } else {

            try {

                $pdo->beginTransaction();

                $senhaHash = password_hash(
                    $senha,
                    PASSWORD_DEFAULT
                );

                $stmt = $pdo->prepare("
                    INSERT INTO usuarios
                    (nome, email, senha, telefone, curso, bairro, cidade, tipo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $nome,
                    $email,
                    $senhaHash,
                    $telefone,
                    $curso,
                    $bairro,
                    $cidade,
                    $tipo
                ]);

                $idUsuario = $pdo->lastInsertId();

                criarCarteira($idUsuario);

                $pdo->commit();

                header("Location: login.php?cadastro=sucesso");
                exit;

            } catch (PDOException $erroBanco) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                if ($erroBanco->getCode() === "23000") {
                    $erro = "Este e-mail já está cadastrado.";
                } else {
                    error_log($erroBanco->getMessage());
                    $erro = "Não foi possível realizar o cadastro.";
                }
            }
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
    <title>Cadastro | UniRota</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="pagina-acesso">

    <main class="acesso-container">

        <a href="login.php" class="logo">
            <i></i> UniRota
        </a>

        <h1>Crie sua conta</h1>
        <p>Comece a compartilhar seus caminhos.</p>

        <?php if ($erro): ?>
            <div class="mensagem-sistema erro">
                <?= escapar($erro) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="formulario-acesso">

            <input type="hidden" name="csrf_token"
                   value="<?= escapar($token) ?>">

            <label>
                Nome completo
                <input type="text" name="nome" maxlength="100"
                       value="<?= escapar($_POST["nome"] ?? "") ?>"
                       required>
            </label>

            <label>
                E-mail
                <input type="email" name="email" maxlength="150"
                       value="<?= escapar($_POST["email"] ?? "") ?>"
                       required>
            </label>

            <label>
                Senha
                <input type="password" name="senha"
                       minlength="8" required>
            </label>

            <label>
                Telefone
                <input type="tel" name="telefone"
                       value="<?= escapar($_POST["telefone"] ?? "") ?>">
            </label>

            <label>
                Curso ou profissão
                <input type="text" name="curso"
                       value="<?= escapar($_POST["curso"] ?? "") ?>">
            </label>

            <label>
                Bairro
                <input type="text" name="bairro" required
                       value="<?= escapar($_POST["bairro"] ?? "") ?>">
            </label>

            <label>
                Cidade
                <input type="text" name="cidade"
                       value="<?= escapar($_POST["cidade"] ?? "Araçatuba") ?>"
                       required>
            </label>

            <label>
                Tipo de conta
                <select name="tipo" required>
                    <option value="ambos">Passageiro e motorista</option>
                    <option value="passageiro">Somente passageiro</option>
                    <option value="motorista">Somente motorista</option>
                </select>
            </label>

            <button type="submit" class="btn sun">
                Criar conta
            </button>

        </form>

        <p class="texto-acesso">
            Já possui uma conta?
            <a href="login.php">Entrar</a>
        </p>

    </main>

</body>
</html>