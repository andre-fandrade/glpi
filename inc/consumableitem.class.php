<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

//!  ConsumableItem Class
/**
 * This class is used to manage the various types of consumables.
 * @see Consumable
 * @author Julien Dombre
*/
class ConsumableItem extends CommonDBTM {
   // From CommonDBTM
   static protected $forward_entity_to = ['Consumable', 'Infocom'];
   protected $usenotepad               = true;

   static $rightname                   = 'consumable';


   static function getTypeName($nb = 0) {
      return _n('Consumable model', 'Consumable models', $nb);
   }


   /**
    * @see CommonGLPI::getMenuName()
    *
    * @since 0.85
   **/
   static function getMenuName() {
      return Consumable::getTypeName(Session::getPluralNumber());
   }


   /**
    * @see CommonGLPI::getAdditionalMenuLinks()
    *
    * @since 0.85
   **/
   static function getAdditionalMenuLinks() {

      if (static::canView()) {
         return ['summary' => '/front/consumableitem.php?synthese=yes'];
      }
      return false;
   }


   /**
    * @since 0.84
    *
    * @see CommonDBTM::getPostAdditionalInfosForName
   **/
   function getPostAdditionalInfosForName() {

      if (isset($this->fields["ref"]) && !empty($this->fields["ref"])) {
         return $this->fields["ref"];
      }
      return '';
   }


   function cleanDBonPurge() {

      $class = new Consumable();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $class = new Alert();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
   }


   function post_getEmpty() {

      $this->fields["alarm_threshold"] = Entity::getUsedConfig(
              "consumables_alert_repeat", $this->fields["entities_id"],
              "default_consumables_alarm_threshold", 10);
   }


   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Consumable', $ong, $options);
      $this->addStandardTab('Infocom', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('Link', $ong, $options);
      $this->addStandardTab('Notepad', $ong, $options);

      return $ong;
   }


   /**
    * Print the consumable type form
    *
    * @param $ID        integer ID of the item
    * @param $options   array
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    * @return Nothing (display)
    *
    **/
   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td>".__('Type')."</td>";
      echo "<td>";
      ConsumableItemType::dropdown(['value' => $this->fields["consumableitemtypes_id"]]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Reference')."</td>\n";
      echo "<td>";
      Html::autocompletionTextField($this, "ref");
      echo "</td>";
      echo "<td>".__('Manufacturer')."</td>";
      echo "<td>";
      Manufacturer::dropdown(['value' => $this->fields["manufacturers_id"]]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Technician in charge of the hardware')."</td>";
      echo "<td>";
      User::dropdown(['name'   => 'users_id_tech',
                           'value'  => $this->fields["users_id_tech"],
                           'right'  => 'own_ticket',
                           'entity' => $this->fields["entities_id"]]);
      echo "</td>";
      echo "<td rowspan='4' class='middle'>".__('Comments')."</td>";
      echo "<td class='middle' rowspan='4'>
             <textarea cols='45' rows='9' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Group in charge of the hardware')."</td>";
      echo "<td>";
      Group::dropdown(['name'      => 'groups_id_tech',
                            'value'     => $this->fields['groups_id_tech'],
                            'entity'    => $this->fields['entities_id'],
                            'condition' => '`is_assign`']);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Stock location')."</td>";
      echo "<td>";
      Location::dropdown(['value'  => $this->fields["locations_id"],
                               'entity' => $this->fields["entities_id"]]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Alert threshold')."</td>";
      echo "<td>";
      Dropdown::showNumber('alarm_threshold', ['value' => $this->fields["alarm_threshold"],
                                                    'min'   => 0,
                                                    'max'   => 100,
                                                    'step'  => 1,
                                                    'toadd' => ['-1' => __('Never')]]);

      Alert::displayLastAlert('ConsumableItem', $ID);
      echo "</td></tr>";

      $this->showFormButtons($options);

      return true;
   }


   /**
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem = null) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      if ($isadmin) {
         MassiveAction::getAddTransferList($actions);
      }

      return $actions;
   }

   function getSearchOptionsNew() {
      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'datatype'           => 'number',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '34',
         'table'              => $this->getTable(),
         'field'              => 'ref',
         'name'               => __('Reference'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => 'glpi_consumableitemtypes',
         'field'              => 'name',
         'name'               => __('Type'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '23',
         'table'              => 'glpi_manufacturers',
         'field'              => 'name',
         'name'               => __('Manufacturer'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '9',
         'table'              => $this->getTable(),
         'field'              => '_virtual',
         'linkfield'          => '_virtual',
         'name'               => _n('Consumable', 'Consumables', Session::getPluralNumber()),
         'datatype'           => 'specific',
         'massiveaction'      => false,
         'nosearch'           => true,
         'nosort'             => true,
         'additionalfields'   => ['alarm_threshold']
      ];

      $tab[] = [
         'id'                 => '17',
         'table'              => 'glpi_consumables',
         'field'              => 'id',
         'name'               => __('Number of used consumables'),
         'datatype'           => 'count',
         'forcegroupby'       => true,
         'usehaving'          => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => 'AND NEWTABLE.`date_out` IS NOT NULL'
         ]
      ];

      $tab[] = [
         'id'                 => '19',
         'table'              => 'glpi_consumables',
         'field'              => 'id',
         'name'               => __('Number of new consumables'),
         'datatype'           => 'count',
         'forcegroupby'       => true,
         'usehaving'          => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => 'AND NEWTABLE.`date_out` IS NULL'
         ]
      ];

      $tab = array_merge($tab, Location::getSearchOptionsToAddNew());

      $tab[] = [
         'id'                 => '24',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'linkfield'          => 'users_id_tech',
         'name'               => __('Technician in charge of the hardware'),
         'datatype'           => 'dropdown',
         'right'              => 'own_ticket'
      ];

      $tab[] = [
         'id'                 => '49',
         'table'              => 'glpi_groups',
         'field'              => 'completename',
         'linkfield'          => 'groups_id_tech',
         'name'               => __('Group in charge of the hardware'),
         'condition'          => '`is_assign`',
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'alarm_threshold',
         'name'               => __('Alert threshold'),
         'datatype'           => 'number',
         'toadd'              => [
            '-1'                 => 'Never'
         ]
      ];

      $tab[] = [
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'massiveaction'      => false,
         'datatype'           => 'dropdown'
      ];

      // add objectlock search options
      $tab = array_merge($tab, ObjectLock::getSearchOptionsToAddNew(get_class($this)));

      $tab = array_merge($tab, Notepad::getSearchOptionsToAddNew());

      return $tab;
   }


   static function cronInfo($name) {
      return ['description' => __('Send alarms on consumables')];
   }


   /**
    * Cron action on consumables : alert if a stock is behind the threshold
    *
    * @param $task   to log, if NULL display (default NULL)
    *
    * @return 0 : nothing to do 1 : done with success
   **/
   static function cronConsumable($task = null) {
      global $DB, $CFG_GLPI;

      $cron_status = 1;

      if ($CFG_GLPI["use_notifications"]) {
         $message = [];
         $items   = [];
         $alert   = new Alert();

         foreach (Entity::getEntitiesToNotify('consumables_alert_repeat') as $entity => $repeat) {

            $query_alert = "SELECT `glpi_consumableitems`.`id` AS consID,
                                   `glpi_consumableitems`.`entities_id` AS entity,
                                   `glpi_consumableitems`.`ref` AS ref,
                                   `glpi_consumableitems`.`name` AS name,
                                   `glpi_consumableitems`.`alarm_threshold` AS threshold,
                                   `glpi_alerts`.`id` AS alertID,
                                   `glpi_alerts`.`date`
                            FROM `glpi_consumableitems`
                            LEFT JOIN `glpi_alerts`
                                 ON (`glpi_consumableitems`.`id` = `glpi_alerts`.`items_id`
                                     AND `glpi_alerts`.`itemtype`='ConsumableItem')
                            WHERE `glpi_consumableitems`.`is_deleted` = '0'
                                  AND `glpi_consumableitems`.`alarm_threshold` >= '0'
                                  AND `glpi_consumableitems`.`entities_id` = '".$entity."'
                                  AND (`glpi_alerts`.`date` IS NULL
                                       OR (`glpi_alerts`.date+$repeat) < CURRENT_TIMESTAMP());";
            $message = "";
            $items   = [];

            foreach ($DB->request($query_alert) as $consumable) {
               if (($unused=Consumable::getUnusedNumber($consumable["consID"]))
                              <=$consumable["threshold"]) {
                  // define message alert
                  //TRANS: %1$s is the consumable name, %2$s its reference, %3$d the remaining number
                  $message .= sprintf(__('Threshold of alarm reached for the type of consumable: %1$s - Reference %2$s - Remaining %3$d'),
                                      $consumable['name'], $consumable['ref'], $unused);
                  $message.='<br>';

                  $items[$consumable["consID"]] = $consumable;

                  // if alert exists -> delete
                  if (!empty($consumable["alertID"])) {
                     $alert->delete(["id" => $consumable["alertID"]]);
                  }
               }
            }

            if (!empty($items)) {
               $options['entities_id'] = $entity;
               $options['items']       = $items;

               if (NotificationEvent::raiseEvent('alert', new ConsumableItem(), $options)) {
                  if ($task) {
                     $task->log(Dropdown::getDropdownName("glpi_entities",
                                                          $entity)." :  $message\n");
                     $task->addVolume(1);
                  } else {
                     Session::addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities",
                                                                                $entity).
                                                      " :  $message");
                  }

                  $input["type"]     = Alert::THRESHOLD;
                  $input["itemtype"] = 'ConsumableItem';

                  // add alerts
                  foreach ($items as $ID=>$consumable) {
                     $input["items_id"] = $ID;
                     $alert->add($input);
                     unset($alert->fields['id']);
                  }

               } else {
                  $entityname = Dropdown::getDropdownName('glpi_entities', $entity);
                  //TRANS: %s is entity name
                  $msg = sprintf(__('%s: send consumable alert failed'), $entityname);
                  if ($task) {
                     $task->log($msg);
                  } else {
                     Session::addMessageAfterRedirect($msg, false, ERROR);
                  }
               }
            }
         }
      }
      return $cron_status;
   }


   function getEvents() {
      return ['alert' => __('Send alarms on consumables')];
   }


   /**
    * Display debug information for current object
   **/
   function showDebug() {

      // see query_alert in cronConsumable()
      $item = ['consID'    => $this->fields['id'],
                    'entity'    => $this->fields['entities_id'],
                    'ref'       => $this->fields['ref'],
                    'name'      => $this->fields['name'],
                    'threshold' => $this->fields['alarm_threshold']];

      $options = [];
      $options['entities_id'] = $this->getEntityID();
      $options['items']       = [$item];
      NotificationEvent::debugEvent($this, $options);
   }


   /**
    * Have I the right to "update" the Object
    *
    * Default is true and check entity if the objet is entity assign
    *
    * May be overloaded if needed
    *
    * Overriden here to check entities recursively
    *
    * @return booleen
   **/
   function canUpdateItem() {

      if (!$this->checkEntity(true)) {
         return false;
      }
      return true;
   }
}
