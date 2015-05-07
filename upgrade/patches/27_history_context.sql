alter table {{%issue_history}}
    add his_context mediumtext not null after his_summary;
