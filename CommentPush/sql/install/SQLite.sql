CREATE TABLE IF NOT EXISTS "{prefix}comment_push" (
  "id" integer PRIMARY KEY AUTOINCREMENT,
  "service" text DEFAULT NULL,
  "object" text DEFAULT NULL,
  "context" text DEFAULT NULL,
  "result" text DEFAULT NULL,
  "error" text DEFAULT NULL,
  "time" integer DEFAULT NULL
);