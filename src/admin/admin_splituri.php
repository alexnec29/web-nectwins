<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

require './../db.php';

if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    switch ($_GET['action']) {
        case 'get_muscle_groups':
            $stmt = $pdo->query("SELECT id, name FROM muscle_group ORDER BY name");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            exit;

        case 'get_training_types':
            $stmt = $pdo->query("SELECT id, name FROM training_type ORDER BY name");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            exit;

        case 'get_split_details':
            $split_id = (int)($_GET['split_id'] ?? 0);
            $stmt = $pdo->prepare("
                SELECT st.id, st.name, ss.section,
                       array_agg(DISTINCT mg.id) as muscle_group_ids,
                       array_agg(DISTINCT mg.name) as muscle_group_names
                FROM split_type st
                LEFT JOIN section_split ss ON st.id = ss.split_id
                JOIN split_subtype sub ON st.id = sub.split_id
                JOIN split_subtype_muscle_group ssmg ON sub.id = ssmg.split_subtype_id
                JOIN muscle_group mg ON ssmg.muscle_group_id = mg.id
                WHERE st.id = :split_id
                GROUP BY st.id, st.name, ss.section
            ");
            $stmt->execute([':split_id' => $split_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($result ?: []);
            exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_split':
                $split_name = trim($_POST['split_name']);
                $section = trim($_POST['section']);
                $days = $_POST['days'] ?? [];

                if (!empty($split_name) && !empty($section) && !empty($days)) {
                    $pdo->beginTransaction();
                    try {
                        $stmt = $pdo->prepare("INSERT INTO split_type (name) VALUES (:name) RETURNING id");
                        $stmt->execute([':name' => $split_name]);
                        $split_id = $stmt->fetchColumn();

                        $stmt = $pdo->prepare("INSERT INTO section_split (section, split_id) VALUES (:section, :split_id)");
                        $stmt->execute([':section' => $section, ':split_id' => $split_id]);

                        foreach ($days as $day_name => $muscle_groups) {
                            if (!empty($muscle_groups)) {
                                $stmt = $pdo->prepare("INSERT INTO split_subtype (name, split_id) VALUES (:name, :split_id) RETURNING id");
                                $stmt->execute([':name' => $day_name, ':split_id' => $split_id]);
                                $subtype_id = $stmt->fetchColumn();

                                $stmt = $pdo->prepare("INSERT INTO split_subtype_muscle_group (split_subtype_id, muscle_group_id) VALUES (:subtype_id, :muscle_group_id)");
                                foreach ($muscle_groups as $mg_id) {
                                    $stmt->execute([':subtype_id' => $subtype_id, ':muscle_group_id' => (int)$mg_id]);
                                }
                            }
                        }

                        $pdo->commit();
                        $_SESSION['success'] = "Split-ul a fost creat cu succes!";
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $_SESSION['error'] = "Eroare la crearea split-ului: " . $e->getMessage();
                    }
                }
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();

            case 'delete_split':
                $split_id = (int)$_POST['split_id'];
                if ($split_id > 0) {
                    $pdo->prepare("DELETE FROM split_type WHERE id = :id")->execute([':id' => $split_id]);
                    $_SESSION['success'] = "Split-ul a fost șters cu succes!";
                }
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
        }
    }
}

$existing_splits = $pdo->query("
    SELECT st.id, st.name, ss.section,
           json_agg(
               json_build_object(
                   'day_name', sub.name,
                   'muscle_groups', (
                       SELECT array_agg(mg.name)
                       FROM split_subtype_muscle_group ssmg
                       JOIN muscle_group mg ON ssmg.muscle_group_id = mg.id
                       WHERE ssmg.split_subtype_id = sub.id
                   )
               )
           ) as days
    FROM split_type st
    LEFT JOIN section_split ss ON st.id = ss.split_id
    LEFT JOIN split_subtype sub ON st.id = sub.split_id
    GROUP BY st.id, st.name, ss.section
    ORDER BY st.name
")->fetchAll(PDO::FETCH_ASSOC);

$muscle_groups = $pdo->query("SELECT id, name FROM muscle_group ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$training_types = $pdo->query("SELECT id, name FROM training_type ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$section_options = [
    'gym' => 'Sală de fitness',
    'kinetoterapie' => 'Kinetoterapie',
    'fizioterapie' => 'Fizioterapie'
];
?>

<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <title>Management Split-uri | FitFlow</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/split.css">
</head>

<body>
    <nav>
        <h1>Gestionare Niveluri de Antrenament</h1>
        <a class="buton-inapoi" href="admin.php">Înapoi</a>
    </nav>

    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="form-section">
            <h2>Creează Split Nou</h2>
            <form method="post" id="split-form">
                <input type="hidden" name="action" value="create_split">

                <div class="form-row">
                    <div class="form-group">
                        <label for="split_name">Nume Split:</label>
                        <input type="text" id="split_name" name="split_name" required>
                    </div>

                    <div class="form-group">
                        <label for="section">Tip antrenament:</label>
                        <select id="section" name="section" required>
                            <option value="">Selectează tipul...</option>
                            <?php foreach ($section_options as $value => $label): ?>
                                <option value="<?= $value ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="days-container">
                    <div class="form-row">
                        <h3>Zile de Antrenament</h3>
                        <button type="button" class="btn btn-success" id="add-day">+ Adaugă Zi</button>
                    </div>
                    <div id="days-list">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Salvează Split</button>
            </form>
        </div>

        <div class="splits-list">
            <h2>Split-uri Existente</h2>

            <?php if (empty($existing_splits)): ?>
                <div>
                    Nu există split-uri create încă.
                </div>
            <?php else: ?>
                <?php foreach ($existing_splits as $split): ?>
                    <div class="split-item">
                        <div class="split-header">
                            <div class="split-name">
                                <?= htmlspecialchars($split['name']) ?>
                                <?php if (!empty($split['section'])): ?>
                                    <span class="section-badge section-<?= $split['section'] ?>">
                                        <?= htmlspecialchars($section_options[$split['section']] ?? $split['section']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <form method="post" onsubmit="return confirm('Ești sigur că vrei să ștergi acest split?');">
                                <input type="hidden" name="action" value="delete_split">
                                <input type="hidden" name="split_id" value="<?= $split['id'] ?>">
                                <button type="submit" class="btn btn-danger">Șterge</button>
                            </form>
                        </div>

                        <?php if (!empty($split['days']) && $split['days'] !== '[null]'): ?>
                            <div class="split-days">
                                <?php
                                $days = json_decode($split['days'], true);
                                if (is_array($days)) {
                                    foreach ($days as $day):
                                        if ($day['day_name']): ?>
                                            <div class="split-day">
                                                <div class="split-day-name"><?= htmlspecialchars($day['day_name']) ?></div>
                                                <div class="split-day-muscles">
                                                    <?= !empty($day['muscle_groups']) ? implode(', ', $day['muscle_groups']) : 'Fără grupe musculare' ?>
                                                </div>
                                            </div>
                                <?php endif;
                                    endforeach;
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let dayCounter = 0;
        const muscleGroups = <?= json_encode($muscle_groups) ?>;

        function createDayElement() {
            dayCounter++;
            const dayDiv = document.createElement('div');
            dayDiv.className = 'day-item';
            dayDiv.innerHTML = `
                <div class="day-header">
                    <input type="text" name="day_name_${dayCounter}" placeholder="Nume zi (ex: Push, Pull, Legs)" >
                    <button type="button" class="remove-day" onclick="removeDay(this)">Șterge</button>
                </div>
                <div class="muscle-groups-grid">
                    ${muscleGroups.map(mg => `
                        <label class="muscle-group-checkbox">
                            <input type="checkbox" name="days[day_${dayCounter}][]" value="${mg.id}">
                            ${mg.name}
                        </label>
                    `).join('')}
                </div>
            `;
            return dayDiv;
        }

        function removeDay(button) {
            button.closest('.day-item').remove();
        }

        document.getElementById('add-day').addEventListener('click', function() {
            const daysList = document.getElementById('days-list');
            daysList.appendChild(createDayElement());
        });

        document.getElementById('split-form').addEventListener('submit', function(e) {
            const dayItems = document.querySelectorAll('.day-item');

            dayItems.forEach((dayItem, index) => {
                const dayNameInput = dayItem.querySelector('input[type="text"]');
                const checkboxes = dayItem.querySelectorAll('input[type="checkbox"]:checked');

                if (dayNameInput && dayNameInput.value.trim()) {
                    dayNameInput.name = `day_names[]`;

                    checkboxes.forEach(checkbox => {
                        checkbox.name = `days[${dayNameInput.value.trim()}][]`;
                    });
                }
            });
        });

        document.getElementById('add-day').click();
    </script>
</body>

</html>