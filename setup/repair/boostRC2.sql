CREATE TABLE mod_boost_version (
  mod_title varchar(20) binary NOT NULL default '',
  version float NOT NULL default '0',
  update_link text,
  branch_allow tinyint NOT NULL default '1',
  KEY mod_title (mod_title)
);