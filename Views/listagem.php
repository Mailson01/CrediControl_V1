<?php
session_start();
if (!isset($_SESSION['loginn'])) {
    header("Location: ./tela_login.php");
    exit();
}

// 15 minutos
$tempo_limite = 2.5 * 60;
if (isset($_SESSION['ultimo_acesso']) && (time() - $_SESSION['ultimo_acesso']) > $tempo_limite) {
    session_unset();
    session_destroy();
    header("Location: ./tela_login.php?expirado=1");
    exit();
}
$_SESSION['ultimo_acesso'] = time();

error_reporting(E_ALL);
ini_set('display_errors', 1);

include __DIR__ . '/../config/conexao.php';
$conexao = new Conexao();
$conn = $conexao->conectar();

try {
    // ====== BUSCA / FILTRO ======
    $temBusca = isset($_GET['buscar']) && $_GET['buscar'] !== '';
    $busca = $temBusca ? '%' . $_GET['buscar'] . '%' : null;

    // ====== TOTAL DE CRÉDITOS POR CLIENTE (apenas não retirados) ======
    $sqlCreditos = "
        SELECT 
            c.id AS cliente_id,
            c.nome AS cliente_nome,
            SUM(CASE WHEN p.retirado = 'Não' THEN p.valor_pago ELSE 0 END) AS total_pago,
            MAX(CASE WHEN p.retirado = 'Não' THEN p.data_prevista_retirada END) AS data_prevista,
            SUBSTRING_INDEX(
                GROUP_CONCAT(CASE WHEN p.retirado = 'Não' THEN p.produto END ORDER BY p.id DESC SEPARATOR ','),
                ',', 1
            ) AS produto
        FROM clientes c
        INNER JOIN pagamentos_adiantados p ON p.cliente_id = c.id
        WHERE p.retirado = 'Não'
        " . ($temBusca ? " AND c.nome LIKE :busca " : "") . "
        GROUP BY c.id, c.nome
        ORDER BY c.nome ASC
    ";
    $stCred = $conn->prepare($sqlCreditos);
    if ($temBusca) $stCred->bindValue(':busca', $busca, PDO::PARAM_STR);
    $stCred->execute();
    $creditos = $stCred->fetchAll(PDO::FETCH_ASSOC);

    // Map inicial de clientes da listagem
    $clientes = [];
    $ids = [];
    foreach ($creditos as $row) {
        $id = (int)$row['cliente_id'];
        $ids[] = $id;
        $clientes[$id] = [
            'nome'         => $row['cliente_nome'],
            'produto'      => $row['produto'],
            'valor_total'  => (float)$row['total_pago'], // será ajustado subtraindo baixas
            'data_prevista'=> $row['data_prevista'],
            'observacoes'  => [],
        ];
    }

    // Se não há clientes, já segue para a view
    if (!empty($ids)) {

        // ====== BAIXAS PARCIAIS (subtrai do crédito) ======
        $baixasPorCliente = [];

        // Checa se existe tabela 'baixas_parciais'
        $chk = $conn->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'baixas_parciais'");
        $chk->execute();
        if ($chk->fetchColumn()) {
            // Soma baixas por cliente
            $inIds = implode(',', array_fill(0, count($ids), '?'));
            $stB = $conn->prepare("SELECT cliente_id, SUM(valor_baixa) AS total_baixa FROM baixas_parciais WHERE cliente_id IN ($inIds) GROUP BY cliente_id");
            $stB->execute($ids);
            foreach ($stB->fetchAll(PDO::FETCH_ASSOC) as $b) {
                $baixasPorCliente[(int)$b['cliente_id']] = (float)$b['total_baixa'];
            }
        } else {
            // Tenta fallback: historico_clientes (assumindo colunas: cliente_id, valor, tipo começando com 'baixa')
            $chk2 = $conn->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'historico_clientes'");
            $chk2->execute();
            if ($chk2->fetchColumn()) {
                $inIds = implode(',', array_fill(0, count($ids), '?'));
                $stB = $conn->prepare("
                    SELECT cliente_id, SUM(valor) AS total_baixa
                    FROM historico_clientes
                    WHERE cliente_id IN ($inIds) 
                      AND LOWER(tipo) LIKE 'baixa%'
                    GROUP BY cliente_id
                ");
                $stB->execute($ids);
                foreach ($stB->fetchAll(PDO::FETCH_ASSOC) as $b) {
                    $baixasPorCliente[(int)$b['cliente_id']] = (float)$b['total_baixa'];
                }
            }
        }

        // Aplica (crédito atual = total_pago - baixas). Trava em zero para evitar negativo.
        foreach ($clientes as $id => &$cli) {
            $baixa = $baixasPorCliente[$id] ?? 0;
            $cli['valor_total'] = max(0, (float)$cli['valor_total'] - (float)$baixa);
        }
        unset($cli);

        // ====== OBSERVAÇÕES (em lote) + remover duplicadas ======
        $inIds = implode(',', array_fill(0, count($ids), '?'));
        $stObs = $conn->prepare("SELECT cliente_id, observacao FROM observacoes WHERE cliente_id IN ($inIds) ORDER BY id DESC");
        $stObs->execute($ids);
        $obsRows = $stObs->fetchAll(PDO::FETCH_ASSOC);
        foreach ($obsRows as $o) {
            $cid = (int)$o['cliente_id'];
            if (isset($clientes[$cid]) && !empty($o['observacao'])) {
                $clientes[$cid]['observacoes'][] = $o['observacao'];
            }
        }
        foreach ($clientes as &$c) {
            $c['observacoes'] = array_values(array_unique($c['observacoes']));
        }
        unset($c);
    }

} catch (PDOException $e) {
    echo "Erro na consulta: " . htmlspecialchars($e->getMessage());
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Controle de Pagamentos Adiantados</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: #f8f8f8;
        margin: 0;
        padding: 20px;
    }
    h2 { color: #003366; text-align: center; }
    .btn-container {
        max-width: 1200px;
        margin: 0 auto 20px;
        text-align: center;
    }
    .btn-container a {
        display: inline-block;
        background-color: #003366;
        color: white;
        padding: 10px 20px;
        margin: 5px 10px;
        border-radius: 6px;
        font-weight: bold;
        text-decoration: none;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }
    .btn-container a:hover { background-color: #002244; }
    .search-container {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
    }
    table {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    th, td {
        padding: 12px 15px;
        border-bottom: 1px solid #ddd;
        text-align: center;
        color: #333;
    }
    th { background-color: #003366; color: white; font-weight: bold; }
    tr:hover { background-color: #f1f9ff; }
    .observacoes {
        text-align: left;
        font-size: 12px;
        font-style: italic;
        color: #666;
    }
    form { display: inline-block; margin: 5px; }
    button {
        background-color: #003366;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
        transition: background-color 0.3s ease;
    }
    button:hover { background-color: #002244; }
    input[type="number"], input[type="text"] {
        padding: 4px 6px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 14px;
        box-sizing: border-box;
    }
    input[type="number"] { width: 90px; }
    input[type="text"] { width: 150px; }
    @media screen and (max-width: 768px) {
        table, thead, tbody, th, td, tr { display: block; width: 100%; }
        th { display: none; }
        td { text-align: left; padding-left: 50%; position: relative; border-bottom: 1px solid #ddd; }
        td::before {
            position: absolute;
            left: 15px;
            width: 45%;
            font-weight: bold;
            white-space: nowrap;
        }
        td:nth-of-type(1)::before { content: "Cliente"; }
        td:nth-of-type(2)::before { content: "Produto"; }
        td:nth-of-type(3)::before { content: "Crédito Atual"; }
        td:nth-of-type(4)::before { content: "Data Prevista"; }
        td:nth-of-type(5)::before { content: "Observações"; }
        td:nth-of-type(6)::before { content: "Ações"; }
        input[type="text"], input[type="number"], button { width: 100%; margin-bottom: 5px; }
    }
</style>
</head>
<body>

<h2>Pagamentos Adiantados</h2>

<div class="search-container">
    <form method="GET">
        <input type="text" name="buscar" placeholder="Buscar cliente..." value="<?= isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : '' ?>">
        <button type="submit">🔍 Buscar</button>
    </form>
    <?php if (!empty($_GET['buscar'])): ?>
        <a href="listagem.php">Limpar Busca</a>
    <?php endif; ?>
</div>

<div class="btn-container">
    <a href="cadastro.php">Cadastrar Pagamento</a>
    <a href="dashboard.php">Dashboard</a>
</div>

<table>
    <tr>
        <th>Cliente</th>
        <th>Produto</th>
        <th>Crédito Atual</th>
        <th>Data Prevista</th>
        <th>Observações</th>
        <th>Ações</th>
    </tr>
    <?php if (empty($clientes)): ?>
        <tr><td colspan="6">Nenhum pagamento registrado.</td></tr>
    <?php else: ?>
        <?php foreach ($clientes as $id => $cli): ?>
            <tr>
                <td><?= htmlspecialchars($cli['nome']) ?></td>
                <td><?= htmlspecialchars($cli['produto'] ?? '-') ?></td>
                <td>R$ <?= number_format((float)$cli['valor_total'], 2, ',', '.') ?></td>
                <td><?= !empty($cli['data_prevista']) ? date('d/m/Y', strtotime($cli['data_prevista'])) : '-' ?></td>
                <td class="observacoes">
                    <?php if (empty($cli['observacoes'])): ?>
                        Nenhuma observação.
                    <?php else: ?>
                        <?php foreach ($cli['observacoes'] as $obs): ?>
                            - <?= htmlspecialchars($obs) ?><br>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </td>
                <td>
                    <form action="../action/baixa_parcial.php" method="POST">
                        <input type="hidden" name="cliente_id" value="<?= (int)$id ?>">
                        <input type="number" name="valor_baixa" step="0.01" placeholder="Valor" required>
                        <input type="text" name="observacao" placeholder="Observação">
                        <button type="submit">Baixa Parcial</button>
                    </form>
                    <form action="../action/marcar_retirado.php" method="POST">
                        <input type="hidden" name="cliente_id" value="<?= (int)$id ?>">
                        <input type="text" name="observacao" placeholder="Observação">
                        <button type="submit">Retirar Total</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</table>

</body>
</html>
