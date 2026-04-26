<?php
	session_start();
	if(!isset($_SESSION['utl_id'])) header('Location: registar.php');
	$bd = new PDO("mysql:host=localhost;dbname=tools4thetrade;charset=utf8mb4", "root", "");
	if(!array_key_exists('utl_foto', $_SESSION)) {
		$fotoQ = $bd->prepare("SELECT utl_foto FROM utilizador WHERE utl_id = ?");
		$fotoQ->execute([$_SESSION['utl_id']]);
		$_SESSION['utl_foto'] = $fotoQ->fetchColumn() ?: '';
	}
	$userFoto = $_SESSION['utl_foto'];

	// Top 10 most rented
	$q = "SELECT f.fer_id, f.fer_nome, f.fer_descricao, f.fer_preco, f.fer_preco_base, f.fer_lat, f.fer_lng, c.cat_nome,
	             (SELECT COUNT(*) FROM aluguer a WHERE a.alu_fer_id = f.fer_id AND a.alu_estado IN ('Reservado','Alugado')) > 0 AS ocupada,
	             (SELECT COUNT(*) FROM aluguer a2 WHERE a2.alu_fer_id = f.fer_id) AS total_alugueres
	      FROM ferramenta f
	      JOIN categoria c ON f.fer_cat_id = c.cat_id
	      WHERE f.fer_ativa = 1
	      ORDER BY total_alugueres DESC
	      LIMIT 10";
	$ferramentas = $bd->query($q)->fetchAll(PDO::FETCH_ASSOC);

	// Fetch all images for top 10
	$imagesByTool = [];
	if (!empty($ferramentas)) {
		$ids = array_column($ferramentas, 'fer_id');
		$placeholders = implode(',', array_fill(0, count($ids), '?'));
		$imgStmt = $bd->prepare(
			"SELECT img_fer_id, img_path FROM ferramenta_imagem
			 WHERE img_fer_id IN ($placeholders)
			 ORDER BY img_fer_id, img_principal DESC, img_ordem ASC"
		);
		$imgStmt->execute($ids);
		foreach ($imgStmt->fetchAll(PDO::FETCH_ASSOC) as $img) {
			$imagesByTool[$img['img_fer_id']][] = $img['img_path'];
		}
	}

	// Categories for filter bar
	$cats = $bd->query("SELECT * FROM categoria ORDER BY cat_nome")->fetchAll(PDO::FETCH_ASSOC);

	// All tools with filters
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
	$stmtAll = $bd->prepare(
		"SELECT f.fer_id, f.fer_nome, f.fer_descricao, f.fer_preco, f.fer_preco_base, f.fer_lat, f.fer_lng, c.cat_nome,
		        (SELECT COUNT(*) FROM aluguer a WHERE a.alu_fer_id = f.fer_id AND a.alu_estado IN ('Reservado','Alugado')) > 0 AS ocupada,
		        (SELECT img_path FROM ferramenta_imagem WHERE img_fer_id = f.fer_id AND img_principal = 1 LIMIT 1) AS img_principal
		 FROM ferramenta f
		 JOIN categoria c ON f.fer_cat_id = c.cat_id
		 WHERE $whereStr
		 ORDER BY f.fer_nome"
	);
	$stmtAll->execute($params);
	$todasFerramentas = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

	$ferramentasComLoc = array_values(array_filter($todasFerramentas, fn($f) => $f['fer_lat'] !== null && $f['fer_lng'] !== null));
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="Tools 4 The Trade">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tools 4 The Trade</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
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
                <section class="hero">
                    <h1>TOOLS 4 THE TRADE</h1>
                    <p>Encontre e alugue ferramentas de forma simples e rápida.</p>
                </section>

                <section class="map-section">
                    <h2>Mapa de Ferramentas</h2>
                    <div id="mapa"></div>
                </section>

                <section class="tools-section">
                    <h2>Top 10 ferramentas mais alugadas</h2>

                    <div class="tools-grid">
                        <?php if(empty($ferramentas)): ?>
                            <p>Não existem ferramentas disponíveis de momento.</p>
                        <?php else: ?>
                            <?php foreach($ferramentas as $f):
                                $imgs = $imagesByTool[$f['fer_id']] ?? [];
                                $mainImg = $imgs[0] ?? null;
                            ?>
                                <article class="tool-card"
                                    data-lat="<?php echo $f['fer_lat'] !== null ? (float)$f['fer_lat'] : ''; ?>"
                                    data-lng="<?php echo $f['fer_lng'] !== null ? (float)$f['fer_lng'] : ''; ?>">
                                    <?php if($mainImg): ?>
                                        <img src="<?php echo htmlspecialchars($mainImg); ?>" class="tool-card-img" alt="<?php echo htmlspecialchars($f['fer_nome']); ?>">
                                    <?php else: ?>
                                        <div class="tool-card-img-placeholder"></div>
                                    <?php endif; ?>
                                    <h3><?php echo htmlspecialchars($f['fer_nome']); ?></h3>
                                    <p>Categoria: <?php echo htmlspecialchars($f['cat_nome']); ?></p>
                                    <p><?php echo number_format($f['fer_preco'], 2); ?>€/dia</p>
                                    <?php if($f['ocupada']): ?>
                                        <span class="badge-indisponivel">Indisponível</span>
                                    <?php endif; ?>
                                    <button class="btn-ver-mais"
                                        data-id="<?php echo $f['fer_id']; ?>"
                                        data-ocupada="<?php echo $f['ocupada'] ? '1' : '0'; ?>"
                                        data-nome="<?php echo htmlspecialchars($f['fer_nome'], ENT_QUOTES); ?>"
                                        data-categoria="<?php echo htmlspecialchars($f['cat_nome'], ENT_QUOTES); ?>"
                                        data-descricao="<?php echo htmlspecialchars($f['fer_descricao'] ?? '', ENT_QUOTES); ?>"
                                        data-preco="<?php echo number_format($f['fer_preco'], 2); ?>"
                                        data-preco-base="<?php echo number_format($f['fer_preco_base'], 2); ?>"
                                        data-imagens="<?php echo htmlspecialchars(json_encode($imgs), ENT_QUOTES); ?>">Ver mais</button>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="tools-section">
                    <h2>Todas as Ferramentas</h2>

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
                            <a href="index.php" class="simple-button" style="background:#888;">Limpar</a>
                        <?php endif; ?>
                    </form>

                    <div class="tools-grid">
                        <?php if(empty($todasFerramentas)): ?>
                            <p class="empty-msg">Nenhuma ferramenta encontrada.</p>
                        <?php else: ?>
                            <?php foreach($todasFerramentas as $f): ?>
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
                                    <?php endif; ?>
                                    <button class="btn-ver-mais"
                                        data-id="<?php echo $f['fer_id']; ?>"
                                        data-ocupada="<?php echo $f['ocupada'] ? '1' : '0'; ?>"
                                        data-nome="<?php echo htmlspecialchars($f['fer_nome'], ENT_QUOTES); ?>"
                                        data-categoria="<?php echo htmlspecialchars($f['cat_nome'], ENT_QUOTES); ?>"
                                        data-descricao="<?php echo htmlspecialchars($f['fer_descricao'] ?? '', ENT_QUOTES); ?>"
                                        data-preco="<?php echo number_format($f['fer_preco'], 2); ?>"
                                        data-preco-base="<?php echo number_format($f['fer_preco_base'], 2); ?>"
                                        data-imagens="<?php echo htmlspecialchars(json_encode($f['img_principal'] ? [$f['img_principal']] : []), ENT_QUOTES); ?>">Ver mais</button>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

            </main>
        </div>
    </div>

    <div class="modal-overlay" id="modalOverlay">
        <div class="modal-box">
            <button class="modal-close" id="modalClose">&times;</button>
            <h2 id="modalNome"></h2>
            <div class="modal-galeria" id="modalGaleria">
                <img id="modalImgMain" class="modal-img-main" src="" alt="">
                <div id="modalImgThumbs" class="modal-img-thumbs"></div>
            </div>
            <div class="modal-field"><span class="modal-label">Categoria:</span><span id="modalCategoria"></span></div>
            <div class="modal-field"><span class="modal-label">Descrição:</span><span id="modalDescricao"></span></div>
            <div class="modal-field"><span class="modal-label">Preço base:</span><span id="modalPrecoBase"></span>€/dia</div>
            <div class="modal-field"><span class="modal-label">Preço atual:</span><span id="modalPreco"></span>€/dia</div>
            <div class="modal-actions">
                <a href="#" id="modalAlugarLink" class="simple-button">Alugar</a>
<span id="modalIndisponivel" class="badge-indisponivel" style="display:none;">Indisponível</span>
            </div>
        </div>
    </div>

    <script>window.ferramentasGeo = <?php echo json_encode(array_values($ferramentasComLoc)); ?>;</script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../js/script.js"></script>
</body>
</html>
