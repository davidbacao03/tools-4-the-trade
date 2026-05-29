<?php
    session_start();
    if(!isset($_SESSION['utl_id'])) { header('Location: login.php'); exit; }
    $bd = new PDO("mysql:host=localhost;dbname=tools4thetrade;charset=utf8mb4", "root", "");
    $uid = (int)$_SESSION['utl_id'];

    if(!array_key_exists('utl_foto', $_SESSION)) {
        $fotoQ = $bd->prepare("SELECT utl_foto FROM utilizador WHERE utl_id = ?");
        $fotoQ->execute([$uid]);
        $_SESSION['utl_foto'] = $fotoQ->fetchColumn() ?: '';
    }
    $userFoto = $_SESSION['utl_foto'];

    // Toggle active/inactive state
    if($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_state') {
        $ferId = (int)($_POST['fer_id'] ?? 0);
        $bd->prepare("UPDATE ferramenta SET fer_ativa = NOT fer_ativa WHERE fer_id = ? AND fer_utl_id = ?")->execute([$ferId, $uid]);
        header('Location: Ferramentas.php'); exit;
    }

    // Rental status update
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alu_id'], $_POST['estado'])) {
        $estados = ['Reservado', 'Alugado', 'Devolvido'];
        if(in_array($_POST['estado'], $estados)) {
            $devolvido = $_POST['estado'] === 'Devolvido' ? date('Y-m-d H:i:s') : null;
            $bd->prepare(
                "UPDATE aluguer SET alu_estado = ?, alu_devolvido = ?
                 WHERE alu_id = ? AND alu_fer_id IN (SELECT fer_id FROM ferramenta WHERE fer_utl_id = ?)"
            )->execute([$_POST['estado'], $devolvido, (int)$_POST['alu_id'], $uid]);
        }
        header('Location: Ferramentas.php'); exit;
    }

    // Delete tool
    if($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_tool') {
        $ferId = (int)($_POST['fer_id'] ?? 0);
        $own = $bd->prepare("SELECT fer_id FROM ferramenta WHERE fer_id = ? AND fer_utl_id = ?");
        $own->execute([$ferId, $uid]);
        if($own->fetchColumn()) {
            $imgPaths = $bd->prepare("SELECT img_path FROM ferramenta_imagem WHERE img_fer_id = ?");
            $imgPaths->execute([$ferId]);
            foreach($imgPaths->fetchAll(PDO::FETCH_COLUMN) as $p) { @unlink(__DIR__ . '/' . $p); }
            $bd->prepare("DELETE FROM aluguer WHERE alu_fer_id = ?")->execute([$ferId]);
            $bd->prepare("DELETE FROM ferramenta WHERE fer_id = ? AND fer_utl_id = ?")->execute([$ferId, $uid]);
        }
        header('Location: Ferramentas.php'); exit;
    }

    $stmt = $bd->prepare(
        "SELECT f.*, c.cat_nome,
                (SELECT img_path FROM ferramenta_imagem WHERE img_fer_id = f.fer_id AND img_principal = 1 LIMIT 1) AS img_principal
         FROM ferramenta f
         JOIN categoria c ON f.fer_cat_id = c.cat_id
         WHERE f.fer_utl_id = ?
         ORDER BY f.fer_criada DESC"
    );
    $stmt->execute([$uid]);
    $minhasFerramentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Active rentals keyed by fer_id
    $alugueres = [];
    if(!empty($minhasFerramentas)) {
        $ferIds = array_column($minhasFerramentas, 'fer_id');
        $placeholders = implode(',', array_fill(0, count($ferIds), '?'));
        $aluStmt = $bd->prepare(
            "SELECT a.alu_id, a.alu_fer_id, a.alu_estado, a.alu_inicio, a.alu_fim, u.utl_nome
             FROM aluguer a
             JOIN utilizador u ON a.alu_utl_id = u.utl_id
             WHERE a.alu_fer_id IN ($placeholders) AND a.alu_estado IN ('Reservado','Alugado')
             ORDER BY a.alu_inicio ASC"
        );
        $aluStmt->execute($ferIds);
        foreach($aluStmt->fetchAll(PDO::FETCH_ASSOC) as $a) {
            $alugueres[$a['alu_fer_id']] = $a;
        }
    }
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
            <a href="index.php" class="logo"><img src="uploads/logo.png" alt="Logo"><span>Tools 4 The Trade</span></a>
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
                    <p style="color:#828282;font-size:0.9rem;margin:0 0 16px;">Ferramentas que adicionaste para alugar. O estado muda automaticamente quando outro utilizador faz uma reserva.</p>

                    <div class="page-action">
                        <a href="adicionarferramentas.php" class="simple-button">+ Adicionar Ferramenta</a>
                    </div>

                    <?php if(empty($minhasFerramentas)): ?>
                        <p class="empty-msg">Ainda não adicionaste nenhuma ferramenta. <a href="adicionarferramentas.php">Adiciona a primeira!</a></p>
                    <?php else: ?>
                    <table class="profile-table">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Nome</th>
                                <th>Categoria</th>
                                <th>Preço</th>
                                <th>Estado</th>
                                <th>Aluguer</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($minhasFerramentas as $f):
                                $aluguer = $alugueres[$f['fer_id']] ?? null;
                            ?>
                            <tr>
                                <td style="width:48px;">
                                    <?php if($f['img_principal']): ?>
                                        <img src="<?php echo htmlspecialchars($f['img_principal']); ?>" style="width:44px;height:44px;object-fit:cover;border-radius:6px;display:block;">
                                    <?php else: ?>
                                        <div style="width:44px;height:44px;background:#F5F5F5;border-radius:6px;"></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($f['fer_nome']); ?></td>
                                <td><?php echo htmlspecialchars($f['cat_nome']); ?></td>
                                <td><?php echo number_format($f['fer_preco_base'], 2); ?>€/dia</td>
                                <td>
                                    <?php if(!$f['fer_ativa']): ?>
                                        <span class="badge-inativa">Inativa</span>
                                    <?php elseif($aluguer): ?>
                                        <span class="badge-indisponivel">Alugada</span>
                                    <?php else: ?>
                                        <span class="badge-disponivel">Disponível</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($aluguer): ?>
                                        <form method="post" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                            <input type="hidden" name="alu_id" value="<?php echo $aluguer['alu_id']; ?>">
                                            <span style="font-size:0.8rem;color:#828282;"><?php echo htmlspecialchars($aluguer['utl_nome']); ?></span>
                                            <select name="estado" class="estado-select estado-<?php echo $aluguer['alu_estado']; ?>" onchange="this.form.submit()">
                                                <?php foreach(['Reservado','Alugado','Devolvido'] as $e): ?>
                                                    <option value="<?php echo $e; ?>" <?php echo $aluguer['alu_estado'] === $e ? 'selected' : ''; ?>><?php echo $e; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    <?php else: ?>
                                        <span style="font-size:0.82rem;color:#BDBDBD;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                                        <a href="editarferramenta.php?id=<?php echo $f['fer_id']; ?>" class="simple-button" style="font-size:0.78rem;padding:5px 10px;">Editar</a>
                                        <form method="post" style="margin:0;">
                                            <input type="hidden" name="action" value="toggle_state">
                                            <input type="hidden" name="fer_id" value="<?php echo $f['fer_id']; ?>">
                                            <button type="submit" class="simple-button" style="font-size:0.78rem;padding:5px 10px;background:#555;"><?php echo $f['fer_ativa'] ? 'Desativar' : 'Ativar'; ?></button>
                                        </form>
                                        <form method="post" style="margin:0;" class="delete-tool-form">
                                            <input type="hidden" name="action" value="delete_tool">
                                            <input type="hidden" name="fer_id" value="<?php echo $f['fer_id']; ?>">
                                            <button type="submit" class="simple-button btn-delete-tool" style="background:#c0392b;font-size:0.78rem;padding:5px 10px;">Apagar</button>
                                        </form>
                                    </div>
                                </td>
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
