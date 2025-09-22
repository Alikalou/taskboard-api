CREATE TABLE IF NOT EXISTS tasks (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  title      TEXT    NOT NULL,
  status     TEXT    NOT NULL DEFAULT 'open',   -- e.g., open|in_progress|done
  created_at TEXT    NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%SZ','now')),
  updated_at TEXT    NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%SZ','now'))
);
--The above definition is for the attributes of the task table.
--Each task has an ID, title, status, creation date and last updated date.


-- auto-update updated_at on any change
CREATE TRIGGER IF NOT EXISTS tasks_updated_at
AFTER UPDATE ON tasks
FOR EACH ROW
BEGIN
  UPDATE tasks SET updated_at = strftime('%Y-%m-%dT%H:%M:%SZ','now') WHERE id = OLD.id;
END;

--Create a trigger when you run the script for the first time, if the trigger exist it will not create a new one.
--After any change on tasks, change the updated_at field to the time of chnage.
