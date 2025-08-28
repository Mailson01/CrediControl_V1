<?php
session_start();
if (!isset($_SESSION['loginn'])) {
    header("Location: ./tela_login.php");
    exit();
}

include __DIR__.'/../config/conexao.php';

$conexao = new Conexao();
$conn = $conexao->conectar();

$id = $_GET['id'] ?? 0;

// Busca os dados do cliente
$stmt = $conn->prepare("SELECT * FROM pagamentos_adiantados WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$dados = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dados) {
    echo "Crédito não encontrado!";
    exit();
}
?>

<h2>Dar baixa parcial no crédito</h2>
<p>Cliente: <?= htmlspecialchars($dados['nome']) ?></p>
<p>Crédito Atual: R$ <?= number_format($dados['valor_pago'], 2, ',', '.') ?></p>

<form method="POST" action="processa_baixa.php">
    <input type="hidden" name="id" value="<?= $dados['id'] ?>">
    <label>Valor a dar baixa:</label>
    <input type="number" name="valor_baixa" step="0.01" required>
    <button type="submit">Confirmar Baixa</button>
</form>
