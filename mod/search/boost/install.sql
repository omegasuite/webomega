CREATE TABLE mod_search (
  id int NOT NULL, 
  module varchar(255) NOT NULL,
  show_block int NOT NULL default '0',
  block_title varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
);
