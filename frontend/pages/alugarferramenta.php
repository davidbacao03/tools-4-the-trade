<?php
    session_start();
    if(!isset($_SESSION['utl_id'])) header('Location: login.php');

    $bd = new PDO("mysql:host=localhost;dbname=tools4thetrade;charset=utf8mb4", "root", "");
    if(!array_key_exists('utl_foto', $_SESSION)) {
        $fotoQ = $bd->prepare("SELECT utl_foto FROM utilizador WHERE utl_id = ?");
        $fotoQ->execute([$_SESSION['utl_id']]);
        $_SESSION['utl_foto'] = $fotoQ->fetchColumn() ?: '';
    }
    $userFoto = $_SESSION['utl_foto'];

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if($id === 0) { header('Location: ferramentas.php'); exit; }

    $q = "SELECT f.*, c.cat_nome FROM ferramenta f
          JOIN categoria c ON f.fer_cat_id = c.cat_id
          WHERE f.fer_id = ? AND f.fer_ativa = 1";
    $stmt = $bd->prepare($q);
    $stmt->execute([$id]);
    $f = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$f) { header('Location: ferramentas.php'); exit; }

    $propria = ($f['fer_utl_id'] == $_SESSION['utl_id']);

    $rangesStmt = $bd->prepare(
        "SELECT alu_inicio, alu_fim FROM aluguer
         WHERE alu_fer_id = ? AND alu_estado IN ('Reservado','Alugado')
         ORDER BY alu_inicio"
    );
    $rangesStmt->execute([$id]);
    $datasOcupadas = $rangesStmt->fetchAll(PDO::FETCH_ASSOC);

    $imgStmt = $bd->prepare("SELECT img_path FROM ferramenta_imagem WHERE img_fer_id = ? ORDER BY img_principal DESC, img_ordem ASC");
    $imgStmt->execute([$id]);
    $imagens = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

    $erro = '';
    $sucesso = false;

    if(!$propria && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $inicio = $_POST['inicio'] ?? '';
        $fim    = $_POST['fim']    ?? '';

        if(!$inicio || !$fim) {
            $erro = 'Por favor preenche as datas de início e fim.';
        } elseif($fim <= $inicio) {
            $erro = 'A data de fim tem de ser posterior à data de início.';
        } else {
            $overlap = $bd->prepare(
                "SELECT COUNT(*) FROM aluguer
                 WHERE alu_fer_id = ? AND alu_estado IN ('Reservado','Alugado')
                 AND alu_inicio <= ? AND alu_fim >= ?"
            );
            $overlap->execute([$id, $fim, $inicio]);
            if($overlap->fetchColumn() > 0) {
                $erro = 'As datas selecionadas já estão reservadas. Escolhe outras datas.';
            } else {
                $bd->prepare("INSERT INTO aluguer (alu_fer_id, alu_utl_id, alu_inicio, alu_fim) VALUES (?, ?, ?, ?)")
                   ->execute([$id, $_SESSION['utl_id'], $inicio, $fim]);
                $sucesso = true;
            }
        }
    }

    $hoje = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alugar Ferramenta - Tools 4 The Trade</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
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
                    <a href="perfil.php" class="profile-circle" title="Perfil" <?php if(!empty($userFoto)): ?>style="background-image:url('<?php echo htmlspecialchars($userFoto); ?>');background-size:cover;background-color:transparent;"<?php endif; ?>></a>
                </div>
            </header>

            <main class="main-area">
                <section class="form-section">
                    <h1>Alugar Ferramenta</h1>

                    <?php if($sucesso): ?>
                        <div class="msg-sucesso">
                            Aluguer registado com sucesso! <a href="index.php">Voltar ao início</a>
                        </div>
                    <?php elseif($propria): ?>
                        <div class="msg-erro">
                            Não podes alugar a tua própria ferramenta. <a href="ferramentas.php">Ver outras ferramentas</a>
                        </div>
                    <?php else: ?>

                    <?php if($erro): ?>
                        <div class="msg-erro"><?php echo htmlspecialchars($erro); ?></div>
                    <?php endif; ?>

                    <?php if(!empty($imagens)): ?>
                    <div class="galeria-ferramenta">
                        <img id="galeriaMain" class="galeria-img-main"
                             src="<?php echo htmlspecialchars($imagens[0]); ?>"
                             alt="<?php echo htmlspecialchars($f['fer_nome']); ?>">
                        <?php if(count($imagens) > 1): ?>
                        <div class="galeria-thumbs">
                            <?php foreach($imagens as $i => $img): ?>
                                <img src="<?php echo htmlspecialchars($img); ?>"
                                     class="galeria-thumb<?php echo $i === 0 ? ' active' : ''; ?>"
                                     data-full="<?php echo htmlspecialchars($img); ?>"
                                     alt="">
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="rental-grid">
                        <div class="tool-info-card">
                            <h2><?php echo htmlspecialchars($f['fer_nome']); ?></h2>
                            <div class="info-row">
                                <span class="info-label">Categoria:</span><?php echo htmlspecialchars($f['cat_nome']); ?>
                            </div>
                            <?php if($f['fer_descricao']): ?>
                            <div class="info-row">
                                <span class="info-label">Descrição:</span><?php echo htmlspecialchars($f['fer_descricao']); ?>
                            </div>
                            <?php endif; ?>
                            <div class="info-row">
                                <span class="info-label">Preço base:</span><?php echo number_format($f['fer_preco_base'], 2); ?>€/dia
                            </div>
                            <div class="info-row">
                                <span class="info-label">Preço atual:</span><?php echo number_format($f['fer_preco'], 2); ?>€/dia
                            </div>
                            <?php if($f['fer_lat'] !== null && $f['fer_lng'] !== null): ?>
                            <div id="mapaFerramenta"
                                 data-lat="<?php echo (float)$f['fer_lat']; ?>"
                                 data-lng="<?php echo (float)$f['fer_lng']; ?>"
                                 style="height:220px; border-radius:6px; margin-top:14px; border:1px solid #ddd;"></div>
                            <?php endif; ?>
                        </div>

                        <form class="tool-form" method="post">
                            <label>Seleciona o período de aluguer</label>
                            <div id="calendarContainer"></div>
                            <input type="hidden" name="inicio" id="inicio" value="<?php echo htmlspecialchars($_POST['inicio'] ?? ''); ?>">
                            <input type="hidden" name="fim" id="fim" value="<?php echo htmlspecialchars($_POST['fim'] ?? ''); ?>">

                            <div class="price-summary" id="resumoPreco" style="display:none;">
                                Total estimado: <span id="totalPreco"></span>
                            </div>

                            <button type="submit" style="margin-top:16px;">Confirmar aluguer</button>
                        </form>
                    </div>

                    <?php endif; ?>
                </section>
            </main>
        </div>
    </div>

    <script>window.aluguerData = { bookedRanges: <?php echo json_encode(array_map(function($d) { return ['from' => $d['alu_inicio'], 'to' => $d['alu_fim']]; }, $datasOcupadas)); ?>, precoDia: <?php echo (float)$f['fer_preco']; ?> };</script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
    <script src="../js/script.js?v=2"></script>
</body>
</html>
