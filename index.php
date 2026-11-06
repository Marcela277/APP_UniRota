<?php
require_once __DIR__ . "/php/funcoes.php";
exigirLogin();

$usuario = usuarioAtual();
$idUsuario = $_SESSION["id_usuario"];
$token = gerarToken();

function dinheiro($valor) {
    return "R$ " . number_format((float)$valor, 2, ",", ".");
}

// Filtros
$origem = trim($_GET["origem"] ?? "");
$destino = trim($_GET["destino"] ?? "");
$data = trim($_GET["data"] ?? "");

// Caronas disponíveis
$sql = "
    SELECT c.*, u.nome AS motorista, u.curso,
           v.modelo, v.cor
    FROM caronas c
    JOIN usuarios u ON u.id_usuario = c.id_motorista
    LEFT JOIN veiculos v ON v.id_veiculo = c.id_veiculo
    WHERE c.status = 'disponivel'
      AND c.vagas > 0
      AND c.data_hora >= NOW()
      AND c.id_motorista <> ?
";

$params = [$idUsuario];

if ($origem !== "") {
    $sql .= " AND (c.origem LIKE ? OR c.bairro_origem LIKE ?)";
    $params[] = "%$origem%";
    $params[] = "%$origem%";
}

if ($destino !== "") {
    $sql .= " AND (c.destino LIKE ? OR c.bairro_destino LIKE ?)";
    $params[] = "%$destino%";
    $params[] = "%$destino%";
}

if ($data !== "") {
    $sql .= " AND DATE(c.data_hora) = ?";
    $params[] = $data;
}

$sql .= " ORDER BY c.data_hora ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$caronas = $stmt->fetchAll();

// Localização do passageiro para ordenar por proximidade
$latBusca = filter_var($_GET["latitude_busca"] ?? null, FILTER_VALIDATE_FLOAT);
$lonBusca = filter_var($_GET["longitude_busca"] ?? null, FILTER_VALIDATE_FLOAT);

if ($latBusca !== false && $lonBusca !== false &&
    $latBusca !== null && $lonBusca !== null &&
    $latBusca >= -90 && $latBusca <= 90 &&
    $lonBusca >= -180 && $lonBusca <= 180) {

    $distanciaPontos = function ($lat1, $lon1, $lat2, $lon2) {
        $raio = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) ** 2;

        return 2 * $raio * asin(sqrt($a));
    };

    usort($caronas, function ($a, $b) use ($latBusca, $lonBusca, $distanciaPontos) {
        $da = ($a["latitude"] !== null && $a["longitude"] !== null)
            ? $distanciaPontos($latBusca, $lonBusca, $a["latitude"], $a["longitude"])
            : PHP_FLOAT_MAX;

        $db = ($b["latitude"] !== null && $b["longitude"] !== null)
            ? $distanciaPontos($latBusca, $lonBusca, $b["latitude"], $b["longitude"])
            : PHP_FLOAT_MAX;

        return $da <=> $db;
    });
}

// Reservas feitas pelo passageiro
$stmt = $pdo->prepare("
    SELECT r.*, c.origem, c.destino, c.data_hora,
           u.nome AS motorista
    FROM reservas r
    JOIN caronas c ON c.id_carona = r.id_carona
    JOIN usuarios u ON u.id_usuario = c.id_motorista
    WHERE r.id_passageiro = ?
    ORDER BY r.data_reserva DESC
");
$stmt->execute([$idUsuario]);
$minhasReservas = $stmt->fetchAll();

// Reservas recebidas pelo motorista
$stmt = $pdo->prepare("
    SELECT r.*, c.origem, c.destino, c.data_hora,
           u.nome AS passageiro, u.telefone
    FROM reservas r
    JOIN caronas c ON c.id_carona = r.id_carona
    JOIN usuarios u ON u.id_usuario = r.id_passageiro
    WHERE c.id_motorista = ?
    ORDER BY r.data_reserva DESC
");
$stmt->execute([$idUsuario]);
$reservasRecebidas = $stmt->fetchAll();

// Caronas publicadas
$stmt = $pdo->prepare("
    SELECT * FROM caronas
    WHERE id_motorista = ?
    ORDER BY data_hora DESC
");
$stmt->execute([$idUsuario]);
$minhasCaronas = $stmt->fetchAll();

// Resumo da carteira
$stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN tipo='pagamento' AND status='pendente'
            THEN valor ELSE 0 END),0) AS pendente,
        COALESCE(SUM(CASE WHEN tipo='pagamento' AND status='concluida'
            THEN valor ELSE 0 END),0) AS gastos,
        COALESCE(SUM(CASE WHEN tipo='recebimento' AND status='concluida'
            THEN valor ELSE 0 END),0) AS recebimentos
    FROM transacoes
    WHERE id_carteira = (
        SELECT id_carteira FROM carteiras WHERE id_usuario = ?
    )
");
$stmt->execute([$idUsuario]);
$carteira = $stmt->fetch();

// Histórico financeiro
$stmt = $pdo->prepare("
    SELECT t.*
    FROM transacoes t
    JOIN carteiras c ON c.id_carteira = t.id_carteira
    WHERE c.id_usuario = ?
    ORDER BY t.data_transacao DESC
    LIMIT 30
");
$stmt->execute([$idUsuario]);
$transacoes = $stmt->fetchAll();

function mostrarMensagem() {
    if (!empty($_SESSION["mensagem"])) {
        echo '<div class="mensagem-sistema ' .
            escapar($_SESSION["mensagem"]["tipo"]) . '">' .
            escapar($_SESSION["mensagem"]["texto"]) .
            '</div>';

        unset($_SESSION["mensagem"]);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>UniRota – Caronas sustentáveis em Araçatuba</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;700;800&family=Inter:wght@400;500;600&display=swap"
          rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">

    <script src="js/app.js" defer></script>
    <script src="js/calculo.js" defer></script>
</head>

<body>

<header>
    <a href="#buscar" class="logo">
    <img src="img/logo.png" alt="UniRota">
</a>

    <nav aria-label="Menu principal">
        <a href="#buscar" data-v="buscar">Buscar</a>
        <a href="#oferecer" data-v="oferecer">Oferecer</a>
        <a href="#viagens" data-v="viagens">Minhas viagens</a>
        <a href="#carteira" data-v="carteira">Carteira</a>
        <a href="#impacto" data-v="impacto">Calculadora</a>
        <a href="#como-funciona" data-v="como-funciona">Como funciona</a>
    </nav>

    <div class="usuario-logado">
        Olá, <?= escapar($usuario["nome"]) ?>
        <a href="logout.php">Sair</a>
    </div>
</header>

<main>

<?php mostrarMensagem(); ?>

<!-- BUSCAR -->
<section class="view" id="v-buscar">

    <div class="hero">
        <div>
            <h1>Divida a rota. Chegue ao seu destino.</h1>

            <p>
                Encontre pessoas que fazem trajetos parecidos com o seu
                em Araçatuba e região. Compartilhe os custos e o caminho.
            </p>

            <div class="stats">
                <div>
                    <strong><?= count($caronas) ?></strong>
                    caronas encontradas
                </div>

                <div>
                    <strong>
                        <?= array_sum(array_column($caronas, "vagas")) ?>
                    </strong>
                    lugares disponíveis
                </div>
            </div>
        </div>

        <form class="panel busca-form" id="busca" method="GET" action="index.php#buscar">

            <label>
                Saindo de
                <input type="text" name="origem"
                       placeholder="Bairro ou rua de saída"
                       value="<?= escapar($origem) ?>">
            </label>

            <label>
                Indo para
                <input type="text" name="destino"
                       placeholder="Bairro ou rua de destino"
                       value="<?= escapar($destino) ?>">
            </label>

            <div class="row">
                <label>
                    Data
                    <input type="date" name="data"
                           value="<?= escapar($data) ?>" id="dataBusca">
                </label>
            </div>

            <input type="hidden" name="latitude_busca" id="latitudeBusca">
            <input type="hidden" name="longitude_busca" id="longitudeBusca">

            <button class="btn sun" type="submit">
                Buscar caronas
            </button>

            <button class="btn ghost" type="button" id="localizarUsuario">
                Usar minha localização
            </button>

            <p id="mensagemLocalizacao" class="texto-auxiliar"></p>

        </form>
    </div>

    <div class="route" aria-hidden="true">
        <svg viewBox="0 0 700 120">
            <path d="M40 70 C180 10 260 110 360 60 S560 20 660 60"
                  fill="none" stroke="currentColor" stroke-width="4"
                  stroke-dasharray="2 10" stroke-linecap="round" opacity=".45"/>
            <circle cx="40" cy="70" r="12" fill="#ffb400"
                    stroke="currentColor" stroke-width="4"/>
            <rect x="648" y="48" width="24" height="24" rx="5"
                  fill="currentColor"/>
        </svg>
    </div>

    <h2>Caronas disponíveis</h2>

    <div class="list">

        <?php if ($caronas): ?>

            <?php foreach ($caronas as $carona): ?>

                <article class="ride">

                    <div class="time">
                        <?= date("H:i", strtotime($carona["data_hora"])) ?>
                        <small>
                            <?= date("d/m/Y", strtotime($carona["data_hora"])) ?>
                        </small>
                    </div>

                    <div>
                        <div class="path">
                            <?= escapar($carona["bairro_origem"]) ?>
                            <span>para</span>
                            <?= escapar($carona["bairro_destino"]) ?>
                        </div>

                        <div class="meta">
                            <span>Motorista: <?= escapar($carona["motorista"]) ?></span>

                            <?php if ($carona["curso"]): ?>
                                <span><?= escapar($carona["curso"]) ?></span>
                            <?php endif; ?>

                            <span>
                                <?= (int)$carona["vagas"] ?>
                                <?= (int)$carona["vagas"] === 1 ? "vaga" : "vagas" ?>
                            </span>

                            <span><?= escapar($carona["cidade_origem"]) ?> → <?= escapar($carona["cidade_destino"]) ?></span>

                            <?php if ($carona["somente_mulheres"]): ?>
                                <span class="tag f">Só mulheres</span>
                            <?php endif; ?>

                            <?php if ($carona["modelo"]): ?>
                                <span>
                                    <?= escapar($carona["modelo"]) ?>
                                    <?= escapar($carona["cor"]) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <p class="texto-auxiliar">
                            Saída: <?= escapar($carona["origem"]) ?><br>
                            Destino: <?= escapar($carona["destino"]) ?>
                        </p>
                    </div>

                    <div class="price">
                        <b><?= dinheiro($carona["preco"]) ?></b>
                        <small>por pessoa</small>

                        <form method="POST" action="php/acoes.php">
                            <input type="hidden" name="csrf_token"
                                   value="<?= escapar($token) ?>">
                            <input type="hidden" name="acao" value="reservar">
                            <input type="hidden" name="id_carona"
                                   value="<?= (int)$carona["id_carona"] ?>">

                            <label>
                                Lugares
                                <select name="quantidade">
                                    <?php for ($i = 1; $i <= min(3, (int)$carona["vagas"]); $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </label>

                            <button class="btn sun" type="submit">
                                Reservar
                            </button>
                        </form>
                    </div>

                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty">
                Nenhuma carona encontrada.
                Experimente alterar os filtros ou oferecer uma carona.
            </div>

        <?php endif; ?>

    </div>

</section>

<!-- OFERECER -->
<section class="view" id="v-oferecer">

    <div class="narrow">

        <h1>Ofereça uma carona</h1>

        <p class="texto-auxiliar">
            Cadastre seu trajeto e compartilhe os custos da viagem.
        </p>

        <form class="panel" method="POST" action="php/acoes.php">

            <input type="hidden" name="csrf_token"
                   value="<?= escapar($token) ?>">
            <input type="hidden" name="acao" value="publicar_carona">

            <div class="row">
                <label>
                    Endereço de saída
                    <input type="text" name="origem" required>
                </label>

                <label>
                    Bairro de saída
                    <input type="text" name="bairro_origem" required>
                </label>
            </div>

            <div class="row">
                <label>
                    Endereço de destino
                    <input type="text" name="destino" required>
                </label>

                <label>
                    Bairro de destino
                    <input type="text" name="bairro_destino" required>
                </label>
            </div>

            <div class="row">
                <label>
                    Cidade de saída
                    <input type="text" name="cidade_origem"
                           value="<?= escapar($usuario["cidade"]) ?>" required>
                </label>

                <label>
                    Cidade de destino
                    <input type="text" name="cidade_destino"
                           value="<?= escapar($usuario["cidade"]) ?>" required>
                </label>
            </div>

            <div class="row">
                <label>
                    Data e horário
                    <input type="datetime-local" name="data_hora"
                           id="dataHoraOferta" required>
                </label>

                <label>
                    Vagas disponíveis
                    <input type="number" name="vagas"
                           min="1" max="8" value="3" required>
                </label>
            </div>

            <div class="row">
                <label>
                    Ajuda de custo por pessoa (R$)
                    <input type="number" name="preco"
                           min="0" max="200" step="0.01" required>
                </label>

                <label>
                    Distância aproximada (km)
                    <input type="number" name="distancia"
                           min="0.1" step="0.1" required>
                </label>
            </div>

            <div class="row">
                <label>
                    Modelo do veículo
                    <input type="text" name="modelo"
                           placeholder="Opcional">
                </label>

                <label>
                    Cor do veículo
                    <input type="text" name="cor"
                           placeholder="Opcional">
                </label>
            </div>

            <div class="row">
                <label>
                    Placa
                    <input type="text" name="placa"
                           maxlength="10" placeholder="Opcional">
                </label>

                <label>
                    Capacidade do veículo
                    <input type="number" name="capacidade"
                           min="1" max="8" value="4" required>
                </label>
            </div>

            <input type="hidden" name="latitude" id="latitudeOferta">
            <input type="hidden" name="longitude" id="longitudeOferta">

            <button type="button" class="btn ghost" id="localizarOferta">
                Registrar minha localização de saída
            </button>

            <p id="mensagemOferta" class="texto-auxiliar"></p>

            <label class="chk">
                <input type="checkbox" name="somente_mulheres" value="1">
                Carona exclusiva para mulheres
            </label>

            <button type="submit" class="btn sun">
                Publicar carona
            </button>

        </form>

    </div>

</section>

<!-- MINHAS VIAGENS -->
<section class="view" id="v-viagens">

    <h1>Minhas viagens</h1>

    <h2>Reservas que solicitei</h2>

    <div class="list">

        <?php if ($minhasReservas): ?>

            <?php foreach ($minhasReservas as $reserva): ?>

                <article class="ride">
                    <div>
                        <div class="path">
                            <?= escapar($reserva["origem"]) ?>
                            <span>para</span>
                            <?= escapar($reserva["destino"]) ?>
                        </div>

                        <div class="meta">
                            <span>Motorista: <?= escapar($reserva["motorista"]) ?></span>
                            <span><?= date("d/m/Y H:i", strtotime($reserva["data_hora"])) ?></span>
                            <span><?= (int)$reserva["quantidade"] ?> vaga(s)</span>
                            <span><?= dinheiro($reserva["valor_total"]) ?></span>
                            <span>Status: <?= escapar($reserva["status"]) ?></span>
                        </div>
                    </div>

                    <div class="price">

                        <?php if (in_array($reserva["status"], ["pendente", "confirmada"], true)): ?>

                            <form method="POST" action="php/acoes.php">
                                <input type="hidden" name="csrf_token" value="<?= escapar($token) ?>">
                                <input type="hidden" name="acao" value="cancelar_reserva">
                                <input type="hidden" name="id_reserva" value="<?= (int)$reserva["id_reserva"] ?>">
                                <button class="btn ghost" type="submit">Cancelar</button>
                            </form>

                        <?php endif; ?>

                        <?php if ($reserva["status"] === "confirmada"): ?>

                            <form method="POST" action="php/acoes.php"
                                  onsubmit="return confirm('Você confirma que já pagou o motorista?');">
                                <input type="hidden" name="csrf_token" value="<?= escapar($token) ?>">
                                <input type="hidden" name="acao" value="registrar_pagamento">
                                <input type="hidden" name="id_reserva" value="<?= (int)$reserva["id_reserva"] ?>">
                                <button class="btn sun" type="submit">
                                    Confirmar pagamento
                                </button>
                            </form>

                        <?php endif; ?>

                    </div>
                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty">Você ainda não solicitou nenhuma carona.</div>

        <?php endif; ?>

    </div>

    <h2>Solicitações recebidas</h2>

    <div class="list">

        <?php if ($reservasRecebidas): ?>

            <?php foreach ($reservasRecebidas as $reserva): ?>

                <article class="ride">
                    <div>
                        <div class="path">
                            <?= escapar($reserva["origem"]) ?>
                            <span>para</span>
                            <?= escapar($reserva["destino"]) ?>
                        </div>

                        <div class="meta">
                            <span>Passageiro: <?= escapar($reserva["passageiro"]) ?></span>
                            <span>Telefone: <?= escapar($reserva["telefone"]) ?></span>
                            <span><?= (int)$reserva["quantidade"] ?> vaga(s)</span>
                            <span><?= dinheiro($reserva["valor_total"]) ?></span>
                            <span>Status: <?= escapar($reserva["status"]) ?></span>
                        </div>
                    </div>

                    <div class="price">

                        <?php if ($reserva["status"] === "pendente"): ?>

                            <form method="POST" action="php/acoes.php">
                                <input type="hidden" name="csrf_token" value="<?= escapar($token) ?>">
                                <input type="hidden" name="acao" value="aceitar_reserva">
                                <input type="hidden" name="id_reserva" value="<?= (int)$reserva["id_reserva"] ?>">
                                <button class="btn sun" type="submit">Aceitar</button>
                            </form>

                            <form method="POST" action="php/acoes.php">
                                <input type="hidden" name="csrf_token" value="<?= escapar($token) ?>">
                                <input type="hidden" name="acao" value="recusar_reserva">
                                <input type="hidden" name="id_reserva" value="<?= (int)$reserva["id_reserva"] ?>">
                                <button class="btn ghost" type="submit">Recusar</button>
                            </form>

                        <?php endif; ?>

                    </div>
                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty">Você ainda não recebeu solicitações.</div>

        <?php endif; ?>

    </div>

    <h2>Caronas que publiquei</h2>

    <div class="list">

        <?php if ($minhasCaronas): ?>

            <?php foreach ($minhasCaronas as $carona): ?>

                <article class="ride">
                    <div>
                        <div class="path">
                            <?= escapar($carona["bairro_origem"]) ?>
                            <span>para</span>
                            <?= escapar($carona["bairro_destino"]) ?>
                        </div>

                        <div class="meta">
                            <span><?= date("d/m/Y H:i", strtotime($carona["data_hora"])) ?></span>
                            <span><?= (int)$carona["vagas"] ?> vaga(s) restantes</span>
                            <span>Status: <?= escapar($carona["status"]) ?></span>
                        </div>
                    </div>

                    <?php if (in_array($carona["status"], ["disponivel", "lotada"], true)): ?>
                        <form method="POST" action="php/acoes.php">
                            <input type="hidden" name="csrf_token" value="<?= escapar($token) ?>">
                            <input type="hidden" name="acao" value="finalizar_carona">
                            <input type="hidden" name="id_carona" value="<?= (int)$carona["id_carona"] ?>">
                            <button class="btn ghost" type="submit">Finalizar</button>
                        </form>
                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty">Você ainda não publicou caronas.</div>

        <?php endif; ?>

    </div>

</section>

<!-- CARTEIRA -->
<section class="view" id="v-carteira">

    <div class="narrow">
        <h1>Minha carteira</h1>

        <p class="texto-auxiliar">
            Acompanhe os valores das ajudas de custo das suas viagens.
        </p>

        <div class="stats carteira-resumo">
            <div>
                <strong><?= dinheiro($carteira["gastos"]) ?></strong>
                Total gasto
            </div>

            <div>
                <strong><?= dinheiro($carteira["pendente"]) ?></strong>
                Pendente a pagar
            </div>

            <div>
                <strong><?= dinheiro($carteira["recebimentos"]) ?></strong>
                Recebimentos registrados
            </div>
        </div>

        <h2>Histórico</h2>

        <?php if ($transacoes): ?>

            <?php foreach ($transacoes as $transacao): ?>

                <div class="orow">
                    <span>
                        <?= escapar($transacao["descricao"]) ?><br>
                        <small><?= date("d/m/Y H:i", strtotime($transacao["data_transacao"])) ?></small>
                    </span>

                    <b>
                        <?= dinheiro($transacao["valor"]) ?><br>
                        <small><?= escapar($transacao["status"]) ?></small>
                    </b>
                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty">Nenhuma movimentação registrada.</div>

        <?php endif; ?>

    </div>

</section>

<!-- CALCULADORA -->
<section class="view" id="v-impacto">

    <div class="narrow">

        <h1>Quanto sua carona economiza?</h1>

        <p class="texto-auxiliar">
            Informe os dados da viagem para estimar o custo do combustível.
        </p>

        <form class="panel" id="formCalculadora">

            <div class="row">
                <label>
                    Distância (km)
                    <input id="dist" type="number" min="0.1" step="any" value="100" required>
                </label>

                <label>
                    Autonomia (km/L)
                    <input id="auto" type="number" min="0.1" step="any" value="12" required>
                </label>
            </div>

            <div class="row">
                <label>
                    Preço do litro (R$)
                    <input id="preco" type="number" min="0.01" step="0.01" value="5.80" required>
                </label>

                <label>
                    Combustível
                    <select id="comb">
                        <option value="2.3" data-preco="5.80">Gasolina</option>
                        <option value="0.6" data-preco="4.20">Etanol</option>
                    </select>
                </label>
            </div>

            <label>
                Pessoas no carro, incluindo o motorista
                <input id="pess" type="number" min="1" max="8" value="3" required>
            </label>

            <div aria-live="polite">
                <div class="orow"><span>Combustível gasto</span><b id="rL">–</b></div>
                <div class="orow"><span>Emissão estimada</span><b id="rC">–</b></div>
                <div class="orow"><span>Custo total</span><b id="rR">–</b></div>
                <div class="orow"><span>Custo por pessoa</span><b id="rP">–</b></div>
            </div>

            <div class="big">
                <small>Economia estimada por pessoa</small>
                <strong id="rE">–</strong>
                <p id="rCO">–</p>
            </div>

        </form>

    </div>

</section>

<!-- COMO FUNCIONA -->
<section class="view" id="v-como-funciona">

    <div class="narrow">

        <h1>Como funciona o UniRota?</h1>

        <p class="texto-auxiliar">
            Uma maneira simples de compartilhar trajetos e dividir despesas.
        </p>

        <ol class="steps">
            <li>
                <b>Cadastre-se.</b>
                Crie sua conta e informe seu bairro e cidade.
            </li>

            <li>
                <b>Busque uma carona.</b>
                Informe a origem, o destino e a data desejada.
            </li>

            <li>
                <b>Ofereça seu trajeto.</b>
                Se você já vai fazer uma viagem, publique os detalhes e as vagas.
            </li>

            <li>
                <b>Combine a viagem.</b>
                O motorista poderá aceitar ou recusar a solicitação.
            </li>

            <li>
                <b>Compartilhe os custos.</b>
                O valor informado é uma ajuda de custo para dividir despesas da viagem, como combustível.
            </li>
        </ol>

        <div class="big">
            <strong>Importante</strong>
            <p>
                O UniRota organiza caronas entre usuários. A ajuda de custo
                é combinada diretamente entre motorista e passageiros.
                A carteira é apenas um registro de valores: o sistema não
                realiza Pix, cartão ou transferência bancária.
            </p>
        </div>

    </div>

</section>

</main>

<div class="toast" id="toast" role="status" hidden></div>

</body>
</html>