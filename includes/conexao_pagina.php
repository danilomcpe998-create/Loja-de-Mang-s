<?php
try {
    require_once __DIR__ . "/conexao.php";
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    $tituloPagina = "Erro";
    include __DIR__ . "/cabecalho.php";
    ?>
    <div class="alert alert-danger">
        Não foi possível conectar ao banco de dados. Verifique se o MySQL está ligado e tente novamente.
    </div>
    <?php
    include __DIR__ . "/rodape.php";
    exit;
}