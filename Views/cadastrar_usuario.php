<?php
session_start();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuário - CDF MATERIAL</title>
    <link rel="icon" href="sistema_controle_pag/img/favicon_io/favicon.ico" type="image/x-icon">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f8f8;
            margin: 0;
        }
        header {
            background: #003366;
            color: white;
            padding: 20px;
            text-align: center;
        }
        header h1 {
            margin: 0;
            text-decoration: underline;
            font-weight: bolder;
            font-size: 20px;
        }
        .form-container {
            background: white;
            width: 300px;
            margin: 50px auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            text-align: center;
        }
        .form-container h2 {
            color: #003366;
            margin-bottom: 20px;
        }
        input[type="text"], input[type="password"] {
            width: 90%;
            padding: 8px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background-color: #003366;
            color: white;
            border: none;
            padding: 10px 20px;
            margin-top: 10px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #002244;
        }
        a {
            display: inline-block;
            margin-top: 10px;
            color: #003366;
            text-decoration: none;
            font-weight: bold;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<header>
    
</header>

<div class="form-container">
    <h2>Cadastro de Usuário</h2>
    <form action="./verifica_cadastro.php" method="POST">
        <input type="text" name="loginn" placeholder="Login" required><br>
        <input type="password" name="senha" placeholder="Senha" required><br>
        <button type="submit">Cadastrar</button>
    </form>
    <a href="tela_login.php">Já tem conta? Entrar</a>
</div>

</body>
</html>
