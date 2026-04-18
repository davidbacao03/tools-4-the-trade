<?php
	$bd = new PDO("mysql:host=localhost;dbname=tools4thetrade", "root", "");
?>
<!DOCTYPE html>
<html>
<head>
<title>Registar - Tools 4 The Trade</title>
<meta charset="utf-8">
<style>
body { display:flex; justify-content:center; align-items:center; height:100vh; margin:0; font-family:Arial, sans-serif; background-color:#f5f5f5; }
div { display:flex; flex-direction:column; gap:10px; width:400px; background-color:#ffffff; padding:30px; border:1px solid #dddddd; border-radius:6px; }
form { display:flex; flex-direction:column; gap:10px; }
h2 { text-align:center; margin:0; }
input { padding:8px; border:1px solid #cccccc; border-radius:4px; }
button { padding:10px; background-color:#333333; color:#ffffff; border:none; border-radius:4px; cursor:pointer; }
a { text-align:center; color:#333333; }
</style>
</head>
<body>
<div>
<h2>Registar</h2>
<?php
	if(isset($_POST['nome'])) {
		$q = "INSERT INTO utilizador (utl_nome, utl_email, utl_passe) VALUES (?,?,?)";
		$stat = $bd->prepare($q);
		$num = $stat->execute(array($_POST['nome'], $_POST['email'], $_POST['passe']));
		if($num == 1) echo "<p>Registo efetuado com sucesso</p>";
	}
?>
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