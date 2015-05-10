--
-- while we add only his_context column,
-- we do alter for other fields as well to have non-text fields beginning of the table
-- as we are doing full table rebuild anyway with this alter
--

alter table eventum_issue_history
    add his_context mediumtext not null after his_summary,
    modify `his_htt_id` tinyint(2) NOT NULL DEFAULT '0' after `his_usr_id`,
    modify `his_is_hidden` tinyint(1) NOT NULL DEFAULT '0' after his_htt_id
;
