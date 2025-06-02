-- 8.2. Tabela muscle_group și muscle_subgroup
INSERT INTO muscle_group (name) VALUES
  ('Piept'),
  ('Spate'),
  ('Picioare'),
  ('Umeri'),
  ('Brațe');

INSERT INTO health_condition (name) VALUES
  ('Hernie de disc'),
  ('Durere lombară'),
  ('Luxație umăr'),
  ('Artroză genunchi'),
  ('Diabet'),
  ('Hipertensiune');

INSERT INTO users (username, password, email, nume, varsta, gen, inaltime, greutate) VALUES
  ('ana123', 'parola1', 'ana@gmail.com', 'Ana Popescu', 27, 'F', 165, 58),
  ('ionut99', 'pass99', 'ionut@yahoo.com', 'Ionuț Vasile', 34, 'M', 180, 80),
  ('maria_fit', 'mfit', 'maria.fit@gmail.com', 'Maria Ionescu', 45, 'F', 170, 70),
  ('vali_recuperare', 'recval', 'vali@recuperare.ro', 'Vali Petrescu', 52, 'M', 172, 85);

  -- Ionuț (id=2) → Hernie de disc
INSERT INTO user_health_condition (user_id, condition_id) VALUES (2, 1);

-- Maria (id=3) → Artroză genunchi, Diabet
INSERT INTO user_health_condition (user_id, condition_id) VALUES (3, 4), (3, 5);

-- Vali (id=4) → Durere lombară
INSERT INTO user_health_condition (user_id, condition_id) VALUES (4, 2);

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
  ('Gym'),
  ('Kinetoterapie'),
  ('Fizioterapie');

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
  ('Bro Split');

INSERT INTO split_subtype (name, split_type_id) VALUES
  -- Push/Pull/Legs
  ('push', 3),
  ('pull', 3),
  ('legs', 3),

  -- Upper/Lower
  ('upper', 2),
  ('lower', 2),

  -- Bro Split
  ('chest', 4),
  ('back', 4),
  ('arms', 4),
  ('shoulders', 4),
  ('legs', 4),

  -- Full Body
  ('full-body', 1);

INSERT INTO split_subtype_muscle_group (split_subtype_id, muscle_group_id) VALUES
  -- Push
  ((SELECT id FROM split_subtype WHERE name = 'push'), (SELECT id FROM muscle_group WHERE name = 'Piept')),
  ((SELECT id FROM split_subtype WHERE name = 'push'), (SELECT id FROM muscle_group WHERE name = 'Umeri')),
  ((SELECT id FROM split_subtype WHERE name = 'push'), (SELECT id FROM muscle_group WHERE name = 'Brațe')),

  -- Pull
  ((SELECT id FROM split_subtype WHERE name = 'pull'), (SELECT id FROM muscle_group WHERE name = 'Spate')),
  ((SELECT id FROM split_subtype WHERE name = 'pull'), (SELECT id FROM muscle_group WHERE name = 'Brațe')),

  -- Legs (Push/Pull/Legs și Bro Split folosesc același nume → se separă prin split_subtype_id)
  ((SELECT id FROM split_subtype WHERE name = 'legs' AND split_type_id = 3), (SELECT id FROM muscle_group WHERE name = 'Picioare')),
  ((SELECT id FROM split_subtype WHERE name = 'legs' AND split_type_id = 4), (SELECT id FROM muscle_group WHERE name = 'Picioare')),

  -- Upper
  ((SELECT id FROM split_subtype WHERE name = 'upper'), (SELECT id FROM muscle_group WHERE name = 'Piept')),
  ((SELECT id FROM split_subtype WHERE name = 'upper'), (SELECT id FROM muscle_group WHERE name = 'Spate')),
  ((SELECT id FROM split_subtype WHERE name = 'upper'), (SELECT id FROM muscle_group WHERE name = 'Umeri')),
  ((SELECT id FROM split_subtype WHERE name = 'upper'), (SELECT id FROM muscle_group WHERE name = 'Brațe')),

  -- Lower
  ((SELECT id FROM split_subtype WHERE name = 'lower'), (SELECT id FROM muscle_group WHERE name = 'Picioare')),

  -- Bro Split
  ((SELECT id FROM split_subtype WHERE name = 'chest'), (SELECT id FROM muscle_group WHERE name = 'Piept')),
  ((SELECT id FROM split_subtype WHERE name = 'back'), (SELECT id FROM muscle_group WHERE name = 'Spate')),
  ((SELECT id FROM split_subtype WHERE name = 'arms'), (SELECT id FROM muscle_group WHERE name = 'Brațe')),
  ((SELECT id FROM split_subtype WHERE name = 'shoulders'), (SELECT id FROM muscle_group WHERE name = 'Umeri')),

  -- Full Body
  ((SELECT id FROM split_subtype WHERE name = 'full-body'), (SELECT id FROM muscle_group WHERE name = 'Piept')),
  ((SELECT id FROM split_subtype WHERE name = 'full-body'), (SELECT id FROM muscle_group WHERE name = 'Spate')),
  ((SELECT id FROM split_subtype WHERE name = 'full-body'), (SELECT id FROM muscle_group WHERE name = 'Umeri')),
  ((SELECT id FROM split_subtype WHERE name = 'full-body'), (SELECT id FROM muscle_group WHERE name = 'Brațe')),
  ((SELECT id FROM split_subtype WHERE name = 'full-body'), (SELECT id FROM muscle_group WHERE name = 'Picioare'));

-- 8.6. Tabela location (locații de antrenament)
-- GYM
INSERT INTO location (name, section) VALUES 
  ('Acasă', 'gym'),
  ('Sală', 'gym');

-- KINETO
INSERT INTO location (name, section) VALUES 
  ('Centru recuperare', 'kineto'),
  ('Spital', 'kineto');

-- FIZIO
INSERT INTO location (name, section) VALUES 
  ('Terapie fizică', 'fizio'),
  ('Ambulator', 'fizio');

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
   1, 1, FALSE, TRUE, NULL),
  ('Podul gluteal',
   'Mobilitate și activare fesieri.',
   1, 1, TRUE, FALSE, 'https://youtu.be/podul_gluteal'),
  ('Planșă pe antebrațe',
   'Exercițiu pentru core și stabilitate, potrivit și pentru reabilitare.',
   1, 1, TRUE, FALSE, 'https://youtu.be/planșa_antebrate'),
  ('Stretching spate inferior',
   'Exercițiu de stretching pentru regiunea lombară.',
   NULL, 1, TRUE, FALSE, 'https://youtu.be/stretch_lombar');

   -- Gym: id-uri 1 – 14
INSERT INTO exercise_section (exercise_id, section) VALUES
  (1, 'gym'), (2, 'gym'), (3, 'gym'), (4, 'gym'),
  (5, 'gym'), (6, 'gym'), (7, 'gym'), (8, 'gym'),
  (9, 'gym'), (10, 'gym'), (11, 'gym'), (12, 'gym'),
  (13, 'gym'), (14, 'gym');

-- Kineto: id-uri 15 – 28
INSERT INTO exercise_section (exercise_id, section) VALUES
  (15, 'kineto'), (16, 'kineto'), (17, 'kineto'), (18, 'kineto'),
  (19, 'kineto'), (20, 'kineto'), (21, 'kineto'), (22, 'kineto'),
  (23, 'kineto'), (24, 'kineto'), (25, 'kineto'), (26, 'kineto'),
  (27, 'kineto'), (28, 'kineto');

-- Fizio: id-uri 31 – 38
INSERT INTO exercise_section (exercise_id, section) VALUES
  (31, 'fizio'), (32, 'fizio'), (33, 'fizio'), (34, 'fizio'),
  (35, 'fizio'), (36, 'fizio'), (37, 'fizio'), (38, 'fizio');


-- ===============================================
-- 10. Populare tabela exercise_muscle_group (legături exercițiu – subgrupă)
-- ===============================================

-- 10.1. „Împins la bancă orizontal” (id=1) → Piept Superior (1), Triceps (15)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (1, 1),  -- Piept Superior
  (1, 15); -- Triceps

-- 10.2. „Flotări clasice” (id=2) → Piept Inferior (2), Deltoid Anterior (11), Triceps (15)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (2, 2),  -- Piept Inferior
  (2, 11), -- Deltoid Anterior
  (2, 15); -- Triceps

-- 10.3. „Tracțiuni la bară fixă” (id=3) → Dorsali (4), Biceps (14)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (3, 4),  -- Dorsali
  (3, 14); -- Biceps

-- 10.4. „Ramat cu haltera din aplecat” (id=4) → Dorsali (4), Trapéz (5), Biceps (14)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (4, 4),  -- Dorsali
  (4, 5),  -- Trapéz
  (4, 14); -- Biceps

-- 10.5. „Genuflexiuni cu bară” (id=5) → Cvadricepși (7), Fesieri (8), Femurali (9)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (5, 7),  -- Cvadricepși
  (5, 8),  -- Fesieri
  (5, 9);  -- Femurali

-- 10.6. „Fandări cu gantere” (id=6) → Cvadricepși (7), Fesieri (8)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (6, 7),  -- Cvadricepși
  (6, 8);  -- Fesieri

-- 10.7. „Presa militară cu haltera” (id=7) → Deltoid Anterior (11), Triceps (15)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (7, 11), -- Deltoid Anterior
  (7, 15); -- Triceps

-- 10.8. „Ridicări laterale cu gantere” (id=8) → Deltoid Lateral (12)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (8, 12); -- Deltoid Lateral

-- 10.9. „Flexii biceps cu haltera” (id=9) → Biceps (14)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (9, 14); -- Biceps

-- 10.10. „Extensii triceps la scripete” (id=10) → Triceps (15)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (10, 15); -- Triceps

-- 10.11. „Alergare pe bandă” (id=11) → Cardio general – fără asociere musculară, se omite

-- 10.12. „Podul gluteal” (id=12) → Fesieri (8)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (12, 8); -- Fesieri

-- 10.13. „Planșă pe antebrațe” (id=13) → Antebraț (16)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (13, 16); -- Antebraț

-- 10.14. „Stretching spate inferior” (id=14) → Romboizi (6), Dorsali (4)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (14, 6),  -- Romboizi
  (14, 4);  -- Dorsali

-- kineto
INSERT INTO split_type (name) VALUES
  ('Recuperare'),
  ('Mobilitate'),
  ('Intarire');

  INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  -- Recuperare Genunchi (Picioare)
  ('Întinderi pentru genunchi',
   'Exercițiu de întindere ușoară pentru genunchi și mușchii picioarelor.',
   1, 2, TRUE, FALSE, 'https://youtu.be/intinderi_genunchi'),

  ('Ridicări picior întins',
   'Activare mușchi cvadricepși cu piciorul întins, recomandat în recuperare.',
   1, 2, TRUE, FALSE, 'https://youtu.be/ridicari_picior_intins'),

  -- Recuperare Umăr (Umeri)
  ('Rotiri de umăr cu banda elastică',
   'Mobilitate și reabilitare pentru articulația umărului cu banda elastică.',
   1, 2, TRUE, TRUE, 'https://youtu.be/rotiri_umar'),

  ('Ridicări frontale cu gantere ușoare',
   'Întărire ușoară a deltoidului anterior.',
   2, 2, FALSE, TRUE, 'https://youtu.be/ridicari_frontale'),

  -- Recuperare Spate (Spate)
  ('Extensii lombare',
   'Mobilitate și întărire pentru zona lombară.',
   1, 2, TRUE, FALSE, 'https://youtu.be/extensii_lombare'),

  ('Ridicări în pronație',
   'Activare mușchi spate pentru reabilitare.',
   1, 2, TRUE, FALSE, 'https://youtu.be/ridicari_pronatie'),

  -- Mobilitate Generală
  ('Rotiri de trunchi',
   'Exercițiu de mobilitate pentru coloana vertebrală și trunchi.',
   1, 2, TRUE, FALSE, 'https://youtu.be/rotiri_trunchi'),

  ('Genuflexiuni lente',
   'Genuflexiuni controlate pentru mobilitate și flexibilitate.',
   1, 2, TRUE, FALSE, 'https://youtu.be/genuflexiuni_lente'),

  -- Mobilitate Membre
  ('Cercuri cu brațele',
   'Mobilitate pentru umeri și brațe.',
   1, 2, TRUE, FALSE, 'https://youtu.be/cercuri_brațe'),

  ('Ridicări pe vârfuri',
   'Mobilitate pentru glezne și gambe.',
   1, 2, TRUE, FALSE, 'https://youtu.be/ridicari_varfuri'),

  -- Intarire Trunchi
  ('Plank cu menținere',
   'Exercițiu de întărire a trunchiului și core-ului.',
   2, 2, TRUE, FALSE, 'https://youtu.be/plank'),

  ('Ridicări de bazin',
   'Întărire pentru fesieri și spate inferior.',
   2, 2, TRUE, FALSE, 'https://youtu.be/ridicari_bazin'),

  -- Intarire Postura
  ('Ridicări laterale cu gantere',
   'Întărire deltoizi laterali pentru postură corectă.',
   2, 2, FALSE, TRUE, 'https://youtu.be/ridicari_laterale'),

  ('Remedieri scapulare',
   'Exercițiu pentru corectarea posturii scapulare.',
   2, 2, TRUE, FALSE, 'https://youtu.be/remedieri_scapulare');

-- Recuperare Genunchi (exerciții pentru Cvadricepși – id 7)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (15, 7),  -- Întinderi pentru genunchi → Cvadricepși
  (16, 7);  -- Ridicări picior întins → Cvadricepși

-- Recuperare Umăr (exerciții pentru Deltoid Anterior – id 11)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (17, 11), -- Rotiri de umăr cu banda elastică → Deltoid Anterior
  (18, 11); -- Ridicări frontale cu gantere ușoare → Deltoid Anterior

-- Recuperare Spate (Romboizi – id 6, Dorsali – id 4)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (19, 6),  -- Extensii lombare → Romboizi
  (20, 4);  -- Ridicări în pronație → Dorsali

-- Mobilitate Generală (implică mai multe grupe: Piept, Spate, Umeri, Brațe, Picioare)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (21, 6),  -- Rotiri de trunchi → Romboizi (Spate)
  (21, 1),  -- Rotiri de trunchi → Piept Superior
  (21, 11), -- Rotiri de trunchi → Deltoid Anterior
  (21, 14), -- Rotiri de trunchi → Biceps (Brațe)
  (21, 7);  -- Rotiri de trunchi → Cvadricepși (Picioare)

-- Mobilitate Generală – Genuflexiuni lente (doar Cvadricepși – id 7)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (22, 7); -- Genuflexiuni lente → Cvadricepși

-- Mobilitate Membre (Brațe și Picioare)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (23, 14), -- Cercuri cu brațele → Biceps
  (23, 15), -- Cercuri cu brațele → Triceps
  (24, 10); -- Ridicări pe vârfuri → Gambele

-- Întărire Trunchi (core, fesieri)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (25, 16), -- Plank cu menținere → Antebraț (în lipsa unui subgrup "Core")
  (26, 8);  -- Ridicări de bazin → Fesieri

-- Întărire Postură (Umeri, Spate)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (27, 12), -- Ridicări laterale cu gantere → Deltoid Lateral
  (28, 6);  -- Remedieri scapulare → Romboizi

  --fizio
  INSERT INTO split_type (name) VALUES
  ('recuperare post-operatorie'),
  ('reeducare neuromusculara'),
  ('dureri cronice');

  INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  ('Flexii genunchi în șezut',
   'Întindere și mobilitate pentru genunchi, efectuată în șezut.',
   1, 3, TRUE, FALSE, 'https://youtu.be/flexii_genunchi_sezut'),

  ('Rotiri umeri cu gantere ușoare',
   'Mobilitate și întărire ușoară pentru umăr, cu gantere mici.',
   1, 3, FALSE, TRUE, 'https://youtu.be/rotiri_umeri_ganter'),

  ('Întindere ischio în decubit dorsal',
   'Stretching pentru femurali, realizat în poziție culcat pe spate.',
   1, 3, TRUE, FALSE, 'https://youtu.be/intindere_ischio_culcat'),

  ('Ridicări gambe pe treaptă',
   'Întărire și mobilitate pentru gambe, cu suport pe treaptă.',
   1, 3, TRUE, FALSE, 'https://youtu.be/ridicari_gambe_treapta'),

  ('Exercițiu izometric pentru coapsa frontală',
   'Contractare izometrică a cvadricepșilor fără mișcare articulară.',
   1, 3, TRUE, FALSE, 'https://youtu.be/izometric_cvadriceps'),

  ('Plank lateral',
   'Exercițiu pentru stabilitatea core și tonifiere laterală a trunchiului.',
   2, 3, TRUE, FALSE, 'https://youtu.be/plank_lateral'),

  ('Întinderi pentru tendonul ahilean',
   'Stretching pentru tendonul Ahilean și gambe.',
   1, 3, TRUE, FALSE, 'https://youtu.be/intinderi_tendon_ahilean'),

  ('Exercițiu de mobilitate pentru șold',
   'Mobilitate articulație șold, realizată în picioare sau sprijinit.',
   1, 3, TRUE, FALSE, 'https://youtu.be/mobilitate_sold');

-- 1. Genunchi – Cvadricepși (id 7)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (31, 7);   -- Flexii genunchi în șezut → Cvadricepși

-- 2. Umăr – Deltoid Anterior (id 11)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (32, 11);  -- Rotiri umeri cu gantere → Deltoid Anterior

-- 3. Posterior coapsă – Femurali (id 9)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (33, 9);   -- Întindere ischio în decubit dorsal → Femurali

-- 4. Gambe – Gambele (id 10) și Femurali (id 9) (activare multiplă)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (34, 9),   -- Ridicări gambe pe treaptă → Femurali
  (34, 10);  -- Ridicări gambe pe treaptă → Gambele

-- 5. Cvadricepși – Izometric (id 7)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (35, 7);   -- Exercițiu izometric pentru coapsa frontală → Cvadricepși

-- 6. Core lateral – folosim Antebraț (id 16) în lipsa unui subgrup "Core"
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (36, 16);  -- Plank lateral → Antebraț

-- 7. Tendon Ahilean – Gambele (id 10)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (37, 10);  -- Întinderi pentru tendonul ahilean → Gambele

-- 8. Șold – Fesieri (id 8)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (38, 8);   -- Exercițiu de mobilitate pentru șold → Fesieri

  -- Exerciții potrivite pentru: Hernie de disc
INSERT INTO exercise_health_condition (exercise_id, condition_id) VALUES
  (14, 1), -- Stretching lombar
  (19, 1), -- Extensii lombare
  (20, 1); -- Ridicări în pronație

-- Durere lombară
INSERT INTO exercise_health_condition (exercise_id, condition_id) VALUES
  (14, 2),
  (19, 2),
  (21, 2); -- Rotiri trunchi

-- Artroză genunchi
INSERT INTO exercise_health_condition (exercise_id, condition_id) VALUES
  (15, 4),
  (16, 4),
  (31, 4);

-- Diabet – exerciții moderate recomandate
INSERT INTO exercise_health_condition (exercise_id, condition_id) VALUES
  (12, 5), -- Pod gluteal
  (13, 5); -- Planșă antebrațe

-- Hipertensiune
INSERT INTO exercise_health_condition (exercise_id, condition_id) VALUES
  (21, 6),
  (22, 6);

-- Workout generic pentru Ana (fără afecțiuni)
INSERT INTO workout (name, user_id, duration_minutes, type_id, level_id, split_id, location_id, section)
VALUES
  ('Full Body Basic', 1, 30, 1, 1, 1, 2, 'gym');  -- Sala, Gym

-- Workout pentru Ionuț (hernie de disc → kineto)
INSERT INTO workout (name, user_id, duration_minutes, type_id, level_id, split_id, location_id, section)
VALUES
  ('Recuperare Spate', 2, 20, 2, 1, 5, 3, 'kineto'); -- Centru recuperare

-- Workout pentru Maria (artroză genunchi)
INSERT INTO workout (name, user_id, duration_minutes, type_id, level_id, split_id, location_id, section)
VALUES
  ('Genunchi Mobilitate', 3, 25, 3, 1, 7, 5, 'fizio'); -- Terapie fizică

-- Workout pentru Vali (lombară)
INSERT INTO workout (name, user_id, duration_minutes, type_id, level_id, split_id, location_id, section)
VALUES
  ('Spate Mobil', 4, 20, 2, 1, 6, 4, 'kineto'); -- Spital

-- Ana a făcut deja o sesiune
INSERT INTO workout_session (workout_id, user_id, started_at, completed_at)
VALUES (1, 1, NOW() - INTERVAL '2 days', NOW() - INTERVAL '2 days' + INTERVAL '30 minutes');

-- Ionuț în curs
INSERT INTO workout_session (workout_id, user_id, started_at)
VALUES (2, 2, NOW() - INTERVAL '1 hour');

-- Maria a finalizat deja 2 sesiuni
INSERT INTO workout_session (workout_id, user_id, started_at, completed_at)
VALUES 
  (3, 3, NOW() - INTERVAL '3 days', NOW() - INTERVAL '3 days' + INTERVAL '25 minutes'),
  (3, 3, NOW() - INTERVAL '1 day', NOW() - INTERVAL '1 day' + INTERVAL '25 minutes');

-- Vali nu a început sesiunea încă
INSERT INTO workout_session (workout_id, user_id)
VALUES (4, 4);