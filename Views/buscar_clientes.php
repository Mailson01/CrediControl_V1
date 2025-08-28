<?php
include __DIR__.'/../config/conexao.php';

$conexao = new Conexao();
$conn = $conexao->Conectar();

$term = $_GET['term'] ?? '';

if ($term != '') {
    $stmt = $conn->prepare("SELECT nome FROM clientes WHERE nome LIKE :term LIMIT 10");
    $stmt->bindValue(':term', "%$term%", PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $names = [];
    foreach ($results as $row) {
        $names[] = $row['nome'];
    }

    echo json_encode($names);
}
?>
