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
<style>
body { display:flex; justify-content:center; align-items:center; height:100vh; margin:0; font-family:Arial, sans-serif; background-color:#f5f5f5; }
div { display:flex; flex-direction:column; gap:10px; width:400px; background-color:#ffffff; padding:30px; border:1px solid #dddddd; border-radius:6px; }
form { display:flex; flex-direction:column; gap:10px; }
h2 { text-align:center; margin:0; }
input { padding:8px; border:1px solid #cccccc; border-radius:4px; }
button { padding:10px; background-color:#333333; color:#ffffff; border:none; border-radius:4px; cursor:pointer; }
a { text-align:center; color:#333333; }
p.erro { color:#c0392b; margin:0; font-size:0.9rem; }
</style>
</head>
<body>
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