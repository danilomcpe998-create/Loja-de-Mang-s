type SituacaoEstoque = "Crítico" | "Baixo" | "Normal";

interface Produto {
    id_produto: number;
    titulo_produto: string;
    nome_categoria: string;
    estoque_produto: number;
    situacao_estoque: SituacaoEstoque;
    total_vendido: number;
    faturamento: number;
}

interface Venda {
    id_venda: number;
    data_venda: string;
    titulo_produto: string;
    nome_categoria: string;
    quantidade: number;
    preco_unitario: number;
    valor_total: number;
}

interface EstadoVendas {
    categoria: string;
    pagina: number;
}

const ITENS_POR_PAGINA: number = 5;
const estado: EstadoVendas = { categoria: "", pagina: 1 };

function escaparHtml(texto: string): string {
    return texto
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

function formatarMoeda(valor: number): string {
    return valor.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
}

function formatarData(data: string): string {
    return new Date(data.replace(" ", "T")).toLocaleString("pt-BR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

async function buscarProdutos(): Promise<Produto[]> {
    const resposta = await fetch("api/produtos.php");
    if (!resposta.ok) {
        throw new Error("Falha ao buscar os produtos.");
    }
    const dados: Produto[] = await resposta.json();
    return dados;
}

async function buscarVendas(categoria: string, pagina: number): Promise<Venda[]> {
    const parametros = new URLSearchParams({
        categoria: categoria,
        limite: String(ITENS_POR_PAGINA + 1),
        offset: String((pagina - 1) * ITENS_POR_PAGINA),
    });
    const resposta = await fetch(`api/vendas.php?${parametros.toString()}`);
    if (!resposta.ok) {
        throw new Error("Falha ao buscar as vendas.");
    }
    const dados: Venda[] = await resposta.json();
    return dados;
}

function calcularFaturamentoTotal(produtos: Produto[]): number {
    return produtos.reduce(
        (total: number, produto: Produto): number => total + produto.faturamento,
        0
    );
}

function encontrarMaisVendido(produtos: Produto[]): Produto | null {
    return produtos.reduce<Produto | null>(
        (maior: Produto | null, produto: Produto): Produto | null =>
            maior === null || produto.total_vendido > maior.total_vendido ? produto : maior,
        null
    );
}

function filtrarEstoqueCritico(produtos: Produto[]): Produto[] {
    return produtos.filter(
        (produto: Produto): boolean => produto.situacao_estoque === "Crítico"
    );
}

function extrairCategorias(produtos: Produto[]): string[] {
    const nomes: string[] = produtos.map((produto: Produto): string => produto.nome_categoria);
    return Array.from(new Set(nomes)).sort((a: string, b: string): number => a.localeCompare(b, "pt-BR"));
}

function mostrarErro(idCorpo: string, colunas: number, mensagem: string): void {
    const corpo = document.getElementById(idCorpo);
    if (corpo === null) {
        return;
    }
    corpo.innerHTML = `<tr><td colspan="${colunas}" class="text-center text-danger">${escaparHtml(mensagem)}</td></tr>`;
}

function renderizarIndicadores(produtos: Produto[]): void {
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

function renderizarTabela(produtos: Produto[]): void {
    const corpo = document.getElementById("tabela-produtos");
    if (corpo === null) {
        return;
    }

    if (produtos.length === 0) {
        corpo.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Nenhum dado registrado.</td></tr>`;
        return;
    }

    const linhas: string[] = produtos.map((produto: Produto): string => `
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

function renderizarVendas(vendas: Venda[], corpo: HTMLElement): void {
    if (vendas.length === 0) {
        corpo.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Nenhuma venda encontrada.</td></tr>`;
        return;
    }

    const linhas: string[] = vendas.map((venda: Venda): string => `
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

async function carregarVendas(): Promise<void> {
    const corpo = document.getElementById("tabela-vendas");
    const rotulo = document.getElementById("pagina-atual");
    const anterior = document.getElementById("pagina-anterior");
    const proxima = document.getElementById("pagina-proxima");

    if (
        corpo === null ||
        rotulo === null ||
        !(anterior instanceof HTMLButtonElement) ||
        !(proxima instanceof HTMLButtonElement)
    ) {
        return;
    }

    try {
        const vendas = await buscarVendas(estado.categoria, estado.pagina);
        const haProxima = vendas.length > ITENS_POR_PAGINA;

        renderizarVendas(vendas.slice(0, ITENS_POR_PAGINA), corpo);
        rotulo.textContent = `Página ${estado.pagina}`;
        anterior.disabled = estado.pagina === 1;
        proxima.disabled = !haProxima;
    } catch (erro) {
        console.error("Não foi possível carregar as vendas:", erro);
        mostrarErro("tabela-vendas", 6, "Não foi possível carregar as vendas.");
    }
}

function configurarFiltros(produtos: Produto[]): void {
    const filtro = document.getElementById("filtro-categoria");
    const anterior = document.getElementById("pagina-anterior");
    const proxima = document.getElementById("pagina-proxima");

    if (
        !(filtro instanceof HTMLSelectElement) ||
        !(anterior instanceof HTMLButtonElement) ||
        !(proxima instanceof HTMLButtonElement)
    ) {
        return;
    }

    const opcoes: string[] = extrairCategorias(produtos).map(
        (nome: string): string => `<option value="${escaparHtml(nome)}">${escaparHtml(nome)}</option>`
    );
    filtro.innerHTML = `<option value="">Todas as categorias</option>${opcoes.join("")}`;

    filtro.addEventListener("change", (): void => {
        estado.categoria = filtro.value;
        estado.pagina = 1;
        void carregarVendas();
    });

    anterior.addEventListener("click", (): void => {
        estado.pagina = Math.max(1, estado.pagina - 1);
        void carregarVendas();
    });

    proxima.addEventListener("click", (): void => {
        estado.pagina += 1;
        void carregarVendas();
    });
}

async function iniciar(): Promise<void> {
    try {
        const produtos = await buscarProdutos();
        renderizarIndicadores(produtos);
        renderizarTabela(produtos);
        configurarFiltros(produtos);
    } catch (erro) {
        console.error("Não foi possível carregar a dashboard:", erro);
        mostrarErro("tabela-produtos", 6, "Não foi possível carregar os produtos.");
    }

    await carregarVendas();
}

iniciar();