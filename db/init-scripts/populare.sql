-- Script unificat de populare a tabelelor (mysql/PostgreSQL)

--------------------------------------------------------------------------------
-- Presupuneri:
--  - Tabelele există deja cu structura specificată.
--  - Ștergerea (DROP) nu este inclusă aici, ci doar popularea cu date de referință.
--------------------------------------------------------------------------------

--SUPERADMIN--
CREATE EXTENSION IF NOT EXISTS pgcrypto;
INSERT INTO users (username, password, email, rol)
VALUES (
  'superadmin',
  encode(digest('parola123', 'sha256'), 'hex'),
  'admin@fitflow.com',
  3
);

--------------------------------------------------------------------------------
-- 1. Grupe musculare (muscle_group) și Subgrupe musculare (muscle_subgroup)
--------------------------------------------------------------------------------

-- 1.1. Muscle groups
INSERT INTO muscle_group (name) VALUES
  ('Piept'),
  ('Spate'),
  ('Picioare'),
  ('Umeri'),
  ('Brațe');

-- 1.2. Muscle subgroups
-- Piept
INSERT INTO muscle_subgroup (name, principal_group) VALUES
  ('Piept Superior',      (SELECT id FROM muscle_group WHERE name = 'Piept')),
  ('Piept Inferior',      (SELECT id FROM muscle_group WHERE name = 'Piept')),
  ('Piept Lateral',       (SELECT id FROM muscle_group WHERE name = 'Piept'));

-- Spate
INSERT INTO muscle_subgroup (name, principal_group) VALUES
  ('Dorsali',             (SELECT id FROM muscle_group WHERE name = 'Spate')),
  ('Trapéz',              (SELECT id FROM muscle_group WHERE name = 'Spate')),
  ('Romboizi',            (SELECT id FROM muscle_group WHERE name = 'Spate'));

-- Picioare
INSERT INTO muscle_subgroup (name, principal_group) VALUES
  ('Cvadricepși',         (SELECT id FROM muscle_group WHERE name = 'Picioare')),
  ('Fesieri',             (SELECT id FROM muscle_group WHERE name = 'Picioare')),
  ('Femurali (ischio)',   (SELECT id FROM muscle_group WHERE name = 'Picioare')),
  ('Gambele',             (SELECT id FROM muscle_group WHERE name = 'Picioare'));

-- Umeri
INSERT INTO muscle_subgroup (name, principal_group) VALUES
  ('Deltoid Anterior',    (SELECT id FROM muscle_group WHERE name = 'Umeri')),
  ('Deltoid Lateral',     (SELECT id FROM muscle_group WHERE name = 'Umeri')),
  ('Deltoid Posterior',   (SELECT id FROM muscle_group WHERE name = 'Umeri'));

-- Brațe
INSERT INTO muscle_subgroup (name, principal_group) VALUES
  ('Biceps',              (SELECT id FROM muscle_group WHERE name = 'Brațe')),
  ('Triceps',             (SELECT id FROM muscle_group WHERE name = 'Brațe')),
  ('Antebraț',            (SELECT id FROM muscle_group WHERE name = 'Brațe'));

--------------------------------------------------------------------------------
-- 2. Condiții de sănătate (health_condition) și asocieri cu utilizatorii
--------------------------------------------------------------------------------

INSERT INTO health_condition (name) VALUES
  ('Hernie de disc'),
  ('Durere lombară'),
  ('Luxație umăr'),
  ('Artroză genunchi'),
  ('Diabet'),
  ('Hipertensiune');

--------------------------------------------------------------------------------
-- 3. Utilizatori (users) și legături cu condiții de sănătate
--------------------------------------------------------------------------------

INSERT INTO users (username, password, email, nume, varsta, gen, inaltime, greutate) VALUES
  ('ana123',           'parola1',      'ana@gmail.com',          'Ana Popescu',        27, 'F', 165, 58),
  ('ionut99',          'pass99',       'ionut@yahoo.com',        'Ionuț Vasile',       34, 'M', 180, 80),
  ('maria_fit',        'mfit',         'maria.fit@gmail.com',    'Maria Ionescu',      45, 'F', 170, 70),
  ('vali_recuperare',  'recval',       'vali@recuperare.ro',     'Vali Petrescu',      52, 'M', 172, 85);

-- Asocieri user → health_condition
-- Ionuț (id = 2) → Hernie de disc (id = 1)
INSERT INTO user_health_condition (user_id, condition_id) VALUES
  (2, (SELECT id FROM health_condition WHERE name = 'Hernie de disc'));

-- Maria (id = 3) → Artroză genunchi (id = 4), Diabet (id = 5)
INSERT INTO user_health_condition (user_id, condition_id) VALUES
  (3, (SELECT id FROM health_condition WHERE name = 'Artroză genunchi')),
  (3, (SELECT id FROM health_condition WHERE name = 'Diabet'));

-- Vali (id = 4) → Durere lombară (id = 2)
INSERT INTO user_health_condition (user_id, condition_id) VALUES
  (4, (SELECT id FROM health_condition WHERE name = 'Durere lombară'));

--------------------------------------------------------------------------------
-- 4. Tipuri și niveluri de antrenament
--------------------------------------------------------------------------------

-- 4.1. training_type
INSERT INTO training_type (name) VALUES
  ('Gym'),
  ('Kinetoterapie'),
  ('Fizioterapie');

-- 4.2. training_level
INSERT INTO training_level (name) VALUES
  ('Începător'),
  ('Intermediar'),
  ('Avansat');

--------------------------------------------------------------------------------
-- 5. Split-uri (split_type), subtipuri (split_subtype) și legătura cu grupe musculare
--------------------------------------------------------------------------------

-- 5.1. split_type (pentru „Gym”)
INSERT INTO split_type (name) VALUES
  ('Full Body'),       -- id = 1
  ('Upper/Lower'),     -- id = 2
  ('Push/Pull/Legs'),  -- id = 3
  ('Bro Split');       -- id = 4

-- 5.2. split_subtype (legat de split_type_id)
INSERT INTO split_subtype (name, split_id) VALUES
  -- Push/Pull/Legs (split_type_id = 3)
  ('push',  (SELECT id FROM split_type WHERE name = 'Push/Pull/Legs')),
  ('pull',  (SELECT id FROM split_type WHERE name = 'Push/Pull/Legs')),
  ('legs',  (SELECT id FROM split_type WHERE name = 'Push/Pull/Legs')),
  -- Upper/Lower (split_type_id = 2)
  ('upper', (SELECT id FROM split_type WHERE name = 'Upper/Lower')),
  ('lower', (SELECT id FROM split_type WHERE name = 'Upper/Lower')),
  -- Bro Split (split_type_id = 4)
  ('chest',     (SELECT id FROM split_type WHERE name = 'Bro Split')),
  ('back',      (SELECT id FROM split_type WHERE name = 'Bro Split')),
  ('arms',      (SELECT id FROM split_type WHERE name = 'Bro Split')),
  ('shoulders', (SELECT id FROM split_type WHERE name = 'Bro Split')),
  ('legs',      (SELECT id FROM split_type WHERE name = 'Bro Split')),
  -- Full Body (split_type_id = 1)
  ('full-body', (SELECT id FROM split_type WHERE name = 'Full Body'));

-- 5.3. split_subtype_muscle_group (asociere subtip → muscle_group)
INSERT INTO split_subtype_muscle_group (split_subtype_id, muscle_group_id) VALUES
  -- Push (push → Piept, Umeri, Brațe)
  (
    (SELECT id FROM split_subtype WHERE name = 'push'
      AND split_id = (SELECT id FROM split_type WHERE name = 'Push/Pull/Legs')),
    (SELECT id FROM muscle_group WHERE name = 'Piept')
  ),
  (
    (SELECT id FROM split_subtype WHERE name = 'push'
      AND split_id = (SELECT id FROM split_type WHERE name = 'Push/Pull/Legs')),
    (SELECT id FROM muscle_group WHERE name = 'Umeri')
  ),
  (
    (SELECT id FROM split_subtype WHERE name = 'push'
      AND split_id = (SELECT id FROM split_type WHERE name = 'Push/Pull/Legs')),
    (SELECT id FROM muscle_group WHERE name = 'Brațe')
  ),

  -- Pull (pull → Spate, Brațe)
  (
    (SELECT id FROM split_subtype WHERE name = 'pull'
      AND split_id = (SELECT id FROM split_type WHERE name = 'Push/Pull/Legs')),
    (SELECT id FROM muscle_group WHERE name = 'Spate')
  ),
  (
    (SELECT id FROM split_subtype WHERE name = 'pull'
      AND split_id = (SELECT id FROM split_type WHERE name = 'Push/Pull/Legs')),
    (SELECT id FROM muscle_group WHERE name = 'Brațe')
  ),

  -- Legs (pentru Push/Pull/Legs, split_type_id = 3)
  (
    (SELECT id FROM split_subtype WHERE name = 'legs'
      AND split_id = (SELECT id FROM split_type WHERE name = 'Push/Pull/Legs')),
    (SELECT id FROM muscle_group WHERE name = 'Picioare')
  ),

  -- Legs (pentru Bro Split, split_type_id = 4)
  (
    (SELECT id FROM split_subtype WHERE name = 'legs'
      AND split_id = (SELECT id FROM split_type WHERE name = 'Bro Split')),
    (SELECT id FROM muscle_group WHERE name = 'Picioare')
  ),

  -- Upper (upper → Piept, Spate, Umeri, Brațe)
  (
    (SELECT id FROM split_subtype WHERE name = 'upper'
      AND split_id = (SELECT id FROM split_type WHERE name = 'Upper/Lower')),
    (SELECT id FROM muscle_group WHERE name = 'Piept')
  ),
  (
    (SELECT id FROM split_subtype WHERE name = 'upper'
      AND split_id = (SELECT id FROM split_type WHERE name = 'Upper/Lower')),
    (SELECT id FROM muscle_group WHERE name = 'Spate')
  ),
  (
    (SELECT id FROM split_subtype WHERE name = 'upper'
      AND split_id = (SELECT id FROM split_type WHERE name = 'Upper/Lower')),
    (SELECT id FROM muscle_group WHERE name = 'Umeri')
  ),
  (
    (SELECT id FROM split_subtype WHERE name = 'upper'
      AND split_id = (SELECT id FROM split_type WHERE name = 'Upper/Lower')),
    (SELECT id FROM muscle_group WHERE name = 'Brațe')
  ),

  -- Lower (lower → Picioare)
  (
    (SELECT id FROM split_subtype WHERE name = 'lower'
      AND split_id = (SELECT id FROM split_type WHERE name = 'Upper/Lower')),
    (SELECT id FROM muscle_group WHERE name = 'Picioare')
  ),

  -- Bro Split
  (
    (SELECT id FROM split_subtype WHERE name = 'chest'
      AND split_id = (SELECT id FROM split_type WHERE name = 'Bro Split')),
    (SELECT id FROM muscle_group WHERE name = 'Piept')
  ),
  (
    (SELECT id FROM split_subtype WHERE name = 'back'
      AND split_id = (SELECT id FROM split_type WHERE name = 'Bro Split')),
    (SELECT id FROM muscle_group WHERE name = 'Spate')
  ),
  (
    (SELECT id FROM split_subtype WHERE name = 'arms'
      AND split_id = (SELECT id FROM split_type WHERE name = 'Bro Split')),
    (SELECT id FROM muscle_group WHERE name = 'Brațe')
  ),
  (
    (SELECT id FROM split_subtype WHERE name = 'shoulders'
      AND split_id = (SELECT id FROM split_type WHERE name = 'Bro Split')),
    (SELECT id FROM muscle_group WHERE name = 'Umeri')
  ),
  (
    (SELECT id FROM split_subtype WHERE name = 'legs'
      AND split_id = (SELECT id FROM split_type WHERE name = 'Bro Split')),
    (SELECT id FROM muscle_group WHERE name = 'Picioare')
  ),

  -- Full Body (full-body → Piept, Spate, Umeri, Brațe, Picioare)
  (
    (SELECT id FROM split_subtype WHERE name = 'full-body'
      AND split_id = (SELECT id FROM split_type WHERE name = 'Full Body')),
    (SELECT id FROM muscle_group WHERE name = 'Piept')
  ),
  (
    (SELECT id FROM split_subtype WHERE name = 'full-body'
      AND split_id = (SELECT id FROM split_type WHERE name = 'Full Body')),
    (SELECT id FROM muscle_group WHERE name = 'Spate')
  ),
  (
    (SELECT id FROM split_subtype WHERE name = 'full-body'
      AND split_id = (SELECT id FROM split_type WHERE name = 'Full Body')),
    (SELECT id FROM muscle_group WHERE name = 'Umeri')
  ),
  (
    (SELECT id FROM split_subtype WHERE name = 'full-body'
      AND split_id = (SELECT id FROM split_type WHERE name = 'Full Body')),
    (SELECT id FROM muscle_group WHERE name = 'Brațe')
  ),
  (
    (SELECT id FROM split_subtype WHERE name = 'full-body'
      AND split_id = (SELECT id FROM split_type WHERE name = 'Full Body')),
    (SELECT id FROM muscle_group WHERE name = 'Picioare')
  )
  ON CONFLICT DO NOTHING;
--------------------------------------------------------------------------------
-- 6. Locații de antrenament (location)
--------------------------------------------------------------------------------

-- 6.1. Gym
INSERT INTO location (name, section) VALUES
  ('Acasă', 'gym'),
  ('Sală', 'gym');

-- 6.2. Kinetoterapie
INSERT INTO location (name, section) VALUES
  ('Centru recuperare', 'kinetoterapie'),
  ('Spital', 'kinetoterapie');

-- 6.3. Fizioterapie
INSERT INTO location (name, section) VALUES
  ('Terapie fizică', 'fizioterapie'),
  ('Ambulator', 'fizioterapie');

--------------------------------------------------------------------------------
-- 7. Exerciții (exercise) și legături M-N: muscle_group, section, health_condition
--------------------------------------------------------------------------------

-- 7.1. Exerciții de Gym (type_id = 1)
--------------------------------------------------------------------------------
-- Bench Press
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Bench Press',
    'Împins de la piept cu bara pe bancă orizontală',
    (SELECT id FROM training_level WHERE name = 'Intermediar'),
    (SELECT id FROM training_type WHERE name = 'Gym'),
    FALSE,
    TRUE,
    'https://www.youtube.com/watch?v=rT7DgCr-3pg'
  );
-- Asociere muscle_subgroup (Piept Superior, Piept Mediu → în script avem Piept Lateral în loc de Mediu)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Bench Press'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Piept Superior')
  ),
  (
    (SELECT id FROM exercise WHERE name = 'Bench Press'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Piept Lateral')
  );
-- Asocieri de secțiune
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Bench Press'), 'gym');

--------------------------------------------------------------------------------
-- Squat (Back Squat)
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Back Squat',
    'Genuflexiuni cu bara plasată pe umeri, picioarele depărtate la nivelul umerilor',
    (SELECT id FROM training_level WHERE name = 'Începător'),
    (SELECT id FROM training_type WHERE name = 'Gym'),
    FALSE,
    TRUE,
    'https://www.youtube.com/watch?v=Dy28eq2PjcM'
  );
-- Asociere muscle_subgroup (Cvadricepși, Fesieri, Femurali (ischio), Gambele)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Back Squat'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Cvadricepși')
  ),
  (
    (SELECT id FROM exercise WHERE name = 'Back Squat'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Fesieri')
  ),
  (
    (SELECT id FROM exercise WHERE name = 'Back Squat'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Femurali (ischio)')
  ),
  (
    (SELECT id FROM exercise WHERE name = 'Back Squat'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Gambele')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Back Squat'), 'gym');

--------------------------------------------------------------------------------
-- Dumbbell Row
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Dumbbell Row',
    'Ramat unilateral cu gantera, aplecat, spate drept',
    (SELECT id FROM training_level WHERE name = 'Intermediar'),
    (SELECT id FROM training_type WHERE name = 'Gym'),
    FALSE,
    TRUE,
    'https://www.youtube.com/watch?v=8POcxV1dU7k'
  );
-- Asociere muscle_subgroup (Dorsali, Trapéz)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Dumbbell Row'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Dorsali')
  ),
  (
    (SELECT id FROM exercise WHERE name = 'Dumbbell Row'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Trapéz')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Dumbbell Row'), 'gym');

--------------------------------------------------------------------------------
-- Dumbbell Biceps Curl
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Dumbbell Biceps Curl',
    'Flexii brațe cu gantere, pe rând, control total al mișcării',
    (SELECT id FROM training_level WHERE name = 'Începător'),
    (SELECT id FROM training_type WHERE name = 'Gym'),
    FALSE,
    TRUE,
    'https://www.youtube.com/watch?v=ykJmrZ5v0Oo'
  );
-- Asociere muscle_subgroup (Biceps)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Dumbbell Biceps Curl'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Biceps')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Dumbbell Biceps Curl'), 'gym');

--------------------------------------------------------------------------------
-- Triceps Dips
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Triceps Dips',
    'Flotări descendențe la bare, focus pe triceps, corpul menținut vertical',
    (SELECT id FROM training_level WHERE name = 'Intermediar'),
    (SELECT id FROM training_type WHERE name = 'Gym'),
    TRUE,
    FALSE,
    'https://www.youtube.com/watch?v=0326dy_-CzM'
  );
-- Asociere muscle_subgroup (Triceps)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Triceps Dips'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Triceps')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Triceps Dips'), 'gym');

--------------------------------------------------------------------------------
-- Overhead Press (Shoulder Press)
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Overhead Press',
    'Împins de la umeri cu bara, stând în picioare, spate drept',
    (SELECT id FROM training_level WHERE name = 'Avansat'),
    (SELECT id FROM training_type WHERE name = 'Gym'),
    FALSE,
    TRUE,
    'https://www.youtube.com/watch?v=2yjwXTZQDDI'
  );
-- Asociere muscle_subgroup (Deltoid Anterior, Deltoid Lateral)
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Overhead Press'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Deltoid Anterior')
  ),
  (
    (SELECT id FROM exercise WHERE name = 'Overhead Press'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Deltoid Lateral')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Overhead Press'), 'gym');

--------------------------------------------------------------------------------
-- Plank
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Plank',
    'Menținere poziție pe antebrațe și vârfuri de picioare, corpul drept ca o scândură',
    (SELECT id FROM training_level WHERE name = 'Începător'),
    (SELECT id FROM training_type WHERE name = 'Gym'),
    TRUE,
    FALSE,
    'https://www.youtube.com/watch?v=pSHjTRCQxIw'
  );
-- Asociere muscle_subgroup (Antebraț) — ne folosim de Antebraț pentru core
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Plank'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Antebraț')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Plank'), 'gym');

--------------------------------------------------------------------------------
-- Deadlift
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Deadlift',
    'Îndreptări cu bara, picioarele depărtate la lățimea șoldurilor, spate neutru',
    (SELECT id FROM training_level WHERE name = 'Avansat'),
    (SELECT id FROM training_type WHERE name = 'Gym'),
    FALSE,
    TRUE,
    'https://www.youtube.com/watch?v=op9kVnSso6Q'
  );
-- Asociere muscle_subgroup (Dorsali, Femurali (ischio))
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Deadlift'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Dorsali')
  ),
  (
    (SELECT id FROM exercise WHERE name = 'Deadlift'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Femurali (ischio)')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Deadlift'), 'gym');


--------------------------------------------------------------------------------
-- 7.2. Exerciții de Kinetoterapie (type_id = 2)
--------------------------------------------------------------------------------
-- Întinderi pentru genunchi (Cvadricepși)
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Întinderi pentru genunchi',
    'Exercițiu de întindere ușoară pentru genunchi și mușchii picioarelor.',
    (SELECT id FROM training_level WHERE name = 'Începător'),
    (SELECT id FROM training_type WHERE name = 'Kinetoterapie'),
    TRUE,
    FALSE,
    'https://youtu.be/intinderi_genunchi'
  );
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Întinderi pentru genunchi'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Cvadricepși')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Întinderi pentru genunchi'), 'kinetoterapie');

-- Ridicări picior întins (Cvadricepși)
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Ridicări picior întins',
    'Activare mușchi cvadricepși cu piciorul întins, recomandat în recuperare.',
    (SELECT id FROM training_level WHERE name = 'Începător'),
    (SELECT id FROM training_type WHERE name = 'Kinetoterapie'),
    TRUE,
    FALSE,
    'https://youtu.be/ridicari_picior_intins'
  );
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Ridicări picior întins'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Cvadricepși')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Ridicări picior întins'), 'kinetoterapie');

-- Rotiri de umăr cu banda elastică (Deltoid Anterior)
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Rotiri de umăr cu banda elastică',
    'Mobilitate și reabilitare pentru articulația umărului cu banda elastică.',
    (SELECT id FROM training_level WHERE name = 'Începător'),
    (SELECT id FROM training_type WHERE name = 'Kinetoterapie'),
    TRUE,
    TRUE,
    'https://youtu.be/rotiri_umar'
  );
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Rotiri de umăr cu banda elastică'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Deltoid Anterior')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Rotiri de umăr cu banda elastică'), 'kinetoterapie');

-- Ridicări frontale cu gantere ușoare (Deltoid Anterior)
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Ridicări frontale cu gantere ușoare',
    'Întărire ușoară a deltoidului anterior.',
    (SELECT id FROM training_level WHERE name = 'Intermediar'),
    (SELECT id FROM training_type WHERE name = 'Kinetoterapie'),
    FALSE,
    TRUE,
    'https://youtu.be/ridicari_frontale'
  );
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Ridicări frontale cu gantere ușoare'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Deltoid Anterior')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Ridicări frontale cu gantere ușoare'), 'kinetoterapie');

-- Extensii lombare (Romboizi)
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Extensii lombare',
    'Mobilitate și întărire pentru zona lombară.',
    (SELECT id FROM training_level WHERE name = 'Începător'),
    (SELECT id FROM training_type WHERE name = 'Kinetoterapie'),
    TRUE,
    FALSE,
    'https://youtu.be/extensii_lombare'
  );
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Extensii lombare'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Romboizi')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Extensii lombare'), 'kinetoterapie');

-- Ridicări în pronație (Dorsali)
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Ridicări în pronație',
    'Activare mușchi spate pentru reabilitare.',
    (SELECT id FROM training_level WHERE name = 'Începător'),
    (SELECT id FROM training_type WHERE name = 'Kinetoterapie'),
    TRUE,
    FALSE,
    'https://youtu.be/ridicari_pronatie'
  );
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Ridicări în pronație'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Dorsali')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Ridicări în pronație'), 'kinetoterapie');

-- Rotiri de trunchi (Romboizi, Piept Lateral, Deltoid Anterior, Biceps, Cvadricepși)
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Rotiri de trunchi',
    'Exercițiu de mobilitate pentru coloana vertebrală și trunchi.',
    (SELECT id FROM training_level WHERE name = 'Începător'),
    (SELECT id FROM training_type WHERE name = 'Kinetoterapie'),
    TRUE,
    FALSE,
    'https://youtu.be/rotiri_trunchi'
  );
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Rotiri de trunchi'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Romboizi')
  ),
  (
    (SELECT id FROM exercise WHERE name = 'Rotiri de trunchi'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Piept Lateral')
  ),
  (
    (SELECT id FROM exercise WHERE name = 'Rotiri de trunchi'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Deltoid Anterior')
  ),
  (
    (SELECT id FROM exercise WHERE name = 'Rotiri de trunchi'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Biceps')
  ),
  (
    (SELECT id FROM exercise WHERE name = 'Rotiri de trunchi'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Cvadricepși')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Rotiri de trunchi'), 'kinetoterapie');

-- Genuflexiuni lente (Cvadricepși)
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Genuflexiuni lente',
    'Genuflexiuni controlate pentru mobilitate și flexibilitate.',
    (SELECT id FROM training_level WHERE name = 'Începător'),
    (SELECT id FROM training_type WHERE name = 'Kinetoterapie'),
    TRUE,
    FALSE,
    'https://youtu.be/genuflexiuni_lente'
  );
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Genuflexiuni lente'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Cvadricepși')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Genuflexiuni lente'), 'kinetoterapie');

-- Cercuri cu brațele (Biceps, Triceps)
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Cercuri cu brațele',
    'Mobilitate pentru umeri și brațe.',
    (SELECT id FROM training_level WHERE name = 'Începător'),
    (SELECT id FROM training_type WHERE name = 'Kinetoterapie'),
    TRUE,
    FALSE,
    'https://youtu.be/cercuri_brațe'
  );
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Cercuri cu brațele'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Biceps')
  ),
  (
    (SELECT id FROM exercise WHERE name = 'Cercuri cu brațele'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Triceps')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Cercuri cu brațele'), 'kinetoterapie');

-- Ridicări pe vârfuri (Gambele)
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Ridicări pe vârfuri',
    'Mobilitate pentru glezne și gambe.',
    (SELECT id FROM training_level WHERE name = 'Începător'),
    (SELECT id FROM training_type WHERE name = 'Kinetoterapie'),
    TRUE,
    FALSE,
    'https://youtu.be/ridicari_varfuri'
  );
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Ridicări pe vârfuri'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Gambele')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Ridicări pe vârfuri'), 'kinetoterapie');

-- Plank cu menținere (Antebraț)
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Plank cu menținere',
    'Exercițiu de întărire a trunchiului și core-ului.',
    (SELECT id FROM training_level WHERE name = 'Intermediar'),
    (SELECT id FROM training_type WHERE name = 'Kinetoterapie'),
    TRUE,
    FALSE,
    'https://youtu.be/plank'
  );
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Plank cu menținere'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Antebraț')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Plank cu menținere'), 'kinetoterapie');

-- Ridicări de bazin (Fesieri)
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Ridicări de bazin',
    'Întărire pentru fesieri și spate inferior.',
    (SELECT id FROM training_level WHERE name = 'Intermediar'),
    (SELECT id FROM training_type WHERE name = 'Kinetoterapie'),
    TRUE,
    FALSE,
    'https://youtu.be/ridicari_bazin'
  );
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Ridicări de bazin'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Fesieri')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Ridicări de bazin'), 'kinetoterapie');

-- Ridicări laterale cu gantere (Deltoid Lateral)
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Ridicări laterale cu gantere',
    'Întărire deltoizi laterali pentru postură corectă.',
    (SELECT id FROM training_level WHERE name = 'Intermediar'),
    (SELECT id FROM training_type WHERE name = 'Kinetoterapie'),
    FALSE,
    TRUE,
    'https://youtu.be/ridicari_laterale'
  );
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Ridicări laterale cu gantere'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Deltoid Lateral')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Ridicări laterale cu gantere'), 'kinetoterapie');

-- Remedieri scapulare (Romboizi)
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Remedieri scapulare',
    'Exercițiu pentru corectarea posturii scapulare.',
    (SELECT id FROM training_level WHERE name = 'Intermediar'),
    (SELECT id FROM training_type WHERE name = 'Kinetoterapie'),
    TRUE,
    FALSE,
    'https://youtu.be/remedieri_scapulare'
  );
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Remedieri scapulare'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Romboizi')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Remedieri scapulare'), 'kinetoterapie');


--------------------------------------------------------------------------------
-- 7.3. Exerciții de Fizioterapie (type_id = 3)
--------------------------------------------------------------------------------
-- Flexii genunchi în șezut (Cvadricepși)
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Flexii genunchi în șezut',
    'Întindere și mobilitate pentru genunchi, efectuată în șezut.',
    (SELECT id FROM training_level WHERE name = 'Începător'),
    (SELECT id FROM training_type WHERE name = 'Fizioterapie'),
    TRUE,
    FALSE,
    'https://youtu.be/flexii_genunchi_sezut'
  );
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Flexii genunchi în șezut'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Cvadricepși')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Flexii genunchi în șezut'), 'fizioterapie');

-- Rotiri umeri cu gantere ușoare (Deltoid Anterior)
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Rotiri umeri cu gantere ușoare',
    'Mobilitate și întărire ușoară pentru umăr, cu gantere mici.',
    (SELECT id FROM training_level WHERE name = 'Începător'),
    (SELECT id FROM training_type WHERE name = 'Fizioterapie'),
    FALSE,
    TRUE,
    'https://youtu.be/rotiri_umeri_ganter'
  );
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Rotiri umeri cu gantere ușoare'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Deltoid Anterior')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Rotiri umeri cu gantere ușoare'), 'fizioterapie');

-- Întindere ischio în decubit dorsal (Femurali (ischio))
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Întindere ischio în decubit dorsal',
    'Stretching pentru femurali, realizat în poziție culcat pe spate.',
    (SELECT id FROM training_level WHERE name = 'Începător'),
    (SELECT id FROM training_type WHERE name = 'Fizioterapie'),
    TRUE,
    FALSE,
    'https://youtu.be/intindere_ischio_culcat'
  );
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Întindere ischio în decubit dorsal'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Femurali (ischio)')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Întindere ischio în decubit dorsal'), 'fizioterapie');

-- Ridicări gambe pe treaptă (Gambele, Femurali (ischio))
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Ridicări gambe pe treaptă',
    'Întărire și mobilitate pentru gambe, cu suport pe treaptă.',
    (SELECT id FROM training_level WHERE name = 'Începător'),
    (SELECT id FROM training_type WHERE name = 'Fizioterapie'),
    TRUE,
    FALSE,
    'https://youtu.be/ridicari_gambe_treapta'
  );
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Ridicări gambe pe treaptă'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Femurali (ischio)')
  ),
  (
    (SELECT id FROM exercise WHERE name = 'Ridicări gambe pe treaptă'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Gambele')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Ridicări gambe pe treaptă'), 'fizioterapie');

-- Exercițiu izometric pentru coapsa frontală (Cvadricepși)
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Exercițiu izometric pentru coapsa frontală',
    'Contractare izometrică a cvadricepșilor fără mișcare articulară.',
    (SELECT id FROM training_level WHERE name = 'Începător'),
    (SELECT id FROM training_type WHERE name = 'Fizioterapie'),
    TRUE,
    FALSE,
    'https://youtu.be/izometric_cvadriceps'
  );
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Exercițiu izometric pentru coapsa frontală'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Cvadricepși')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Exercițiu izometric pentru coapsa frontală'), 'fizioterapie');

-- Plank lateral (Antebraț)
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Plank lateral',
    'Exercițiu pentru stabilitatea core și tonifiere laterală a trunchiului.',
    (SELECT id FROM training_level WHERE name = 'Intermediar'),
    (SELECT id FROM training_type WHERE name = 'Fizioterapie'),
    TRUE,
    FALSE,
    'https://youtu.be/plank_lateral'
  );
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Plank lateral'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Antebraț')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Plank lateral'), 'fizioterapie');

-- Întinderi pentru tendonul ahilean (Gambele)
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Întinderi pentru tendonul ahilean',
    'Stretching pentru tendonul Ahilean și gambe.',
    (SELECT id FROM training_level WHERE name = 'Începător'),
    (SELECT id FROM training_type WHERE name = 'Fizioterapie'),
    TRUE,
    FALSE,
    'https://youtu.be/intinderi_tendon_ahilean'
  );
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Întinderi pentru tendonul ahilean'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Gambele')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Întinderi pentru tendonul ahilean'), 'fizioterapie');

-- Exercițiu de mobilitate pentru șold (Fesieri)
INSERT INTO exercise (name, description, dificulty, type_id, is_bodyweight, equipment_needed, link) VALUES
  (
    'Exercițiu de mobilitate pentru șold',
    'Mobilitate articulație șold, realizată în picioare sau sprijinit.',
    (SELECT id FROM training_level WHERE name = 'Începător'),
    (SELECT id FROM training_type WHERE name = 'Fizioterapie'),
    TRUE,
    FALSE,
    'https://youtu.be/mobilitate_sold'
  );
INSERT INTO exercise_muscle_group (exercise_id, muscle_subgroup_id) VALUES
  (
    (SELECT id FROM exercise WHERE name = 'Exercițiu de mobilitate pentru șold'),
    (SELECT id FROM muscle_subgroup WHERE name = 'Fesieri')
  );
INSERT INTO exercise_section (exercise_id, section) VALUES
  ((SELECT id FROM exercise WHERE name = 'Exercițiu de mobilitate pentru șold'), 'fizioterapie');

--------------------------------------------------------------------------------
-- 8. Asocieri exercițiu ↔️ health_condition
--------------------------------------------------------------------------------

-- Observație: se pot semnala exerciții contraindicate sau recomandate
-- pentru anumite afecțiuni. Vom marca în tabelul exercise_health_condition.

-- Hernie de disc (id = 1)
INSERT INTO exercise_health_condition (exercise_id, condition_id) VALUES
  ((SELECT id FROM exercise WHERE name = 'Deadlift'),            (SELECT id FROM health_condition WHERE name = 'Hernie de disc')),
  ((SELECT id FROM exercise WHERE name = 'Extensii lombare'),    (SELECT id FROM health_condition WHERE name = 'Hernie de disc'));

-- Durere lombară (id = 2)
INSERT INTO exercise_health_condition (exercise_id, condition_id) VALUES
  ((SELECT id FROM exercise WHERE name = 'Extensii lombare'),       (SELECT id FROM health_condition WHERE name = 'Durere lombară')),
  ((SELECT id FROM exercise WHERE name = 'Rotiri de trunchi'),      (SELECT id FROM health_condition WHERE name = 'Durere lombară')),
  ((SELECT id FROM exercise WHERE name = 'Plank cu menținere'),     (SELECT id FROM health_condition WHERE name = 'Durere lombară'));

-- Luxație umăr (id = 3)
INSERT INTO exercise_health_condition (exercise_id, condition_id) VALUES
  ((SELECT id FROM exercise WHERE name = 'Rotiri de umăr cu banda elastică'), (SELECT id FROM health_condition WHERE name = 'Luxație umăr')),
  ((SELECT id FROM exercise WHERE name = 'Ridicări frontale cu gantere ușoare'), (SELECT id FROM health_condition WHERE name = 'Luxație umăr')),
  ((SELECT id FROM exercise WHERE name = 'Rotiri umeri cu gantere ușoare'),     (SELECT id FROM health_condition WHERE name = 'Luxație umăr'));

-- Artroză genunchi (id = 4)
INSERT INTO exercise_health_condition (exercise_id, condition_id) VALUES
  ((SELECT id FROM exercise WHERE name = 'Întinderi pentru genunchi'),            (SELECT id FROM health_condition WHERE name = 'Artroză genunchi')),
  ((SELECT id FROM exercise WHERE name = 'Ridicări picior întins'),               (SELECT id FROM health_condition WHERE name = 'Artroză genunchi')),
  ((SELECT id FROM exercise WHERE name = 'Flexii genunchi în șezut'),              (SELECT id FROM health_condition WHERE name = 'Artroză genunchi'));

-- Diabet (id = 5) – exerciții cu impact moderat, cardio ușor
INSERT INTO exercise_health_condition (exercise_id, condition_id) VALUES
  ((SELECT id FROM exercise WHERE name = 'Plank'),                         (SELECT id FROM health_condition WHERE name = 'Diabet')),
  ((SELECT id FROM exercise WHERE name = 'Cercuri cu brațele'),            (SELECT id FROM health_condition WHERE name = 'Diabet'));

-- Hipertensiune (id = 6) – exerciții cu intensitate controlată
INSERT INTO exercise_health_condition (exercise_id, condition_id) VALUES
  ((SELECT id FROM exercise WHERE name = 'Rotiri de trunchi'),            (SELECT id FROM health_condition WHERE name = 'Hipertensiune')),
  ((SELECT id FROM exercise WHERE name = 'Genuflexiuni lente'),           (SELECT id FROM health_condition WHERE name = 'Hipertensiune')),
  ((SELECT id FROM exercise WHERE name = 'Ridicări pe vârfuri'),          (SELECT id FROM health_condition WHERE name = 'Hipertensiune'));

--------------------------------------------------------------------------------
-- 9. Legături exercițiu ↔️ locație ↔️ secțiune (exercise_location se poate omite dacă folosim doar exercise_section)
--------------------------------------------------------------------------------
-- Exerciții doar la Sală
INSERT INTO exercise_location (exercise_id, location_id) VALUES
  ((SELECT id FROM exercise WHERE name = 'Bench Press'), 1),
  ((SELECT id FROM exercise WHERE name = 'Deadlift'), 1),
  ((SELECT id FROM exercise WHERE name = 'Back Squat'), 1);

-- Exerciții doar Acasă
INSERT INTO exercise_location (exercise_id, location_id) VALUES
  ((SELECT id FROM exercise WHERE name = 'Plank'), 2);

-- Exerciții disponibile în ambele locații
INSERT INTO exercise_location (exercise_id, location_id) VALUES
  ((SELECT id FROM exercise WHERE name = 'Plank cu menținere'), 1),
  ((SELECT id FROM exercise WHERE name = 'Plank cu menținere'), 2),
  ((SELECT id FROM exercise WHERE name = 'Flexii genunchi în șezut'), 1),
  ((SELECT id FROM exercise WHERE name = 'Flexii genunchi în șezut'), 2),
  ((SELECT id FROM exercise WHERE name = 'Ridicări pe vârfuri'), 1),
  ((SELECT id FROM exercise WHERE name = 'Ridicări pe vârfuri'), 2);

  -- Acasă (id = 1)
INSERT INTO exercise_location (exercise_id, location_id) VALUES
  (3, 1),   -- Dumbbell Row
  (4, 1),   -- Dumbbell Biceps Curl
  (5, 1),   -- Triceps Dips
  (6, 1),   -- Overhead Press
  (20, 1),  -- Ridicări de bazin
  (21, 1),  -- Ridicări laterale cu gantere
  (28, 1),  -- Plank lateral
  (30, 1);  -- Mobilitate șold

-- Sală (id = 2)
INSERT INTO exercise_location (exercise_id, location_id) VALUES
  (3, 2),   -- Dumbbell Row (disponibil în ambele)
  (4, 2),   -- Dumbbell Biceps Curl (disponibil în ambele)
  (6, 2),   -- Overhead Press
  (22, 2),  -- Remedieri scapulare
  (24, 2),  -- Rotiri umeri cu gantere ușoare
  (26, 2),  -- Ridicări gambe pe treaptă
  (27, 2);  -- Izometric coapsă

-- Centru recuperare (id = 3)
INSERT INTO exercise_location (exercise_id, location_id) VALUES
  (9, 3),   -- Întinderi genunchi
  (10, 3),  -- Ridicări picior întins
  (11, 3),  -- Rotiri umăr cu bandă
  (12, 3),  -- Ridicări frontale
  (13, 3),  -- Extensii lombare
  (14, 3),  -- Ridicări în pronație
  (15, 3),  -- Rotiri trunchi
  (16, 3),  -- Genuflexiuni lente
  (17, 3);  -- Cercuri brațe

-- Terapie fizică (id = 5)
INSERT INTO exercise_location (exercise_id, location_id) VALUES
  (25, 5),  -- Întindere ischio dorsal
  (29, 5);  -- Întinderi tendon ahilean



--------------------------------------------------------------------------------
-- 10. Workout-uri (workout) și Workout Sessions (workout_session)
--------------------------------------------------------------------------------

-- 10.1. Workout generic pentru Ana (user_id = 1), fără afecțiuni → Full Body de Gym
INSERT INTO workout (name, user_id, duration_minutes, type_id, level_id, split_id, location_id, section) VALUES
  (
    'Full Body Basic',
    (SELECT id FROM users WHERE username = 'ana123'),
    30,
    (SELECT id FROM training_type WHERE name = 'Gym'),
    (SELECT id FROM training_level WHERE name = 'Începător'),
    (SELECT id FROM split_type WHERE name = 'Full Body'),
    (SELECT id FROM location WHERE name = 'Sală'),
    'gym'
  );

-- 10.2. Workout pentru Ionuț (user_id = 2), hernie de disc → Kinetoterapie – Recuperare Spate
INSERT INTO split_type (name) VALUES ('Recuperare Spate');
INSERT INTO section_split (section, split_id)
VALUES ('kinetoterapie', (SELECT id FROM split_type WHERE name = 'Recuperare Spate'));

INSERT INTO workout (name, user_id, duration_minutes, type_id, level_id, split_id, location_id, section) VALUES
  (
    'Recuperare Spate',
    (SELECT id FROM users WHERE username = 'ionut99'),
    20,
    (SELECT id FROM training_type WHERE name = 'Kinetoterapie'),
    (SELECT id FROM training_level WHERE name = 'Începător'),
    (SELECT id FROM split_type WHERE name = 'Recuperare Spate'),
    (SELECT id FROM location WHERE name = 'Centru recuperare'),
    'kinetoterapie'
  );

-- 10.3. Workout pentru Maria (user_id = 3), artroză genunchi → Fizioterapie – Mobilitate Genunchi
INSERT INTO split_type (name) VALUES ('Mobilitate Genunchi');
INSERT INTO section_split (section, split_id)
VALUES ('fizioterapie', (SELECT id FROM split_type WHERE name = 'Mobilitate Genunchi'));

INSERT INTO workout (name, user_id, duration_minutes, type_id, level_id, split_id, location_id, section) VALUES
  (
    'Mobilitate Genunchi',
    (SELECT id FROM users WHERE username = 'maria_fit'),
    25,
    (SELECT id FROM training_type WHERE name = 'Fizioterapie'),
    (SELECT id FROM training_level WHERE name = 'Începător'),
    (SELECT id FROM split_type WHERE name = 'Mobilitate Genunchi'),
    (SELECT id FROM location WHERE name = 'Terapie fizică'),
    'fizioterapie'
  );

-- 10.4. Workout pentru Vali (user_id = 4), durere lombară → Kinetoterapie – Intarire Spate
INSERT INTO split_type (name) VALUES ('Intarire Spate');
INSERT INTO section_split (section, split_id)
VALUES ('kinetoterapie', (SELECT id FROM split_type WHERE name = 'Intarire Spate'));

INSERT INTO workout (name, user_id, duration_minutes, type_id, level_id, split_id, location_id, section) VALUES
  (
    'Intarire Spate',
    (SELECT id FROM users WHERE username = 'vali_recuperare'),
    20,
    (SELECT id FROM training_type WHERE name = 'Kinetoterapie'),
    (SELECT id FROM training_level WHERE name = 'Începător'),
    (SELECT id FROM split_type WHERE name = 'Intarire Spate'),
    (SELECT id FROM location WHERE name = 'Spital'),
    'kinetoterapie'
  );

--------------------------------------------------------------------------------
-- 11. Workout Session (workout_session) – exemple de utilizare
--------------------------------------------------------------------------------

-- Ana (workout_id = ?, user_id = 1) – a terminat sesiunea în urmă cu 2 zile
INSERT INTO workout_session (workout_id, user_id, started_at, completed_at) VALUES
  (
    (SELECT id FROM workout WHERE name = 'Full Body Basic'),
    (SELECT id FROM users WHERE username = 'ana123'),
    NOW() - INTERVAL '2 days',
    NOW() - INTERVAL '2 days' + INTERVAL '30 minutes'
  );

-- Ionuț (workout_id = ?, user_id = 2) – în curs (start cu 1h în urmă)
INSERT INTO workout_session (workout_id, user_id, started_at) VALUES
  (
    (SELECT id FROM workout WHERE name = 'Recuperare Spate'),
    (SELECT id FROM users WHERE username = 'ionut99'),
    NOW() - INTERVAL '1 hour'
  );

-- Maria (workout_id = ?, user_id = 3) – 2 sesiuni finalizate
INSERT INTO workout_session (workout_id, user_id, started_at, completed_at) VALUES
  (
    (SELECT id FROM workout WHERE name = 'Mobilitate Genunchi'),
    (SELECT id FROM users WHERE username = 'maria_fit'),
    NOW() - INTERVAL '3 days',
    NOW() - INTERVAL '3 days' + INTERVAL '25 minutes'
  ),
  (
    (SELECT id FROM workout WHERE name = 'Mobilitate Genunchi'),
    (SELECT id FROM users WHERE username = 'maria_fit'),
    NOW() - INTERVAL '1 day',
    NOW() - INTERVAL '1 day' + INTERVAL '25 minutes'
  );

-- Vali (workout_id = ?, user_id = 4) – nu a început încă sesiunea
INSERT INTO workout_session (workout_id, user_id) VALUES
  (
    (SELECT id FROM workout WHERE name = 'Intarire Spate'),
    (SELECT id FROM users WHERE username = 'vali_recuperare')
  );

--------------------------------------------------------------------------------
-- END Script de populare
--------------------------------------------------------------------------------
