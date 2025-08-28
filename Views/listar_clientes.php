<?php
session_start();
if (!isset($_SESSION['loginn'])) {
    header("Location: ./tela_login.php");
    exit();
}

include __DIR__.'/../config/conexao.php';
$conexao = new Conexao();
$conn = $conexao->conectar();

// Listar clientes
$stmt = $conn->prepare("SELECT * FROM clientes ORDER BY id DESC");
$stmt->execute();
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Lista de Clientes</title>
<style>
body { font-family: Arial, sans-serif; background: #f8f8f8; padding: 20px; }
h2 { text-align: center; color: #003366; }
table { width: 100%; border-collapse: collapse; background: white; margin: 20px auto; max-width: 900px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);}
th, td { border: 1px solid #ddd; padding: 12px; text-align: center;}
th { background-color: #003366; color: white;}
button, a.btn { background-color: #003366; color: white; padding: 8px 12px; text-decoration: none; border: none; border-radius: 5px; margin: 2px; cursor: pointer; display: inline-block; transition: 0.3s;}
button:hover, a.btn:hover { background-color: #002244;}
.actions { display: flex; justify-content: center; gap: 5px;}
.top-btns { text-align: center; margin-bottom: 20px;}
</style>
</head>
<body>

<h2>Listagem de Clientes</h2>

<div class="top-btns">
    <a href="cadastro.php" class="btn">Cadastrar Novo Cliente</a>
    <a href="dashboard.php" class="btn">Dashboard</a>
</div>

<table>
    <tr>
        <th>ID</th>
        <th>Nome</th>
        <th>Crédito</th>
        <th>Ações</th>
    </tr>

    <?php if ($clientes): ?>
        <?php foreach ($clientes as $cliente): ?>
        <?php
            // Calcula saldo positivo atual do cliente
            $stmtSaldo = $conn->prepare("SELECT SUM(valor_pago) as saldo FROM pagamentos_adiantados WHERE cliente_id = :cliente_id AND retirado = 'Não'");
            $stmtSaldo->bindParam(':cliente_id', $cliente['id'], PDO::PARAM_INT);
            $stmtSaldo->execute();
            $saldo = $stmtSaldo->fetchColumn() ?: 0;
        ?>
        <tr>
            <td><?= htmlspecialchars($cliente['id']) ?></td>
            <td><?= htmlspecialchars($cliente['nome'])?></td>
            <td>R$ <?= number_format($saldo, 2, ',', '.') ?></td>
            <td class="actions">
                <a href="editar_cliente.php?id=<?= $cliente['id'] ?>" class="btn">Editar</a>
                <a href="../action/excluir_cliente.php?id=<?= $cliente['id'] ?>" class="btn" onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</a>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="4">Nenhum cliente encontrado.</td></tr>
    <?php endif; ?>
</table>

</body>
</html>
