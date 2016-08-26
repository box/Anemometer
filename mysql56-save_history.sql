USE slow_query_log;

CREATE TEMPORARY TABLE `slow_query_log`.`statements_temp`
SELECT * FROM performance_schema.events_statements_summary_by_digest;

INSERT INTO `slow_query_log`.`events_statements` (DIGEST, DIGEST_TEXT, first_seen, last_seen)
SELECT DIGEST, DIGEST_TEXT, FIRST_SEEN, LAST_SEEN
FROM `slow_query_log`.`statements_temp`
  ON DUPLICATE KEY UPDATE
    first_seen = LEAST(VALUES(`slow_query_log`.`events_statements`.first_seen),
                              `slow_query_log`.`events_statements`.first_seen),
    last_seen = GREATEST(VALUES(`slow_query_log`.`events_statements`.last_seen),
                                `slow_query_log`.`events_statements`.last_seen);

SELECT CONCAT('INSERT IGNORE INTO events_statements_history (',
              GROUP_CONCAT(DISTINCT a.column_name),',hostname) SELECT ',
              GROUP_CONCAT(DISTINCT a.column_name),', @@hostname FROM statements_temp')
              INTO @stmt
FROM information_schema.columns a
  JOIN information_schema.columns b
    ON a.column_name=b.column_name AND b.table_name='events_statements_history'
WHERE a.table_schema='performance_schema' AND a.table_name='events_statements_summary_by_digest';

PREPARE stmt FROM @stmt;

EXECUTE stmt;

DROP TABLE IF EXISTS statements_temp;
TRUNCATE TABLE performance_schema.events_statements_summary_by_digest;
