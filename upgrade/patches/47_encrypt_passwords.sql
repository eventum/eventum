# increase storage for password field
ALTER TABLE {{%email_account}} MODIFY ema_password VARCHAR(255) NOT NULL DEFAULT '';
