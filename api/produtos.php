<?php
require_once __DIR__ . "/conexao.php";

try {
    $resultado = $conexao->query("SELECT * FROM vw_resumo_produtos");
    $produtos = [];

    //while lê uma linha de cada vez e monta um array novo, campo por campo.
    //(int) converte para inteiro e (float) para decimal. Os campos de texto ficam como estão.
    while ($linha = $resultado->fetch_assoc()) {
        $produtos[] = [
            "id_produto"       => (int) $linha["id_produto"],
            "titulo_produto"   => $linha["titulo_produto"],
            "nome_categoria"   => $linha["nome_categoria"],
            "estoque_produto"  => (int) $linha["estoque_produto"],
            "situacao_estoque" => $linha["situacao_estoque"],
            "total_vendido"    => (int) $linha["total_vendido"],
            "faturamento"      => (float) $linha["faturamento"],
        ];
    }

    echo json_encode($produtos, JSON_UNESCAPED_UNICODE);
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(["erro" => "Falha ao buscar os produtos."]);
}