<?php
	session_start();
	if(!isset($_SESSION['utl_id'])) header('Location: login.php');
	$bd = new PDO("mysql:host=localhost;dbname=tools4thetrade", "root", "");
	$q = "SELECT f.*, c.cat_nome FROM ferramenta f
	      JOIN categoria c ON f.fer_cat_id = c.cat_id
	      WHERE f.fer_ativa = 1";
	$ferramentas = $bd->query($q)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="Lista de ferramentas">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ferramentas - Tools 4 The Trade</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="layout">

        <aside class="sidebar">
            <h2 class="logo">Tools 4 The Trade</h2>
            <nav class="menu">
                <a href="index.php">Home</a>
                <a href="ferramentas.php">Ferramentas</a>
                <a href="dashboard.php">Dashboard</a>
            </nav>
        </aside>

        <div class="content">
            <header class="topbar">
                <div class="search-box">
                    <input type="text" placeholder="Pesquisar ferramenta...">
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <a href="logout.php">Sair</a>
                    <a href="perfil.php" class="profile-circle" title="Perfil"></a>
                </div>
            </header>

            <main class="main-area">
                <section class="tools-section">
                    <h1>Lista de Ferramentas</h1>

                    <div class="page-action">
                        <a href="adicionarferramentas.php" class="simple-button">Adicionar Ferramenta</a>
                    </div>

                    <div class="tools-grid">
                        <?php if(empty($ferramentas)): ?>
                            <p>Não existem ferramentas disponíveis de momento.</p>
                        <?php else: ?>
                            <?php foreach($ferramentas as $f): ?>
                                <article class="tool-card">
                                    <h3><?php echo htmlspecialchars($f['fer_nome']); ?></h3>
                                    <p>Categoria: <?php echo htmlspecialchars($f['cat_nome']); ?></p>
                                    <p><?php echo number_format($f['fer_preco'], 2); ?>€/dia</p>
                                    <a href="alugarferramenta.php?id=<?php echo $f['fer_id']; ?>" class="simple-button">Alugar</a>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>
