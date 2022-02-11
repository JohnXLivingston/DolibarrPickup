<?php
/* Copyright (C) 2022		Jonathan Dollé		<license@jonathandolle.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Return the list of mobile forms for mobilecats.
 */
function mobileListProductForms() {
  global $conf;

  $r = array();
  $r[''] = 'Formulaire par défaut';

  if (!empty($conf->global->PICKUP_USE_DEEE)) {
    $r['create_product_deee_off'] = 'Non DEEE';
    $r['create_product_deee_gef'] = 'GEF';
    $r['create_product_deee_ghf'] = 'GHF';
    $r['create_product_deee_pam'] = 'PAM';
    $r['create_product_deee_pampro'] = 'PAM Pro';
    $r['create_product_deee_ecr'] = 'ECR (Ecran < 1m2)';
    $r['create_product_deee_ecrpro'] = 'ECR Pro (Ecran > 1m2)';
    $r['create_product_deee_pam_or_pampro'] = 'PAM ou PAM Pro';
    $r['create_product_deee_ecr_or_ecrpro'] = 'ECR ou ECR Pro';
  }

  return $r;
}
