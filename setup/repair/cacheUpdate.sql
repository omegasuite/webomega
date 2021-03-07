CREATE TABLE cache (
  mod_title varchar(30) NOT NULL default '',
  id varchar(32) NOT NULL default '',
  data text NOT NULL,
  ttl int unsigned NOT NULL default '0',
  KEY mod_title (mod_title,id)
  );
