document.addEventListener("DOMContentLoaded", function () {

    const secoes = [
        "buscar",
        "oferecer",
        "viagens",
        "carteira",
        "impacto",
        "como-funciona"
    ];

    function navegar() {
        let pagina = location.hash.replace("#", "");

        if (!secoes.includes(pagina)) {
            pagina = "buscar";
        }

        secoes.forEach(function (nome) {
            const secao = document.getElementById("v-" + nome);

            if (secao) {
                secao.classList.toggle("on", nome === pagina);
            }
        });

        document.querySelectorAll("nav a").forEach(function (link) {
            if (link.dataset.v === pagina) {
                link.setAttribute("aria-current", "page");
            } else {
                link.removeAttribute("aria-current");
            }
        });

        window.scrollTo(0, 0);
    }

    window.addEventListener("hashchange", navegar);
    navegar();

    // Data mínima para publicação de carona
    const dataHora = document.getElementById("dataHoraOferta");

    if (dataHora) {
        const agora = new Date();
        agora.setMinutes(agora.getMinutes() - agora.getTimezoneOffset());
        dataHora.min = agora.toISOString().slice(0, 16);
    }

    // Localização para busca
    const botaoBusca = document.getElementById("localizarUsuario");

    if (botaoBusca) {
        botaoBusca.addEventListener("click", function () {

            if (!navigator.geolocation) {
                document.getElementById("mensagemLocalizacao").textContent =
                    "Seu navegador não permite obter localização.";
                return;
            }

            const mensagem = document.getElementById("mensagemLocalizacao");

            mensagem.textContent = "Obtendo localização...";

            navigator.geolocation.getCurrentPosition(
                function (posicao) {

                    document.getElementById("latitudeBusca").value =
                        posicao.coords.latitude;

                    document.getElementById("longitudeBusca").value =
                        posicao.coords.longitude;

                    mensagem.textContent =
                        "Localização encontrada. Pesquisando caronas próximas...";

                    document.getElementById("busca").requestSubmit();
                },

                function () {
                    mensagem.textContent =
                        "Não foi possível obter sua localização. Verifique a permissão do navegador.";
                },

                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 60000
                }
            );
        });
    }

    // Localização para publicação de carona
    const botaoOferta = document.getElementById("localizarOferta");

    if (botaoOferta) {
        botaoOferta.addEventListener("click", function () {

            if (!navigator.geolocation) {
                document.getElementById("mensagemOferta").textContent =
                    "Seu navegador não permite obter localização.";
                return;
            }

            const mensagem = document.getElementById("mensagemOferta");

            mensagem.textContent = "Obtendo localização...";

            navigator.geolocation.getCurrentPosition(
                function (posicao) {

                    document.getElementById("latitudeOferta").value =
                        posicao.coords.latitude;

                    document.getElementById("longitudeOferta").value =
                        posicao.coords.longitude;

                    mensagem.textContent =
                        "Localização de saída registrada.";
                },

                function () {
                    mensagem.textContent =
                        "Não foi possível obter a localização.";
                },

                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 60000
                }
            );
        });
    }

});