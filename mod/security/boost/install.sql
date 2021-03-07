CREATE TABLE mod_security_log (
  log_id int PRIMARY KEY,
  timestamp timestamp NOT NULL,
  ip_address varchar(15) default NULL,
  sec_mod_name varchar(20) default NULL,
  offense text,
  sec_user_id int  NOT NULL default '0'
);

CREATE TABLE mod_security_ipinfo (
  ban_allow_id int PRIMARY KEY,
  ipaddress text NOT NULL,
  timestamp timestamp NOT NULL,
  allow smallint NOT NULL default '0'
);

CREATE TABLE mod_security_errorpage (
  page_id int(11) PRIMARY KEY,
  error int NOT NULL default '0',
  label text NOT NULL,
  content text NOT NULL
);

CREATE TABLE mod_security_settings (
  name varchar(80) NOT NULL default '',
  data text NOT NULL
  );

INSERT INTO mod_security_settings VALUES ('access_default', '1');
INSERT INTO mod_security_settings VALUES ('htaccess_extra', 'php_flag display_errors Off');
