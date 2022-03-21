-- Copyright (C) 2021-2022		John Livingston		<license@john-livingston.fr>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU Affero General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU Affero General Public License for more details.
--
-- You should have received a copy of the GNU Affero General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.


CREATE TABLE llx_pickup_pickupline(
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	fk_pickup integer NOT NULL,
	fk_product integer NOT NULL,
	description text,
	weight double(24,8) DEFAULT NULL,
	weight_units tinyint DEFAULT NULL,
	length double(24,8) DEFAULT NULL,
	length_units tinyint DEFAULT NULL,
	surface double(24,8) DEFAULT NULL,
	surface_units tinyint DEFAULT NULL,
	volume double(24,8) DEFAULT NULL,
	volume_units tinyint DEFAULT NULL,
	deee tinyint DEFAULT NULL,
	-- NB: deee_type should be a tinyint, but it seems it is a varchar on llx_product_extrafields
	deee_type varchar(255) DEFAULT NULL,
	qty integer DEFAULT 1 NOT NULL,
	batch varchar(128) DEFAULT NULL,
	tms timestamp NOT NULL, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	position integer NOT NULL,
	fk_stock_movement integer
) ENGINE=innodb;
