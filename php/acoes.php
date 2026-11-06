<?php

require_once __DIR__ . "/funcoes.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php");
    exit;
}

if (!usuarioLogado()) {
    header("Location: ../login.php");
    exit;
}

if (!validarToken()) {
    die("Solicitação inválida. Atualize a página.");
}

$idUsuario = $_SESSION["id_usuario"];
$acao = $_POST["acao"] ?? "";

function voltar($mensagem, $tipo = "sucesso") {
    $_SESSION["mensagem"] = [
        "texto" => $mensagem,
        "tipo" => $tipo
    ];

    header("Location: ../index.php");
    exit;
}

function coordenadaValida($valor, $min, $max) {
    if ($valor === null || $valor === "") {
        return null;
    }

    if (!is_numeric($valor) || $valor < $min || $valor > $max) {
        return null;
    }

    return (float)$valor;
}

try {

    // PUBLICAR CARONA
    if ($acao === "publicar_carona") {

        $origem = trim($_POST["origem"] ?? "");
        $bairroOrigem = trim($_POST["bairro_origem"] ?? "");
        $destino = trim($_POST["destino"] ?? "");
        $bairroDestino = trim($_POST["bairro_destino"] ?? "");
        $cidadeOrigem = trim($_POST["cidade_origem"] ?? "");
        $cidadeDestino = trim($_POST["cidade_destino"] ?? "");
        $dataHora = trim($_POST["data_hora"] ?? "");

        $vagas = filter_var($_POST["vagas"] ?? null, FILTER_VALIDATE_INT);
        $capacidade = filter_var($_POST["capacidade"] ?? null, FILTER_VALIDATE_INT);
        $preco = filter_var($_POST["preco"] ?? null, FILTER_VALIDATE_FLOAT);
        $distancia = filter_var($_POST["distancia"] ?? null, FILTER_VALIDATE_FLOAT);

        $modelo = trim($_POST["modelo"] ?? "");
        $cor = trim($_POST["cor"] ?? "");
        $placa = strtoupper(trim($_POST["placa"] ?? ""));

        $latitude = coordenadaValida($_POST["latitude"] ?? null, -90, 90);
        $longitude = coordenadaValida($_POST["longitude"] ?? null, -180, 180);

        if (
            $origem === "" || $bairroOrigem === "" ||
            $destino === "" || $bairroDestino === "" ||
            $cidadeOrigem === "" || $cidadeDestino === "" ||
            $dataHora === ""
        ) {
            voltar("Preencha todos os campos obrigatórios.", "erro");
        }

        if ($bairroOrigem === $bairroDestino && $cidadeOrigem === $cidadeDestino) {
            voltar("A origem e o destino precisam ser diferentes.", "erro");
        }

        if (!$vagas || !$capacidade || $vagas < 1 ||
            $capacidade < 1 || $capacidade > 8 || $vagas > $capacidade) {
            voltar("Confira a quantidade de vagas e a capacidade do veículo.", "erro");
        }

        if ($preco === false || $preco < 0 || $preco > 200 ||
            $distancia === false || $distancia <= 0) {
            voltar("Confira o preço e a distância informados.", "erro");
        }

        $dataTimestamp = strtotime($dataHora);

        if (!$dataTimestamp || $dataTimestamp <= time()) {
            voltar("A data da carona precisa ser futura.", "erro");
        }

        if ($modelo !== "" && $placa === "") {
            voltar("Informe a placa do veículo ou deixe o modelo em branco.", "erro");
        }

        $pdo->beginTransaction();

        $idVeiculo = null;

        if ($modelo !== "") {
            $stmt = $pdo->prepare("
                INSERT INTO veiculos
                (id_usuario, modelo, cor, placa, capacidade)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $idUsuario,
                $modelo,
                $cor ?: null,
                $placa,
                $capacidade
            ]);

            $idVeiculo = $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare("
            INSERT INTO caronas (
                id_motorista,
                id_veiculo,
                origem,
                bairro_origem,
                destino,
                bairro_destino,
                cidade_origem,
                cidade_destino,
                latitude,
                longitude,
                data_hora,
                vagas,
                preco,
                distancia,
                somente_mulheres,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'disponivel')
        ");

        $stmt->execute([
            $idUsuario,
            $idVeiculo,
            $origem,
            $bairroOrigem,
            $destino,
            $bairroDestino,
            $cidadeOrigem,
            $cidadeDestino,
            $latitude,
            $longitude,
            date("Y-m-d H:i:s", $dataTimestamp),
            $vagas,
            $preco,
            $distancia
        ]);

        $pdo->commit();

        voltar("Carona publicada com sucesso!");

    }

    // RESERVAR CARONA
    elseif ($acao === "reservar") {

        $idCarona = filter_var($_POST["id_carona"] ?? null, FILTER_VALIDATE_INT);
        $quantidade = filter_var($_POST["quantidade"] ?? null, FILTER_VALIDATE_INT);

        if (!$idCarona || !$quantidade || $quantidade < 1) {
            voltar("Dados da reserva inválidos.", "erro");
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT *
            FROM caronas
            WHERE id_carona = ?
            FOR UPDATE
        ");

        $stmt->execute([$idCarona]);
        $carona = $stmt->fetch();

        if (!$carona || $carona["status"] !== "disponivel") {
            $pdo->rollBack();
            voltar("Essa carona não está disponível.", "erro");
        }

        if ((int)$carona["id_motorista"] === (int)$idUsuario) {
            $pdo->rollBack();
            voltar("Você não pode reservar sua própria carona.", "erro");
        }

        if ((int)$carona["vagas"] < $quantidade) {
            $pdo->rollBack();
            voltar("Não existem vagas suficientes.", "erro");
        }

        if (strtotime($carona["data_hora"]) <= time()) {
            $pdo->rollBack();
            voltar("Essa carona já passou.", "erro");
        }

        $stmt = $pdo->prepare("
            SELECT id_reserva
            FROM reservas
            WHERE id_carona = ?
              AND id_passageiro = ?
              AND status IN ('pendente', 'confirmada', 'concluida')
        ");

        $stmt->execute([$idCarona, $idUsuario]);

        if ($stmt->fetch()) {
            $pdo->rollBack();
            voltar("Você já possui uma reserva para essa carona.", "erro");
        }

        $valorTotal = $quantidade * (float)$carona["preco"];

        $stmt = $pdo->prepare("
            INSERT INTO reservas
            (id_carona, id_passageiro, quantidade, valor_total, status)
            VALUES (?, ?, ?, ?, 'pendente')
        ");

        $stmt->execute([
            $idCarona,
            $idUsuario,
            $quantidade,
            $valorTotal
        ]);

        $idReserva = $pdo->lastInsertId();

        $vagasRestantes = (int)$carona["vagas"] - $quantidade;
        $statusCarona = $vagasRestantes === 0 ? "lotada" : "disponivel";

        $stmt = $pdo->prepare("
            UPDATE caronas
            SET vagas = ?, status = ?
            WHERE id_carona = ?
        ");

        $stmt->execute([
            $vagasRestantes,
            $statusCarona,
            $idCarona
        ]);

        $stmt = $pdo->prepare("
            SELECT id_carteira
            FROM carteiras
            WHERE id_usuario = ?
        ");

        $stmt->execute([$idUsuario]);
        $carteira = $stmt->fetch();

        if (!$carteira) {
            throw new Exception("Carteira do passageiro não encontrada.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO transacoes
            (id_carteira, id_reserva, tipo, descricao, valor, status)
            VALUES (?, ?, 'pagamento', ?, ?, 'pendente')
        ");

        $stmt->execute([
            $carteira["id_carteira"],
            $idReserva,
            "Ajuda de custo da carona #" . $idCarona,
            $valorTotal
        ]);

        $pdo->commit();

        voltar("Solicitação enviada ao motorista!");

    }

    // ACEITAR RESERVA
    elseif ($acao === "aceitar_reserva") {

        $idReserva = filter_var($_POST["id_reserva"] ?? null, FILTER_VALIDATE_INT);

        if (!$idReserva) {
            voltar("Reserva inválida.", "erro");
        }

        $stmt = $pdo->prepare("
            UPDATE reservas r
            JOIN caronas c ON c.id_carona = r.id_carona
            SET r.status = 'confirmada'
            WHERE r.id_reserva = ?
              AND c.id_motorista = ?
              AND r.status = 'pendente'
              AND c.status IN ('disponivel', 'lotada')
        ");

        $stmt->execute([$idReserva, $idUsuario]);

        if ($stmt->rowCount() === 0) {
            voltar("Não foi possível aceitar essa reserva.", "erro");
        }

        voltar("Reserva aceita!");

    }

    // RECUSAR RESERVA
    elseif ($acao === "recusar_reserva") {

        $idReserva = filter_var($_POST["id_reserva"] ?? null, FILTER_VALIDATE_INT);

        if (!$idReserva) {
            voltar("Reserva inválida.", "erro");
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT r.*, c.id_motorista, c.id_carona, c.status AS status_carona
            FROM reservas r
            JOIN caronas c ON c.id_carona = r.id_carona
            WHERE r.id_reserva = ?
              AND c.id_motorista = ?
            FOR UPDATE
        ");

        $stmt->execute([$idReserva, $idUsuario]);
        $reserva = $stmt->fetch();

        if (!$reserva || $reserva["status"] !== "pendente") {
            $pdo->rollBack();
            voltar("Essa solicitação não pode ser recusada.", "erro");
        }

        $stmt = $pdo->prepare("
            UPDATE reservas
            SET status = 'cancelada'
            WHERE id_reserva = ?
        ");
        $stmt->execute([$idReserva]);

        if (in_array($reserva["status_carona"], ["disponivel", "lotada"], true)) {
            $stmt = $pdo->prepare("
                UPDATE caronas
                SET vagas = vagas + ?, status = 'disponivel'
                WHERE id_carona = ?
            ");

            $stmt->execute([
                $reserva["quantidade"],
                $reserva["id_carona"]
            ]);
        }

        $stmt = $pdo->prepare("
            UPDATE transacoes
            SET status = 'cancelada'
            WHERE id_reserva = ?
              AND tipo = 'pagamento'
              AND status = 'pendente'
        ");

        $stmt->execute([$idReserva]);

        $pdo->commit();

        voltar("Solicitação recusada.");

    }

    // CANCELAR RESERVA PELO PASSAGEIRO
    elseif ($acao === "cancelar_reserva") {

        $idReserva = filter_var($_POST["id_reserva"] ?? null, FILTER_VALIDATE_INT);

        if (!$idReserva) {
            voltar("Reserva inválida.", "erro");
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT r.*, c.status AS status_carona, c.id_carona
            FROM reservas r
            JOIN caronas c ON c.id_carona = r.id_carona
            WHERE r.id_reserva = ?
              AND r.id_passageiro = ?
            FOR UPDATE
        ");

        $stmt->execute([$idReserva, $idUsuario]);
        $reserva = $stmt->fetch();

        if (!$reserva || !in_array($reserva["status"], ["pendente", "confirmada"], true)) {
            $pdo->rollBack();
            voltar("Essa reserva não pode ser cancelada.", "erro");
        }

        $stmt = $pdo->prepare("
            SELECT id_transacao
            FROM transacoes
            WHERE id_reserva = ?
              AND tipo = 'pagamento'
              AND status = 'concluida'
        ");

        $stmt->execute([$idReserva]);

        if ($stmt->fetch()) {
            $pdo->rollBack();
            voltar("O pagamento já foi registrado. Entre em contato com o motorista para combinar o estorno.", "erro");
        }

        $stmt = $pdo->prepare("
            UPDATE reservas
            SET status = 'cancelada'
            WHERE id_reserva = ?
        ");
        $stmt->execute([$idReserva]);

        if (in_array($reserva["status_carona"], ["disponivel", "lotada"], true)) {
            $stmt = $pdo->prepare("
                UPDATE caronas
                SET vagas = vagas + ?, status = 'disponivel'
                WHERE id_carona = ?
            ");

            $stmt->execute([
                $reserva["quantidade"],
                $reserva["id_carona"]
            ]);
        }

        $stmt = $pdo->prepare("
            UPDATE transacoes
            SET status = 'cancelada'
            WHERE id_reserva = ?
              AND tipo = 'pagamento'
              AND status = 'pendente'
        ");

        $stmt->execute([$idReserva]);

        $pdo->commit();

        voltar("Reserva cancelada.");

    }

    // REGISTRAR PAGAMENTO INFORMADO PELO PASSAGEIRO
    elseif ($acao === "registrar_pagamento") {

        $idReserva = filter_var($_POST["id_reserva"] ?? null, FILTER_VALIDATE_INT);

        if (!$idReserva) {
            voltar("Reserva inválida.", "erro");
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT r.*, c.id_motorista, c.id_carona
            FROM reservas r
            JOIN caronas c ON c.id_carona = r.id_carona
            WHERE r.id_reserva = ?
              AND r.id_passageiro = ?
            FOR UPDATE
        ");

        $stmt->execute([$idReserva, $idUsuario]);
        $reserva = $stmt->fetch();

        if (!$reserva || $reserva["status"] !== "confirmada") {
            $pdo->rollBack();
            voltar("A reserva precisa estar confirmada para registrar o pagamento.", "erro");
        }

        $stmt = $pdo->prepare("
            SELECT id_transacao
            FROM transacoes
            WHERE id_reserva = ?
              AND tipo = 'pagamento'
              AND status = 'pendente'
        ");

        $stmt->execute([$idReserva]);

        if (!$stmt->fetch()) {
            $pdo->rollBack();
            voltar("Esse pagamento já foi registrado ou não está pendente.", "erro");
        }

        $stmt = $pdo->prepare("
            SELECT id_carteira
            FROM carteiras
            WHERE id_usuario = ?
        ");

        $stmt->execute([$reserva["id_motorista"]]);
        $carteiraMotorista = $stmt->fetch();

        if (!$carteiraMotorista) {
            throw new Exception("Carteira do motorista não encontrada.");
        }

        $stmt = $pdo->prepare("
            UPDATE transacoes
            SET status = 'concluida'
            WHERE id_reserva = ?
              AND tipo = 'pagamento'
              AND status = 'pendente'
        ");

        $stmt->execute([$idReserva]);

        $stmt = $pdo->prepare("
            INSERT INTO transacoes
            (id_carteira, id_reserva, tipo, descricao, valor, status)
            VALUES (?, ?, 'recebimento', ?, ?, 'concluida')
        ");

        $stmt->execute([
            $carteiraMotorista["id_carteira"],
            $idReserva,
            "Ajuda de custo da carona #" . $reserva["id_carona"],
            $reserva["valor_total"]
        ]);

        $pdo->commit();

        voltar("Pagamento registrado na carteira do motorista.");

    }

    // FINALIZAR CARONA
    elseif ($acao === "finalizar_carona") {

        $idCarona = filter_var($_POST["id_carona"] ?? null, FILTER_VALIDATE_INT);

        if (!$idCarona) {
            voltar("Carona inválida.", "erro");
        }

        $stmt = $pdo->prepare("
            UPDATE caronas
            SET status = 'finalizada'
            WHERE id_carona = ?
              AND id_motorista = ?
              AND status IN ('disponivel', 'lotada')
        ");

        $stmt->execute([$idCarona, $idUsuario]);

        if ($stmt->rowCount() === 0) {
            voltar("Não foi possível finalizar essa carona.", "erro");
        }

        voltar("Carona finalizada!");

    }

    else {
        voltar("Ação não reconhecida.", "erro");
    }

} catch (Throwable $erro) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($erro->getMessage());

    voltar("Ocorreu um erro ao processar sua solicitação.", "erro");
}