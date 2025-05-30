-- Insert muscle groups
INSERT INTO
   muscle_group (name)
VALUES
   ('Chest'),
   ('Back'),
   ('Legs'),
   ('Shoulders'),
   ('Arms'),
   ('Core');

-- Insert exercises
INSERT INTO
   exercise (
      name,
      description,
      is_bodyweight,
      equipment_needed,
      link
   )
VALUES
   (
      'Push-up',
      'Bodyweight chest exercise',
      TRUE,
      false,
      'https://youtu.be/_l3ySVKYVJ8'
   ),
   (
      'Pull-up',
      'Upper body pulling exercise',
      TRUE,
      false,
      'https://youtu.be/eGo4IYlbE5g'
   ),
   (
      'Squat',
      'Lower body strength exercise',
      TRUE,
      false,
      'https://youtu.be/aclHkVaku9U'
   ),
   (
      'Dumbbell Shoulder Press',
      'Shoulder exercise with dumbbells',
      false,
      TRUE,
      'https://youtu.be/B-aVuyhvLHU'
   ),
   (
      'Plank',
      'Core stabilization exercise',
      TRUE,
      false,
      'https://youtu.be/pSHjTRCQxIw'
   ),
   (
      'Barbell Curl',
      'Bicep isolation exercise',
      false,
      TRUE,
      'https://youtu.be/kwG2ipFRgfo'
   );

-- Link exercises to muscle groups
INSERT INTO
   exercise_muscle_group (exercise_id, muscle_group_id)
VALUES
   (1, 1),
   -- Push-up → Chest
   (2, 2),
   -- Pull-up → Back
   (3, 3),
   -- Squat → Legs
   (4, 4),
   -- Shoulder Press → Shoulders
   (5, 6),
   -- Plank → Core
   (6, 5);

-- Curl → Arms
-- Insert training types
INSERT INTO
   training_type (name)
VALUES
   ('Strength'),
   ('Hypertrophy'),
   ('Endurance'),
   ('Cardio');

-- Insert training levels
INSERT INTO
   training_level (name)
VALUES
   ('Beginner'),
   ('Intermediate'),
   ('Advanced');

-- Insert split types
INSERT INTO
   split_type (name)
VALUES
   ('Full Body'),
   ('Upper/Lower'),
   ('Push/Pull/Legs'),
   ('Bro Split');

-- Insert locations
INSERT INTO
   location (name)
VALUES
   ('Home'),
   ('Gym'),
   ('Outdoor');

-- Insert workouts
INSERT INTO
   workout (
      name,
      duration_minutes,
      type_id,
      level_id,
      split_id,
      location_id
   )
VALUES
   ('Beginner Full Body', 45, 1, 1, 1, 1),
   ('Upper Body Strength', 60, 1, 2, 2, 2),
   ('Core Crusher', 30, 3, 1, 1, 1);

-- Link workouts to exercises
INSERT INTO
   workout_exercise (
      workout_id,
      exercise_id,
      order_in_workout,
      sets,
      reps
   )
VALUES
   (1, 1, 1, 3, 12),
   -- Push-up
   (1, 3, 2, 3, 15),
   -- Squat
   (1, 5, 3, 3, 30),
   -- Plank
   (2, 2, 1, 4, 8),
   -- Pull-up
   (2, 4, 2, 4, 10),
   -- Dumbbell Shoulder Press
   (2, 6, 3, 4, 12),
   -- Barbell Curl
   (3, 5, 1, 3, 60);

-- Plank for time