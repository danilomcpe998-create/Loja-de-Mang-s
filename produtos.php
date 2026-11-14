<?php
session_start();
require_once __DIR__ . "/includes/conexao_pagina.php";

$tituloPagina = "Produtos";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["excluir"])) {
    $idExcluir = (int) $_POST["excluir"];

    $stmt = $conexao->prepare("SELECT COUNT(*) AS total FROM itens_venda WHERE id_produto = ?");
    $stmt->bind_param("i", $idExcluir);
    $stmt->execute();
    $total = (int) $stmt->get_result()->fetch_assoc()["total"];

    if ($total > 0) {
        $_SESSION["mensagem"] = [
            "tipo"  => "danger",
            "texto" => "Não foi possível excluir: este produto aparece em $total item(ns) de venda. Produtos já vendidos não podem ser removidos, para preservar o histórico."
        ];
    } else {
        $stmt = $conexao->prepare("DELETE FROM produtos WHERE id_produto = ?");
        $stmt->bind_param("i", $idExcluir);
        $stmt->execute();
        $_SESSION["mensagem"] = [
            "tipo"  => "success",
            "texto" => "Produto excluído com sucesso."
        ];
    }

    header("Location: produtos.php");
    exit;
}

$mensagem = $_SESSION["mensagem"] ?? null;
unset($_SESSION["mensagem"]);

$resultado = $conexao->query("
    SELECT p.id_produto, p.titulo_produto, c.nome_categoria,
           p.preco_produto, p.estoque_produto,
           fn_status_estoque(p.estoque_produto) AS situacao
    FROM produtos p
    JOIN categorias c ON c.id_categoria = p.id_categoria
    ORDER BY p.titulo_produto
");
$produtos = $resultado->fetch_all(MYSQLI_ASSOC);

include __DIR__ . "/includes/cabecalho.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0">Produtos</h2>
    <a href="produto_form.php" class="btn btn-primary">Novo produto</a>
</div>

<?php if ($mensagem !== null) : ?>
    <div class="alert alert-<?= htmlspecialchars($mensagem["tipo"]) ?>"><?= htmlspecialchars($mensagem["texto"]) ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Título</th>
                    <th>Categoria</th>
                    <th>Preço</th>
                    <th>Estoque</th>
                    <th>Situação</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($produtos) === 0) : ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">Nenhum produto cadastrado.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($produtos as $produto) : ?>
                        <tr>
                            <td><?= (int) $produto["id_produto"] ?></td>
                            <td><?= htmlspecialchars($produto["titulo_produto"]) ?></td>
                            <td><?= htmlspecialchars($produto["nome_categoria"]) ?></td>
                            <td>R$ <?= number_format((float) $produto["preco_produto"], 2, ",", ".") ?></td>
                            <td><?= (int) $produto["estoque_produto"] ?></td>
                            <td><?= htmlspecialchars($produto["situacao"]) ?></td>
                            <td class="text-end">
                                <a href="produto_form.php?id=<?= (int) $produto["id_produto"] ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                                <form method="post" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este produto?');">
                                    <button type="submit" name="excluir" value="<?= (int) $produto["id_produto"] ?>" class="btn btn-sm btn-outline-danger">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . "/includes/rodape.php"; ?>