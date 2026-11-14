<?php
header("Content-Type: application/json; charset=utf-8");

try {
    require_once __DIR__ . "/../includes/conexao.php";
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(["erro" => "Falha na conexão com o banco de dados."]);
    exit;
}