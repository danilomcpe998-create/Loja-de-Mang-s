<?php
require_once __DIR__ . "/includes/conexao_pagina.php";

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$editando = $id > 0;
$tituloPagina = $editando ? "Editar categoria" : "Nova categoria";
$erro = "";
$nome = "";

if ($editando && $_SERVER["REQUEST_METHOD"] !== "POST") {
    $stmt = $conexao->prepare("SELECT nome_categoria FROM categorias WHERE id_categoria = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $linha = $stmt->get_result()->fetch_assoc();

    if ($linha === null) {
        header("Location: categorias.php");
        exit;
    }
    $nome = $linha["nome_categoria"];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST["nome_categoria"] ?? "");

    if ($nome === "") {
        $erro = "Informe o nome da categoria.";
    } else {
        if ($editando) {
            $stmt = $conexao->prepare("UPDATE categorias SET nome_categoria = ? WHERE id_categoria = ?");
            $stmt->bind_param("si", $nome, $id);
        } else {
            $stmt = $conexao->prepare("INSERT INTO categorias (nome_categoria) VALUES (?)");
            $stmt->bind_param("s", $nome);
        }
        $stmt->execute();

        header("Location: categorias.php");
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
        <label for="nome_categoria" class="form-label">Nome</label>
        <input type="text" class="form-control" id="nome_categoria" name="nome_categoria" maxlength="50" value="<?= htmlspecialchars($nome) ?>">
    </div>
    <div>
        <button type="submit" class="btn btn-primary">Salvar</button>
        <a href="categorias.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>

<?php include __DIR__ . "/includes/rodape.php"; ?>