<?php
    session_start();
    if(!isset($_SESSION['utl_id'])) { header('Location: login.php'); exit; }
    $bd = new PDO("mysql:host=localhost;dbname=tools4thetrade;charset=utf8mb4", "root", "");
    if(!array_key_exists('utl_foto', $_SESSION)) {
        $fotoQ = $bd->prepare("SELECT utl_foto FROM utilizador WHERE utl_id = ?");
        $fotoQ->execute([$_SESSION['utl_id']]);
        $_SESSION['utl_foto'] = $fotoQ->fetchColumn() ?: '';
    }
    $userFoto = $_SESSION['utl_foto'];
    $uid = $_SESSION['utl_id'];

    $stmt = $bd->prepare(
        "SELECT f.*, c.cat_nome,
                (SELECT COUNT(*) FROM aluguer a WHERE a.alu_fer_id = f.fer_id AND a.alu_estado IN ('Reservado','Alugado')) > 0 AS ocupada,
                (SELECT img_path FROM ferramenta_imagem WHERE img_fer_id = f.fer_id AND img_principal = 1 LIMIT 1) AS img_principal
         FROM ferramenta f
         JOIN categoria c ON f.fer_cat_id = c.cat_id
         WHERE f.fer_utl_id = ? AND f.fer_ativa = 1
         ORDER BY f.fer_criada DESC"
    );
    $stmt->execute([$uid]);
    $minhasFerramentas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>As Minhas Ferramentas - Tools 4 The Trade</title>
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
                    <a href="perfil.php" class="profile-circle" title="Perfil" <?php if(!empty($userFoto)): ?>style="background-image:url('<?php echo htmlspecialchars($userFoto); ?>');background-size:cover;background-color:transparent;"<?php endif; ?>></a>
                </div>
            </header>

            <main class="main-area">
                <section class="tools-section">
                    <h1>As Minhas Ferramentas</h1>

                    <div class="page-action">
                        <a href="adicionarferramentas.php" class="simple-button">+ Adicionar Ferramenta</a>
                    </div>

                    <div class="tools-grid">
                        <?php if(empty($minhasFerramentas)): ?>
                            <p class="empty-msg">Ainda não adicionaste nenhuma ferramenta. <a href="adicionarferramentas.php">Adiciona a primeira!</a></p>
                        <?php else: ?>
                            <?php foreach($minhasFerramentas as $f): ?>
                                <article class="tool-card">
                                    <?php if($f['img_principal']): ?>
                                        <img src="<?php echo htmlspecialchars($f['img_principal']); ?>" class="tool-card-img" alt="<?php echo htmlspecialchars($f['fer_nome']); ?>">
                                    <?php else: ?>
                                        <div class="tool-card-img-placeholder"></div>
                                    <?php endif; ?>
                                    <h3><?php echo htmlspecialchars($f['fer_nome']); ?></h3>
                                    <p>Categoria: <?php echo htmlspecialchars($f['cat_nome']); ?></p>
                                    <p><?php echo number_format($f['fer_preco'], 2); ?>€/dia</p>
                                    <?php if($f['ocupada']): ?>
                                        <span class="badge-indisponivel">Alugada</span>
                                    <?php else: ?>
                                        <span class="badge-disponivel">Disponível</span>
                                    <?php endif; ?>
                                    <a href="editarferramenta.php?id=<?php echo $f['fer_id']; ?>" class="simple-button" style="margin-top:8px;">Editar</a>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <script src="../js/script.js?v=2"></script>
</body>
</html>
