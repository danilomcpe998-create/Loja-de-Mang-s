
<?php
//O  require_once reaproveita a conexão.php que já define o cabeçalho json.

require_once __DIR__ . "/conexao.php";

//O $_GET[...] ?? ... lê parametros opcionais da url: categoria, limite e offset, com valores padrão.
$categoria = $_GET["categoria"] ?? null;
if ($categoria ==="") {
    $categoria = null;
}

$limite = (int) ($_GET["limite"] ?? 50);
$offset = (int) ($_GET["offset"] ?? 0);

try {
    $stmt = $conexao->prepare("CALL sp_listar_vendas(?, ?, ?)");
     $stmt->bind_param("sii", $categoria, $limite, $offset);
      $stmt->execute();
         
      $resultado = $stmt->get_result();
      $vendas = $resultado->fetch_all(MYSQLI_ASSOC);
      
      
      echo json_encode($vendas, JSON_UNESCAPED_UNICODE);

} catch (mysqli_sql_exception $e) {
    https_response_code(500);
    echo json_encode(["erro" => "Falha ao buscar as vendas."]);
}
