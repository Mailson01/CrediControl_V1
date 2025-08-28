<?php
include __DIR__.'/../config/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

$conexao = new Conexao();
$conn = $conexao->conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $valorDebito = floatval($_POST['valor_debito']);
    $descricaoDebito = trim($_POST['descricao_debito']);

    // Busca o registro atual do pagamento adiantado
    $stmt = $conn->prepare("SELECT * FROM pagamentos_adiantados WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $pagamento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pagamento) {
        die("Pagamento não encontrado.");
    }

    $saldoAtual = floatval($pagamento['valor_pago']);
    if ($valorDebito > $saldoAtual) {
        die("Erro: valor do débito maior que o saldo disponível.");
    }

    // Calcula novo saldo
    $novoSaldo = $saldoAtual - $valorDebito;

    // Atualiza o saldo no banco
    $atualizar = $conn->prepare("UPDATE pagamentos_adiantados SET valor_pago = :novoSaldo WHERE id = :id");
    $atualizar->execute([':novoSaldo' => $novoSaldo, ':id' => $id]);

    // Registra o débito em uma tabela de histórico (precisa criar essa tabela)
    // Exemplo da tabela debitos_adiantados(id, pagamento_id, valor_debito, descricao, data_hora)
    $insertHistorico = $conn->prepare("INSERT INTO debitos_adiantados (pagamento_id, valor_debito, descricao, data_hora) VALUES (:pagamento_id, :valor_debito, :descricao, :data_hora)");
    $insertHistorico->execute([
        ':pagamento_id' => $id,
        ':valor_debito' => $valorDebito,
        ':descricao' => $descricaoDebito,
        ':data_hora' => date('Y-m-d H:i:s')
    ]);

    // Exibe comprovante
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8" />
        <title>Comprovante de Débito Parcial</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; background: #f8f8f8; }
            .comprovante { background: white; max-width: 500px; margin: 20px auto; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);}
            h2 { color: #003366; }
            p { font-size: 18px; margin: 10px 0; }
            strong { color: #003366; }
            button { background-color: #003366; color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; font-weight: bold; }
            button:hover { background-color: #002244; }
            a { display: inline-block; margin-top: 20px; color: #003366; font-weight: bold; text-decoration: none; }
            a:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <div class="comprovante">
            <h2>Curral de Fora mat. de construções</h2>
            <h4>Comprovante de Débito Parcial</h4>
            <p><strong>Cliente:</strong> <?= htmlspecialchars($pagamento['cliente']) ?></p>
            <p><strong>Produto:</strong> <?= htmlspecialchars($pagamento['produto']) ?></p>
            <p><strong>Valor debitado:</strong> R$ <?= number_format($valorDebito, 2, ',', '.') ?></p>
            <p><strong>Descrição do que pegou:</strong> <?= htmlspecialchars($descricaoDebito) ?></p>
            <p><strong>Saldo restante:</strong> R$ <?= number_format($novoSaldo, 2, ',', '.') ?></p>
            <p><strong>Data e hora do débito:</strong> <?= date('d/m/Y H:i:s') ?></p>
            <button onclick="window.print()">Imprimir Comprovante</button>
            <br>
            <a href="listagem.php">Voltar à Listagem</a> | <a href="dashboard.php">Dashboard</a>
        </div>
    </body>
    </html>
    <?php
} else {
    echo "Método inválido.";
}
?>
