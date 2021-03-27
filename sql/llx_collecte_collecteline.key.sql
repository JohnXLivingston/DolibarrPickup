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


ALTER TABLE llx_collecte_collecteline ADD INDEX idx_collecte_collecteline_rowid (rowid);
ALTER TABLE llx_collecte_collecteline ADD INDEX idx_collecte_collecteline_fk_collecte (fk_collecte);
ALTER TABLE llx_collecte_collecteline ADD CONSTRAINT llx_collecte_collecteline_fk_collecte FOREIGN KEY (fk_collecte) REFERENCES llx_collecte_collecte(rowid);
ALTER TABLE llx_collecte_collecteline ADD INDEX idx_collecte_collecteline_fk_product (fk_product);
ALTER TABLE llx_collecte_collecteline ADD CONSTRAINT llx_collecte_collecteline_fk_product FOREIGN KEY (fk_product) REFERENCES llx_product(rowid);
ALTER TABLE llx_collecte_collecteline ADD CONSTRAINT llx_collecte_collecteline_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_collecte_collecteline ADD CONSTRAINT llx_collecte_collecteline_fk_user_modif FOREIGN KEY (fk_user_modif) REFERENCES llx_user(rowid);
ALTER TABLE llx_collecte_collecteline ADD CONSTRAINT llx_collecte_collecteline_fk_stock_movement FOREIGN KEY (fk_stock_movement) REFERENCES llx_stock_mouvement(rowid);

ALTER TABLE llx_collecte_collecteline ADD INDEX idx_collecte_collecteline_fk_collecte_position (fk_collecte, position);

