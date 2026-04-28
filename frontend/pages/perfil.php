<?php
    session_start();
    if(!isset($_SESSION['utl_id'])) header('Location: login.php');

    $bd = new PDO("mysql:host=localhost;dbname=tools4thetrade;charset=utf8mb4", "root", "");
    $uid = $_SESSION['utl_id'];

    // Delete tool handler
    if($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_tool') {
        $ferId = (int)($_POST['fer_id'] ?? 0);
        $own = $bd->prepare("SELECT fer_id FROM ferramenta WHERE fer_id = ? AND fer_utl_id = ?");
        $own->execute([$ferId, $uid]);
        if($own->fetchColumn()) {
            // Get image paths before deleting
            $imgPaths = $bd->prepare("SELECT img_path FROM ferramenta_imagem WHERE img_fer_id = ?");
            $imgPaths->execute([$ferId]);
            foreach($imgPaths->fetchAll(PDO::FETCH_COLUMN) as $p) { @unlink(__DIR__ . '/' . $p); }
            // Delete rentals then tool (cascade removes images from DB)
            $bd->prepare("DELETE FROM aluguer WHERE alu_fer_id = ?")->execute([$ferId]);
            $bd->prepare("DELETE FROM ferramenta WHERE fer_id = ? AND fer_utl_id = ?")->execute([$ferId, $uid]);
        }
        header('Location: perfil.php'); exit;
    }

    // Rental status update handler
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alu_id'], $_POST['estado'])) {
        $estados = ['Reservado', 'Alugado', 'Devolvido'];
        if(in_array($_POST['estado'], $estados)) {
            $devolvido = $_POST['estado'] === 'Devolvido' ? date('Y-m-d H:i:s') : null;
            $upd = $bd->prepare(
                "UPDATE aluguer SET alu_estado = ?, alu_devolvido = ?
                 WHERE alu_id = ?
                   AND alu_fer_id IN (SELECT fer_id FROM ferramenta WHERE fer_utl_id = ?)"
            );
            $upd->execute([$_POST['estado'], $devolvido, (int)$_POST['alu_id'], $uid]);
        }
        header('Location: perfil.php'); exit;
    }

    $flash = $_SESSION['flash'] ?? '';
    unset($_SESSION['flash']);

    $stmt = $bd->prepare("SELECT * FROM utilizador WHERE utl_id = ?");
    $stmt->execute([$uid]);
    $utl = $stmt->fetch(PDO::FETCH_ASSOC);

    // Cache user photo in session
    $_SESSION['utl_foto'] = $utl['utl_foto'] ?? '';
    $userFoto = $_SESSION['utl_foto'];

    $ferramentas = $bd->prepare(
        "SELECT f.*, c.cat_nome FROM ferramenta f
         JOIN categoria c ON f.fer_cat_id = c.cat_id
         WHERE f.fer_utl_id = ?
         ORDER BY f.fer_criada DESC"
    );
    $ferramentas->execute([$uid]);
    $minhasFerramentas = $ferramentas->fetchAll(PDO::FETCH_ASSOC);

    $ferramentasAlugadas = $bd->prepare(
        "SELECT a.alu_id, a.alu_inicio, a.alu_fim, a.alu_estado,
                f.fer_nome, f.fer_id,
                u.utl_nome, u.utl_email
         FROM aluguer a
         JOIN ferramenta f ON a.alu_fer_id = f.fer_id
         JOIN utilizador u ON a.alu_utl_id = u.utl_id
         WHERE f.fer_utl_id = ?
           AND a.alu_estado IN ('Reservado','Alugado')
         ORDER BY a.alu_inicio ASC"
    );
    $ferramentasAlugadas->execute([$uid]);
    $minhasAlugadas = $ferramentasAlugadas->fetchAll(PDO::FETCH_ASSOC);

    $alugueres = $bd->prepare(
        "SELECT a.*, f.fer_nome, c.cat_nome FROM aluguer a
         JOIN ferramenta f ON a.alu_fer_id = f.fer_id
         JOIN categoria c ON f.fer_cat_id = c.cat_id
         WHERE a.alu_utl_id = ?
         ORDER BY a.alu_criado DESC"
    );
    $alugueres->execute([$uid]);
    $meusAlugueres = $alugueres->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - Tools 4 The Trade</title>
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
                    <a href="logout.php">Sair</a>
                    <a href="perfil.php" class="profile-circle" title="Perfil"
                       <?php if(!empty($userFoto)): ?>style="background-image:url('<?php echo htmlspecialchars($userFoto); ?>');background-size:cover;background-color:transparent;"<?php endif; ?>></a>
                </div>
            </header>

            <main class="main-area">

                <?php if($flash): ?>
                <div class="msg-sucesso" style="margin:16px 24px 0;"><?php echo htmlspecialchars($flash); ?></div>
                <?php endif; ?>

                <section class="form-section">
                    <div class="profile-header">
                        <div class="profile-avatar" id="avatarClick" title="Clica para alterar a foto de perfil">
                            <?php if(!empty($utl['utl_foto'])): ?>
                                <img id="avatarImg" src="<?php echo htmlspecialchars($utl['utl_foto']); ?>" alt="Foto de perfil">
                            <?php else: ?>
                                <svg id="avatarSvg" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                                </svg>
                            <?php endif; ?>
                            <div class="avatar-edit-overlay">
                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 15.2a3.2 3.2 0 1 0 0-6.4 3.2 3.2 0 0 0 0 6.4z"/><path d="M9 2 7.17 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3.17L15 2H9zm3 15a5 5 0 1 1 0-10 5 5 0 0 1 0 10z"/></svg>
                            </div>
                            <input type="file" id="avatarInput" accept="image/*" style="display:none;">
                        </div>
                        <p class="profile-name"><?php echo htmlspecialchars($utl['utl_nome']); ?></p>
                        <div class="profile-meta">
                            <span><?php echo htmlspecialchars($utl['utl_email']); ?></span>
                            <?php if($utl['utl_admin']): ?>
                                <a href="admin.php" style="color:#333;font-weight:600;text-decoration:none;">Admin</a>
                            <?php endif; ?>
                            <span>Membro desde <?php echo date('M Y', strtotime($utl['utl_criado'])); ?></span>
                        </div>
                    </div>
                </section>

                <section class="tools-section">
                    <h2>As minhas ferramentas</h2>
                    <?php if(empty($minhasFerramentas)): ?>
                        <p class="empty-msg">Ainda não adicionaste nenhuma ferramenta.</p>
                    <?php else: ?>
                        <table class="profile-table">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Categoria</th>
                                    <th>Preço atual</th>
                                    <th>Estado</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($minhasFerramentas as $f): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($f['fer_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($f['cat_nome']); ?></td>
                                    <td><?php echo number_format($f['fer_preco'], 2); ?>€/dia</td>
                                    <td><?php echo $f['fer_ativa'] ? 'Ativa' : 'Inativa'; ?></td>
                                    <td style="display:flex; gap:8px; align-items:center;">
                                        <a href="editarferramenta.php?id=<?php echo $f['fer_id']; ?>" class="simple-button" style="font-size:0.8rem; padding:6px 12px;">Editar</a>
                                        <form method="post" style="margin:0;" class="delete-tool-form">
                                            <input type="hidden" name="action" value="delete_tool">
                                            <input type="hidden" name="fer_id" value="<?php echo $f['fer_id']; ?>">
                                            <button type="submit" class="simple-button btn-delete-tool" style="background:#c0392b; font-size:0.8rem; padding:6px 12px;">Apagar</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </section>

                <section class="tools-section">
                    <h2>As minhas ferramentas em aluguer</h2>
                    <?php if(empty($minhasAlugadas)): ?>
                        <p class="empty-msg">Nenhuma das tuas ferramentas está atualmente alugada ou reservada.</p>
                    <?php else: ?>
                        <table class="profile-table">
                            <thead>
                                <tr>
                                    <th>Ferramenta</th>
                                    <th>Arrendatário</th>
                                    <th>Contacto</th>
                                    <th>Início</th>
                                    <th>Fim</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($minhasAlugadas as $a): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($a['fer_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($a['utl_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($a['utl_email']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($a['alu_inicio'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($a['alu_fim'])); ?></td>
                                    <td>
                                        <form method="post" style="display:flex;align-items:center;gap:8px;">
                                            <input type="hidden" name="alu_id" value="<?php echo $a['alu_id']; ?>">
                                            <select name="estado" class="estado-select estado-<?php echo $a['alu_estado']; ?>" onchange="this.form.submit()">
                                                <?php foreach(['Reservado','Alugado','Devolvido'] as $e): ?>
                                                    <option value="<?php echo $e; ?>" <?php echo $a['alu_estado'] === $e ? 'selected' : ''; ?>><?php echo $e; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </section>

                <section class="tools-section">
                    <h2>Os meus alugueres</h2>
                    <?php if(empty($meusAlugueres)): ?>
                        <p class="empty-msg">Ainda não alugaste nenhuma ferramenta.</p>
                    <?php else: ?>
                        <table class="profile-table">
                            <thead>
                                <tr>
                                    <th>Ferramenta</th>
                                    <th>Categoria</th>
                                    <th>Início</th>
                                    <th>Fim</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($meusAlugueres as $a): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($a['fer_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($a['cat_nome']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($a['alu_inicio'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($a['alu_fim'])); ?></td>
                                    <td><span class="estado-badge estado-<?php echo $a['alu_estado']; ?>"><?php echo $a['alu_estado']; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </section>

            </main>
        </div>
    </div>

    <script src="../js/script.js?v=2"></script>
</body>
</html>
