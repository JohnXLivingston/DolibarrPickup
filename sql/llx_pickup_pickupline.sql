-- Copyright (C) 2021		Jonathan Dollé		<license@jonathandolle.fr>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.


CREATE TABLE llx_pickup_pickupline(
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	fk_pickup integer NOT NULL, 
	fk_product integer NOT NULL, 
	description text, 
	qty integer DEFAULT 1 NOT NULL, 
	tms timestamp NOT NULL, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	position integer NOT NULL,
	fk_stock_movement integer
) ENGINE=innodb;
