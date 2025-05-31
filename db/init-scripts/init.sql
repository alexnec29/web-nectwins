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