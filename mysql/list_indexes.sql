ALTER TABLE lists MODIFY user_id int(11) NOT NULL;

ALTER TABLE lists MODIFY vote int(11) DEFAULT NULL;

ALTER TABLE lists MODIFY share BOOLEAN DEFAULT 0;

CREATE INDEX `idx_user_id` ON lists (`user_id`);

ALTER TABLE listids MODIFY listid int(11) DEFAULT NULL;

ALTER TABLE listids MODIFY listitemid int(11) DEFAULT NULL;

CREATE INDEX `idx_listid` ON listids (`listid`);

