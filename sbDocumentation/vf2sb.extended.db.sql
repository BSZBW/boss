-- Additional/modified db structures
-- to VuFind2 database for Swissbib specific features
-- ------------------------------------------------------
ALTER TABLE `user` ADD `favorite_institutions` TEXT NOT NULL;
ALTER TABLE `user` ADD `language` VARCHAR( 2 ) NOT NULL DEFAULT '';
ALTER TABLE `user` ADD `max_hits` SMALLINT NOT NULL DEFAULT '0';
ALTER TABLE `user` ADD `default_sort` VARCHAR(255) NOT NULL DEFAULT '';
alter table user modify username VARCHAR(200);

#You have to do it by yourself because it's part of VuFind DBMS schema (no swissbib extension)
#ALTER TABLE `user` ADD `verify_hash` varchar(42) NOT NULL DEFAULT '';
#`sb_nickname` text NOT NULL, seems to be a column created in the beginnings of our development
#delete it with
#ALTER TABLE `user` DROP COLUMN `sb_nickname`;


