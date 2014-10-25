update {{%issue_history}} set his_summary = replace(his_summary, 'Issue associated to #', 'Issue associated to Issue #') where his_summary like 'Issue associated to #%';
update {{%issue_history}} set his_summary = replace(his_summary, 'Issue association #', ' Issue association to Issue #') where his_summary like 'Issue association #%';
