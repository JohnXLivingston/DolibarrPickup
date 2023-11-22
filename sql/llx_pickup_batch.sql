-- Copyright (C) 2023		John Livingston		<license@john-livingston.fr>
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



-- This table is used to generate batch/serial numbers, and keep track of it.

CREATE TABLE llx_pickup_batch(
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	fk_product integer NOT NULL,
  -- the pickupline attached to this batch number (could be null when the number was not generated from a pickup).
	-- fk_pickupline == NULL can be used when generating a non-unique batch_number per product.
	-- fk_pickupline can also be null when the pickupline was deleted (constraint 'ON DELETE SET NULL').
	fk_pickupline integer DEFAULT NULL,
	batch_number varchar(30) NOT NULL,
	tms timestamp NOT NULL, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer
) ENGINE=innodb;
