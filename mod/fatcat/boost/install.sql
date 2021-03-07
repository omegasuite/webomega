CREATE TABLE mod_fatcat_categories (
  cat_id int NOT NULL default '0',
  title varchar(60) default NULL,
  description text,
  template varchar(40) default NULL,
  image text,
  icon text,
  parent int NOT NULL default '0',
  children text,
  PRIMARY KEY  (cat_id)
  );

CREATE TABLE mod_fatcat_elements (
  element_id int NOT NULL default '0',
  cat_id int NOT NULL default '0',
  title varchar(80) default NULL,
  link text,
  module_id int NOT NULL default '0',
  module_title varchar(60) default NULL,
  href varchar(4) NOT NULL default 'home',
  rating int NOT NULL default '50',
  active smallint NOT NULL default '0',
  groups text,
  created int NOT NULL default '0',
  PRIMARY KEY  (element_id)
);

CREATE TABLE mod_fatcat_settings (
  relatedtext text NOT NULL,
  multipleGroup smallint NOT NULL default '1',
  defaultIcon varchar(40) default NULL,
  relatedLimit smallint NOT NULL default '0',
  relatedEnabled smallint NOT NULL default '1'
);

INSERT INTO mod_fatcat_settings VALUES ('These might interest you as well', 1, '', 5);
