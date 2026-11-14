<?php
require_once __DIR__ . "/includes/conexao_pagina.php";

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$editando = $id > 0;
$tituloPagina = $editando ? "Editar venda" : "Nova venda";
$erro = "";
$totalLinhas = 3;

$produtos = $conexao
    ->query("SELECT id_produto, titulo_produto, preco_produto, estoque_produto FROM produtos ORDER BY titulo_produto")
    ->fetch_all(MYSQLI_ASSOC);

$dataVenda = date("Y-m-d\TH:i");
$itensAtuais = [];

if ($editando) {
    $stmt = $conexao->prepare("SELECT data_venda FROM vendas WHERE id_venda = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $venda = $stmt->get_result()->fetch_assoc();

    if ($venda === null) {
        header("Location: vendas.php");
        exit;
    }

    $stmt = $conexao->prepare("SELECT id_produto, quantidade, preco_unitario FROM itens_venda WHERE id_venda = ? ORDER BY id_item");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $itensAtuais = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $dataVenda = date("Y-m-d\TH:i", strtotime($venda["data_venda"]));
    $totalLinhas = max(3, count($itensAtuais) + 1);
}

$postProdutos = [];
$postQuantidades = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postProdutos = is_array($_POST["produto"] ?? null) ? array_values($_POST["produto"]) : [];
    $postQuantidades = is_array($_POST["quantidade"] ?? null) ? array_values($_POST["quantidade"]) : [];
    $totalLinhas = max(1, min(20, count($postProdutos)));
    $dataVenda = (string) ($_POST["data_venda"] ?? $dataVenda);
}

$linhas = array_fill(0, $totalLinhas, ["produto" => 0, "quantidade" => ""]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    foreach ($itensAtuais as $i => $item) {
        $linhas[$i] = [
            "produto"    => (int) $item["id_produto"],
            "quantidade" => (string) $item["quantidade"],
        ];
    }
} else {
    for ($i = 0; $i < $totalLinhas; $i++) {
        $linhas[$i] = [
            "produto"    => (int) ($postProdutos[$i] ?? 0),
            "quantidade" => trim((string) ($postQuantidades[$i] ?? "")),
        ];
    }

    $timestamp = strtotime($dataVenda);
    $quantidades = [];

    if ($timestamp === false) {
        $erro = "Informe uma data válida.";
    }

    foreach ($linhas as $i => $linha) {
        if ($erro !== "") {
            break;
        }
        if ($linha["produto"] === 0 && $linha["quantidade"] === "") {
            continue;
        }

        $numero = $i + 1;
        $quantidade = filter_var($linha["quantidade"], FILTER_VALIDATE_INT);

        if ($linha["produto"] === 0) {
            $erro = "Escolha o produto na linha $numero.";
        } elseif ($quantidade === false || $quantidade < 1) {
            $erro = "Informe uma quantidade válida (inteiro maior que zero) na linha $numero.";
        } else {
            $quantidades[$linha["produto"]] = ($quantidades[$linha["produto"]] ?? 0) + $quantidade;
        }
    }

    if ($erro === "" && count($quantidades) === 0) {
        $erro = "Adicione pelo menos um item à venda.";
    }

    if ($erro === "") {
        $dataBanco = date("Y-m-d H:i:s", $timestamp);
        $conexao->begin_transaction();

        try {
            $precosAntigos = [];

            if ($editando) {
                $stmt = $conexao->prepare("UPDATE vendas SET data_venda = ? WHERE id_venda = ?");
                $stmt->bind_param("si", $dataBanco, $id);
                $stmt->execute();
                $idVenda = $id;

                $devolver = $conexao->prepare("UPDATE produtos SET estoque_produto = estoque_produto + ? WHERE id_produto = ?");
                foreach ($itensAtuais as $item) {
                    $qtdAntiga = (int) $item["quantidade"];
                    $idAntigo = (int) $item["id_produto"];
                    $precosAntigos[$idAntigo] = (float) $item["preco_unitario"];
                    $devolver->bind_param("ii", $qtdAntiga, $idAntigo);
                    $devolver->execute();
                }

                $apagar = $conexao->prepare("DELETE FROM itens_venda WHERE id_venda = ?");
                $apagar->bind_param("i", $id);
                $apagar->execute();
            } else {
                $stmt = $conexao->prepare("INSERT INTO vendas (data_venda) VALUES (?)");
                $stmt->bind_param("s", $dataBanco);
                $stmt->execute();
                $idVenda = $conexao->insert_id;
            }

            $buscar = $conexao->prepare("SELECT titulo_produto, preco_produto FROM produtos WHERE id_produto = ?");
            $baixar = $conexao->prepare("UPDATE produtos SET estoque_produto = estoque_produto - ? WHERE id_produto = ? AND estoque_produto >= ?");
            $inserirItem = $conexao->prepare("INSERT INTO itens_venda (id_venda, id_produto, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");

            foreach ($quantidades as $idProduto => $quantidade) {
                $buscar->bind_param("i", $idProduto);
                $buscar->execute();
                $produto = $buscar->get_result()->fetch_assoc();

                if ($produto === null) {
                    throw new RuntimeException("Um dos produtos escolhidos não existe mais.");
                }

                $baixar->bind_param("iii", $quantidade, $idProduto, $quantidade);
                $baixar->execute();

                if ($baixar->affected_rows === 0) {
                    throw new RuntimeException("Estoque insuficiente para \"" . $produto["titulo_produto"] . "\".");
                }

                $preco = $precosAntigos[$idProduto] ?? (float) $produto["preco_produto"];
                $inserirItem->bind_param("iiid", $idVenda, $idProduto, $quantidade, $preco);
                $inserirItem->execute();
            }

            $conexao->commit();
            header("Location: vendas.php");
            exit;
        } catch (RuntimeException $e) {
            $conexao->rollback();
            $erro = $e->getMessage();
        } catch (mysqli_sql_exception $e) {
            $conexao->rollback();
            $erro = "Não foi possível salvar a venda. Nada foi alterado.";
        }
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
        <label for="data_venda" class="form-label">Data e hora</label>
        <input type="datetime-local" class="form-control" id="data_venda" name="data_venda" value="<?= htmlspecialchars($dataVenda) ?>">
    </div>

    <label class="form-label">Itens da venda</label>
    <?php for ($i = 0; $i < $totalLinhas; $i++) : ?>
        <div class="row mb-2">
            <div class="col-md-9">
                <select class="form-select" name="produto[]">
                    <option value="0">Selecione um produto...</option>
                    <?php foreach ($produtos as $produto) : ?>
                        <option value="<?= (int) $produto["id_produto"] ?>" <?= $linhas[$i]["produto"] === (int) $produto["id_produto"] ? "selected" : "" ?>>
                            <?= htmlspecialchars($produto["titulo_produto"]) ?> — R$ <?= number_format((float) $produto["preco_produto"], 2, ",", ".") ?> (estoque: <?= (int) $produto["estoque_produto"] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control" name="quantidade[]" inputmode="numeric" placeholder="Quantidade" value="<?= htmlspecialchars($linhas[$i]["quantidade"]) ?>">
            </div>
        </div>
    <?php endfor; ?>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary"><?= $editando ? "Salvar alterações" : "Registrar venda" ?></button>
        <a href="vendas.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>

<?php include __DIR__ . "/includes/rodape.php"; ?>