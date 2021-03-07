CREATE TABLE mod_help (
  help_id int NOT NULL,
  label text,
  content text,
  active smallint NOT NULL default '0',
  label_name_id varchar(30),
  reg_id int NOT NULL default '0',
  PRIMARY KEY  (help_id)
);

CREATE TABLE mod_help_reg (
  reg_id int NOT NULL,
  mod_name varchar(20) NOT NULL default '',
  active smallint NOT NULL default '1',
  PRIMARY KEY  (reg_id)
);
