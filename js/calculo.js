document.addEventListener("DOMContentLoaded", function () {

    const formulario = document.getElementById("formCalculadora");

    if (!formulario) {
        return;
    }

    const distancia = document.getElementById("dist");
    const autonomia = document.getElementById("auto");
    const preco = document.getElementById("preco");
    const combustivel = document.getElementById("comb");
    const pessoas = document.getElementById("pess");

    function formatar(valor) {
        return valor.toLocaleString("pt-BR", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function calcular() {

        const km = Number(distancia.value);
        const kmLitro = Number(autonomia.value);
        const valorLitro = Number(preco.value);
        const fatorCO2 = Number(combustivel.value);
        const quantidadePessoas = Number(pessoas.value);

        const camposValidos =
            km > 0 &&
            kmLitro > 0 &&
            valorLitro > 0 &&
            quantidadePessoas >= 1;

        if (!camposValidos) {
            ["rL", "rC", "rR", "rP", "rE"].forEach(function (id) {
                document.getElementById(id).textContent = "–";
            });

            document.getElementById("rCO").textContent =
                "Confira os valores informados.";

            return;
        }

        const litros = km / kmLitro;
        const custoTotal = litros * valorLitro;
        const custoPessoa = custoTotal / quantidadePessoas;
        const emissao = litros * fatorCO2;
        const emissaoPessoa = emissao / quantidadePessoas;
        const economia = custoTotal - custoPessoa;

        document.getElementById("rL").textContent =
            formatar(litros) + " litros";

        document.getElementById("rC").textContent =
            formatar(emissao) + " kg de CO₂";

        document.getElementById("rR").textContent =
            "R$ " + formatar(custoTotal);

        document.getElementById("rP").textContent =
            "R$ " + formatar(custoPessoa);

        document.getElementById("rE").textContent =
            "R$ " + formatar(economia);

        document.getElementById("rCO").textContent =
            "Emissão estimada por pessoa: " +
            formatar(emissaoPessoa) + " kg de CO₂.";
    }

    formulario.addEventListener("submit", function (evento) {
        evento.preventDefault();
        calcular();
    });

    [distancia, autonomia, preco, pessoas].forEach(function (campo) {
        campo.addEventListener("input", calcular);
    });

    combustivel.addEventListener("change", function () {
        const opcao = combustivel.options[combustivel.selectedIndex];
        preco.value = opcao.dataset.preco;
        calcular();
    });

    calcular();

});