"use strict";
const ITENS_POR_PAGINA = 5;
const estado = { categoria: "", pagina: 1 };
function escaparHtml(texto) {
    return texto
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}
function formatarMoeda(valor) {
    return valor.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
}
function formatarData(data) {
    return new Date(data.replace(" ", "T")).toLocaleString("pt-BR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}
async function buscarProdutos() {
    const resposta = await fetch("api/produtos.php");
    if (!resposta.ok) {
        throw new Error("Falha ao buscar os produtos.");
    }
    const dados = await resposta.json();
    return dados;
}
async function buscarVendas(categoria, pagina) {
    const parametros = new URLSearchParams({
        categoria: categoria,
        limite: String(ITENS_POR_PAGINA + 1),
        offset: String((pagina - 1) * ITENS_POR_PAGINA),
    });
    const resposta = await fetch(`api/vendas.php?${parametros.toString()}`);
    if (!resposta.ok) {
        throw new Error("Falha ao buscar as vendas.");
    }
    const dados = await resposta.json();
    return dados;
}
function calcularFaturamentoTotal(produtos) {
    return produtos.reduce((total, produto) => total + produto.faturamento, 0);
}
function encontrarMaisVendido(produtos) {
    return produtos.reduce((maior, produto) => maior === null || produto.total_vendido > maior.total_vendido ? produto : maior, null);
}
function filtrarEstoqueCritico(produtos) {
    return produtos.filter((produto) => produto.situacao_estoque === "Crítico");
}
function extrairCategorias(produtos) {
    const nomes = produtos.map((produto) => produto.nome_categoria);
    return Array.from(new Set(nomes)).sort((a, b) => a.localeCompare(b, "pt-BR"));
}
function mostrarErro(idCorpo, colunas, mensagem) {
    const corpo = document.getElementById(idCorpo);
    if (corpo === null) {
        return;
    }
    corpo.innerHTML = `<tr><td colspan="${colunas}" class="text-center text-danger">${escaparHtml(mensagem)}</td></tr>`;
}
function renderizarIndicadores(produtos) {
    const faturamento = document.getElementById("faturamento-total");
    if (faturamento !== null) {
        faturamento.textContent = formatarMoeda(calcularFaturamentoTotal(produtos));
    }
    const campoMaisVendido = document.getElementById("mais-vendido");
    if (campoMaisVendido !== null) {
        const maisVendido = encontrarMaisVendido(produtos);
        campoMaisVendido.textContent =
            maisVendido !== null && maisVendido.total_vendido > 0
                ? `${maisVendido.titulo_produto} (${maisVendido.total_vendido} un.)`
                : "Nenhum dado registrado";
    }
    const campoCritico = document.getElementById("estoque-critico");
    if (campoCritico !== null) {
        campoCritico.textContent = String(filtrarEstoqueCritico(produtos).length);
    }
}
function renderizarTabela(produtos) {
    const corpo = document.getElementById("tabela-produtos");
    if (corpo === null) {
        return;
    }
    if (produtos.length === 0) {
        corpo.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Nenhum dado registrado.</td></tr>`;
        return;
    }
    const linhas = produtos.map((produto) => `
        <tr>
            <td>${escaparHtml(produto.titulo_produto)}</td>
            <td>${escaparHtml(produto.nome_categoria)}</td>
            <td>${produto.estoque_produto}</td>
            <td>${produto.situacao_estoque}</td>
            <td>${produto.total_vendido}</td>
            <td>${formatarMoeda(produto.faturamento)}</td>
        </tr>`);
    corpo.innerHTML = linhas.join("");
}
function renderizarVendas(vendas, corpo) {
    if (vendas.length === 0) {
        corpo.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Nenhuma venda encontrada.</td></tr>`;
        return;
    }
    const linhas = vendas.map((venda) => `
        <tr>
            <td>#${venda.id_venda}</td>
            <td>${formatarData(venda.data_venda)}</td>
            <td>${escaparHtml(venda.titulo_produto)}</td>
            <td>${escaparHtml(venda.nome_categoria)}</td>
            <td>${venda.quantidade}</td>
            <td>${formatarMoeda(venda.valor_total)}</td>
        </tr>`);
    corpo.innerHTML = linhas.join("");
}
async function carregarVendas() {
    const corpo = document.getElementById("tabela-vendas");
    const rotulo = document.getElementById("pagina-atual");
    const anterior = document.getElementById("pagina-anterior");
    const proxima = document.getElementById("pagina-proxima");
    if (corpo === null ||
        rotulo === null ||
        !(anterior instanceof HTMLButtonElement) ||
        !(proxima instanceof HTMLButtonElement)) {
        return;
    }
    try {
        const vendas = await buscarVendas(estado.categoria, estado.pagina);
        const haProxima = vendas.length > ITENS_POR_PAGINA;
        renderizarVendas(vendas.slice(0, ITENS_POR_PAGINA), corpo);
        rotulo.textContent = `Página ${estado.pagina}`;
        anterior.disabled = estado.pagina === 1;
        proxima.disabled = !haProxima;
    }
    catch (erro) {
        console.error("Não foi possível carregar as vendas:", erro);
        mostrarErro("tabela-vendas", 6, "Não foi possível carregar as vendas.");
    }
}
function configurarFiltros(produtos) {
    const filtro = document.getElementById("filtro-categoria");
    const anterior = document.getElementById("pagina-anterior");
    const proxima = document.getElementById("pagina-proxima");
    if (!(filtro instanceof HTMLSelectElement) ||
        !(anterior instanceof HTMLButtonElement) ||
        !(proxima instanceof HTMLButtonElement)) {
        return;
    }
    const opcoes = extrairCategorias(produtos).map((nome) => `<option value="${escaparHtml(nome)}">${escaparHtml(nome)}</option>`);
    filtro.innerHTML = `<option value="">Todas as categorias</option>${opcoes.join("")}`;
    filtro.addEventListener("change", () => {
        estado.categoria = filtro.value;
        estado.pagina = 1;
        void carregarVendas();
    });
    anterior.addEventListener("click", () => {
        estado.pagina = Math.max(1, estado.pagina - 1);
        void carregarVendas();
    });
    proxima.addEventListener("click", () => {
        estado.pagina += 1;
        void carregarVendas();
    });
}
async function iniciar() {
    try {
        const produtos = await buscarProdutos();
        renderizarIndicadores(produtos);
        renderizarTabela(produtos);
        configurarFiltros(produtos);
    }
    catch (erro) {
        console.error("Não foi possível carregar a dashboard:", erro);
        mostrarErro("tabela-produtos", 6, "Não foi possível carregar os produtos.");
    }
    await carregarVendas();
}
iniciar();
