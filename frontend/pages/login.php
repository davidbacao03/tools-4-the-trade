<?php
	session_start();
	$bd = new PDO("mysql:host=localhost;dbname=tools4thetrade", "root", "");
?>
<!DOCTYPE html>
<html>
<head>
<title>Login - Tools 4 The Trade</title>
<meta charset="utf-8">
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<h2>Login</h2>
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
			echo "Email ou password incorretos.";
		}
	}
?>
<form action="" method="post">
<p>Email: <input type="email" name="email"></p>
<p>Password: <input type="password" name="passe"></p>
<p><button>Entrar</button></p>
</form>
<p><a href="registar.php">Ainda não tens conta? Regista-te</a></p>
</body>
</html>