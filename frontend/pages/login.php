<?php
	session_start();
	$bd = new PDO("mysql:host=localhost;dbname=tools4thetrade", "root", "");
?>
<!DOCTYPE html>
<html>
<head>
<title>Login - Tools 4 The Trade</title>
<meta charset="utf-8">
<style>
body { display:flex; justify-content:center; align-items:center; height:100vh; margin:0; font-family:Arial, sans-serif; background-color:#f5f5f5; }
div { display:flex; flex-direction:column; gap:10px; width:300px; background-color:#ffffff; padding:30px; border:1px solid #dddddd; border-radius:6px; }
h2 { text-align:center; margin:0; }
input { padding:8px; border:1px solid #cccccc; border-radius:4px; }
button { padding:10px; background-color:#333333; color:#ffffff; border:none; border-radius:4px; cursor:pointer; }
a { text-align:center; color:#333333; }
</style>
</head>
<body>
<div>
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