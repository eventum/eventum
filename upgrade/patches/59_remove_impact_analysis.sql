DROP TABLE {{%issue_requirement}};
ALTER TABLE {{%issue}} DROP COLUMN iss_impact_analysis;
DELETE FROM {{%history_type}} WHERE htt_name IN('impact_analysis_added', 'impact_analysis_updated', 'impact_analysis_removed');