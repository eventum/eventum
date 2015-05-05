alter table {{%issue_history}}
    add context his_context mediumtext not null after his_summary;
