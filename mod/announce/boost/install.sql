CREATE TABLE mod_announce (
  id int PRIMARY KEY NOT NULL,
  org int NOT NULL,
  subject text NOT NULL,
  summary text,
  body text,
  image text,
  hits int NOT NULL DEFAULT '0',
  approved smallint NOT NULL DEFAULT '0',
  active smallint NOT NULL DEFAULT '1',
  comments smallint NOT NULL DEFAULT '0',
  anonymous smallint NOT NULL DEFAULT '0',
  userCreated varchar(20) NOT NULL,
  userUpdated varchar(20) NOT NULL,
  dateCreated datetime NOT NULL,
  dateUpdated datetime NOT NULL,
  poston datetime NOT NULL,
  expiration datetime NOT NULL,
  sticky_id int NOT NULL DEFAULT '0'
);

CREATE TABLE mod_announce_settings (
  numHome int NOT NULL DEFAULT '20',
  numPast int NOT NULL DEFAULT '10',
  showCurrent smallint NOT NULL DEFAULT '1',
  showPast smallint NOT NULL DEFAULT '1'
);

INSERT INTO mod_announce_settings VALUES ('20', '10', '1', '1');
