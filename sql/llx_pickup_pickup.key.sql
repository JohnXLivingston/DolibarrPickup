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
-- along with this program.  If not, see http://www.gnu.org/licenses/.

ALTER TABLE llx_pickup_pickup ADD INDEX idx_pickup_pickup_rowid (rowid);
ALTER TABLE llx_pickup_pickup ADD INDEX idx_pickup_pickup_ref (ref);
ALTER TABLE llx_pickup_pickup ADD INDEX idx_pickup_pickup_fk_soc (fk_soc);
ALTER TABLE llx_pickup_pickup ADD INDEX idx_pickup_pickup_date_pickup (date_pickup);
ALTER TABLE llx_pickup_pickup ADD CONSTRAINT llx_pickup_pickup_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_pickup_pickup ADD CONSTRAINT llx_pickup_pickup_fk_user_modif FOREIGN KEY (fk_user_modif) REFERENCES llx_user(rowid);
ALTER TABLE llx_pickup_pickup ADD INDEX idx_pickup_pickup_status (status);

