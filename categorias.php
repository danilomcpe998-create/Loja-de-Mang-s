<?php
session_start();
require_once __DIR__ . "/includes/conexao_pagina.php";

$tituloPagina = "Categorias";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["excluir"])) {
    $idExcluir = (int) $_POST["excluir"];

    $stmt = $conexao->prepare("SELECT COUNT(*) AS total FROM produtos WHERE id_categoria = ?");
    $stmt->bind_param("i", $idExcluir);
    $stmt->execute();
    $total = (int) $stmt->get_result()->fetch_assoc()["total"];

    if ($total > 0) {
        $_SESSION["mensagem"] = [
            "tipo"  => "danger",
            "texto" => "Não foi possível excluir: esta categoria possui $total produto(s). Mova ou exclua esses produtos antes."
        ];
    } else {
        $stmt = $conexao->prepare("DELETE FROM categorias WHERE id_categoria = ?");
        $stmt->bind_param("i", $idExcluir);
        $stmt->execute();
        $_SESSION["mensagem"] = [
            "tipo"  => "success",
            "texto" => "Categoria excluída com sucesso."
        ];
    }

    header("Location: categorias.php");
    exit;
}

$mensagem = $_SESSION["mensagem"] ?? null;
unset($_SESSION["mensagem"]);

$resultado = $conexao->query("SELECT id_categoria, nome_categoria FROM categorias ORDER BY nome_categoria");
$categorias = $resultado->fetch_all(MYSQLI_ASSOC);

include __DIR__ . "/includes/cabecalho.php";
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0">Categorias</h2>
    <a href="categoria_form.php" class="btn btn-primary">Nova categoria</a>
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
                    <th>Nome</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($categorias) === 0) : ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted">Nenhuma categoria cadastrada.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($categorias as $categoria) : ?>
                        <tr>
                            <td><?= (int) $categoria["id_categoria"] ?></td>
                            <td><?= htmlspecialchars($categoria["nome_categoria"]) ?></td>
                            <td class="text-end">
                                <a href="categoria_form.php?id=<?= (int) $categoria["id_categoria"] ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                                <form method="post" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta categoria?');">
                                    <button type="submit" name="excluir" value="<?= (int) $categoria["id_categoria"] ?>" class="btn btn-sm btn-outline-danger">Excluir</button>
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