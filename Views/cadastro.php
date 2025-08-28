<?php
session_start();
if (!isset($_SESSION['loginn'])){ //se o usuario n estiver logado
    header ("Refresh:0.5, url= ./tela_login.php"); // redirecione para a tela de login
    exit();
}
$tempo_limite = 2.5 * 60; // 15 minutos em segundos
if (isset($_SESSION['ultimo_acesso']) && (time() - $_SESSION['ultimo_acesso']) > $tempo_limite) {
    // Tempo de inatividade excedido
    session_unset(); // limpa a sessão
    session_destroy(); // destrói a sessão
    header("Location: ./tela_login.php?expirado=1"); // redireciona para a página de login com mensagem de sessão expirada
    exit;
}

// Atualiza o tempo do último acesso
$_SESSION['ultimo_acesso'] = time();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  
<meta charset="UTF-8" />
<title>Cadastrar Pagamento Adiantado</title>
<style>
  body {
    font-family: Arial, sans-serif;
    background: #f8f8f8;
    padding: 20px;
  }
  form {
    background: white;
    max-width: 400px;
    margin: 30px auto 10px;
    padding: 20px 30px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
  }
  form label {
    display: block;
    margin-top: 15px;
    color: #003366;
    font-weight: bold;
  }
  form input[type="text"],
  form input[type="number"],
  form input[type="date"] {
    width: 100%;
    padding: 8px 10px;
    margin-top: 5px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
    font-size: 16px;
  }
  button[type="submit"] {
    margin-top: 20px;
    width: 100%;
    background-color: #003366;
    color: white;
    border: none;
    padding: 12px;
    font-size: 18px;
    font-weight: bold;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }
  button[type="submit"]:hover {
    background-color: #002244;
  }
  .btn-container {
    max-width: 400px;
    margin: 10px auto 30px;
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
  .btn-container a:hover {
    background-color: #002244;
  }
</style>
</head>
<body>

<form action="../action/salvar_pagamento.php" method="POST">
  <label for="cliente">Cliente:</label>
  <input type="text" id="cliente" name="cliente" required>

  <label for="valor_pago">Valor Pago:</label>
  <input type="number" id="valor_pago" name="valor_pago" step="0.01" required>

  <label for="produto">Produto:</label>
  <input type="text" id="produto" name="produto" required>

  <label for="observacoes">Observações:</label>
  <input type="text" id="observacoes" name="observacoes" required placeholder="Formas de pagamento e conta destino">

  <label for="data_prevista_retirada">Data Prevista de Retirada:</label>
  <input type="date" id="data_prevista_retirada" name="data_prevista_retirada" required>

  <button type="submit">Cadastrar Pagamento Adiantado</button>
</form>

<div class="btn-container">
  <a href="listagem.php">Listar Pagamentos</a>
  <a href="dashboard.php">Dashboard</a>
</div>

</body>
</html>
