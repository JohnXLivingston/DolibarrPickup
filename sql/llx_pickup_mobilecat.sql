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
-- along with this program.  If not, see http://www.gnu.org/licenses/.


CREATE TABLE llx_pickup_mobilecat(
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	fk_category integer NOT NULL,
	active boolean DEFAULT 1 NOT NULL,
	batch_constraint varchar(20),
	form varchar(255),
	date_creation datetime NOT NULL,
	tms timestamp,
	fk_user_creat integer NOT NULL,
	fk_user_modif integer,
	import_key varchar(14),
	notes text
) ENGINE=innodb;
