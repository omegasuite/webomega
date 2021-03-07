-- $Id: install.sql,v 1.32 2005/03/02 14:24:30 steven Exp $

CREATE TABLE cache (
  mod_title varchar(30) NOT NULL default '',
  id varchar(32) NOT NULL default '',
  data text NOT NULL,
  ttl int NOT NULL default '0',
  PRIMARY KEY (mod_title, id)
  );

CREATE TABLE modules (
  mod_title varchar(20) NOT NULL default '',
  mod_pname varchar(30) NOT NULL default '',
  mod_directory varchar(255) NOT NULL default '',
  mod_filename varchar(30) NOT NULL default '',
  admin_op text,
  user_op text,
  allow_view text,
  priority smallint(2) NOT NULL default '0',
  mod_icon varchar(255) default NULL,
  user_icon varchar(255) default NULL,
  user_mod tinyint(1) NOT NULL default '0',
  admin_mod tinyint(1) NOT NULL default '0',
  deity_mod tinyint(1) NOT NULL default '0',
  mod_class_files text,
  mod_sessions text,
  init_object text,
  active char(3) NOT NULL default '',
  index (mod_title)
  );
