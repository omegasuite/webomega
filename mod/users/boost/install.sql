CREATE TABLE mod_user_groups (
  group_id int  NOT NULL default '0',
  group_name varchar(30) default NULL,
  description text,
  members text,
  PRIMARY KEY  (group_id)
  );

CREATE TABLE mod_user_groupvar (
  group_id int  NOT NULL default '0',
  module_title varchar(20) NOT NULL default '',
  varName varchar(30) NOT NULL default '',
  varValue text,
  index (group_id)
  );

CREATE TABLE mod_user_settings (
  allow_cookies smallint NOT NULL default '0',
  timelimit int NOT NULL default '30',
  secure smallint NOT NULL default '0',
  user_signup varchar(6) default NULL,
  max_log_attempts int NOT NULL default '10',
  nu_subj varchar(255) default NULL,
  greeting text,
  user_contact varchar(255) default NULL,
  user_authentication text,
  external_auth_file text,
  show_login smallint NOT NULL default '1',
  show_remember_me smallint NOT NULL default '1',
  welcomeURL text
  );

INSERT INTO mod_user_settings VALUES (1, 10, 0, 'login', 10, 'Welcome to LawClerksonline', 'Welcome to LawClerksonline.com the world leading legal research site.\r\n', 'admin@lawclerksonline.com', 'local', 'external_authorization.php', 1, 1, 'index.php?module=phpwscontacts&CONTACTS_MAN_OP=edit');


CREATE TABLE mod_user_uservar (
  user_id int  NOT NULL default '0',
  module_title varchar(20) NOT NULL default '',
  varName varchar(30) NOT NULL default '',
  varValue text,
  index (user_id)
  );


CREATE TABLE mod_users (
  user_id int(11) NOT NULL default '0',
  username varchar(20) NOT NULL default '',
  password varchar(32) NOT NULL default '',
  email varchar(50) NULL default NULL,
  admin_switch smallint NOT NULL default '0',
  groups text NULL default NULL,
  deity smallint NOT NULL default '0',
  log_sess int  NOT NULL default '0',
  last_on int  NOT NULL default '0',
  cookie varchar(32),
  activatecode int(11)  NOT NULL default '0',
  name_prefix varchar(6) NULL default NULL,
  last_name varchar(20) NULL default NULL,
  first_name varchar(20) NULL default NULL,
  MI char(1) NULL default NULL,
  title varchar(20) NULL default NULL,
  SBN int(11)  NOT NULL default '0',
  SBN_state varchar(20) NULL default NULL,
  company varchar(20) NULL default NULL,
  address varchar(80) NULL default NULL,
  city varchar(20) NULL default NULL,
  state varchar(20) NULL default NULL,
  zip int(11) NOT NULL default '0',
  phone varchar(12) NULL default NULL,
  ext varchar(5) NULL default NULL,
  fax varchar(12) NULL default NULL,
  name_suffix varchar(6) NULL default NULL,
  PRIMARY KEY  (user_id)
  );
