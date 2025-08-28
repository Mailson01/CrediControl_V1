<?php
session_start();
if (!isset($_SESSION['loginn'])) {
    header("Location: ./tela_login.php");
    exit();
}

include __DIR__.'/../config/conexao.php';

$conexao = new Conexao();
$conn = $conexao->conectar();

$id = $_POST['id'] ?? 0;
$valor_baixa = $_POST['valor_baixa'] ?? 0;

if ($id > 0 && $valor_baixa > 0) {
    // Verifica o valor atual do crédito
    $stmt = $conn->prepare("SELECT valor_pago FROM pagamentos_adiantados WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $dado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($dado && $dado['valor_pago'] >= $valor_baixa) {
        $novo_valor = $dado['valor_pago'] - $valor_baixa;

        // Atualiza o valor no banco
        $stmt2 = $conn->prepare("UPDATE pagamentos_adiantados SET valor_pago = :novo_valor WHERE id = :id");
        $stmt2->bindParam(':novo_valor', $novo_valor);
        $stmt2->bindParam(':id', $id);
        $stmt2->execute();

        header("Location: listagem.php?sucesso=1");
        exit();
    } else {
        echo "Valor inválido!";
    }
} else {
    echo "Dados inválidos!";
}
