<?php
session_start();
require_once __DIR__ . "/includes/conexao_pagina.php";

$tituloPagina = "Vendas";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["excluir"])) {
    $idExcluir = (int) $_POST["excluir"];
    $conexao->begin_transaction();

    try {
        $stmt = $conexao->prepare("SELECT id_produto, quantidade FROM itens_venda WHERE id_venda = ?");
        $stmt->bind_param("i", $idExcluir);
        $stmt->execute();
        $itens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $devolver = $conexao->prepare("UPDATE produtos SET estoque_produto = estoque_produto + ? WHERE id_produto = ?");
        foreach ($itens as $item) {
            $quantidade = (int) $item["quantidade"];
            $idProduto = (int) $item["id_produto"];
            $devolver->bind_param("ii", $quantidade, $idProduto);
            $devolver->execute();
        }

        $apagarItens = $conexao->prepare("DELETE FROM itens_venda WHERE id_venda = ?");
        $apagarItens->bind_param("i", $idExcluir);
        $apagarItens->execute();

        $apagarVenda = $conexao->prepare("DELETE FROM vendas WHERE id_venda = ?");
        $apagarVenda->bind_param("i", $idExcluir);
        $apagarVenda->execute();

        $conexao->commit();
        $totalItens = count($itens);
        $_SESSION["mensagem"] = [
            "tipo"  => "success",
            "texto" => "Venda #$idExcluir excluída. Os $totalItens item(ns) foram removidos e as quantidades devolvidas ao estoque."
        ];
    } catch (mysqli_sql_exception $e) {
        $conexao->rollback();
        $_SESSION["mensagem"] = [
            "tipo"  => "danger",
            "texto" => "Não foi possível excluir a venda. Nada foi alterado."
        ];
    }

    header("Location: vendas.php");
    exit;
}

$mensagem = $_SESSION["mensagem"] ?? null;
unset($_SESSION["mensagem"]);

$resultado = $conexao->query("
    SELECT v.id_venda, v.data_venda,
           COUNT(iv.id_item) AS qtd_itens,
           COALESCE(SUM(iv.quantidade * iv.preco_unitario), 0) AS total
    FROM vendas v
    LEFT JOIN itens_venda iv ON iv.id_venda = v.id_venda
    GROUP BY v.id_venda, v.data_venda
    ORDER BY v.data_venda DESC
");
$vendas = $resultado->fetch_all(MYSQLI_ASSOC);

include __DIR__ . "/includes/cabecalho.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0">Vendas</h2>
    <a href="venda_form.php" class="btn btn-primary">Nova venda</a>
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
                    <th>Data</th>
                    <th>Itens</th>
                    <th>Total</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($vendas) === 0) : ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">Nenhuma venda registrada.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($vendas as $venda) : ?>
                        <tr>
                            <td><?= (int) $venda["id_venda"] ?></td>
                            <td><?= date("d/m/Y H:i", strtotime($venda["data_venda"])) ?></td>
                            <td><?= (int) $venda["qtd_itens"] ?></td>
                            <td>R$ <?= number_format((float) $venda["total"], 2, ",", ".") ?></td>
                            <td class="text-end">
                                <form method="post" class="d-inline" onsubmit="return confirm('Excluir esta venda? O estoque dos produtos será devolvido.');">
                                    <button type="submit" name="excluir" value="<?= (int) $venda["id_venda"] ?>" class="btn btn-sm btn-outline-danger">Excluir</button>
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