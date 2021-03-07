CREATE TABLE mod_layout_box (
  id int NOT NULL default '0',
  theme varchar(30) NOT NULL default '',
  mod_title varchar(50) NOT NULL default '',
  content_var varchar(50) default NULL,
  theme_var varchar(50) NOT NULL default '',
  box_file varchar(100) default NULL,
  popbox varchar(100) default NULL,
  box_order int NOT NULL default '0',
  home_only smallint NOT NULL default '0',
  PRIMARY KEY  (id)
);

CREATE TABLE mod_layout_config (
  default_theme varchar(50) NOT NULL default '',
  userAllow smallint NOT NULL default '1',
  page_title varchar(255) default NULL,
  meta_keywords text,
  meta_description varchar(180) default NULL,
  meta_robots char(2) default NULL,
  meta_owner varchar(40) default NULL,
  meta_author varchar(40) default NULL,
  meta_content varchar(40) NOT NULL default ''
);

INSERT INTO mod_layout_config VALUES ('Default', 1, 'phpWebSite', 'phpwebsite', '', '11', '', '', 'UTF-8');

    
