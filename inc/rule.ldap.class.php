<?php
/*
 * @version $Id: rule.ocs.class.php 4582 2007-03-13 23:37:19Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------
if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

/**
* Rule class store all informations about a GLPI rule :
*   - description
*   - criterias
*   - actions
* 
**/
class LdapAffectEntityRule extends Rule {

	function LdapAffectEntityRule() {
		global $RULES_CRITERIAS;	
	
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
		$this->rule_type = RULE_LDAP_AFFECT_ENTITY;
		$this->stop_on_first_match = false;
		
		//Dynamically add all the ldap criterias to the current list of rule's criterias
		$this->addLdapCriteriasToArray();
	}

	/**
	 * Get the attributes needed for processing the rules
	 * @param type type of the rule
	 * @param extra_params extra parameters given
	 * @return an array of attributes
	 */
	function prepareInputDataForProcess($input,$params){
		global $RULES_CRITERIAS;
		if (count($input))
		{
				$rule_parameters = array();
				$input = $input[0];

				//Get all the ldap fields
				$fields = $this->getFieldsForQuery(RULE_LDAP_AFFECT_ENTITY);
				
				foreach ($fields as $field)
				{
						switch(strtoupper($field))
						{
							case "LDAP_SERVER":
								$rule_parameters["LDAP_SERVER"] = $params["ldap_server"];
								break;
							case "GROUPS" :
									foreach ($params["groups"] as $group)
										$rule_parameters["GROUPS"][] = $group;
							break;
							default :
								if (isset($input[$field]))
								{
									if (!is_array($input[$field]))
										$rule_parameters[$field] = $input[$field];
										else
										{
												for ($i=0;$i < count($input[$field]) -1;$i++)
													$rule_parameters[$field][] = $input[$field][$i];
												break;
										}	
								}
						}
				}

				return $rule_parameters;
		}
		else return $input;
	}

	function maxActionsCount(){
		// Unlimited
		return 2;
	}
	/**
	 * Display form to add rules
	 * @param rule_type Type of rule (ocs_affectation, ldap_rights)
	 */
	function showAndAddRuleForm($target, $ID) {
		global $LANG, $CFG_GLPI;

		$canedit = haveRight("config", "w");

		echo "<form name='entityaffectation_form' id='entityaffectation_form' method='post' action=\"$target\">";

		if ($canedit) {

			echo "<div align='center'>";
			echo "<table  class='tab_cadre_fixe'>";
			echo "<tr class='tab_bg_1'><th colspan='5'>" . $LANG["rulesengine"][21] . $LANG["rulesengine"][18] . "</tr><tr><td class='tab_bg_2' align='center'>";
			echo $LANG["common"][16] . ":";
			echo "</td><td align='center' class='tab_bg_2'>";
			autocompletionTextField("name", "glpi_rules_descriptions", "name", "", 30);
			echo $LANG["joblist"][6] . ":";
			autocompletionTextField("description", "glpi_rules_descriptions", "description", "", 30);
			echo "</td><td align='center' class='tab_bg_2'>";
			echo $LANG["rulesengine"][9] . ":";
			$this->dropdownRulesMatch("match", "AND");
			echo "</td><td align='center' class='tab_bg_2'>";
			echo "<input type=hidden name='rule_type' value=\"" . $this->rule_type . "\">";
			echo "<input type=hidden name='FK_entities' value=\"-1\">";
			echo "<input type=hidden name='affectentity' value=\"" . $ID . "\">";
			echo "<input type='submit' name='add_rule' value=\"" . $LANG["buttons"][8] . "\" class='submit'>";
			echo "</td></tr>";

			echo "</table></div><br>";

		}

		echo "<div align='center'><table class='tab_cadrehov'><tr><th colspan='3'>" . $LANG["entity"][5] . "</th></tr>";

		//Get all rules and actions
		$rules = $this->getRulesByID( $ID, 0, 1);

		if (!empty ($rules)) {

			foreach ($rules as $rule) {
				echo "<tr class='tab_bg_1'>";

				if ($canedit) {
					echo "<td width='10'>";
					$sel = "";
					if (isset ($_GET["select"]) && $_GET["select"] == "all")
						$sel = "checked";
					echo "<input type='checkbox' name='item[" . $rule->fields["ID"] . "]' value='1' $sel>";
					echo "</td>";
				}

				if ($canedit)
					echo "<td><a href=\"" . $CFG_GLPI["root_doc"] . "/front/rule.ocs.form.php?ID=" . $rule->fields["ID"] . "&amp;onglet=1\">" . $rule->fields["name"] . "</a></td>";
				else
					echo "<td>" . $rule->fields["name"] . "</td>";

				echo "<td>" . $rule->fields["description"] . "</td>";
				echo "</tr>";
			}
		}
		echo "<table>";

		if ($canedit) {
			echo "<div align='center'>";
			echo "<table cellpadding='5' width='80%'>";
			echo "<tr><td><img src=\"" . $CFG_GLPI["root_doc"] . "/pics/arrow-left.png\" alt=''></td><td><a onclick= \"if ( markAllRows('entityaffectation_form') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?ID=$ID&amp;select=all'>" . $LANG["buttons"][18] . "</a></td>";

			echo "<td>/</td><td><a onclick= \"if ( unMarkAllRows('entityaffectation_form') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?ID=$ID&amp;select=none'>" . $LANG["buttons"][19] . "</a>";
			echo "</td><td align='left' width='80%'>";
			echo "<input type='submit' name='delete_rule' value=\"" . $LANG["buttons"][6] . "\" class='submit'>";
			echo "</td>";
			echo "</table>";

			echo "</div>";

		}
		echo "</form>";
	}

	/**
	 * Get all ldap rules criterias from the DB and add them into the RULES_CRITERIAS
	 */
	function addLdapCriteriasToArray()
	{
		global $DB,$RULES_CRITERIAS;

			$sql = "SELECT name,value,rule_type FROM glpi_rules_ldap_parameters WHERE rule_type=".RULE_LDAP_AFFECT_ENTITY;
			$result = $DB->query($sql);
			while ($datas = $DB->fetch_array($result))
			{
					$RULES_CRITERIAS[RULE_LDAP_AFFECT_ENTITY][$datas["value"]]['name']=$datas["name"];
					$RULES_CRITERIAS[RULE_LDAP_AFFECT_ENTITY][$datas["value"]]['field']=$datas["value"];
					$RULES_CRITERIAS[RULE_LDAP_AFFECT_ENTITY][$datas["value"]]['linkfield']='';
					$RULES_CRITERIAS[RULE_LDAP_AFFECT_ENTITY][$datas["value"]]['table']='';
				}
	}

	/**
	* Execute the actions as defined in the rule
	* @param fields the fields to manipulate
	* @return the fields modified
	*/
	function executeActions($output,$params)
	{
		$entity='';
		$right='';
		
		if (count($this->actions)){
			foreach ($this->actions as $action){
				switch ($action->fields["action_type"]){
					case "assign" :
						if ($action->fields["field"] == "FK_entities") $entity = $action->fields["value"]; 
						elseif ($action->fields["field"] == "FK_profiles") $right = $action->fields["value"];
					break;
				}
			}
		}

		//Nothing to be returned by the function :
		//Store in session the entity and/or right
		if ($entity != '' && $right != '')
			$_SESSION["rules_entities_rights"][]=array($entity=>$right);
		elseif ($entity != '') 
			$_SESSION["rules_entities"][]=$entity;
		elseif ($right != '') 
			$_SESSION["rules_rights"][]=$right;
			
		return $output;
	}
	
	/**
	 * Get all the dynamic affectations, and insert it into database
	 */
	function processAffectations($userid)
	{
		global $DB;
		//TODO : do not erase all the dynamic rights, but compare it with the ones in DB
		//and add/update/delete only if it's necessary !
		if (isset($_SESSION["rules_entities_rights"]))
			$entities_rules = $_SESSION["rules_entities_rights"];
		else
			$entities_rules = array();

		if (isset($_SESSION["rules_entities"]))
			$entities = $_SESSION["rules_entities"];
		else 
			$entities = array();
			
		if (isset($_SESSION["rules_rights"]))
			$rights = $_SESSION["rules_rights"];
		else
			$rights = array();
			
		//First delete all the dynamic affectations for this user in database
		$sql = "DELETE FROM glpi_users_profiles WHERE FK_users=".$userid." AND dynamic=1";
		$DB->query($sql);

		//For each affectation -> write it in DB		
		foreach($entities_rules as $value)
		{
			$affectation["FK_entities"] = key($value);
			$affectation["FK_profiles"] = $value[$affectation["FK_entities"]];
			$affectation["FK_users"] = $userid;
			
			$affectation["recursive"] = 0;
			$affectation["dynamic"] = 1;
			addUserProfileEntity($affectation);
		}

		foreach($entities as $entity)
		{
				foreach($rights as $right)
				{
					$affectation["FK_entities"] = $entity;
					$affectation["FK_profiles"] = $right;
					$affectation["FK_users"] = $userid;
					
					$affectation["recursive"] = 0;
					$affectation["dynamic"] = 1;
					addUserProfileEntity($affectation);
				}
		}
		
		//Unset all the temporary tables
		unset($_SESSION["rules_entities_rights"]);
		unset($_SESSION["rules_rights"]);
		unset($_SESSION["rules_entities"]);
	}

	/**
	 * Get the list of fields to be retreived to process rules
	 */
	function getFieldsForQuery()
	{
		global $RULES_CRITERIAS;

		$fields = array();
		foreach ($RULES_CRITERIAS[$this->rule_type] as $criteria){
				if (isset($criteria['virtual']) && $criteria['virtual'] == "true")
					$fields[]=$criteria['id'];
				else	
				$fields[]=$criteria['field'];	
		}
		
		return $fields;		  
	}
}


class LdapRuleCollection extends RuleCollection {

	function LdapRuleCollection() {
		global $DB;
		$this->rule_type = RULE_LDAP_AFFECT_ENTITY;
		$this->rule_class_name = 'LdapAffectEntityRule';
		$this->stop_on_first_match=false;
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][31];
	}
	
	/**
	 * Get all the fields needed to perform the rule
	 */
	function getFieldsToLookFor()
	{
		global $DB;
		$params = array();
		$sql = "SELECT DISTINCT value " .
				"FROM glpi_rules_descriptions, glpi_rules_criterias, glpi_rules_ldap_parameters " .
				"WHERE glpi_rules_descriptions.rule_type=".RULE_LDAP_AFFECT_ENTITY." AND glpi_rules_criterias.FK_rules=glpi_rules_descriptions.ID AND glpi_rules_criterias.criteria=glpi_rules_ldap_parameters.value";
		
		$result = $DB->query($sql);
		while ($param = $DB->fetch_array($result))
		{
			//Dn is alwsays retreived from ldap : don't need to ask for it !
			if ($param["value"] != "dn")
				$params[]=$param["value"];
		}
		return $params;
	}
	
}
?>
