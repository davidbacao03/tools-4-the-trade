<?php
	session_start();
	if(!isset($_SESSION['utl_id'])) header('Location: login.php');
	$bd = new PDO("mysql:host=localhost;dbname=tools4thetrade", "root", "");
	if(!array_key_exists('utl_foto', $_SESSION)) {
		$fotoQ = $bd->prepare("SELECT utl_foto FROM utilizador WHERE utl_id = ?");
		$fotoQ->execute([$_SESSION['utl_id']]);
		$_SESSION['utl_foto'] = $fotoQ->fetchColumn() ?: '';
	}
	$userFoto = $_SESSION['utl_foto'];

	$cats = $bd->query("SELECT * FROM categoria ORDER BY cat_nome")->fetchAll(PDO::FETCH_ASSOC);

	$where  = ["f.fer_ativa = 1"];
	$params = [];

	if(!empty($_GET['cat'])) {
		$where[]  = "f.fer_cat_id = ?";
		$params[] = (int)$_GET['cat'];
	}
	if(isset($_GET['preco_min']) && $_GET['preco_min'] !== '') {
		$where[]  = "f.fer_preco >= ?";
		$params[] = (float)$_GET['preco_min'];
	}
	if(isset($_GET['preco_max']) && $_GET['preco_max'] !== '') {
		$where[]  = "f.fer_preco <= ?";
		$params[] = (float)$_GET['preco_max'];
	}
	if(!empty($_GET['disponivel'])) {
		$where[] = "(SELECT COUNT(*) FROM aluguer a WHERE a.alu_fer_id = f.fer_id AND a.alu_estado IN ('Reservado','Alugado')) = 0";
	}

	$whereStr = implode(' AND ', $where);
	$stmt = $bd->prepare(
		"SELECT f.*, c.cat_nome,
		        (SELECT COUNT(*) FROM aluguer a WHERE a.alu_fer_id = f.fer_id AND a.alu_estado IN ('Reservado','Alugado')) > 0 AS ocupada,
		        (SELECT img_path FROM ferramenta_imagem WHERE img_fer_id = f.fer_id AND img_principal = 1 LIMIT 1) AS img_principal
		 FROM ferramenta f
		 JOIN categoria c ON f.fer_cat_id = c.cat_id
		 WHERE $whereStr
		 ORDER BY f.fer_nome"
	);
	$stmt->execute($params);
	$ferramentas = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <a href="calendario.php">Calendário</a>
            </nav>
        </aside>

        <div class="content">
            <header class="topbar">
                <div class="search-box">
                    <input type="text" placeholder="Pesquisar ferramenta...">
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <a href="perfil.php" class="profile-circle" title="Perfil" <?php if(!empty($userFoto)): ?>style="background-image:url('<?php echo htmlspecialchars($userFoto); ?>');background-size:cover;background-color:transparent;"<?php endif; ?>></a>
                </div>
            </header>

            <main class="main-area">
                <section class="tools-section">
                    <h1>Lista de Ferramentas</h1>

                    <div class="page-action">
                        <a href="adicionarferramentas.php" class="simple-button">Adicionar Ferramenta</a>
                    </div>

                    <form method="get" class="filter-bar">
                        <select name="cat">
                            <option value="">Todas as categorias</option>
                            <?php foreach($cats as $c): ?>
                                <option value="<?php echo $c['cat_id']; ?>" <?php echo (isset($_GET['cat']) && $_GET['cat'] == $c['cat_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['cat_nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="preco_min" placeholder="Preço mín. (€)" step="0.01" min="0"
                               value="<?php echo htmlspecialchars($_GET['preco_min'] ?? ''); ?>">
                        <input type="number" name="preco_max" placeholder="Preço máx. (€)" step="0.01" min="0"
                               value="<?php echo htmlspecialchars($_GET['preco_max'] ?? ''); ?>">
                        <label class="filter-check">
                            <input type="checkbox" name="disponivel" value="1" <?php echo !empty($_GET['disponivel']) ? 'checked' : ''; ?>>
                            Disponíveis
                        </label>
                        <button type="submit" class="simple-button">Filtrar</button>
                        <?php if(!empty(array_filter($_GET))): ?>
                            <a href="ferramentas.php" class="simple-button" style="background:#888;">Limpar</a>
                        <?php endif; ?>
                    </form>

                    <div class="tools-grid">
                        <?php if(empty($ferramentas)): ?>
                            <p>Não existem ferramentas disponíveis de momento.</p>
                        <?php else: ?>
                            <?php foreach($ferramentas as $f): ?>
                                <article class="tool-card"
                                    data-lat="<?php echo $f['fer_lat'] !== null ? (float)$f['fer_lat'] : ''; ?>"
                                    data-lng="<?php echo $f['fer_lng'] !== null ? (float)$f['fer_lng'] : ''; ?>">
                                    <?php if($f['img_principal']): ?>
                                        <img src="<?php echo htmlspecialchars($f['img_principal']); ?>" class="tool-card-img" alt="<?php echo htmlspecialchars($f['fer_nome']); ?>">
                                    <?php else: ?>
                                        <div class="tool-card-img-placeholder"></div>
                                    <?php endif; ?>
                                    <h3><?php echo htmlspecialchars($f['fer_nome']); ?></h3>
                                    <p>Categoria: <?php echo htmlspecialchars($f['cat_nome']); ?></p>
                                    <p><?php echo number_format($f['fer_preco'], 2); ?>€/dia</p>
                                    <?php if($f['ocupada']): ?>
                                        <span class="badge-indisponivel">Indisponível</span>
                                    <?php else: ?>
                                        <a href="alugarferramenta.php?id=<?php echo $f['fer_id']; ?>" class="simple-button">Alugar</a>
                                    <?php endif; ?>
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
