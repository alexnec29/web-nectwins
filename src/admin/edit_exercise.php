<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

require './../db.php';

$exercise_id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT * FROM exercise WHERE id = :id'
);
$stmt->execute([':id' => $exercise_id]);
$exercise = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$exercise) {
    die('Exercițiu neegăsit.');
}

$levels     = $pdo->query('SELECT id, name FROM training_level ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$groups     = $pdo->query('SELECT id, name FROM muscle_group ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$subgroups  = $pdo->query("SELECT id, name, principal_group FROM muscle_subgroup ORDER BY principal_group, name")->fetchAll(PDO::FETCH_ASSOC);
$locations  = $pdo->query('SELECT id, name FROM location ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$conditions = $pdo->query('SELECT id, name FROM health_condition ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$sections   = ['gym', 'kineto', 'fizio'];

$existing_subs = $pdo->prepare('SELECT muscle_subgroup_id FROM exercise_muscle_group WHERE exercise_id = :id');
$existing_subs->execute([':id' => $exercise_id]);
$existing_subs = array_column($existing_subs->fetchAll(PDO::FETCH_ASSOC), 'muscle_subgroup_id');

$existing_locs = $pdo->prepare('SELECT location_id FROM exercise_location WHERE exercise_id = :id');
$existing_locs->execute([':id' => $exercise_id]);
$existing_locs = array_column($existing_locs->fetchAll(PDO::FETCH_ASSOC), 'location_id');

$existing_secs = $pdo->prepare('SELECT section FROM exercise_section WHERE exercise_id = :id');
$existing_secs->execute([':id' => $exercise_id]);
$existing_secs = array_column($existing_secs->fetchAll(PDO::FETCH_ASSOC), 'section');

$existing_conds = $pdo->prepare('SELECT condition_id FROM exercise_health_condition WHERE exercise_id = :id');
$existing_conds->execute([':id' => $exercise_id]);
$existing_conds = array_column($existing_conds->fetchAll(PDO::FETCH_ASSOC), 'condition_id');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']);
    $description = trim($_POST['description']);
    $link        = trim($_POST['link']);
    $difficulty  = (int)$_POST['difficulty'];
    $bodyweight  = isset($_POST['is_bodyweight']) ? 1 : 0;
    $equipment   = isset($_POST['equipment_needed']) ? 1 : 0;

    $subs   = array_map('intval', $_POST['subgroup'] ?? []);
    $locs   = array_map('intval', $_POST['location'] ?? []);
    $secs   = array_map('strval', $_POST['section'] ?? []);
    $conds  = array_map('intval', $_POST['conditions'] ?? []);

    try {
        $pdo->beginTransaction();

        $upd = $pdo->prepare(
            'UPDATE exercise SET name=:name, description=:desc, link=:link, dificulty=:diff, is_bodyweight=:bw, equipment_needed=:eq WHERE id=:id'
        );
        $upd->execute([
            ':name' => $name,
            ':desc' => $description,
            ':link' => $link,
            ':diff' => $difficulty,
            ':bw' => $bodyweight,
            ':eq' => $equipment,
            ':id' => $exercise_id
        ]);

        $pdo->prepare('DELETE FROM exercise_muscle_group WHERE exercise_id=:id')->execute([':id' => $exercise_id]);
        if ($subs) {
            $ins = $pdo->prepare('INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES (:eid,:mid)');
            foreach ($subs as $mid) $ins->execute([':eid' => $exercise_id, ':mid' => $mid]);
        }

        $pdo->prepare('DELETE FROM exercise_location WHERE exercise_id=:id')->execute([':id' => $exercise_id]);
        if ($locs) {
            $ins = $pdo->prepare('INSERT INTO exercise_location (exercise_id, location_id) VALUES (:eid,:lid)');
            foreach ($locs as $lid) $ins->execute([':eid' => $exercise_id, ':lid' => $lid]);
        }

        $pdo->prepare('DELETE FROM exercise_section WHERE exercise_id=:id')->execute([':id' => $exercise_id]);
        if ($secs) {
            $ins = $pdo->prepare('INSERT INTO exercise_section (exercise_id, section) VALUES (:eid,:sec)');
            foreach ($secs as $sec) $ins->execute([':eid' => $exercise_id, ':sec' => $sec]);
        }

        $pdo->prepare('DELETE FROM exercise_health_condition WHERE exercise_id=:id')->execute([':id' => $exercise_id]);
        if ($conds) {
            $ins = $pdo->prepare('INSERT INTO exercise_health_condition (exercise_id, condition_id) VALUES (:eid,:cid)');
            foreach ($conds as $cid) $ins->execute([':eid' => $exercise_id, ':cid' => $cid]);
        }

        $pdo->commit();
        header('Location: edit_exercise.php?id=' . $exercise_id . '&updated=1');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die('Eroare la actualizare: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <title>Editare Exercițiu | FitFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/add.css">
</head>

<body>
    <nav>
        <h1>Editare Exercițiu</h1>
        <a class="buton-inapoi" href="admin_exercitii.php">Înapoi</a>
    </nav>
    <?php if (isset($_GET['updated'])): ?>
        <div class="success-message">Exercițiul a fost actualizat cu succes!</div>
    <?php endif; ?>
    <form method="post">
        <label>Nume exercițiu:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($exercise['name']) ?>" required>

        <label>Descriere:</label>
        <textarea name="description" rows="4"><?= htmlspecialchars($exercise['description']) ?></textarea>

        <label>Link video:</label>
        <input type="url" name="link" value="<?= htmlspecialchars($exercise['link']) ?>">

        <label>Dificultate:</label>
        <select name="difficulty" required>
            <option value="">Alege nivel</option>
            <?php foreach ($levels as $lvl): ?>
                <option value="<?= $lvl['id'] ?>" <?= $lvl['id'] == $exercise['dificulty'] ? 'selected' : '' ?>><?= htmlspecialchars($lvl['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <div class="checkbox-group">
            <label><input type="checkbox" name="is_bodyweight" <?= $exercise['is_bodyweight'] ? 'checked' : '' ?>> Bodyweight</label>
            <label><input type="checkbox" name="equipment_needed" <?= $exercise['equipment_needed'] ? 'checked' : '' ?>> Echipament</label>
        </div>

        <fieldset>
            <legend>Grupe & Subgrupe musculare</legend>
            <?php foreach ($groups as $g): ?>
                <div><strong><?= htmlspecialchars($g['name']) ?>:</strong>
                    <?php foreach ($subgroups as $sg): if ($sg['principal_group'] == $g['id']): ?>
                            <label><input type="checkbox" name="subgroup[]" value="<?= $sg['id'] ?>" <?= in_array($sg['id'], $existing_subs) ? 'checked' : '' ?>> <?= htmlspecialchars($sg['name']) ?></label>
                    <?php endif;
                    endforeach; ?>
                </div>
            <?php endforeach; ?>
        </fieldset>

        <fieldset>
            <legend>Locații</legend>
            <?php foreach ($locations as $l): ?>
                <label><input type="checkbox" name="location[]" value="<?= $l['id'] ?>" <?= in_array($l['id'], $existing_locs) ? 'checked' : '' ?>> <?= htmlspecialchars($l['name']) ?></label>
            <?php endforeach; ?>
        </fieldset>

        <fieldset>
            <legend>Secțiuni</legend>
            <?php foreach ($sections as $s): ?>
                <label><input type="checkbox" name="section[]" value="<?= $s ?>" <?= in_array($s, $existing_secs) ? 'checked' : '' ?>> <?= ucfirst($s) ?></label>
            <?php endforeach; ?>
        </fieldset>

        <fieldset>
            <legend>Contraindicat pentru</legend>
            <?php foreach ($conditions as $c): ?>
                <label><input type="checkbox" name="conditions[]" value="<?= $c['id'] ?>" <?= in_array($c['id'], $existing_conds) ? 'checked' : '' ?>> <?= htmlspecialchars($c['name']) ?></label>
            <?php endforeach; ?>
        </fieldset>

        <button type="submit">Actualizează Exercițiu</button>
    </form>
</body>

</html>