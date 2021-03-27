-- Copyright (C) ---Put here your own copyright and developer email---
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

ALTER TABLE llx_collecte_dolinputcat ADD INDEX idx_collecte_dolinputcat_rowid (rowid);
ALTER TABLE llx_collecte_dolinputcat ADD INDEX idx_collecte_dolinputcat_fk_category (fk_category);
ALTER TABLE llx_collecte_dolinputcat ADD CONSTRAINT llx_collecte_dolinputcat_fk_category FOREIGN KEY (fk_category) REFERENCES llx_categories(rowid);
ALTER TABLE llx_collecte_dolinputcat ADD INDEX idx_collecte_dolinputcat_active (active);
ALTER TABLE llx_collecte_dolinputcat ADD CONSTRAINT llx_collecte_dolinputcat_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);

ALTER TABLE llx_collecte_dolinputcat ADD UNIQUE INDEX uk_collecte_dolinputcat_fk_category (fk_category);
