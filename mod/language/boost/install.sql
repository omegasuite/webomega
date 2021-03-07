CREATE TABLE mod_dyn_modules (
  reg_id int NOT NULL,
  module_name varchar(20) NOT NULL default '',
  table_name varchar(50) NOT NULL default '',
  id_column varchar(50) NOT NULL default '',
  data_columns text NOT NULL,
  PRIMARY KEY  (reg_id)
  );

CREATE TABLE mod_language_settings (
  langActive smallint NOT NULL default '0',
  dynActive smallint NOT NULL default '0',
  ignoreDefault smallint NOT NULL default '0',
  mark smallint NOT NULL default '1',
  auto_up smallint NOT NULL default '1',
  default_language char(2) NOT NULL default 'en'
  );
