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
-- along with this program.  If not, see https://www.gnu.org/licenses/.

ALTER TABLE llx_pickup_pickupline ADD weight double(24,8) DEFAULT NULL;
ALTER TABLE llx_pickup_pickupline ADD weight_units tinyint DEFAULT NULL;
ALTER TABLE llx_pickup_pickupline ADD deee tinyint DEFAULT NULL;
-- NB: deee_type should be a tinyint, but it seems it is a varchar on llx_product_extrafields
ALTER TABLE llx_pickup_pickupline ADD deee_type varchar(255) DEFAULT NULL;
