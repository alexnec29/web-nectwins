-- 8.1. Tabela users (câteva utilizatori fictivi)
INSERT INTO users (username, password, email, nume, varsta, gen, inaltime, greutate, conditie)
VALUES
  ('alexandru', 'pwd_hashed_1', 'alex@example.com', 'Alex Popescu', 28, 'M', 180, 75, 'Bună formă'),
  ('maria',     'pwd_hashed_2', 'maria@example.com',   'Maria Ionescu', 32, 'F', 165, 60, 'Ușoară scolioză'),
  ('andrei',    'pwd_hashed_3', 'andrei@example.com',  'Andrei Georgescu', 45, 'M', 175, 80, 'Reabilitare ACL');

-- 8.2. Tabela muscle_group și muscle_subgroup
INSERT INTO muscle_group (name) VALUES
  ('Piept'),
  ('Spate'),
  ('Picioare'),
  ('Umeri'),
  ('Brațe');

INSERT INTO muscle_subgroup (name, principal_group) VALUES
  -- Piept
  ('Piept Superior', 1),
  ('Piept Inferior', 1),
  ('Piept Lateral', 1),
  -- Spate
  ('Dorsali', 2),
  ('Trapéz',   2),
  ('Romboizi', 2),
  -- Picioare
  ('Cvadricepși',   3),
  ('Fesieri',       3),
  ('Femurali (ischio)', 3),
  ('Gambele',       3),
  -- Umeri
  ('Deltoid Anterior', 4),
  ('Deltoid Lateral',  4),
  ('Deltoid Posterior',4),
  -- Brațe
  ('Biceps', 5),
  ('Triceps',5),
  ('Antebraț',5);

-- 8.3. Tabela training_type (tipul de exercițiu)
INSERT INTO training_type (name) VALUES
  ('Forță'),
  ('Cardio'),
  ('Mobilitate'),
  ('Stretching'),
  ('Reabilitare');

-- 8.4. Tabela training_level (nivel de dificultate)
INSERT INTO training_level (name) VALUES
  ('Începător'),
  ('Intermediar'),
  ('Avansat');

-- 8.5. Tabela split_type (împărțire pe zile/grupe)
INSERT INTO split_type (name) VALUES
  ('Full Body'),
  ('Upper/Lower'),
  ('Push/Pull/Legs'),
  ('Bro Split (o grupă/zi)');

-- 8.6. Tabela location (locații de antrenament)
INSERT INTO location (name) VALUES
  ('Sală'),
  ('Acasă'),
  ('Kineto Cabinet'),
  ('Fizio Cabinet'),
  ('Aer Liber');

-- =====================================
-- 9. Populare tabela exercise cu câteva exerciții
-- =====================================
-- Observă că dificilty sau type_id pot fi NULL dacă nu vrem să le completăm
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link)
VALUES
  -- Exerciții de Piept (Forță, Intermediar)
  ('Împins la bancă orizontal', 
   'Exercițiu compus pentru Piept și Triceps, cu haltera la bancă orizontală.', 
   2, 1, FALSE, TRUE, 'https://youtu.be/impins_orizontal'),
  ('Flotări clasice',
   'Exercițiu cu greutate corporală pentru Piept, Umeri, Triceps.',
   1, 1, TRUE, FALSE, 'https://youtu.be/flotari_clasice'),
  -- Exerciții de Spate (Forță, Intermediar)
  ('Tracțiuni la bară fixă',
   'Exercițiu cu corpul liber pentru Dorsali și Biceps.',
   2, 1, TRUE, FALSE, 'https://youtu.be/tractiuni'),
  ('Ramat cu haltera din aplecat',
   'Exercițiu compus pentru Spate (Dorsali, Trapéz) și Biceps.',
   2, 1, FALSE, TRUE, 'https://youtu.be/ramat_plecat'),
  -- Exerciții de Picioare (Forță, Intermediar)
  ('Genuflexiuni cu bară',
   'Exercițiu compus pentru Cvadricepși, Fesieri și Femurali.',
   2, 1, FALSE, TRUE, 'https://youtu.be/genuflexiuni'),
  ('Fandări cu gantere',
   'Exercițiu unilateral pentru Cvadricepși și Fesieri.',
   2, 1, FALSE, TRUE, 'https://youtu.be/fandari'),
  -- Exerciții de Umeri (Forță, Intermediar)
  ('Presa militară cu haltera',
   'Exercițiu compus pentru Deltoid Anterior și Triceps.',
   2, 1, FALSE, TRUE, 'https://youtu.be/presa_militara'),
  ('Ridicări laterale cu gantere',
   'Izolare Deltoid Lateral.',
   2, 1, FALSE, TRUE, 'https://youtu.be/ridicari_laterale'),
  -- Exerciții de Brațe (Forță, Începător)
  ('Flexii biceps cu haltera',
   'Izolare Biceps.',
   1, 1, FALSE, TRUE, 'https://youtu.be/flexii_biceps'),
  ('Extensii triceps la scripete',
   'Izolare Triceps.',
   1, 1, FALSE, TRUE, 'https://youtu.be/extensii_triceps'),
  -- Exerciții de Cardio / Mobilitate / Reabilitare
  ('Alergare pe bandă',
   'Cardio ușor / încălzire.',
   1, 2, FALSE, TRUE, NULL),
  ('Podul gluteal',
   'Mobilitate și activare fesieri.',
   1, 3, TRUE, FALSE, 'https://youtu.be/podul_gluteal'),
  ('Planșă pe antebrațe',
   'Exercițiu pentru core și stabilitate, potrivit și pentru reabilitare.',
   1, 3, TRUE, FALSE, 'https://youtu.be/planșa_antebrate'),
  ('Stretching spate inferior',
   'Exercițiu de stretching pentru regiunea lombară.',
   NULL, 4, TRUE, FALSE, 'https://youtu.be/stretch_lombar');

-- ===============================================
-- 10. Populare tabela exercise_muscle_group (legături exercițiu – subgrupă)
-- ===============================================

-- 10.1. Legăm „Împins la bancă orizontal” (id=1) la subgrupele Piept Superior (1) și Triceps (12)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (1, 1),  -- 1: 'Piept Superior'
  (1, 12); -- 12: 'Triceps'

-- 10.2. „Flotări clasice” (id=2) la subgrupele Piept Inferior(2), Deltoid Anterior(10), Triceps(12)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (2, 2),  -- Piept Inferior
  (2, 10), -- Deltoid Anterior
  (2, 12); -- Triceps

-- 10.3. „Tracțiuni la bară fixă” (id=3) la subgrupele Dorsali(4), Biceps(13)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (3, 4),  -- Dorsali
  (3, 13); -- Biceps

-- 10.4. „Ramat cu haltera din aplecat” (id=4) la subgrupele Dorsali(4), Trapéz(5), Biceps(13)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (4, 4),  -- Dorsali
  (4, 5),  -- Trapéz
  (4, 13); -- Biceps

-- 10.5. „Genuflexiuni cu bară” (id=5) la subgrupele Cvadricepși(7), Fesieri(8), Femurali(9)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (5, 7),  -- Cvadricepși
  (5, 8),  -- Fesieri
  (5, 9);  -- Femurali

-- 10.6. „Fandări cu gantere” (id=6) la subgrupele Cvadricepși(7), Fesieri(8)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (6, 7),  -- Cvadricepși
  (6, 8);  -- Fesieri

-- 10.7. „Presa militară cu haltera” (id=7) la subgrupele Deltoid Anterior(10), Triceps(12)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (7, 10), -- Deltoid Anterior
  (7, 12); -- Triceps

-- 10.8. „Ridicări laterale cu gantere” (id=8) la subgrupa Deltoid Lateral(11)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (8, 11); -- Deltoid Lateral

-- 10.9. „Flexii biceps cu haltera” (id=9) la subgrupa Biceps(13)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (9, 13); -- Biceps

-- 10.10. „Extensii triceps la scripete” (id=10) la subgrupa Triceps(12)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (10, 12); -- Triceps

-- 10.11. „Alergare pe bandă” (id=11) la subgrupe Cardio generale – nu e nevoie de legătură cu subgrupă, omitem

-- 10.12. „Podul gluteal” (id=12) la subgrupa Fesieri(8)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (12, 8);  -- Fesieri

-- 10.13. „Planșă pe antebrațe” (id=13) la subgrupe Antebraț(14), Abdomen/Core (nu există ca subgrupă, lăsăm pentru testare doar Antebraț)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (13, 14); -- Antebraț

-- 10.14. „Stretching spate inferior” (id=14) la subgrupele Romboizi(6), Dorsali(4)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (14, 6),  -- Romboizi
  (14, 4);  -- Dorsali

-- ============================
-- 11. Populare tabela workout
-- ============================
-- Exemplu 1: Workout pentru user_id=1 (Alex), un antrenament de forță pentru Piept & Brațe
INSERT INTO workout (user_id, name, duration_minutes, type_id, level_id, split_id, location_id)
VALUES
  (1, 'Piept & Triceps - Forță',  50, 1, 2, 2, 1);  -- type_id=1 (Forță), level_id=2 (Intermediar), split=Upper/Lower, location=Sală

-- Exemplu 2: Workout pentru user_id=2 (Maria), un antrenament de mobilitate ACASĂ
INSERT INTO workout (user_id, name, duration_minutes, type_id, level_id, split_id, location_id)
VALUES
  (2, 'Mobilitate Spate & Core', 30, 3, 1, 1, 2);  -- type_id=3 (Mobilitate), level_id=1 (Începător), split=Full Body, location=Acasă

-- Exemplu 3: Workout pentru user_id=3 (Andrei), un program de reabilitare la kineto
INSERT INTO workout (user_id, name, duration_minutes, type_id, level_id, split_id, location_id)
VALUES
  (3, 'Reabilitare ACL - Ziua 1', 40, 5, 1, NULL, 3);  -- type_id=5 (Reabilitare), level_id=1 (Începător), split=NULL, location=Kineto Cabinet

-- =============================================
-- 12. Populare tabela workout_exercise (conținut)
-- =============================================

-- 12.1. „Piept & Triceps - Forță” (id=1) – user_id=1
INSERT INTO workout_exercise (workout_id, exercise_id, order_in_workout, sets, reps) VALUES
  -- Exercițiu: Împins la bancă orizontal (id=1)
  (1, 1, 1, 4, 8),
  -- Exercițiu: Flotări clasice (id=2)
  (1, 2, 2, 3, 12),
  -- Exercițiu: Extensii triceps la scripete (id=10)
  (1, 10, 3, 3, 15);

-- 12.2. „Mobilitate Spate & Core” (id=2) – user_id=2
INSERT INTO workout_exercise (workout_id, exercise_id, order_in_workout, sets, reps) VALUES
  -- Exercițiu: Podul gluteal (id=12)
  (2, 12, 1, 3, 12),
  -- Exercițiu: Planșă pe antebrațe (id=13) – ținut 30s considerăm reps=30
  (2, 13, 2, 3, 30),
  -- Exercițiu: Stretching spate inferior (id=14)
  (2, 14, 3, 2, 20);

-- 12.3. „Reabilitare ACL - Ziua 1” (id=3) – user_id=3
INSERT INTO workout_exercise (workout_id, exercise_id, order_in_workout, sets, reps) VALUES
  -- Exercițiu: Genuflexiuni cu bară (id=5) – exercițiu modificat pentru reabilitare, repetări ușoare
  (3, 5, 1, 3, 10),
  -- Exercițiu: Fandări cu gantere (id=6)
  (3, 6, 2, 3, 8),
  -- Exercițiu: Planșă pe antebrațe (id=13) – core stabilizare
  (3, 13, 3, 3, 20);
