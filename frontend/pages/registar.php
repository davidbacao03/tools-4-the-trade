<?php
	$bd = new PDO("mysql:host=localhost;dbname=tools4thetrade", "root", "");
?>
<!DOCTYPE html>
<html>
<head>
<title>Registar - Tools 4 The Trade</title>
<meta charset="utf-8">
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<h2>Registar</h2>
<?php
	if(isset($_POST['nome'])) {
		$q = "INSERT INTO utilizador (utl_nome, utl_email, utl_passe) VALUES (?,?,?)";
		$stat = $bd->prepare($q);
		$num = $stat->execute(array($_POST['nome'], $_POST['email'], $_POST['passe']));
		if($num == 1) echo "Registo efetuado com sucesso";
	}
?>
<form action="" method="post">
<p>Nome: <input type="text" name="nome"></p>
<p>Email: <input type="email" name="email"></p>
<p>Password: <input type="password" name="passe"></p>
<p><button>Registar</button></p>
</form>
<p><a href="login.php">Já tens conta? Entra aqui</a></p>
</body>
</html>