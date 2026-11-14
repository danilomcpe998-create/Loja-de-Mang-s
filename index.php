<?php
$tituloPagina = "Dashboard";
$scriptPagina = "dist/main.js";
include __DIR__ . "/includes/cabecalho.php";
?>

<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="card-subtitle text-muted">Faturamento total</h6>
                <p class="card-text fs-3 fw-bold" id="faturamento-total">—</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="card-subtitle text-muted">Produto mais vendido</h6>
                <p class="card-text fs-5 fw-bold" id="mais-vendido">—</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="card-subtitle text-muted">Produtos com estoque crítico</h6>
                <p class="card-text fs-3 fw-bold" id="estoque-critico">—</p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Produtos</div>
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Categoria</th>
                    <th>Estoque</th>
                    <th>Situação</th>
                    <th>Vendidos</th>
                    <th>Faturamento</th>
                </tr>
            </thead>
            <tbody id="tabela-produtos"></tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Itens vendidos</span>
        <select class="form-select form-select-sm w-auto" id="filtro-categoria">
            <option value="">Todas as categorias</option>
        </select>
    </div>
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Venda</th>
                    <th>Data</th>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>Qtd.</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody id="tabela-vendas"></tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="pagina-anterior">Anterior</button>
        <span class="text-muted small" id="pagina-atual">Página 1</span>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="pagina-proxima">Próxima</button>
    </div>
</div>
<?php include __DIR__ . "/includes/rodape.php"; ?>