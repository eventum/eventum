alter table {{%issue_history}}
    add context text not null default '' after his_summary;
