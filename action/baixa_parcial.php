<?php
session_start();
if (!isset($_SESSION['loginn'])) {
    header("Location: ./tela_login.php");
    exit();
}

include __DIR__.'/../config/conexao.php';

$conexao = new Conexao();
$conn = $conexao->conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = intval($_POST['cliente_id']);
    $valor_baixa = floatval($_POST['valor_baixa']);
    $observacao = trim($_POST['observacao'] ?? '');

    try {
        // 1. Buscar o saldo atual do cliente
        $stmt_saldo = $conn->prepare("
            SELECT SUM(valor_pago) AS saldo
            FROM pagamentos_adiantados
            WHERE cliente_id = :cliente_id AND retirado = 'Não'
        ");
        $stmt_saldo->bindParam(':cliente_id', $cliente_id, PDO::PARAM_INT);
        $stmt_saldo->execute();
        $saldo_atual = $stmt_saldo->fetchColumn();

        if ($valor_baixa > $saldo_atual) {
            echo "<script>
                alert('Erro: O valor da baixa é maior que o saldo atual do cliente. Saldo disponível: R$ " . number_format($saldo_atual,2,',','.') . "');
                window.history.back();
            </script>";
            exit();
        }

        // 2. Buscar o pagamento mais antigo que ainda não foi retirado
        $stmt_pagto = $conn->prepare("
            SELECT id, valor_pago
            FROM pagamentos_adiantados
            WHERE cliente_id = :cliente_id AND retirado='Não' AND valor_pago > 0
            ORDER BY id ASC
            LIMIT 1
        ");
        $stmt_pagto->bindParam(':cliente_id', $cliente_id, PDO::PARAM_INT);
        $stmt_pagto->execute();
        $pagamento = $stmt_pagto->fetch(PDO::FETCH_ASSOC);

        if ($pagamento) {
            $novo_valor = $pagamento['valor_pago'] - $valor_baixa;
            if ($novo_valor < 0) $novo_valor = 0;

            // 3. Atualizar o valor do pagamento
            $update = $conn->prepare("
                UPDATE pagamentos_adiantados
                SET valor_pago = :novo_valor
                WHERE id = :id
            ");
            $update->bindParam(':novo_valor', $novo_valor);
            $update->bindParam(':id', $pagamento['id']);
            $update->execute();

            // 4. Salvar observação, se houver
            if (!empty($observacao)) {
                $stmt_obs = $conn->prepare("
                    INSERT INTO observacoes (cliente_id, observacao)
                    VALUES (:cliente_id, :observacao)
                ");
                $stmt_obs->bindParam(':cliente_id', $cliente_id);
                $stmt_obs->bindParam(':observacao', $observacao);
                $stmt_obs->execute();
            }

            // 5. Buscar nome do cliente
            $stmt_cliente = $conn->prepare("SELECT nome FROM clientes WHERE id = :id");
            $stmt_cliente->bindParam(':id', $cliente_id);
            $stmt_cliente->execute();
            $cliente_nome = $stmt_cliente->fetchColumn();

            $novo_saldo = $saldo_atual - $valor_baixa;

            // 6. Exibir comprovante
            ?>
            <!DOCTYPE html>
            <html lang="pt-br">
            <head>
                <meta charset="UTF-8">
                <title>Comprovante de Retirada</title>
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
                    <h2>Comprovante de Retirada</h2>
                    <p><strong>Cliente:</strong> <?= htmlspecialchars($cliente_nome) ?></p>
                    <p><strong>Valor Retirado:</strong> R$ <?= number_format($valor_baixa,2,',','.') ?></p>
                    <p><strong>Saldo Atual:</strong> R$ <?= number_format($novo_saldo,2,',','.') ?></p>
                    <?php if($observacao): ?>
                        <p><strong>Observação:</strong> <?= htmlspecialchars($observacao) ?></p>
                    <?php endif; ?>
                    <p><strong>Data de Retirada:</strong> <?= date('d/m/Y H:i:s') ?></p>
                    <div class="btns">
                        <button class="imprimir" onclick="window.print()">Imprimir</button>
                        <button class="voltar" onclick="window.location='../views/listagem.php'">Voltar</button>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit();

        } else {
            echo "Não há pagamentos disponíveis para baixa.";
        }

    } catch (PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
}
?>
