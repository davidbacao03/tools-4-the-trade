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
<h2>Login</h2>
<?php if(isset($_GET['registered'])): ?><p style="color:#27ae60;margin:0;font-size:0.9rem;">Conta criada com sucesso! Podes fazer login.</p><?php endif; ?>
<?php
	if(isset($_POST['email'])) {
		$q = "SELECT * FROM utilizador WHERE utl_email=? AND utl_passe=?";
		$stat = $bd->prepare($q);
		$stat->execute(array($_POST['email'], $_POST['passe']));
		$linha = $stat->fetch();
		if($linha) {
			$_SESSION['utl_id'] = $linha['utl_id'];
			$_SESSION['utl_nome'] = $linha['utl_nome'];
			$_SESSION['utl_admin'] = $linha['utl_admin'];
			header('Location: index.php');
		} else {
			echo "<p>Email ou password incorretos.</p>";
		}
	}
?>
<form action="" method="post">
<input type="email" name="email" placeholder="Email">
<input type="password" name="passe" placeholder="Password">
<button>Entrar</button>
</form>
<a href="registar.php">Ainda não tens conta? Regista-te</a>
</div>
</body>
</html>