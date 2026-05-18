<?php
    session_start();
    if(!isset($_SESSION['utl_id'])) { header('Location: login.php'); exit; }

    $bd  = new PDO("mysql:host=localhost;dbname=tools4thetrade;charset=utf8mb4", "root", "");
    $uid = (int)$_SESSION['utl_id'];

    if(!array_key_exists('utl_foto', $_SESSION)) {
        $fotoQ = $bd->prepare("SELECT utl_foto FROM utilizador WHERE utl_id = ?");
        $fotoQ->execute([$uid]);
        $_SESSION['utl_foto'] = $fotoQ->fetchColumn() ?: '';
    }
    $userFoto = $_SESSION['utl_foto'];

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if($id === 0)    { header('Location: index.php'); exit; }
    if($id === $uid) { header('Location: perfil.php'); exit; }

    // Public user info + avg owner rating
    $userStmt = $bd->prepare(
        "SELECT u.utl_id, u.utl_nome, u.utl_foto, u.utl_criado,
                ROUND(AVG(av.ava_nota_dono), 1) AS avg_nota_dono,
                COUNT(DISTINCT av.ava_id) AS total_reviews
         FROM utilizador u
         LEFT JOIN avaliacao av ON av.ava_dono_id = u.utl_id
         WHERE u.utl_id = ?
         GROUP BY u.utl_id"
    );
    $userStmt->execute([$id]);
    $dono = $userStmt->fetch(PDO::FETCH_ASSOC);
    if(!$dono) { header('Location: index.php'); exit; }

    // Their active tools
    $toolsStmt = $bd->prepare(
        "SELECT f.fer_id, f.fer_utl_id, f.fer_nome, f.fer_descricao, f.fer_preco_base, f.fer_preco,
                f.fer_desconto_dias, f.fer_preco_desconto, c.cat_nome,
                (SELECT COUNT(*) FROM aluguer a WHERE a.alu_fer_id = f.fer_id AND a.alu_estado IN ('Reservado','Alugado')) > 0 AS ocupada,
                (SELECT img_path FROM ferramenta_imagem WHERE img_fer_id = f.fer_id AND img_principal = 1 LIMIT 1) AS img_principal,
                ROUND((SELECT AVG(av.ava_nota_fer) FROM avaliacao av WHERE av.ava_fer_id = f.fer_id), 1) AS avg_nota_fer,
                (SELECT COUNT(*) FROM avaliacao av WHERE av.ava_fer_id = f.fer_id) AS total_avaliacoes
         FROM ferramenta f
         JOIN categoria c ON f.fer_cat_id = c.cat_id
         WHERE f.fer_utl_id = ? AND f.fer_ativa = 1
         ORDER BY f.fer_nome"
    );
    $toolsStmt->execute([$id]);
    $ferramentas = $toolsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Check which of these tools the logged-in user has favourited
    $favIds = [];
    if(!empty($ferramentas)) {
        $favStmt = $bd->prepare("SELECT fav_fer_id FROM favorito WHERE fav_utl_id = ?");
        $favStmt->execute([$uid]);
        $favIds = array_flip($favStmt->fetchAll(PDO::FETCH_COLUMN));
    }

    // Written reviews about this user's tools (with which tool each belongs to)
    $reviewStmt = $bd->prepare(
        "SELECT av.ava_nota_fer, av.ava_texto, av.ava_criada,
                u.utl_nome AS reviewer, u.utl_foto AS reviewer_foto,
                f.fer_nome, f.fer_id,
                (SELECT img_path FROM ferramenta_imagem WHERE img_fer_id = f.fer_id AND img_principal = 1 LIMIT 1) AS fer_img
         FROM avaliacao av
         JOIN utilizador u ON av.ava_utl_id = u.utl_id
         JOIN ferramenta f ON av.ava_fer_id = f.fer_id
         WHERE f.fer_utl_id = ? AND av.ava_texto IS NOT NULL AND av.ava_texto != ''
         ORDER BY av.ava_criada DESC"
    );
    $reviewStmt->execute([$id]);
    $reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($dono['utl_nome']); ?> - Tools 4 The Trade</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="layout">

        <aside class="sidebar">
            <h2 class="logo">Tools 4 The Trade</h2>
            <nav class="menu">
                <a href="index.php">Home</a>
                <a href="Ferramentas.php">Ferramentas</a>
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
                    <a href="perfil.php" class="profile-circle" title="Perfil"
                       <?php if(!empty($userFoto)): ?>style="background-image:url('<?php echo htmlspecialchars($userFoto); ?>');background-size:cover;background-color:transparent;"<?php endif; ?>></a>
                </div>
            </header>

            <main class="main-area">

                <section class="form-section">
                    <div class="profile-header">
                        <div class="profile-avatar profile-avatar-static">
                            <?php if(!empty($dono['utl_foto'])): ?>
                                <img src="<?php echo htmlspecialchars($dono['utl_foto']); ?>" alt="Foto de perfil">
                            <?php else: ?>
                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <p class="profile-name"><?php echo htmlspecialchars($dono['utl_nome']); ?></p>
                        <div class="profile-meta">
                            <span>Membro desde <?php echo date('M Y', strtotime($dono['utl_criado'])); ?></span>
                            <?php if($dono['avg_nota_dono']): ?>
                                <span>
                                    <span class="stars-display" data-nota="<?php echo $dono['avg_nota_dono']; ?>"></span>
                                    <small style="color:#888;"><?php echo $dono['avg_nota_dono']; ?> como proprietário
                                        (<?php echo $dono['total_reviews']; ?> avalia<?php echo $dono['total_reviews'] == 1 ? 'ção' : 'ções'; ?>)
                                    </small>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <section class="dashboard-section">
                    <h2 class="section-title">Ferramentas de <?php echo htmlspecialchars($dono['utl_nome']); ?></h2>

                    <?php if(empty($ferramentas)): ?>
                        <p class="empty-msg">Este utilizador não tem ferramentas disponíveis.</p>
                    <?php else: ?>
                    <div class="tools-grid">
                        <?php foreach($ferramentas as $f):
                            $isFav = isset($favIds[$f['fer_id']]);
                        ?>
                        <article class="tool-card">
                            <?php if($f['img_principal']): ?>
                                <img src="<?php echo htmlspecialchars($f['img_principal']); ?>" class="tool-card-img" alt="<?php echo htmlspecialchars($f['fer_nome']); ?>">
                            <?php else: ?>
                                <div class="tool-card-img-placeholder"></div>
                            <?php endif; ?>
                            <h3><?php echo htmlspecialchars($f['fer_nome']); ?></h3>
                            <p>Categoria: <?php echo htmlspecialchars($f['cat_nome']); ?></p>
                            <p><?php echo number_format($f['fer_preco_base'], 2); ?>€/dia</p>
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
                                data-desconto-dias="<?php echo $f['fer_desconto_dias'] ?? ''; ?>"
                                data-preco-desconto="<?php echo $f['fer_preco_desconto'] ?? ''; ?>"
                                data-avg-nota="<?php echo $f['avg_nota_fer'] ?? ''; ?>"
                                data-total-avaliacoes="<?php echo $f['total_avaliacoes'] ?? 0; ?>"
                                data-dono-nome="<?php echo htmlspecialchars($dono['utl_nome'], ENT_QUOTES); ?>"
                                data-dono-foto="<?php echo htmlspecialchars($dono['utl_foto'] ?? '', ENT_QUOTES); ?>"
                                data-avg-nota-dono="<?php echo $dono['avg_nota_dono'] ?? ''; ?>"
                                data-dono-id="<?php echo $f['fer_utl_id']; ?>"
                                data-favorito="<?php echo $isFav ? '1' : '0'; ?>"
                                data-imagens="<?php echo htmlspecialchars(json_encode($f['img_principal'] ? [$f['img_principal']] : []), ENT_QUOTES); ?>">Ver mais</button>
                        </article>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </section>

                <?php if(!empty($reviews)): ?>
                <section class="form-section" style="margin-top:0;">
                    <h2>Reviews</h2>
                    <?php foreach($reviews as $r): ?>
                    <div class="review-item review-item-tool">
                        <a href="alugarferramenta.php?id=<?php echo $r['fer_id']; ?>" class="review-tool-thumb">
                            <?php if(!empty($r['fer_img'])): ?>
                                <img src="<?php echo htmlspecialchars($r['fer_img']); ?>" alt="<?php echo htmlspecialchars($r['fer_nome']); ?>">
                            <?php else: ?>
                                <div class="review-tool-thumb-empty"></div>
                            <?php endif; ?>
                        </a>
                        <div class="review-item-body">
                            <div class="review-header">
                                <a href="alugarferramenta.php?id=<?php echo $r['fer_id']; ?>" class="review-tool-name"><?php echo htmlspecialchars($r['fer_nome']); ?></a>
                                <span class="stars-display" data-nota="<?php echo $r['ava_nota_fer']; ?>"></span>
                                <?php if(!empty($r['reviewer_foto'])): ?>
                                    <div class="reviewer-avatar" style="background-image:url('<?php echo htmlspecialchars($r['reviewer_foto']); ?>')"></div>
                                <?php else: ?>
                                    <div class="reviewer-avatar reviewer-avatar-empty"></div>
                                <?php endif; ?>
                                <strong><?php echo htmlspecialchars($r['reviewer']); ?></strong>
                                <span class="review-date"><?php echo date('d/m/Y', strtotime($r['ava_criada'])); ?></span>
                            </div>
                            <p class="review-text"><?php echo htmlspecialchars($r['ava_texto']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </section>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <!-- Tool detail modal (same structure as index.php) -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal-box">
            <button class="modal-close" id="modalClose">&times;</button>
            <div class="modal-title-row">
                <h2 id="modalNome"></h2>
                <span id="modalIndisponivel" class="badge-indisponivel" style="display:none;">Indisponível</span>
            </div>
            <div class="modal-galeria" id="modalGaleria">
                <img id="modalImgMain" class="modal-img-main" src="" alt="">
                <div id="modalImgThumbs" class="modal-img-thumbs"></div>
            </div>
            <div class="modal-field"><span class="modal-label">Categoria:</span><span id="modalCategoria"></span></div>
            <div class="modal-field"><span class="modal-label">Descrição:</span><span id="modalDescricao"></span></div>
            <div class="modal-field"><span class="modal-label">Preço:</span><span id="modalPrecoBase"></span>€/dia</div>
            <div class="modal-field" id="modalDescontoRow" style="display:none;"><span class="modal-label">Desconto:</span><span id="modalDescontoTxt"></span></div>
            <div class="modal-field" id="modalRatingRow" style="display:none;"><span class="modal-label">Avaliação:</span><span id="modalRatingStars"></span><span id="modalRatingCount" style="font-size:0.82rem;color:#999;margin-left:6px;"></span></div>
            <a href="#" class="dono-card dono-card-link" id="modalDonoCard" style="display:none;">
                <div class="dono-avatar" id="modalDonoAvatar"></div>
                <div class="dono-info">
                    <span class="dono-nome" id="modalDonoNome"></span>
                    <span class="dono-stars" id="modalDonoStars"></span>
                </div>
            </a>
            <div class="modal-actions">
                <a href="#" id="modalAlugarLink" class="simple-button">Alugar</a>
                <button type="button" id="modalFavBtn" class="btn-favorito-modal">♡ Favorito</button>
                <button type="button" id="modalReportBtn" class="btn-reportar">⚑ Reportar</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="reportOverlay">
        <div class="modal-box" style="max-width:420px;">
            <button class="modal-close" id="reportClose">&times;</button>
            <h2 style="margin-bottom:16px;">Reportar ferramenta</h2>
            <input type="hidden" id="reportFerId">
            <div class="modal-field" style="flex-direction:column; align-items:flex-start; gap:6px;">
                <span class="modal-label">Motivo</span>
                <select id="reportMotivo" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;">
                    <option value="">Selecionar motivo...</option>
                    <option>Conteúdo inapropriado</option>
                    <option>Ferramenta inexistente</option>
                    <option>Preço enganoso</option>
                    <option>Utilizador suspeito</option>
                    <option>Outro</option>
                </select>
            </div>
            <div class="modal-field" style="flex-direction:column; align-items:flex-start; gap:6px; margin-top:12px;">
                <span class="modal-label">Descrição (opcional)</span>
                <textarea id="reportDescricao" rows="3" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px; resize:vertical;"></textarea>
            </div>
            <div class="modal-actions" style="margin-top:16px;">
                <button type="button" id="reportSubmitBtn" class="simple-button">Enviar</button>
                <span id="reportMsg" style="font-size:0.85rem; margin-left:10px;"></span>
            </div>
        </div>
    </div>

    <script src="../js/script.js?v=2"></script>
</body>
</html>
