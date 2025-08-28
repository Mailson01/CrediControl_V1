<?php
include __DIR__.'/../config/conexao.php';

$conexao = new Conexao();
$conn = $conexao->conectar();

if (isset($_POST['cliente_id'])) {
    $cliente_id = $_POST['cliente_id'];

    // Primeiro, pega o valor atual (não retirado) para o comprovante
    $stmt = $conn->prepare("
        SELECT c.nome, SUM(p.valor_pago) AS valor_atual, MAX(p.data_prevista_retirada) AS data
        FROM pagamentos_adiantados p
        JOIN clientes c ON p.cliente_id = c.id
        WHERE p.cliente_id = :cliente_id AND p.retirado = 'Não'
    ");
    $stmt->bindParam(':cliente_id', $cliente_id, PDO::PARAM_INT);
    $stmt->execute();
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    // Atualiza todos os pagamentos do cliente para "retirado"
    $stmt = $conn->prepare("UPDATE pagamentos_adiantados SET retirado = 'Sim' WHERE cliente_id = :cliente_id");
    $stmt->bindParam(':cliente_id', $cliente_id, PDO::PARAM_INT);
    $stmt->execute();
}
?>

<?php if (!empty($dados)): ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Comprovante de Retirada</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
        .comprovante { border: 2px solid #000; display: inline-block; padding: 20px; background: #f5f5f5; border-radius: 8px; }
        button { margin-top: 15px; padding: 10px 15px; cursor: pointer; font-weight: bold; border-radius: 5px; border: none; background: #003366; color: white; }
        button:hover { background: #002244; }
    </style>
</head>
<body>
    <div class="comprovante">
        <h2>Comprovante de Retirada</h2>
        <p><strong>Cliente:</strong> <?= htmlspecialchars($dados['nome']); ?></p>
        <p><strong>Valor Retirado:</strong> R$ <?= number_format($dados['valor_atual'], 2, ',', '.'); ?></p>
        <p><strong>Data da Retirada:</strong> <?= date('d/m/Y', strtotime($dados['data'])); ?></p>
        <p><strong>Status:</strong> Retirado</p>
    </div>
    <br>
    <button onclick="window.print()">🖨 Imprimir</button>
    <button onclick="window.location.href='../views/listagem.php'">⬅ Voltar</button>
</body>
</html>
<?php else: ?>
    <p>Erro ao gerar comprovante.</p>
<?php endif; ?>
