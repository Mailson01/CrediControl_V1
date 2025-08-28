<?php

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CDF MATERIAL</title>
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
        .login-container {
            background: white;
            width: 300px;
            margin: 50px auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            text-align: center;
        }
        .login-container h2 {
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
        .link-button {
            background: transparent;
            border: 2px solid #003366;
            color: #003366;
            margin-top: 10px;
        }
        .link-button:hover {
            background: #003366;
            color: white;
        }
    </style>
</head>
<body>

<header>
   
</header>
<?php if (isset($_GET['expirado'])): ?>
    <p style="color: red;">Sua sessão expirou por inatividade. Faça login novamente.</p>
<?php endif; ?>
<div class="login-container">
    <h2>Login</h2>
    <form action="action/verif_login.php" method="POST">
        <input type="text" name="loginn" placeholder="Usuário" required><br>
        <input type="password" name="senha" placeholder="Senha" required><br>
        <button type="submit">Entrar</button>
    </form>
    <form action="cadastrar_usuario.php" method="GET">
        <button type="submit" class="link-button">Não tem cadastro?</button>
    </form>
</div>

</body>
</html>
