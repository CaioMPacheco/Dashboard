<?php
$dbHost = 'LocalHost';
$dbUsername = 'root';
$dbPassword = '';
$dbName = 'assistec';


try{
    $conn = new mysqli($dbHost,$dbUsername,$dbPassword,$dbName);
} catch(Exception $e) {
    echo 'Erro ao conectar ao banco de dados';
    echo '<br>';
    echo $e;
}

?>