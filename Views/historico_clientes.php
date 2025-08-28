<?php
session_start();
if (!isset($_SESSION['loginn'])) {
    header("Location: ./tela_login.php");
    exit();
}

include __DIR__ . '/../config/conexao.php';
$conexao = new Conexao();
$conn = $conexao->conectar();

$cliente_selecionado = intval($_GET['cliente_id'] ?? 0);

// Buscar todos os clientes
$clientes = [];
try {
    $stmt = $conn->prepare("SELECT id, nome FROM clientes ORDER BY nome ASC");
    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erro ao buscar clientes: " . $e->getMessage();
    exit;
}

// Buscar histórico do cliente selecionado
$historico = [];
$nome_cliente = '';
if ($cliente_selecionado > 0) {
    try {
        $stmtHist = $conn->prepare("
            SELECT p.id, p.produto, p.valor_pago, p.data_pagamento, p.data_prevista_retirada,
                   p.retirado, 
                   GROUP_CONCAT(DISTINCT o.observacao ORDER BY o.id DESC SEPARATOR ' | ') AS observacoes
            FROM pagamentos_adiantados p
            LEFT JOIN observacoes o ON o.cliente_id = p.cliente_id
            WHERE p.cliente_id = :cliente_id
            GROUP BY p.id
            ORDER BY p.data_pagamento DESC
        ");
        $stmtHist->bindParam(':cliente_id', $cliente_selecionado, PDO::PARAM_INT);
        $stmtHist->execute();
        $historico = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

        // Nome do cliente
        foreach ($clientes as $c) {
            if ($c['id'] == $cliente_selecionado) {
                $nome_cliente = $c['nome'];
                break;
            }
        }

    } catch (PDOException $e) {
        echo "Erro ao buscar histórico: " . $e->getMessage();
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Histórico de Clientes</title>
<style>
body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
h2 { color: #003366; text-align: center; margin-bottom: 20px; }
.container { max-width: 1200px; margin: auto; }
table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 30px; }
th, td { padding: 12px 10px; border-bottom: 1px solid #ddd; text-align: center; }
th { background: #003366; color: white; font-weight: bold; }
tr:hover { background: #f1f9ff; }
button { background: #003366; color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; font-weight: bold; transition: background 0.3s; }
button:hover { background: #002244; }
.observacoes { text-align: left; font-size: 12px; color: #666; }
.back-btn { margin-bottom: 20px; display: inline-block; background: #3498db; }
.back-btn:hover { background: #2980b9; }
@media(max-width:768px){ th, td{ font-size:12px; padding:8px; } }
</style>
</head>
<body>
<div class="container">
<h2>Histórico de Clientes</h2>

<?php if($cliente_selecionado == 0): ?>
    <!-- Lista de clientes -->
    <table>
        <tr>
            <th>Nome do Cliente</th>
            <th>Ações</th>
        </tr>
        <?php if(empty($clientes)): ?>
            <tr><td colspan="2">Nenhum cliente cadastrado.</td></tr>
        <?php else: ?>
            <?php foreach($clientes as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['nome']) ?></td>
                    <td>
                        <a href="historico_clientes.php?cliente_id=<?= $c['id'] ?>"><button>Visualizar Histórico</button></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
<?php else: ?>
    <!-- Histórico do cliente selecionado -->
    <a href="historico_clientes.php" class="back-btn"><button>Voltar</button></a>
    <a href="dashboard.php" class="back-btn"><button>Dashboard</button></a>
    <h3>Transações de <?= htmlspecialchars($nome_cliente) ?></h3>
    <?php if(empty($historico)): ?>
        <p>Nenhuma transação encontrada para este cliente.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Produto</th>
                <th>Valor</th>
                <th>Data Pagamento</th>
                <th>Data Prevista</th>
                <th>Retirado</th>
                <th>Observações</th>
                <th>Ações</th>
            </tr>
            <?php foreach($historico as $h): ?>
                <tr>
                    <td><?= $h['id'] ?></td>
                    <td><?= htmlspecialchars($h['produto']) ?></td>
                    <td>R$ <?= number_format($h['valor_pago'],2,',','.') ?></td>
                    <td><?= date('d/m/Y H:i:s', strtotime($h['data_pagamento'])) ?></td>
                    <td><?= $h['data_prevista_retirada'] ?? '-' ?></td>
                    <td><?= $h['retirado'] ?></td>
                    <td class="observacoes"><?= htmlspecialchars($h['observacoes'] ?? '-') ?></td>
                    <td>
                        <form method="POST" action="imprimir_transacao.php" target="_blank">
                            <input type="hidden" name="transacao_id" value="<?= $h['id'] ?>">
                            <button type="submit">Imprimir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
<?php endif; ?>
</div>
</body>
</html>
