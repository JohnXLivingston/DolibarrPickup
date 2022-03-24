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

ALTER TABLE llx_pickup_pickup ADD INDEX idx_pickup_pickup_rowid (rowid);
-- NB: Before version 1.1.1, the index idx_pickup_pickup_ref was not unique. Dropping it to replace by idx_pickup_pickup_ref_unique.
ALTER TABLE llx_pickup_pickup DROP INDEX IF EXISTS idx_pickup_pickup_ref;
ALTER TABLE llx_pickup_pickup ADD UNIQUE INDEX idx_pickup_pickup_ref_unique (ref);
ALTER TABLE llx_pickup_pickup ADD INDEX idx_pickup_pickup_fk_soc (fk_soc);
ALTER TABLE llx_pickup_pickup ADD INDEX idx_pickup_pickup_date_pickup (date_pickup);
ALTER TABLE llx_pickup_pickup ADD CONSTRAINT llx_pickup_pickup_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_pickup_pickup ADD CONSTRAINT llx_pickup_pickup_fk_user_modif FOREIGN KEY (fk_user_modif) REFERENCES llx_user(rowid);
ALTER TABLE llx_pickup_pickup ADD INDEX idx_pickup_pickup_status (status);
ALTER TABLE llx_pickup_pickup ADD CONSTRAINT llx_pickup_pickup_fk_pickup_type FOREIGN KEY (fk_pickup_type) REFERENCES llx_c_pickup_type(rowid);
