<?php
	session_start();
	$bd = new PDO("mysql:host=localhost;dbname=tools4thetrade;charset=utf8mb4", "root", "");
?>
<!DOCTYPE html>
<html>
<head>
<title>Login - Tools 4 The Trade</title>
<meta charset="utf-8">
<link rel="stylesheet" href="../css/style.css">
</head>
<body class="auth-layout">
<div>
<h2>Bem-vindo de volta</h2>
<p style="color:#828282;margin:0 0 16px;font-size:0.9rem;">Entra na tua conta para continuar.</p>
<?php if(isset($_GET['registered'])): ?><p style="color:#27ae60;margin:0 0 12px;font-size:0.9rem;">Conta criada com sucesso! Podes fazer login.</p><?php endif; ?>
<?php
	if(isset($_POST['email'])) {
		$stat = $bd->prepare("SELECT * FROM utilizador WHERE utl_email = ?");
		$stat->execute([$_POST['email']]);
		$linha = $stat->fetch();
		if($linha && password_verify($_POST['passe'], $linha['utl_passe'])) {
			$_SESSION['utl_id'] = $linha['utl_id'];
			$_SESSION['utl_nome'] = $linha['utl_nome'];
			$_SESSION['utl_admin'] = $linha['utl_admin'];
			header('Location: index.php');
		} else {
			echo "<p class='erro'>Email ou password incorretos.</p>";
		}
	}
?>
<form action="" method="post">
<input type="email" name="email" placeholder="Email" required>
<input type="password" name="passe" placeholder="Password" required>
<button>Entrar</button>
</form>
<div style="text-align:center;margin-top:16px;padding-top:16px;border-top:1px solid #E0E0E0;">
    <span style="font-size:0.88rem;color:#828282;">Ainda não tens conta?</span><br>
    <a href="registar.php" style="font-weight:600;color:#1B5E20;text-decoration:none;">Criar conta gratuita →</a>
</div>
</div>
</body>
</html>