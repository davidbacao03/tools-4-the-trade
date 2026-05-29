<?php
    session_start();
    $bd = new PDO("mysql:host=localhost;dbname=tools4thetrade;charset=utf8mb4", "root", "");
    $erro = '';
    if(isset($_POST['nome'])) {
        $nome  = trim($_POST['nome']  ?? '');
        $email = trim($_POST['email'] ?? '');
        $passe = $_POST['passe'] ?? '';

        if($nome === '') {
            $erro = 'O nome é obrigatório.';
        } elseif($email === '') {
            $erro = 'O email é obrigatório.';
        } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Introduz um email válido.';
        } elseif($passe === '') {
            $erro = 'A password é obrigatória.';
        } elseif(strlen($passe) < 6) {
            $erro = 'A password deve ter pelo menos 6 caracteres.';
        } else {
            try {
                $bd->prepare("INSERT INTO utilizador (utl_nome, utl_email, utl_passe) VALUES (?,?,?)")
                   ->execute([$nome, $email, password_hash($passe, PASSWORD_DEFAULT)]);
                header('Location: login.php?registered=1'); exit;
            } catch(PDOException $e) {
                if($e->getCode() === '23000') $erro = 'Este email já está registado.';
                else throw $e;
            }
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
<h2>Criar conta</h2>
<p style="color:#828282;margin:0 0 16px;font-size:0.9rem;">Junta-te à plataforma de aluguer de ferramentas.</p>
<?php if($erro): ?><p class="erro"><?php echo htmlspecialchars($erro); ?></p><?php endif; ?>
<form action="" method="post" novalidate>
<input type="text" name="nome" placeholder="Nome" value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required>
<input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
<input type="password" name="passe" placeholder="Password (mín. 6 caracteres)" required>
<button>Registar</button>
</form>
<div style="text-align:center;margin-top:16px;padding-top:16px;border-top:1px solid #E0E0E0;">
    <span style="font-size:0.88rem;color:#828282;">Já tens conta?</span><br>
    <a href="login.php" style="font-weight:600;color:#1B5E20;text-decoration:none;">Entrar →</a>
</div>
</div>
</body>
</html>
