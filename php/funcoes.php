<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/conexao.php";

function usuarioLogado() {
    return isset($_SESSION["id_usuario"]);
}

function exigirLogin() {
    if (!usuarioLogado()) {
        header("Location: login.php");
        exit;
    }
}

function usuarioAtual() {
    global $pdo;

    if (!usuarioLogado()) {
        return null;
    }

    $stmt = $pdo->prepare(
        "SELECT * FROM usuarios WHERE id_usuario = ?"
    );

    $stmt->execute([$_SESSION["id_usuario"]]);

    return $stmt->fetch();
}

function criarCarteira($idUsuario) {
    global $pdo;

    $stmt = $pdo->prepare(
        "INSERT INTO carteiras (id_usuario) VALUES (?)"
    );

    $stmt->execute([$idUsuario]);
}

function gerarToken() {
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

function validarToken() {
    return isset($_POST["csrf_token"], $_SESSION["csrf_token"])
        && hash_equals(
            $_SESSION["csrf_token"],
            $_POST["csrf_token"]
        );
}

function escapar($valor) {
    return htmlspecialchars(
        (string)($valor ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}