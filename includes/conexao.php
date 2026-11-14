<?php
$servidor = "localhost";
$usuario  = "root";
$senha    = "";
$banco    = "loja_mangas";

$conexao = new mysqli($servidor, $usuario, $senha, $banco);
$conexao->set_charset("utf8mb4");