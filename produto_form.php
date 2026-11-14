<?php
require_once __DIR__ . "/includes/conexao_pagina.php";

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$editando = $id > 0;
$tituloPagina = $editando ? "Editar produto" : "Novo produto";
$erro = "";

$produto = [
    "titulo_produto"  => "",
    "preco_produto"   => "",
    "estoque_produto" => "",
    "id_categoria"    => 0,
];

$categorias = $conexao
    ->query("SELECT id_categoria, nome_categoria FROM categorias ORDER BY nome_categoria")
    ->fetch_all(MYSQLI_ASSOC);

if ($editando && $_SERVER["REQUEST_METHOD"] !== "POST") {
    $stmt = $conexao->prepare("SELECT titulo_produto, preco_produto, estoque_produto, id_categoria FROM produtos WHERE id_produto = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $linha = $stmt->get_result()->fetch_assoc();

    if ($linha === null) {
        header("Location: produtos.php");
        exit;
    }
    $produto = $linha;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $produto["titulo_produto"]  = trim($_POST["titulo_produto"] ?? "");
    $produto["preco_produto"]   = trim($_POST["preco_produto"] ?? "");
    $produto["estoque_produto"] = trim($_POST["estoque_produto"] ?? "");
    $produto["id_categoria"]    = (int) ($_POST["id_categoria"] ?? 0);

    $precoTexto = str_replace(",", ".", $produto["preco_produto"]);
    $estoque = filter_var($produto["estoque_produto"], FILTER_VALIDATE_INT);
    $idsValidos = array_map("intval", array_column($categorias, "id_categoria"));

    if ($produto["titulo_produto"] === "") {
        $erro = "Informe o título do produto.";
    } elseif (!is_numeric($precoTexto) || (float) $precoTexto < 0) {
        $erro = "Informe um preço válido (zero ou maior).";
    } elseif ($estoque === false || $estoque < 0) {
        $erro = "Informe um estoque válido (número inteiro, zero ou maior).";
    } elseif (!in_array($produto["id_categoria"], $idsValidos, true)) {
        $erro = "Escolha uma categoria.";
    } else {
        $titulo = $produto["titulo_produto"];
        $preco = (float) $precoTexto;
        $categoria = $produto["id_categoria"];

        if ($editando) {
            $stmt = $conexao->prepare("UPDATE produtos SET titulo_produto = ?, preco_produto = ?, estoque_produto = ?, id_categoria = ? WHERE id_produto = ?");
            $stmt->bind_param("sdiii", $titulo, $preco, $estoque, $categoria, $id);
        } else {
            $stmt = $conexao->prepare("INSERT INTO produtos (titulo_produto, preco_produto, estoque_produto, id_categoria) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sdii", $titulo, $preco, $estoque, $categoria);
        }
        $stmt->execute();

        header("Location: produtos.php");
        exit;
    }
}

include __DIR__ . "/includes/cabecalho.php";
?>

<h2 class="h4 mb-3"><?= htmlspecialchars($tituloPagina) ?></h2>

<?php if ($erro !== "") : ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<form method="post" class="card shadow-sm card-body">
    <div class="mb-3">
        <label for="titulo_produto" class="form-label">Título</label>
        <input type="text" class="form-control" id="titulo_produto" name="titulo_produto" maxlength="100" value="<?= htmlspecialchars((string) $produto["titulo_produto"]) ?>">
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="preco_produto" class="form-label">Preço (R$)</label>
            <input type="text" class="form-control" id="preco_produto" name="preco_produto" inputmode="decimal" value="<?= htmlspecialchars((string) $produto["preco_produto"]) ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label for="estoque_produto" class="form-label">Estoque</label>
            <input type="text" class="form-control" id="estoque_produto" name="estoque_produto" inputmode="numeric" value="<?= htmlspecialchars((string) $produto["estoque_produto"]) ?>">
        </div>
    </div>
    <div class="mb-3">
        <label for="id_categoria" class="form-label">Categoria</label>
        <select class="form-select" id="id_categoria" name="id_categoria">
            <option value="0">Selecione...</option>
            <?php foreach ($categorias as $categoria) : ?>
                <option value="<?= (int) $categoria["id_categoria"] ?>" <?= (int) $produto["id_categoria"] === (int) $categoria["id_categoria"] ? "selected" : "" ?>>
                    <?= htmlspecialchars($categoria["nome_categoria"]) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <button type="submit" class="btn btn-primary">Salvar</button>
        <a href="produtos.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>

<?php include __DIR__ . "/includes/rodape.php"; ?>