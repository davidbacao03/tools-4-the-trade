<?php
    session_start();
    if(!isset($_SESSION['utl_id'])) { header('Location: login.php'); exit; }

    $bd = new PDO("mysql:host=localhost;dbname=tools4thetrade;charset=utf8mb4", "root", "");
    $uid = $_SESSION['utl_id'];

    // Redirect non-admins away
    $check = $bd->prepare("SELECT utl_admin FROM utilizador WHERE utl_id = ?");
    $check->execute([$uid]);
    if(!$check->fetchColumn()) { header('Location: index.php'); exit; }

    if(!array_key_exists('utl_foto', $_SESSION)) {
        $fotoQ = $bd->prepare("SELECT utl_foto FROM utilizador WHERE utl_id = ?");
        $fotoQ->execute([$uid]);
        $_SESSION['utl_foto'] = $fotoQ->fetchColumn() ?: '';
    }
    $userFoto = $_SESSION['utl_foto'];

    $erro    = '';
    $sucesso = '';

    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action   = $_POST['action']    ?? '';
        $targetId = (int)($_POST['target_id'] ?? 0);

        // Prevent admin from modifying their own account
        if($targetId === $uid) {
            $erro = 'Não podes modificar a tua própria conta.';

        } elseif($action === 'toggle_admin') {
            $bd->prepare("UPDATE utilizador SET utl_admin = NOT utl_admin WHERE utl_id = ?")
               ->execute([$targetId]);
            $sucesso = 'Permissões de admin atualizadas.';

        } elseif($action === 'delete_user') {
            // Remove tool images from disk
            $imgs = $bd->prepare(
                "SELECT fi.img_path FROM ferramenta_imagem fi
                 JOIN ferramenta f ON fi.img_fer_id = f.fer_id
                 WHERE f.fer_utl_id = ?"
            );
            $imgs->execute([$targetId]);
            foreach($imgs->fetchAll(PDO::FETCH_COLUMN) as $p) { @unlink(__DIR__ . '/' . $p); }

            // Remove profile photo from disk
            $fotoPath = $bd->prepare("SELECT utl_foto FROM utilizador WHERE utl_id = ?");
            $fotoPath->execute([$targetId]);
            $fp = $fotoPath->fetchColumn();
            if($fp) @unlink(__DIR__ . '/' . $fp);

            // Delete records: rentals → tools → user
            $bd->prepare("DELETE FROM aluguer WHERE alu_utl_id = ?")->execute([$targetId]);
            $ferIds = $bd->prepare("SELECT fer_id FROM ferramenta WHERE fer_utl_id = ?");
            $ferIds->execute([$targetId]);
            foreach($ferIds->fetchAll(PDO::FETCH_COLUMN) as $fid) {
                $bd->prepare("DELETE FROM aluguer WHERE alu_fer_id = ?")->execute([$fid]);
            }
            $bd->prepare("DELETE FROM ferramenta WHERE fer_utl_id = ?")->execute([$targetId]);
            $bd->prepare("DELETE FROM utilizador WHERE utl_id = ?")->execute([$targetId]);
            $sucesso = 'Utilizador eliminado com sucesso.';

        } elseif($action === 'toggle_tool') {
            $bd->prepare("UPDATE ferramenta SET fer_ativa = NOT fer_ativa WHERE fer_id = ?")
               ->execute([$targetId]);
            $sucesso = 'Estado da ferramenta atualizado.';

        } elseif($action === 'delete_tool_admin') {
            // Remove tool images from disk
            $imgPaths = $bd->prepare("SELECT img_path FROM ferramenta_imagem WHERE img_fer_id = ?");
            $imgPaths->execute([$targetId]);
            foreach($imgPaths->fetchAll(PDO::FETCH_COLUMN) as $p) { @unlink(__DIR__ . '/' . $p); }

            // Delete records: rentals → tool
            $bd->prepare("DELETE FROM aluguer WHERE alu_fer_id = ?")->execute([$targetId]);
            $bd->prepare("DELETE FROM ferramenta WHERE fer_id = ?")->execute([$targetId]);
            $sucesso = 'Ferramenta eliminada com sucesso.';
        }

        header('Location: admin.php' . ($sucesso ? '?ok=1' : '?erro=1')); exit;
    }

    if(isset($_GET['ok']))   $sucesso = 'Operação realizada com sucesso.';
    if(isset($_GET['erro'])) $erro    = 'Não podes modificar a tua própria conta.';

    // Platform-wide stats
    $stats = $bd->query("
        SELECT
            (SELECT COUNT(*) FROM utilizador)                           AS total_utl,
            (SELECT COUNT(*) FROM utilizador WHERE utl_admin = 1)       AS total_admins,
            (SELECT COUNT(*) FROM ferramenta WHERE fer_ativa = 1)       AS total_ferramentas,
            (SELECT COUNT(*) FROM aluguer)                              AS total_alugueres,
            (SELECT COUNT(*) FROM aluguer WHERE alu_estado = 'Alugado') AS ativos
    ")->fetch(PDO::FETCH_ASSOC);

    // All users with tool and rental counts
    $utilizadores = $bd->query("
        SELECT u.*,
               COUNT(DISTINCT f.fer_id) AS num_ferramentas,
               COUNT(DISTINCT a.alu_id) AS num_alugueres
        FROM utilizador u
        LEFT JOIN ferramenta f ON f.fer_utl_id = u.utl_id AND f.fer_ativa = 1
        LEFT JOIN aluguer    a ON a.alu_utl_id  = u.utl_id
        GROUP BY u.utl_id
        ORDER BY u.utl_criado DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Categories for tool filter
    $cats = $bd->query("SELECT * FROM categoria ORDER BY cat_nome")->fetchAll(PDO::FETCH_ASSOC);

    // All tools with search/filter
    $catFiltro  = (int)($_GET['cat']  ?? 0);
    $nomeFiltro = trim($_GET['nome']  ?? '');
    $toolWhere  = [];
    $toolParams = [];
    if($catFiltro) {
        $toolWhere[]  = "f.fer_cat_id = ?";
        $toolParams[] = $catFiltro;
    }
    if($nomeFiltro !== '') {
        $toolWhere[]  = "f.fer_nome LIKE ?";
        $toolParams[] = '%' . $nomeFiltro . '%';
    }
    $toolWhereStr = $toolWhere ? 'WHERE ' . implode(' AND ', $toolWhere) : '';

    $stmtFerramentas = $bd->prepare(
        "SELECT f.*, c.cat_nome, u.utl_nome AS dono_nome,
                (SELECT COUNT(*) FROM aluguer a WHERE a.alu_fer_id = f.fer_id AND a.alu_estado IN ('Reservado','Alugado')) AS alugueres_ativos
         FROM ferramenta f
         JOIN categoria c ON f.fer_cat_id = c.cat_id
         JOIN utilizador u ON f.fer_utl_id = u.utl_id
         $toolWhereStr
         ORDER BY f.fer_criada DESC"
    );
    $stmtFerramentas->execute($toolParams);
    $listaFerramentas = $stmtFerramentas->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Tools 4 The Trade</title>
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
                <a href="admin.php">Admin</a>
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

                <?php if($sucesso): ?>
                    <div class="msg-sucesso" style="margin:16px 24px 0;"><?php echo htmlspecialchars($sucesso); ?></div>
                <?php endif; ?>
                <?php if($erro): ?>
                    <div class="msg-erro" style="margin:16px 24px 0;"><?php echo htmlspecialchars($erro); ?></div>
                <?php endif; ?>

                <section class="dashboard-section">
                    <h1 class="section-title">Painel de Administração</h1>
                    <div class="stats-grid-2">
                        <div class="stat-box-accent">
                            <h3>Utilizadores</h3>
                            <div class="stat-num"><?php echo $stats['total_utl']; ?></div>
                        </div>
                        <div class="stat-box-accent">
                            <h3>Administradores</h3>
                            <div class="stat-num"><?php echo $stats['total_admins']; ?></div>
                        </div>
                        <div class="stat-box-accent">
                            <h3>Ferramentas ativas</h3>
                            <div class="stat-num"><?php echo $stats['total_ferramentas']; ?></div>
                        </div>
                        <div class="stat-box-accent">
                            <h3>Alugueres ativos</h3>
                            <div class="stat-num"><?php echo $stats['ativos']; ?></div>
                        </div>
                    </div>
                </section>

                <section class="dashboard-section">
                    <h2 class="section-title">Gestão de Utilizadores</h2>
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Membro desde</th>
                                <th>Ferramentas</th>
                                <th>Alugueres</th>
                                <th>Admin</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($utilizadores as $u): ?>
                            <tr>
                                <td><?php echo $u['utl_id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($u['utl_nome']); ?></strong>
                                    <?php if($u['utl_id'] === $uid): ?>
                                        <span style="font-size:0.75rem;color:#888;"> (tu)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($u['utl_email']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($u['utl_criado'])); ?></td>
                                <td><?php echo $u['num_ferramentas']; ?></td>
                                <td><?php echo $u['num_alugueres']; ?></td>
                                <td>
                                    <span class="estado-badge <?php echo $u['utl_admin'] ? 'estado-Alugado' : 'estado-Devolvido'; ?>">
                                        <?php echo $u['utl_admin'] ? 'Sim' : 'Não'; ?>
                                    </span>
                                </td>
                                <td style="display:flex; gap:8px; flex-wrap:wrap;">
                                    <?php if($u['utl_id'] !== $uid): ?>
                                        <form method="post" style="margin:0;">
                                            <input type="hidden" name="action" value="toggle_admin">
                                            <input type="hidden" name="target_id" value="<?php echo $u['utl_id']; ?>">
                                            <button type="submit" class="simple-button" style="font-size:0.78rem; padding:5px 10px;">
                                                <?php echo $u['utl_admin'] ? 'Remover admin' : 'Tornar admin'; ?>
                                            </button>
                                        </form>
                                        <form method="post" style="margin:0;" class="delete-tool-form">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="target_id" value="<?php echo $u['utl_id']; ?>">
                                            <button type="submit" class="simple-button btn-delete-tool"
                                                    style="background:#c0392b; font-size:0.78rem; padding:5px 10px;">
                                                Eliminar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="font-size:0.8rem; color:#aaa;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>

                <section class="dashboard-section">
                    <h2 class="section-title">Gestão de Ferramentas</h2>

                    <form method="get" class="filter-bar" style="margin-bottom:16px;">
                        <input type="text" name="nome" placeholder="Pesquisar por nome..."
                               value="<?php echo htmlspecialchars($nomeFiltro); ?>"
                               style="padding:8px 12px; border:1px solid #ddd; border-radius:6px;">
                        <select name="cat" style="padding:8px 12px; border:1px solid #ddd; border-radius:6px;">
                            <option value="">Todas as categorias</option>
                            <?php foreach($cats as $c): ?>
                                <option value="<?php echo $c['cat_id']; ?>"
                                    <?php echo $catFiltro == $c['cat_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['cat_nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="simple-button">Filtrar</button>
                        <?php if($catFiltro || $nomeFiltro): ?>
                            <a href="admin.php" class="simple-button" style="background:#888;">Limpar</a>
                        <?php endif; ?>
                    </form>

                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Categoria</th>
                                <th>Proprietário</th>
                                <th>Preço</th>
                                <th>Alugueres ativos</th>
                                <th>Estado</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($listaFerramentas)): ?>
                                <tr><td colspan="8" style="text-align:center; color:#aaa;">Nenhuma ferramenta encontrada.</td></tr>
                            <?php else: ?>
                                <?php foreach($listaFerramentas as $t): ?>
                                <tr>
                                    <td><?php echo $t['fer_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($t['fer_nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($t['cat_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($t['dono_nome']); ?></td>
                                    <td><?php echo number_format($t['fer_preco'], 2); ?>€/dia</td>
                                    <td><?php echo $t['alugueres_ativos']; ?></td>
                                    <td>
                                        <span class="estado-badge <?php echo $t['fer_ativa'] ? 'estado-Alugado' : 'estado-Devolvido'; ?>">
                                            <?php echo $t['fer_ativa'] ? 'Ativa' : 'Inativa'; ?>
                                        </span>
                                    </td>
                                    <td style="display:flex; gap:8px; flex-wrap:wrap;">
                                        <form method="post" style="margin:0;">
                                            <input type="hidden" name="action" value="toggle_tool">
                                            <input type="hidden" name="target_id" value="<?php echo $t['fer_id']; ?>">
                                            <button type="submit" class="simple-button" style="font-size:0.78rem; padding:5px 10px;">
                                                <?php echo $t['fer_ativa'] ? 'Desativar' : 'Ativar'; ?>
                                            </button>
                                        </form>
                                        <form method="post" style="margin:0;" class="delete-tool-form">
                                            <input type="hidden" name="action" value="delete_tool_admin">
                                            <input type="hidden" name="target_id" value="<?php echo $t['fer_id']; ?>">
                                            <button type="submit" class="simple-button btn-delete-tool"
                                                    style="background:#c0392b; font-size:0.78rem; padding:5px 10px;">
                                                Eliminar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </section>

            </main>
        </div>
    </div>

    <script src="../js/script.js?v=2"></script>
</body>
</html>