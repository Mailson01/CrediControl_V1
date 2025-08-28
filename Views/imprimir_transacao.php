<?php
session_start();
if (!isset($_SESSION['loginn'])) {
    header("Location: ./tela_login.php");
    exit();
}

if (!isset($_POST['transacao_id'])) {
    echo "Transação não informada.";
    exit;
}

include __DIR__.'/../config/conexao.php';
$conexao = new Conexao();
$conn = $conexao->conectar();

$transacao_id = intval($_POST['transacao_id']);

try {
    // Buscar transação
    $stmt = $conn->prepare("
        SELECT p.id, p.produto, p.valor_pago, p.data_pagamento, p.data_prevista_retirada,
               p.retirado, c.nome AS cliente_nome,
               GROUP_CONCAT(o.observacao ORDER BY o.id DESC SEPARATOR ' | ') AS observacoes
        FROM pagamentos_adiantados p
        INNER JOIN clientes c ON c.id = p.cliente_id
        LEFT JOIN observacoes o ON o.cliente_id = p.cliente_id
        WHERE p.id = :id
        GROUP BY p.id
    ");
    $stmt->bindParam(':id', $transacao_id, PDO::PARAM_INT);
    $stmt->execute();
    $transacao = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transacao) {
        echo "Transação não encontrada.";
        exit;
    }

} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Comprovante de Transação</title>
<style>
body { font-family: Arial, sans-serif; background: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
.comprovante { background: #fff; padding: 20px 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); width: 400px; text-align: center; }
h2 { color: #003366; margin-bottom: 20px; border-bottom: 2px solid #003366; padding-bottom: 5px; }
p { font-size: 16px; margin: 10px 0; }
.btns { margin-top: 20px; }
button { padding: 10px 15px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; transition: 0.3s; }
.imprimir { background-color: #2ecc71; color: white; }
.imprimir:hover { background-color: #27ae60; }
.voltar { background-color: #3498db; color: white; }
.voltar:hover { background-color: #2980b9; }
</style>
</head>
<body>
<div class="comprovante">
    <h2>Comprovante de Transação</h2>
    <p><strong>Cliente:</strong> <?= htmlspecialchars($transacao['cliente_nome']) ?></p>
    <p><strong>Produto:</strong> <?= htmlspecialchars($transacao['produto']) ?></p>
    <p><strong>Valor:</strong> R$ <?= number_format($transacao['valor_pago'],2,',','.') ?></p>
    <p><strong>Data Pagamento:</strong> <?= date('d/m/Y H:i:s', strtotime($transacao['data_pagamento'])) ?></p>
    <p><strong>Data Prevista:</strong> <?= $transacao['data_prevista_retirada'] ?? '-' ?></p>
    <p><strong>Retirado:</strong> <?= $transacao['retirado'] ?></p>
    <p><strong>Observações:</strong> <?= htmlspecialchars($transacao['observacoes'] ?? '-') ?></p>
    <div class="btns">
        <button class="imprimir" onclick="window.print()">Imprimir</button>
        <button class="voltar" onclick="window.close()">Fechar</button>
    </div>
</div>
</body>
</html>
