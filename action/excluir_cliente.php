<?php
session_start();
if (!isset($_SESSION['loginn'])) {
    header("Location: ./tela_login.php");
    exit();
}

include __DIR__.'/../config/conexao.php';

$conexao = new Conexao();
$conn = $conexao->conectar();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../Views/listar_clientes.php");
    exit();
}

$id = (int) $_GET['id'];

// Primeiro vamos buscar o cliente para mostrar o nome e confirmar exclusão
$stmt = $conn->prepare("SELECT nome FROM clientes WHERE id = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    echo "Cliente não encontrado.";
    exit();
}

// Se confirmação recebida via POST, deleta o cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("DELETE FROM clientes WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    if ($stmt->execute()) {
        header("Location: ../views/listar_clientes.php?msg=excluido");
        exit();
    } else {
        $erro = "Erro ao excluir cliente.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Excluir Cliente</title>
<style>
  body { font-family: Arial, sans-serif; padding: 20px; background: #f8f8f8;}
  .container { max-width: 400px; background: white; margin: auto; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);}
  h2 { color: #cc0000; }
  button { background: #cc0000; color: white; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; }
  button:hover { background: #990000; }
  .btn-cancel { margin-top: 10px; display: inline-block; text-align: center; width: 100%; padding: 12px; background: #ccc; color: #333; border-radius: 6px; text-decoration: none; }
  .error { color: red; margin-top: 10px; }
</style>
</head>
<body>

<div class="container">
<h2>Excluir Cliente</h2>

<p>Tem certeza que deseja excluir o cliente <strong><?= htmlspecialchars($cliente['nome']) ?></strong>?</p>

<?php if (!empty($erro)) echo "<p class='error'>{$erro}</p>"; ?>

<form method="POST" action="">
    <button type="submit">Sim, excluir</button>
</form>

<a href="../Views/listar_clientes.php" class="btn-cancel">Cancelar</a>
</div>

</body>
</html>
