/*
 * Delete issue association to issue itself
 */
delete from {{%issue_association}} where isa_issue_id=isa_associated_id;
