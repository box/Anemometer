USE slow_query_log;

CREATE TEMPORARY TABLE `statements_temp` SELECT * FROM performance_schema.events_statements_summary_by_digest;

INSERT INTO events_statements (DIGEST, DIGEST_TEXT, first_seen, last_seen) SELECT DIGEST, DIGEST_TEXT, FIRST_SEEN, LAST_SEEN FROM statements_temp ON DUPLICATE KEY UPDATE first_seen=LEAST(VALUES(events_statements.first_seen), events_statements.first_seen), last_seen=GREATEST(VALUES(events_statements.last_seen),events_statements.last_seen);

SELECT CONCAT('INSERT IGNORE INTO events_statements_history (', GROUP_CONCAT(DISTINCT a.column_name),',hostname) SELECT ', GROUP_CONCAT(DISTINCT a.column_name),', @@hostname FROM statements_temp') INTO @stmt from information_schema.columns a JOIN information_schema.columns b ON a.column_name=b.column_name and b.table_name='events_statements_history'  where a.table_schema='performance_schema' and a.table_name='events_statements_summary_by_digest';
PREPARE stmt FROM @stmt;
EXECUTE stmt;

DROP TABLE IF EXISTS statements_temp;
TRUNCATE TABLE performance_schema.events_statements_summary_by_digest;
