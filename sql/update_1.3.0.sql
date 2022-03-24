-- Copyright (C) 2022		John Livingston		<license@john-livingston.fr>
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

ALTER TABLE llx_pickup_pickup ADD fk_pickup_type integer DEFAULT NULL AFTER date_pickup;

ALTER TABLE llx_pickup_pickupline ADD length double(24,8) DEFAULT NULL AFTER weight_units;
ALTER TABLE llx_pickup_pickupline ADD length_units tinyint DEFAULT NULL AFTER length;

ALTER TABLE llx_pickup_pickupline ADD surface double(24,8) DEFAULT NULL AFTER length_units;
ALTER TABLE llx_pickup_pickupline ADD surface_units tinyint DEFAULT NULL AFTER surface;

ALTER TABLE llx_pickup_pickupline ADD volume double(24,8) DEFAULT NULL AFTER surface_units;
ALTER TABLE llx_pickup_pickupline ADD volume_units tinyint DEFAULT NULL AFTER volume;
