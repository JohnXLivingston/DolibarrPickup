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

-- NB : je met ça là... module builder bugué... il oublie le llx_ devant les REFERENCES
ALTER TABLE llx_collecte_collecte ADD CONSTRAINT llx_collecte_collecte_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);

-- BEGIN MODULEBUILDER INDEXES
ALTER TABLE llx_collecte_collecte ADD INDEX idx_collecte_collecte_rowid (rowid);
ALTER TABLE llx_collecte_collecte ADD INDEX idx_collecte_collecte_ref (ref);
ALTER TABLE llx_collecte_collecte ADD INDEX idx_collecte_collecte_fk_soc (fk_soc);
ALTER TABLE llx_collecte_collecte ADD INDEX idx_collecte_collecte_date_collecte (date_collecte);
ALTER TABLE llx_collecte_collecte ADD CONSTRAINT llx_collecte_collecte_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_collecte_collecte ADD INDEX idx_collecte_collecte_status (status);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_collecte_collecte ADD UNIQUE INDEX uk_collecte_collecte_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_collecte_collecte ADD CONSTRAINT llx_collecte_collecte_fk_field FOREIGN KEY (fk_field) REFERENCES llx_collecte_myotherobject(rowid);

