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


-- We must add a 'ON DELETE SET NULL' on llx_pickup_batch_fk_pickupline constraint.
-- Unfortunately, to do this we must first drop, then recreate.
ALTER TABLE llx_pickup_batch DROP CONSTRAINT llx_pickup_batch_fk_pickupline;
ALTER TABLE llx_pickup_batch ADD CONSTRAINT llx_pickup_batch_fk_pickupline FOREIGN KEY (fk_pickupline) REFERENCES llx_pickup_pickupline(rowid) ON DELETE SET NULL;
