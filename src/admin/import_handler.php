<?php
session_start();
if (!isset($_SESSION["username"]) || !isset($_SESSION["role"]) || $_SESSION["role"] < 2) {
    die("acces interzis");
}

$allowedTables = [
    'muscle_group', 'muscle_subgroup',
    'training_type', 'training_level',
    'split_type', 'section_split', 'split_subtype', 'split_subtype_muscle_group',
    'location', 'location_section',
    'exercise', 'exercise_location', 'exercise_muscle_group', 'exercise_section',
    'health_condition', 'exercise_health_condition',
    'training_goal', 'workout', 'workout_exercise', 'workout_session'
];

function getTableColumnsAndTypes(PDO $pdo, string $table): array {
    $stmt = $pdo->prepare("
        SELECT column_name, data_type, is_nullable
        FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = ?
        ORDER BY ordinal_position
    ");
    $stmt->execute([$table]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function validateValueByType($value, string $type, string $isNullable): bool {
    if ($value === null || $value === '') {
        return $isNullable === 'YES';
    }
    switch ($type) {
        case 'integer':
        case 'smallint':
        case 'bigint':
            return filter_var($value, FILTER_VALIDATE_INT) !== false;
        case 'numeric':
        case 'real':
        case 'double precision':
            return is_numeric($value);
        case 'boolean':
            $val = strtolower($value);
            return in_array($val, ['true', 'false', 't', 'f', '1', '0', 'yes', 'no'], true);
        case 'character varying':
        case 'text':
            return is_string($value);
        case 'date':
        case 'timestamp without time zone':
        case 'timestamp with time zone':
            return strtotime($value) !== false;
        default:
            return true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['target_table']) || !in_array($_POST['target_table'], $allowedTables)) {
        header("Location: admin_upload.php?status=error&message=" . urlencode("tabelă invalidă."));
        exit();
    }

    if (!isset($_FILES['data_file']) || $_FILES['data_file']['error'] !== UPLOAD_ERR_OK) {
        header("Location: admin_upload.php?status=error&message=" . urlencode("eroare la încărcarea fișierului."));
        exit();
    }

    $targetTable = $_POST['target_table'];
    $fileTmpPath = $_FILES['data_file']['tmp_name'];
    $fileName = $_FILES['data_file']['name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    try {
        require './../db.php';

        $columnsInfo = getTableColumnsAndTypes($pdo, $targetTable);
        if (!$columnsInfo) {
            throw new Exception("Tabela '$targetTable' nu există sau nu are coloane.");
        }
        $columnsInfo = array_filter($columnsInfo, fn($col) => $col['column_name'] !== 'id');
        $expectedColumns = array_column($columnsInfo, 'column_name');

        $columnsInfoMap = [];
        foreach ($columnsInfo as $colInfo) {
            $columnsInfoMap[$colInfo['column_name']] = $colInfo;
        }

        $count = 0;
        $duplicates = 0;
        $errors = [];

        if ($fileExt === 'csv') {
            $handle = fopen($fileTmpPath, 'r');
            if ($handle === false) {
                throw new Exception("nu s-a putut deschide fișierul csv.");
            }
            $header = fgetcsv($handle);
            if (!$header) {
                throw new Exception("fișierul csv este gol sau invalid.");
            }

            $missing = array_diff($expectedColumns, $header);
            if (count($missing) > 0) {
                throw new Exception("lipsește coloana(e) obligatorie(e) în fișier: " . implode(", ", $missing));
            }

            $columns = array_map(fn($col) => '"' . str_replace('"', '""', $col) . '"', $header);
            $placeholders = array_fill(0, count($columns), '?');
            $insertSql = "INSERT INTO \"$targetTable\" (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
            $stmt = $pdo->prepare($insertSql);

            $lineNum = 1;
            while (($data = fgetcsv($handle)) !== false) {
                $lineNum++;
                if (count($data) !== count($header)) {
                    $errors[] = "linia $lineNum: număr incorect de coloane, rând ignorat.";
                    continue;
                }

                $validRow = true;
                foreach ($data as $i => $value) {
                    $colName = $header[$i];
                    if (!isset($columnsInfoMap[$colName])) {
                        $errors[] = "linia $lineNum: coloana necunoscută '$colName'";
                        $validRow = false;
                        break;
                    }
                    $colInfo = $columnsInfoMap[$colName];
                    if (!validateValueByType($value, $colInfo['data_type'], $colInfo['is_nullable'])) {
                        $errors[] = "linia $lineNum: valoare invalidă pentru coloana '{$colName}'";
                        $validRow = false;
                        break;
                    }
                }
                if (!$validRow) continue;

                try {
                    $stmt->execute($data);
                    $count++;
                } catch (PDOException $e) {
                    if ($e->getCode() === '23505') {
                        $duplicates++;
                    } else {
                        $errors[] = "linia $lineNum: eroare la inserare - " . $e->getMessage();
                    }
                }
            }
            fclose($handle);

        } elseif ($fileExt === 'json') {
            $jsonContent = file_get_contents($fileTmpPath);
            $rows = json_decode($jsonContent, true);

            if (!is_array($rows)) {
                throw new Exception("fișierul json este invalid sau nu conține un array.");
            }

            $firstRow = reset($rows);
            if (!$firstRow) {
                throw new Exception("fișierul json este gol.");
            }

            $jsonColumns = array_keys($firstRow);
            $missing = array_diff($expectedColumns, $jsonColumns);
            if (count($missing) > 0) {
                throw new Exception("lipsește coloana(e) obligatorie(e) în fișierul json: " . implode(", ", $missing));
            }

            $columns = array_map(fn($col) => '"' . str_replace('"', '""', $col) . '"', $jsonColumns);
            $placeholders = array_fill(0, count($columns), '?');
            $insertSql = "INSERT INTO \"$targetTable\" (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
            $stmt = $pdo->prepare($insertSql);

            $lineNum = 1;
            foreach ($rows as $row) {
                $lineNum++;
                $values = [];
                foreach ($jsonColumns as $col) {
                    $values[] = $row[$col] ?? null;
                }

                $validRow = true;
                foreach ($values as $i => $value) {
                    $colName = $jsonColumns[$i];
                    if (!isset($columnsInfoMap[$colName])) {
                        $errors[] = "linia $lineNum: coloana necunoscută '$colName'";
                        $validRow = false;
                        break;
                    }
                    $colInfo = $columnsInfoMap[$colName];
                    if (!validateValueByType($value, $colInfo['data_type'], $colInfo['is_nullable'])) {
                        $errors[] = "linia $lineNum: valoare invalidă pentru coloana '{$colName}'";
                        $validRow = false;
                        break;
                    }
                }
                if (!$validRow) continue;

                try {
                    $stmt->execute($values);
                    $count++;
                } catch (PDOException $e) {
                    if ($e->getCode() === '23505') {
                        $duplicates++;
                    } else {
                        $errors[] = "linia $lineNum: eroare la inserare - " . $e->getMessage();
                    }
                }
            }

        } else {
            throw new Exception("format fișier neacceptat. folosește csv sau json.");
        }

        $msg = "import reușit: $count rânduri inserate.";
        if ($duplicates > 0) {
            $msg .= " $duplicates rânduri au fost ignorate din cauza duplicatelor.";
        }
        if (count($errors) > 0) {
            $msg .= " " . count($errors) . " erori au fost ignorate.";
        }

        header("Location: admin_upload.php?status=success&message=" . urlencode($msg));
        exit();

    } catch (Exception $e) {
        header("Location: admin_upload.php?status=error&message=" . urlencode($e->getMessage()));
        exit();
    }

} else {
    header("Location: admin_upload.php?status=error&message=" . urlencode("cerere invalidă."));
    exit();
}