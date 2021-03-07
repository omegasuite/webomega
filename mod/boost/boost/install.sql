create table mod_boost_version (
  mod_title varchar(20) NOT NULL default '',
  version varchar(20) NOT NULL default '',
  update_link text,
  branch_allow smallint NOT NULL default '1',
  index (mod_title)				
  );
