<?php
    session_start();
    $bd = new PDO("mysql:host=localhost;dbname=tools4thetrade;charset=utf8mb4", "root", "");
    $erro = '';
    if(isset($_POST['nome'])) {
        try {
            $bd->prepare("INSERT INTO utilizador (utl_nome, utl_email, utl_passe) VALUES (?,?,?)")
               ->execute([$_POST['nome'], $_POST['email'], $_POST['passe']]);
            header('Location: login.php?registered=1'); exit;
        } catch(PDOException $e) {
            if($e->getCode() === '23000') $erro = 'Este email já está registado.';
            else throw $e;
        }
    }
?>
<!DOCTYPE html>
<html>
<head>
<title>Registar - Tools 4 The Trade</title>
<meta charset="utf-8">
<link rel="stylesheet" href="../css/style.css">
</head>
<body class="auth-layout">
<div>
<h2>Registar</h2>
<?php if($erro): ?><p class="erro"><?php echo htmlspecialchars($erro); ?></p><?php endif; ?>
<form action="" method="post">
<input type="text" name="nome" placeholder="Nome">
<input type="email" name="email" placeholder="Email">
<input type="password" name="passe" placeholder="Password">
<button>Registar</button>
</form>
<a href="login.php">Já tens conta? Entra aqui</a>
</div>
</body>
</html>