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
-- along with this program.  If not, see http://www.gnu.org/licenses/.

ALTER TABLE llx_pickup_batch ADD INDEX idx_pickup_batch_rowid (rowid);
ALTER TABLE llx_pickup_batch ADD INDEX idx_pickup_batch_batch_number (batch_number);
ALTER TABLE llx_pickup_batch ADD INDEX idx_pickup_batch_product_pickupline (fk_product, fk_pickupline);

ALTER TABLE llx_pickup_batch ADD CONSTRAINT llx_pickup_batch_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_pickup_batch ADD CONSTRAINT llx_pickup_batch_fk_user_modif FOREIGN KEY (fk_user_modif) REFERENCES llx_user(rowid);
ALTER TABLE llx_pickup_batch ADD CONSTRAINT llx_pickup_batch_fk_product FOREIGN KEY (fk_product) REFERENCES llx_product(rowid);
ALTER TABLE llx_pickup_batch ADD CONSTRAINT llx_pickup_batch_fk_pickupline FOREIGN KEY (fk_pickupline) REFERENCES llx_pickup_pickupline(rowid) ON DELETE CASCADE;
