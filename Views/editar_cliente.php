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
    header("Location: listar_clientes.php");
    exit();
}

$id = (int) $_GET['id'];

// Buscar dados atuais para preencher o formulário
$stmt = $conn->prepare("SELECT * FROM clientes WHERE id = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    echo "Cliente não encontrado.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $valor_pago = $_POST['valor_pago'] ?? '';

    if (empty($nome)) {
        $erro = "Preencha todos os campos.";
    
    } else {
        $stmt = $conn->prepare("UPDATE clientes SET nome = :nome, valor_pago = :valor_pago WHERE id = :id");
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':valor_pago', $valor_pago);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            header("Location: listar_clientes.php?msg=editado");
            exit();
        } else {
            $erro = "Erro ao atualizar cliente.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Editar Cliente</title>
<style>
  body { font-family: Arial, sans-serif; padding: 20px; background: #f8f8f8; }
  form { max-width: 400px; background: #fff; padding: 20px; margin: auto; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);}
  label { display: block; margin-top: 15px; font-weight: bold; color: #003366;}
  input[type="text"], input[type="number"] { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px;}
  button { margin-top: 20px; width: 100%; padding: 12px; background: #003366; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;}
  button:hover { background: #002244; }
  .error { color: red; margin-top: 10px; }
  .btn-back { margin-top: 15px; display: inline-block; color: #003366; text-decoration: none; }
</style>
</head>
<body>

<h2>Editar Cliente</h2>

<?php if (!empty($erro)) echo "<p class='error'>{$erro}</p>"; ?>

<form method="POST" action="">
  <label for="nome">Nome:</label>
  <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($cliente['nome']) ?>" required>

 


  <button type="submit">Salvar Alterações</button>
</form>

<a href="listar_clientes.php" class="btn-back">← Voltar para a lista</a>

</body>
</html>
