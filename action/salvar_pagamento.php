<?php
session_start();
if (!isset($_SESSION['loginn'])) {
    header("Location: ./tela_login.php");
    exit();
}

include __DIR__.'/../config/conexao.php';

$conexao = new Conexao();
$conn = $conexao->conectar();

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente = trim($_POST['cliente']);
    $valor_pago = floatval($_POST['valor_pago']);
    $produto = trim($_POST['produto']);
    $data_prevista = $_POST['data_prevista_retirada'] ?? '';
    
    // **AQUI ESTÁ A CORREÇÃO PRINCIPAL:**
    // Mude de $_POST['observacao'] para $_POST['observacoes'] para corresponder ao formulário.
    $observacao = trim($_POST['observacoes'] ?? '');

    try {
        // Verifica se o cliente já existe
        $stmt = $conn->prepare("SELECT id FROM clientes WHERE nome = :nome");
        $stmt->bindParam(':nome', $cliente);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            $insertCliente = $conn->prepare("INSERT INTO clientes (nome) VALUES (:nome)");
            $insertCliente->bindParam(':nome', $cliente);
            $insertCliente->execute();
            $cliente_id = $conn->lastInsertId();
        } else {
            $cliente_id = $stmt->fetchColumn();
        }

        // Inserir pagamento na tabela pagamentos_adiantados
        $insertPagto = $conn->prepare("INSERT INTO pagamentos_adiantados 
            (cliente_id, valor_pago, produto, data_prevista_retirada, data_pagamento, retirado) 
            VALUES (:cliente_id, :valor_pago, :produto, :data_prevista, NOW(), 'Não')");
        $insertPagto->bindParam(':cliente_id', $cliente_id);
        $insertPagto->bindParam(':valor_pago', $valor_pago);
        $insertPagto->bindParam(':produto', $produto);
        $insertPagto->bindParam(':data_prevista', $data_prevista);
        $insertPagto->execute();

        // Se houver uma observação, insere na tabela 'observacoes'
        if (!empty($observacao)) {
            $insertObs = $conn->prepare("INSERT INTO observacoes (cliente_id, observacao) VALUES (:cliente_id, :observacao)");
            $insertObs->bindParam(':cliente_id', $cliente_id);
            $insertObs->bindParam(':observacao', $observacao);
            $insertObs->execute();
        }

        // ... (resto do comprovante na tela) ...
        ?>
        <!DOCTYPE html>
        <html lang="pt-br">
        <head>
        <meta charset="UTF-8">
        <title>Comprovante de Pagamento</title>
        <style>
            body {
        font-family: Arial, sans-serif;
        background-color: #f5f5f5;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }

    .comprovante {
        background: #fff;
        padding: 20px 30px;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        width: 400px;
        text-align: center;
    }

    .comprovante h2 {
        margin-bottom: 15px;
        color: #333;
    }

    .comprovante p {
        margin: 8px 0;
        font-size: 16px;
        color: #444;
        text-align: left;
    }

    .btns {
        margin-top: 20px;
        display: flex;
        justify-content: space-between;
    }

    .btns button {
        padding: 10px 18px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
        transition: 0.3s ease;
    }

    .btns .imprimir {
        background-color: #28a745;
        color: white;
    }

    .btns .imprimir:hover {
        background-color: #218838;
    }

    .btns .voltar {
        background-color: #007bff;
        color: white;
    }

    .btns .voltar:hover {
        background-color: #0056b3;
    }

    @media print {
        body {
            background: white;
        }
        .btns {
            display: none;
        }
        .comprovante {
            box-shadow: none;
            border: none;
            width: 100%;
        }
    }
</style>
        </style>
        </head>
        <body>
            <div class="comprovante">
                <h2>Comprovante de Pagamento</h2>
                <p><strong>Cliente:</strong> <?= htmlspecialchars($cliente) ?></p>
                <p><strong>Produto:</strong> <?= htmlspecialchars($produto) ?></p>
                <p><strong>Valor Pago:</strong> R$ <?= number_format($valor_pago, 2, ',', '.') ?></p>
                <p><strong>Data de Pagamento:</strong> <?= date('d/m/Y H:i:s') ?></p>
                <?php if($observacao): ?>
                    <p><strong>Observação:</strong> <?= htmlspecialchars($observacao) ?></p>
                <?php endif; ?>
                <div class="btns">
                    <button class="imprimir" onclick="window.print()">Imprimir</button>
                    <button class="voltar" onclick="window.location='../Views/listagem.php'">Voltar</button>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit();

    } catch (PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Comprovante de Pagamento</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f5f5f5;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }

    .comprovante {
        background: #fff;
        padding: 20px 30px;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        width: 400px;
        text-align: center;
    }

    .comprovante h2 {
        margin-bottom: 15px;
        color: #333;
    }

    .comprovante p {
        margin: 8px 0;
        font-size: 16px;
        color: #444;
        text-align: left;
    }

    .btns {
        margin-top: 20px;
        display: flex;
        justify-content: space-between;
    }

    .btns button {
        padding: 10px 18px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
        transition: 0.3s ease;
    }

    .btns .imprimir {
        background-color: #28a745;
        color: white;
    }

    .btns .imprimir:hover {
        background-color: #218838;
    }

    .btns .voltar {
        background-color: #007bff;
        color: white;
    }

    .btns .voltar:hover {
        background-color: #0056b3;
    }

    @media print {
        body {
            background: white;
        }
        .btns {
            display: none;
        }
        .comprovante {
            box-shadow: none;
            border: none;
            width: 100%;
        }
    }
</style>
</head>
<body>
    <div class="comprovante">
        <h2>Comprovante de Pagamento</h2>
        <p><strong>Cliente:</strong> <?= htmlspecialchars($cliente) ?></p>
        <p><strong>Produto:</strong> <?= htmlspecialchars($produto) ?></p>
        <p><strong>Valor Pago:</strong> R$ <?= number_format($valor_pago, 2, ',', '.') ?></p>
        <p><strong>Data de Pagamento:</strong> <?= date('d/m/Y H:i:s') ?></p>
        <?php if($observacao): ?>
            <p><strong>Observação:</strong> <?= htmlspecialchars($observacao) ?></p>
        <?php endif; ?>
        <div class="btns">
            <button class="imprimir" onclick="window.print()">Imprimir</button>
            <button class="voltar" onclick="window.location='../Views/listagem.php'">Voltar</button>
        </div>
    </div>
</body>
</html>
