ALTER TABLE tournaments MODIFY begin time DEFAULT NULL;

ALTER TABLE tournaments MODIFY day char(3) DEFAULT NULL;

ALTER TABLE tournaments MODIFY limittype char(2) DEFAULT NULL;

ALTER TABLE tournaments MODIFY gametype char(64) DEFAULT NULL;

ALTER TABLE tournaments MODIFY site char(64) DEFAULT NULL;

ALTER TABLE tournaments MODIFY turbo BOOLEAN DEFAULT 0;

ALTER TABLE tournaments MODIFY superturbo BOOLEAN DEFAULT 0;

ALTER TABLE tournaments MODIFY headsup BOOLEAN DEFAULT 0;

ALTER TABLE tournaments MODIFY 4max BOOLEAN DEFAULT 0;

ALTER TABLE tournaments MODIFY 6max BOOLEAN DEFAULT 0;

ALTER TABLE tournaments MODIFY timecapped BOOLEAN DEFAULT 0;

ALTER TABLE tournaments MODIFY breakthru BOOLEAN DEFAULT 0;

ALTER TABLE tournaments MODIFY wta BOOLEAN DEFAULT 0;

ALTER TABLE tournaments MODIFY 2chance BOOLEAN DEFAULT 0;

ALTER TABLE tournaments MODIFY 3chance BOOLEAN DEFAULT 0;

ALTER TABLE tournaments MODIFY 4chance BOOLEAN DEFAULT 0;

ALTER TABLE tournaments MODIFY don BOOLEAN DEFAULT 0;

ALTER TABLE tournaments MODIFY ton BOOLEAN DEFAULT 0;

ALTER TABLE tournaments MODIFY shootout BOOLEAN DEFAULT 0;

ALTER TABLE tournaments MODIFY deepstack BOOLEAN DEFAULT 0;

ALTER TABLE tournaments MODIFY escalator BOOLEAN DEFAULT 0;

ALTER TABLE tournaments MODIFY capped BOOLEAN DEFAULT 0;

ALTER TABLE tournaments MODIFY knockout BOOLEAN DEFAULT 0;

ALTER TABLE tournaments MODIFY flipout BOOLEAN DEFAULT 0;

ALTER TABLE tournaments MODIFY fastfold BOOLEAN DEFAULT 0;

ALTER TABLE tournaments MODIFY reentry BOOLEAN DEFAULT 0;

ALTER TABLE tournaments MODIFY multientry BOOLEAN DEFAULT 0;

ALTER TABLE tournaments MODIFY cubed BOOLEAN DEFAULT 0;

ALTER TABLE tournaments MODIFY rebuy BOOLEAN DEFAULT 0;

ALTER TABLE tournaments MODIFY freezeout BOOLEAN DEFAULT 0;

CREATE INDEX `begin_idx` ON tournaments (begin);

CREATE INDEX `day_idx` ON tournaments (day);

CREATE INDEX `limit_idx` ON tournaments (limittype);

CREATE INDEX `game_idx` ON tournaments (gametype);

CREATE INDEX `pricepool_idx` ON tournaments (pricepool);

CREATE INDEX `buyin_idx` ON tournaments (buyin);

CREATE INDEX `site_idx` ON tournaments (site);