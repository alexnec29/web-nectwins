<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

require './../db.php';

if (isset($_GET['ajax']) && $_GET['ajax'] === 'subgroups') {
    $group_id = (int)($_GET['group_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id, name FROM muscle_subgroup WHERE principal_group = :gid ORDER BY name");
    $stmt->execute([':gid' => $group_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    $pdo->prepare("DELETE FROM exercise_health_condition WHERE exercise_id = :id")->execute([':id' => $delete_id]);
    $pdo->prepare("DELETE FROM exercise WHERE id = :id")->execute([':id' => $delete_id]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
    $name        = trim($_POST['name']);
    $description = trim($_POST['description']);
    $link        = trim($_POST['link']);
    $bodyweight  = isset($_POST['is_bodyweight']);
    $equipment   = isset($_POST['equipment_needed']);
    $difficulty  = (int)($_POST['difficulty'] ?? 1);
    $type_id     = 1;

    $subgroupsArr  = array_map('intval', $_POST['subgroup'] ?? []);
    $locationsArr  = array_map('intval', $_POST['location'] ?? []);
    $sectionsArr   = array_map('strval', $_POST['section'] ?? []);
    $conditionsArr = array_map('intval', $_POST['conditions'] ?? []);

    $stmt = $pdo->prepare("CALL add_exercise(:name, :desc, :link, :diff, :type_id, :bw, :eq, :subgroups, :locations, :sections)");
    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':desc', $description);
    $stmt->bindValue(':link', $link);
    $stmt->bindValue(':diff', $difficulty, PDO::PARAM_INT);
    $stmt->bindValue(':type_id', $type_id, PDO::PARAM_INT);
    $stmt->bindValue(':bw', $bodyweight, PDO::PARAM_BOOL);
    $stmt->bindValue(':eq', $equipment, PDO::PARAM_BOOL);
    $stmt->bindValue(':subgroups', '{' . implode(',', $subgroupsArr) . '}', PDO::PARAM_STR);
    $stmt->bindValue(':locations', '{' . implode(',', $locationsArr) . '}', PDO::PARAM_STR);
    $stmt->bindValue(':sections', '{' . implode(',', $sectionsArr) . '}', PDO::PARAM_STR);
    $stmt->execute();

    if (!empty($conditionsArr)) {
        $exercise_id = $pdo->query("SELECT MAX(id) FROM exercise")->fetchColumn();
        $stmtCond = $pdo->prepare("INSERT INTO exercise_health_condition (exercise_id, condition_id) VALUES (:eid, :cid)");
        foreach ($conditionsArr as $cid) {
            $stmtCond->execute([':eid' => $exercise_id, ':cid' => $cid]);
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$levels = $pdo->query("SELECT id, name FROM training_level ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$groups = $pdo->query("SELECT id, name FROM muscle_group ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$subgroups = $pdo->query("SELECT sg.id, sg.name, g.name AS group_name FROM muscle_subgroup sg JOIN muscle_group g ON sg.principal_group = g.id ORDER BY g.name, sg.name")->fetchAll(PDO::FETCH_ASSOC);
$locations = $pdo->query("SELECT id, name FROM location ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$conditions = $pdo->query("SELECT id, name FROM health_condition ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$sections = ['gym', 'kineto', 'fizio'];

$allExercises = $pdo->query(
    "SELECT e.id, e.name, tl.name AS difficulty, e.is_bodyweight, e.equipment_needed
     FROM exercise e
     JOIN training_level tl ON e.dificulty = tl.id
     ORDER BY e.name"
)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Adaugă Exercițiu | FitFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/add.css">
</head>

<body>
    <nav>
        <h1>Adaugă Exercițiu</h1>
        <a class="buton-inapoi" href="admin.php">Înapoi</a>
    </nav>

    <form method="post">
        <label for="name">Nume exercițiu:</label>
        <input type="text" id="name" name="name" required>

        <label for="description">Descriere:</label>
        <textarea id="description" name="description" rows="4"></textarea>

        <label for="link">Link video:</label>
        <input type="url" id="link" name="link">

        <label for="difficulty">Dificultate:</label>
        <select id="difficulty" name="difficulty" required>
            <option value="">Alege nivel</option>
            <?php foreach ($levels as $lvl): ?>
                <option value="<?= $lvl['id'] ?>"><?= htmlspecialchars($lvl['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Caracteristici:</label>
        <div class="checkbox-group">
            <label><input type="checkbox" name="is_bodyweight"> Bodyweight</label>
            <label><input type="checkbox" name="equipment_needed"> Necesită echipament</label>
        </div>

        <fieldset>
            <legend>Grupe & Subgrupe musculare</legend>
            <?php foreach ($groups as $grp): ?>
                <div class="muscle-group-block">
                    <strong><?= htmlspecialchars($grp['name']) ?>:</strong>
                    <div class="checkbox-grid">
                        <?php foreach ($subgroups as $sg): ?>
                            <?php if ($sg['group_name'] === $grp['name']): ?>
                                <label>
                                    <input type="checkbox" name="subgroup[]" value="<?= $sg['id'] ?>">
                                    <?= htmlspecialchars($sg['name']) ?>
                                </label>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </fieldset>

        <fieldset>
            <legend>Locații disponibles</legend>
            <div class="checkbox-grid">
                <?php foreach ($locations as $loc): ?>
                    <label>
                        <input type="checkbox" name="location[]" value="<?= $loc['id'] ?>">
                        <?= htmlspecialchars($loc['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <fieldset>
            <legend>Secțiuni</legend>
            <div class="checkbox-grid">
                <?php foreach ($sections as $sec): ?>
                    <label>
                        <input type="checkbox" name="section[]" value="<?= $sec ?>">
                        <?= ucfirst($sec) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <fieldset>
            <legend>Contraindicat pentru</legend>
            <div class="checkbox-grid">
                <?php foreach ($conditions as $cond): ?>
                    <label>
                        <input type="checkbox" name="conditions[]" value="<?= $cond['id'] ?>">
                        <?= htmlspecialchars($cond['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <button type="submit">Salvează Exercițiu</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nume</th>
                <th>Dificultate</th>
                <th>Bodyweight</th>
                <th>Echipament</th>
                <th>Acțiuni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($allExercises as $ex): ?>
                <tr>
                    <td><?= htmlspecialchars($ex['id']) ?></td>
                    <td><?= htmlspecialchars($ex['name']) ?></td>
                    <td><?= htmlspecialchars($ex['difficulty']) ?></td>
                    <td><?= $ex['is_bodyweight'] ? 'Da' : 'Nu' ?></td>
                    <td><?= $ex['equipment_needed'] ? 'Da' : 'Nu' ?></td>
                    <td class="actions">
                        <a href="edit_exercise.php?id=<?= $ex['id'] ?>" class="delete-button">Editează</a>
                        <form method="post" onsubmit="return confirm('Ești sigur că vrei să ștergi acest exercițiu?');">
                            <input type="hidden" name="delete_id" value="<?= $ex['id'] ?>">
                            <button type="submit" class="delete-button">Șterge</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>