<?php
    session_start();
    if(!isset($_SESSION['utl_id'])) header('Location: login.php');

    $bd = new PDO("mysql:host=localhost;dbname=tools4thetrade", "root", "");
    $uid = $_SESSION['utl_id'];

    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alu_id'], $_POST['estado'])) {
        $estados = ['Reservado', 'Alugado', 'Devolvido'];
        if(in_array($_POST['estado'], $estados)) {
            $upd = $bd->prepare(
                "UPDATE aluguer SET alu_estado = ?
                 WHERE alu_id = ?
                   AND alu_fer_id IN (SELECT fer_id FROM ferramenta WHERE fer_utl_id = ?)"
            );
            $upd->execute([$_POST['estado'], (int)$_POST['alu_id'], $uid]);
        }
        header('Location: perfil.php');
        exit;
    }

    $stmt = $bd->prepare("SELECT * FROM utilizador WHERE utl_id = ?");
    $stmt->execute([$uid]);
    $utl = $stmt->fetch(PDO::FETCH_ASSOC);

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
    <style>
        .profile-header {
            display: flex;
            align-items: center;
            gap: 24px;
            margin-bottom: 10px;
        }
        .profile-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: #444;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .profile-avatar svg {
            width: 50px;
            height: 50px;
            fill: #aaa;
        }
        .profile-name { font-size: 1.4rem; font-weight: bold; margin: 0 0 4px; }
        .profile-meta { color: #666; font-size: 0.9rem; }
        .profile-meta span { margin-right: 16px; }

        .estado-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .estado-Reservado  { background: #fff3cd; color: #856404; }
        .estado-Alugado    { background: #d1ecf1; color: #0c5460; }
        .estado-Devolvido  { background: #d4edda; color: #155724; }
        .estado-select {
            border-radius: 12px;
            border: none;
            padding: 3px 10px;
            font-size: 0.8rem;
            font-weight: bold;
            cursor: pointer;
            appearance: none;
        }

        .profile-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.92rem;
        }
        .profile-table th {
            text-align: left;
            padding: 10px 12px;
            border-bottom: 2px solid #ddd;
            color: #555;
        }
        .profile-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
        }
        .profile-table tr:last-child td { border-bottom: none; }
        .empty-msg { color: #999; font-size: 0.9rem; padding: 12px 0; }
    </style>
</head>
<body>
    <div class="layout">

        <aside class="sidebar">
            <h2 class="logo">Tools 4 The Trade</h2>
            <nav class="menu">
                <a href="index.php">Home</a>
                <a href="Ferramentas.php">Ferramentas</a>
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

                <section class="form-section">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="profile-name"><?php echo htmlspecialchars($utl['utl_nome']); ?></p>
                            <div class="profile-meta">
                                <span><?php echo htmlspecialchars($utl['utl_email']); ?></span>
                                <?php if($utl['utl_admin']): ?>
                                    <span style="color:#333;font-weight:bold;">Admin</span>
                                <?php endif; ?>
                                <span>Membro desde <?php echo date('M Y', strtotime($utl['utl_criado'])); ?></span>
                            </div>
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($minhasFerramentas as $f): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($f['fer_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($f['cat_nome']); ?></td>
                                    <td><?php echo number_format($f['fer_preco'], 2); ?>€/dia</td>
                                    <td><?php echo $f['fer_ativa'] ? 'Ativa' : 'Inativa'; ?></td>
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

    <script src="../js/script.js"></script>
</body>
</html>
