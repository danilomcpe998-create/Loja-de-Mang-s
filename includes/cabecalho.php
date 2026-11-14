<?php
$tituloPagina = $tituloPagina ?? "Loja de Mangás";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina) ?> - Loja de Mangás</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">Loja de Mangás e Quadrinhos</a>
                            <div class="navbar-nav">
                <a class="nav-link" href="index.php">Dashboard</a>
                <a class="nav-link" href="categorias.php">Categorias</a>
                <a class="nav-link" href="produtos.php">Produtos</a>
                <a class="nav-link" href="vendas.php">Vendas</a>
            </div>
        </div>
    </nav>

    <main class="container">
                        