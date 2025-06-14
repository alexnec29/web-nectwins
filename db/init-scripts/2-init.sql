-- generate workout
CREATE OR REPLACE FUNCTION get_exercises_filtered(
    p_user_id INT,
    p_groups TEXT[],
    p_level_id INT,
    p_duration INT,
    p_type_id INT,
    p_location_id INT
)
RETURNS TABLE (
    id INT,
    name TEXT,
    description TEXT,
    link TEXT,
    dificulty INT
) AS $$
DECLARE
    v_count INT;
BEGIN
  RETURN QUERY
  WITH filtered AS (
    SELECT DISTINCT
        e.id,
        e.name::TEXT,
        e.description::TEXT,
        e.link::TEXT,
        e.dificulty
    FROM exercise e
    JOIN exercise_muscle_group emg ON e.id = emg.exercise_id
    JOIN muscle_subgroup ms ON ms.id = emg.muscle_subgroup_id
    JOIN muscle_group mg ON mg.id = ms.principal_group
    JOIN exercise_section es ON es.exercise_id = e.id
    JOIN training_type tt ON tt.id = p_type_id AND LOWER(es.section) = LOWER(tt.name)
    JOIN exercise_location el ON el.exercise_id = e.id AND el.location_id = p_location_id
    WHERE mg.name = ANY(p_groups)
      AND (p_level_id IS NULL OR e.dificulty <= p_level_id)
      AND NOT EXISTS (
          SELECT 1
          FROM user_health_condition uhc
          JOIN exercise_health_condition ehc ON uhc.condition_id = ehc.condition_id
          WHERE uhc.user_id = p_user_id AND ehc.exercise_id = e.id
      )
  ),
  limited AS (
    SELECT * FROM filtered
    ORDER BY RANDOM()
    LIMIT GREATEST(p_duration / 10, 1)
  )
  SELECT * FROM limited;

  GET DIAGNOSTICS v_count = ROW_COUNT;
  IF v_count = 0 THEN
    RAISE EXCEPTION 'Nu s-au găsit exerciții pentru cerințele specificate.' USING ERRCODE = 'P2001';
  END IF;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE PROCEDURE save_generated_workout(
    p_name TEXT,
    p_duration INT,
    p_type_id INT,
    p_level_id INT,
    p_split_id INT,
    p_location_id INT,
    p_user_id INT,
    p_exercise_ids INT[],
    p_section TEXT
)
AS $$
DECLARE
  new_workout_id INT;
  i INT := 1;
BEGIN
  INSERT INTO workout(name, duration_minutes, type_id, level_id, split_id, location_id, user_id, section)
  VALUES (p_name, p_duration, p_type_id, p_level_id, p_split_id, p_location_id, p_user_id, p_section)
  RETURNING id INTO new_workout_id;

  FOREACH i IN ARRAY p_exercise_ids
  LOOP
    INSERT INTO workout_exercise(workout_id, exercise_id, order_in_workout, sets, reps)
    VALUES (new_workout_id, i, i, 3, 10);
  END LOOP;
END;
$$ LANGUAGE plpgsql;


--workouts list
CREATE OR REPLACE PROCEDURE start_workout_session(
    p_workout_id INT,
    p_user_id INT,
    OUT p_session_id INT
)
AS $$
BEGIN
    INSERT INTO workout_session (workout_id, user_id, started_at)
    VALUES (p_workout_id, p_user_id, NOW())
    RETURNING id INTO p_session_id;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION get_latest_session(p_user_id INT, p_workout_id INT)
RETURNS TABLE(id INT, started_at TIMESTAMP, completed_at TIMESTAMP)
AS $$
BEGIN
  RETURN QUERY
  SELECT s.id, s.started_at, s.completed_at
  FROM workout_session s
  WHERE s.user_id = p_user_id
    AND s.workout_id = p_workout_id
    AND s.completed_at IS NULL
  ORDER BY s.started_at DESC
  LIMIT 1;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE PROCEDURE complete_workout_session(p_session_id INT, p_user_id INT)
AS $$
BEGIN
    UPDATE workout_session
    SET completed_at = NOW()
    WHERE id = p_session_id AND user_id = p_user_id;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE PROCEDURE cancel_workout_session(p_session_id INT, p_user_id INT)
AS $$
BEGIN
    DELETE FROM workout_session
    WHERE id = p_session_id AND user_id = p_user_id;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION get_exercises_for_workout(p_workout_id INT)
RETURNS TABLE(
    name TEXT,
    description TEXT,
    link TEXT,
    sets INT,
    reps INT,
    order_in_workout INT
)
AS $$
BEGIN
  RETURN QUERY
  SELECT e.name::TEXT, e.description::TEXT, e.link::TEXT,
         we.sets, we.reps, we.order_in_workout
  FROM workout_exercise we
  JOIN exercise e ON e.id = we.exercise_id
  WHERE we.workout_id = p_workout_id
  ORDER BY we.order_in_workout;
END;
$$ LANGUAGE plpgsql;

-- statistics
CREATE OR REPLACE FUNCTION get_total_completed_workouts(p_user_id INT, p_section TEXT)
RETURNS INT AS $$
DECLARE
    v_count INT;
BEGIN
    SELECT COUNT(*) INTO v_count
    FROM workout_session ws
    JOIN workout w ON w.id = ws.workout_id
    WHERE ws.user_id = p_user_id AND ws.completed_at IS NOT NULL AND w.section = p_section;

    RETURN v_count;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION get_total_workout_duration(p_user_id INT, p_section TEXT)
RETURNS INT AS $$
DECLARE
    v_total_minutes INT;
BEGIN
    SELECT COALESCE(SUM(EXTRACT(EPOCH FROM (ws.completed_at - ws.started_at))/60), 0)::INT
    INTO v_total_minutes
    FROM workout_session ws
    JOIN workout w ON w.id = ws.workout_id
    WHERE ws.user_id = p_user_id
      AND ws.completed_at IS NOT NULL
      AND w.section = p_section;

    RETURN v_total_minutes;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION get_muscle_subgroup_stats(p_user_id INT, p_section TEXT)
RETURNS TABLE(name TEXT, cnt INT)
AS $$
BEGIN
  RETURN QUERY
  SELECT msg.name::TEXT, COUNT(DISTINCT ws.id)::INT
  FROM workout_session ws
  JOIN workout w ON w.id = ws.workout_id
  JOIN workout_exercise we ON we.workout_id = w.id
  JOIN exercise_muscle_group emg ON emg.exercise_id = we.exercise_id
  JOIN muscle_subgroup msg ON msg.id = emg.muscle_subgroup_id
  WHERE ws.user_id = p_user_id 
    AND ws.completed_at IS NOT NULL
    AND w.section = p_section
  GROUP BY msg.name
  ORDER BY cnt DESC;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION get_top_exercises(p_user_id INT, p_section TEXT, p_limit INT DEFAULT 6)
RETURNS TABLE(name TEXT, uses INT)
AS $$
BEGIN
  RETURN QUERY
  SELECT e.name::TEXT, COUNT(*)::INT AS uses
  FROM workout_session ws
  JOIN workout w ON w.id = ws.workout_id
  JOIN workout_exercise we ON we.workout_id = w.id
  JOIN exercise e ON e.id = we.exercise_id
  WHERE ws.user_id = p_user_id AND ws.completed_at IS NOT NULL AND w.section = p_section
  GROUP BY e.name
  ORDER BY uses DESC
  LIMIT p_limit;
END;
$$ LANGUAGE plpgsql;

-- leaderboard
CREATE OR REPLACE FUNCTION get_leaderboard_data(p_section TEXT)
RETURNS TABLE (
    user_id     INT,
    username    TEXT,
    nume        TEXT,
    varsta      INT,
    sesiuni     INT,
    durata      INT,
    nivel       TEXT
)
AS $$
BEGIN
  RETURN QUERY
  SELECT 
      u.id::INT,
      u.username::TEXT,
      u.nume::TEXT,
      u.varsta::INT,
      COUNT(ws.id) FILTER (WHERE ws.completed_at IS NOT NULL)::INT AS sesiuni,
      COALESCE(SUM(EXTRACT(EPOCH FROM (ws.completed_at - ws.started_at))/60), 0)::INT AS durata,
      MAX(tl.name)::TEXT AS nivel
  FROM users u
  LEFT JOIN workout_session ws ON ws.user_id = u.id
  LEFT JOIN workout w ON w.id = ws.workout_id AND w.section = p_section
  LEFT JOIN training_level tl ON tl.id = w.level_id
  GROUP BY u.id;
END;
$$ LANGUAGE plpgsql;

-- 1. Trigger pentru incrementare completări workout
CREATE OR REPLACE FUNCTION trg_increment_workout_completion()
RETURNS TRIGGER AS $$
BEGIN
  IF NEW.completed_at IS NOT NULL AND OLD.completed_at IS NULL THEN
    UPDATE workout
    SET completed_count = completed_count + 1
    WHERE id = NEW.workout_id;
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_workout_session_completed
AFTER UPDATE ON workout_session
FOR EACH ROW
WHEN (NEW.completed_at IS NOT NULL AND OLD.completed_at IS NULL)
EXECUTE FUNCTION trg_increment_workout_completion();


-- 2. Trigger pentru autocompletare started_at dacă NULL
CREATE OR REPLACE FUNCTION trg_autofill_started_at()
RETURNS TRIGGER AS $$
BEGIN
  IF NEW.started_at IS NULL THEN
    NEW.started_at := CURRENT_TIMESTAMP;
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_set_started_at
BEFORE INSERT ON workout_session
FOR EACH ROW
EXECUTE FUNCTION trg_autofill_started_at();

----------------------------------------ADMIN----------------------------------------
CREATE OR REPLACE PROCEDURE add_exercise(
    p_name TEXT,
    p_description TEXT,
    p_link TEXT,
    p_difficulty INT,
    p_type_id INT,
    p_is_bodyweight BOOLEAN,
    p_equipment_needed BOOLEAN,
    p_subgroup_ids INT[],
    p_location_ids INT[],
    p_sections TEXT[]
)
AS $$
DECLARE
    new_id INT;
    sub_id INT;
    loc_id INT;
    sec TEXT;
BEGIN
    INSERT INTO exercise(name, description, link, dificulty, type_id, is_bodyweight, equipment_needed)
    VALUES (p_name, p_description, p_link, p_difficulty, p_type_id, p_is_bodyweight, p_equipment_needed)
    RETURNING id INTO new_id;

    FOREACH sub_id IN ARRAY p_subgroup_ids
    LOOP
        INSERT INTO exercise_muscle_group(exercise_id, muscle_subgroup_id)
        VALUES (new_id, sub_id);
    END LOOP;

    FOREACH loc_id IN ARRAY p_location_ids
    LOOP
        INSERT INTO exercise_location(exercise_id, location_id)
        VALUES (new_id, loc_id);
    END LOOP;

    FOREACH sec IN ARRAY p_sections
    LOOP
        INSERT INTO exercise_section(exercise_id, section)
        VALUES (new_id, sec);
    END LOOP;
END;
$$ LANGUAGE plpgsql;