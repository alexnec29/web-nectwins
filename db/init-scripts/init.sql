-- generate workout
-- 1. Funcție pentru extragerea exercițiilor
CREATE OR REPLACE FUNCTION get_exercises_by_groups(p_groups TEXT[])
RETURNS TABLE(id INT, name TEXT, description TEXT, link TEXT)
AS $$
BEGIN
  RETURN QUERY
    SELECT DISTINCT 
      e.id::INT,
      e.name::TEXT,
      e.description::TEXT,
      e.link::TEXT
    FROM exercise e
    JOIN exercise_muscle_group emg ON e.id = emg.exercise_id
    JOIN muscle_subgroup ms ON ms.id = emg.muscle_subgroup_id
    JOIN muscle_group mg ON mg.id = ms.principal_group
    WHERE mg.name = ANY(p_groups)
    LIMIT 6;
END;
$$ LANGUAGE plpgsql;

-- 2. Procedură pentru salvare
CREATE OR REPLACE PROCEDURE save_generated_workout(
    p_name TEXT,
    p_duration INT,
    p_type_id INT,
    p_level_id INT,
    p_split_id INT,
    p_location_id INT,
    p_user_id INT,
    p_exercise_ids INT[]
)
AS $$
DECLARE
  new_workout_id INT;
  i INT := 1;
BEGIN
  INSERT INTO workout(name, duration_minutes, type_id, level_id, split_id, location_id, user_id)
  VALUES (p_name, p_duration, p_type_id, p_level_id, p_split_id, p_location_id, p_user_id)
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
CREATE OR REPLACE FUNCTION get_total_completed_workouts(p_user_id INT)
RETURNS INT AS $$
DECLARE
    v_count INT;
BEGIN
    SELECT COUNT(*) INTO v_count
    FROM workout_session
    WHERE user_id = p_user_id AND completed_at IS NOT NULL;

    RETURN v_count;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION get_total_workout_duration(p_user_id INT)
RETURNS INT AS $$
DECLARE
    v_minutes INT;
BEGIN
    SELECT COALESCE(SUM(EXTRACT(EPOCH FROM completed_at - started_at) / 60), 0)
    INTO v_minutes
    FROM workout_session
    WHERE user_id = p_user_id AND completed_at IS NOT NULL;

    RETURN ROUND(v_minutes);
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION get_muscle_subgroup_stats(p_user_id INT)
RETURNS TABLE(name TEXT, cnt INT)
AS $$
BEGIN
  RETURN QUERY
  SELECT msg.name::TEXT, COUNT(DISTINCT ws.id)::INT
  FROM workout_session ws
  JOIN workout_exercise we ON we.workout_id = ws.workout_id
  JOIN exercise_muscle_group emg ON emg.exercise_id = we.exercise_id
  JOIN muscle_subgroup msg ON msg.id = emg.muscle_subgroup_id
  WHERE ws.user_id = p_user_id AND ws.completed_at IS NOT NULL
  GROUP BY msg.name
  ORDER BY cnt DESC;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION get_top_exercises(p_user_id INT, p_limit INT DEFAULT 5)
RETURNS TABLE(name TEXT, uses INT)
AS $$
BEGIN
  RETURN QUERY
  SELECT e.name::TEXT, COUNT(*)::INT AS uses
  FROM workout_session ws
  JOIN workout_exercise we ON we.workout_id = ws.workout_id
  JOIN exercise e ON e.id = we.exercise_id
  WHERE ws.user_id = p_user_id AND ws.completed_at IS NOT NULL
  GROUP BY e.name
  ORDER BY uses DESC
  LIMIT p_limit;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION get_training_type_stats(p_user_id INT)
RETURNS TABLE(name TEXT, cnt INT)
AS $$
BEGIN
  RETURN QUERY
  SELECT tt.name::TEXT, COUNT(DISTINCT ws.id)::INT
  FROM workout_session ws
  JOIN workout w ON ws.workout_id = w.id
  JOIN training_type tt ON tt.id = w.type_id
  WHERE ws.user_id = p_user_id AND ws.completed_at IS NOT NULL
  GROUP BY tt.name
  ORDER BY cnt DESC;
END;
$$ LANGUAGE plpgsql;