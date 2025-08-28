<?php
session_start();
$tempo_limite = 2.5 * 60; // 15 minutos em segundos

if (!isset($_SESSION['loginn'])){ //se o usuario n estiver logado
    header ("Refresh:0.5, url= ../tela_login.php"); // redirecione para a tela de login
    exit();
}
if (isset($_SESSION['ultimo_acesso']) && (time() - $_SESSION['ultimo_acesso']) > $tempo_limite) {
    // Tempo de inatividade excedido
    session_unset(); // limpa a sessão
    session_destroy(); // destrói a sessão
    header("Location: ../tela_login.php?expirado=1"); // redireciona para a página de login com mensagem de sessão expirada
    exit;
}

// Atualiza o tempo do último acesso
$_SESSION['ultimo_acesso'] = time();

error_reporting(E_ALL);
ini_set('display_errors', 1);

include __DIR__.'/../config/conexao.php';

$conexao = new Conexao();
$conn = $conexao->conectar();
$usuarioLogado = $_SESSION['loginn'] ?? 'Convidado';
// Verifica se tem filtro de período
if (!empty($_GET['data_inicio']) && !empty($_GET['data_fim'])) {
    $inicio = $_GET['data_inicio'];
    $fim = $_GET['data_fim'];

    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT cliente_id) as total_clientes, SUM(valor_pago) as total_credito 
        FROM pagamentos_adiantados 
        WHERE retirado = 'Não' AND valor_pago > 0 AND data_pagamento BETWEEN :inicio AND :fim
    ");
    $stmt->bindParam(':inicio', $inicio);
    $stmt->bindParam(':fim', $fim);
    $stmt->execute();
    $data_credito = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt2 = $conn->prepare("
        SELECT COUNT(DISTINCT cliente_id) as retiradas_proximas, SUM(valor_pago) as total_retiradas 
        FROM pagamentos_adiantados 
        WHERE retirado = 'Não' AND valor_pago > 0 
        AND data_prevista_retirada BETWEEN :inicio AND :fim
    ");
    $stmt2->bindParam(':inicio', $inicio);
    $stmt2->bindParam(':fim', $fim);
    $stmt2->execute();
    $data_retirada = $stmt2->fetch(PDO::FETCH_ASSOC);

} else {
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT cliente_id) as total_clientes, SUM(valor_pago) as total_credito 
        FROM pagamentos_adiantados 
        WHERE retirado = 'Não' AND valor_pago > 0
    ");
    $stmt->execute();
    $data_credito = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt2 = $conn->prepare("
        SELECT COUNT(DISTINCT cliente_id) as retiradas_proximas, SUM(valor_pago) as total_retiradas 
        FROM pagamentos_adiantados 
        WHERE retirado = 'Não' AND valor_pago > 0 
        AND data_prevista_retirada <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ");
    $stmt2->execute();
    $data_retirada = $stmt2->fetch(PDO::FETCH_ASSOC);
}



// Créditos já retirados (sempre pega tudo)
$stmt3 = $conn->prepare("SELECT SUM(valor_pago) as total_retirado FROM pagamentos_adiantados WHERE retirado = 'Sim'");
$stmt3->execute();
$data_retirado = $stmt3->fetch(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Controle de Créditos</title>
    <link rel="icon" href="sistema_controle_pag/img/favicon_io/favicon.ico" type="image/x-icon">
<style>
    body {
        font-family: Arial, sans-serif;
        background: #c5c1c1ff;
        margin: 0;
       
    }

    header {
        background: #003366;
        color: white;
        padding: 20px;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        text-align: center;
    }

    header div {
        flex: 1 1 100%;
        margin: 5px 0;
    }

    h1 {
        margin: 0;
        text-decoration: underline;
        font-weight: bolder;
        font-size: 20px;
    }

    .main-content {
        display: flex;
        flex-direction: row;
        gap: 20px;
        padding: 3; /* Removido o padding para que a sidebar ocupe toda a altura */
    }

    .sidebar {
        height: 70vh;
        background: #003366;;
        color: white;
        padding: 20px;
        width: 150px; /* Largura fixa para a sidebar */
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);
    }
    
    .sidebar h4 {
        margin-bottom: 30px;
        font-size: 20px;
        color: black;
        background-color: white;
        text-align: center;
        border-radius: 10px;
    }

    .sidebar a {
        color: black;
        display: block;
        margin: 10px 0;
        text-decoration: none;
        padding: 12px 20px; /* Aumentado para botões maiores */
        border-radius: 5px;
        transition: 0.3s;
        font-weight: bold;
          background-color: white;
        text-align: center;
        border-radius: 10px;
    }

    .sidebar a:hover {
        background: #007bff;
        text-decoration: none;
    }
    
    .dashboard-content {
        flex: 1;
        padding: 20px;
        padding-top: 0;
    }

    .container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: center;
        padding: 20px 0;
    }

    .card {
        background: white;
        border-radius: 8px;
        padding: 10px;
        width: 220px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        text-align: center;
    }

    .card h3 {
        margin: 0;
        color: #003366;
    }

    .card p {
        font-size: 24px;
        margin: 10px 0;
        font-weight: bold;
    }

    footer {
        background: #003366;
        color: white;
        text-align: center;
        padding: 10px;
        margin-top: 20px;
    }

    form {
        text-align: center;
        margin: 20px 0;
    }

    #graficoCreditos {
        max-width: 450px;
        max-height: 220px;
        display: block;
        margin: 20px auto;
    }

    /* Responsividade */
    @media (max-width: 768px) {
        header {
            flex-direction: column;
            text-align: center;
        }

        header div {
            flex: 1 1 100%;
        }

        .main-content {
            flex-direction: column;
        }

        .sidebar {
            height: auto;
            width: 100%;
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            padding: 10px;
        }
        
        .sidebar h4 {
            width: 100%;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .sidebar a {
            width: 45%;
            margin: 5px;
            text-align: center;
        }
        
        .dashboard-content {
            padding: 10px;
        }

        .container {
            flex-direction: column;
            align-items: center;
        }

        .card {
            width: 90%;
        }

        form {
            display: block;
            margin: 10px auto;
        }
    }

    @media (max-width: 480px) {
        h1 {
            font-size: 18px;
        }

        .card p {
            font-size: 20px;
        }
        
        .sidebar a {
            width: 100%;
        }
    }
</style>

</head>
<body>

<header style="background: #003366; color: white; padding: 20px; display: flex; align-items: center; justify-content: space-between;">
    <div style="flex: 1; text-align: left; font-weight: bold;" id="dataHora"></div>
  

    <div style="flex: 1; text-align: center;">
        
        <span style="font-size: 38px; font-weight: bold;">CURRAL DE FORA MAT. DE CONSTRUÇÕES</span>

    </div>

    <div style="flex: 1; text-align: right;">
        <form action="../action/logout.php" method="POST" style="display: inline;">  <?php
    echo '<span style="font-weight:bold; color:WEIGT; font-size:20px">Olá ' . htmlspecialchars($usuarioLogado) . '!!</span>';

    ?>
            <br><br>
            <button type="submit" style="
                background: transparent;
                color: white;
                border: 2px solid white;
                padding: 5px 12px;
                border-radius: 20px;
                cursor: pointer;
                font-weight: bold;
                transition: 0.3s;
            " onmouseover="this.style.background='white'; this.style.color='#003366';" onmouseout="this.style.background='transparent'; this.style.color='white';">
                Sair
            </button>
        </form>
    </div>
</header>

<div class="main-content">
    <div class="sidebar">
        <h4>Dashboard</h4>
        <a href="listagem.php">Listar Créditos</a>
        <a href="listar_clientes.php">Listar Clientes</a>
        <a href="cadastro.php">Cadastrar Novo Crédito</a>
        <div class="btn-container">
    <!-- Botão existente -->
    <a href="historico_clientes.php">Histórico de Clientes</a>
</div>

    </div>

    <div class="dashboard-content">
        <div class="container">
            <div class="card">
                <h3>Clientes com Crédito</h3>
                <p><?= $data_credito['total_clientes'] ?? 0 ?></p>
            </div>
            <div class="card">
                <h3>Total em Créditos</h3>
                <p>R$ <?= number_format($data_credito['total_credito'] ?? 0, 2, ',', '.') ?></p>
            </div>
            <div class="card">
                <h3>Previsão p/ 7 dias</h3>
                <p><?= $data_retirada['retiradas_proximas'] ?? 0 ?> clientes</p>
            </div>
            <div class="card">
                <h3>Créditos Retirados</h3>
                <p>R$ <?= number_format($data_retirado['total_retirado'] ?? 0, 2, ',', '.') ?></p>
            </div>
            <div class="card">
                <h3>Saldo Ativo</h3>
                <p>R$ <?= number_format(($data_credito['total_credito'] ?? 0), 2, ',', '.') ?></p>
            </div>
        </div>

        <canvas id="graficoCreditos"></canvas>
    
        <form action="dashboard.php" method="GET">
            Data Inicial: <input type="date" name="data_inicio" required>
            Data Final: <input type="date" name="data_fim" required>
            <button type="submit">Filtrar</button>
        </form>
    </div>
</div>

<footer>
    Sistema de Controle © <?= date('Y') ?> -  | Desenvolvido por ME Tech Digital!
</footer>

<script>
function atualizarHora() {
    const agora = new Date();
    const data = agora.toLocaleDateString('pt-BR');
    const hora = agora.toLocaleTimeString('pt-BR');
    document.getElementById('dataHora').innerHTML = data + ' - ' + hora;
}
setInterval(atualizarHora, 1000);
atualizarHora();
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('graficoCreditos').getContext('2d');
ctx.canvas.width = 450;
ctx.canvas.height = 220;

const grafico = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Créditos Ativos', 'Créditos Retirados', 'Previsão 7 Dias'],
        datasets: [{
            label: 'Valores em R$',
            data: [
                <?= $data_credito['total_credito'] ?? 0 ?>,
                <?= $data_retirado['total_retirado'] ?? 0 ?>,
                <?= $data_retirada['total_retiradas'] ?? 0 ?>
            ],
            backgroundColor: ['#3498db', '#2ecc71', '#f1c40f']
        }]
    },
    options: {
        scales: {
            y: { beginAtZero: true }
        },
        maintainAspectRatio: false // importante para respeitar o tamanho do canvas
    }
});

</script>

</body>
</html>