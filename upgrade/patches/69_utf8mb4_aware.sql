# make these columns use different encoding to fit future utf8mb4 encoding:
# - faq.faq_title
# - news.nws_title
# - user.usr_email
# - user_alias.ual_email

ALTER TABLE {{%faq}}
	modify `faq_title` varchar(255) CHARACTER SET utf8 NOT NULL;

ALTER TABLE {{%news}}
	modify `nws_title` varchar(255) CHARACTER SET utf8 NOT NULL;

ALTER TABLE {{%user}}
	modify `usr_email` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT '';

ALTER TABLE {{%user_alias}}
	modify `ual_email` varchar(255) CHARACTER SET latin1 DEFAULT NULL;
