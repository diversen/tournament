ALTER TABLE tournaments MODIFY latereg int(11) DEFAULT 0;

CREATE INDEX `latereg_idx` ON tournaments (latereg);