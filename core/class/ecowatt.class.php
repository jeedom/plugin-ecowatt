<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class ecowatt extends eqLogic {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*
	 * Fonction exécutée automatiquement toutes les minutes par Jeedom
	public static function cron() {

	}
	 */

	/*
	 * Fonction exécutée automatiquement toutes les heures par Jeedom	 */
	public static function cronHourly() {
		foreach (self::byType('ecowatt') as $ecowatt) {
			if ($ecowatt->getConfiguration('datasource') == 'ecowatt' || $ecowatt->getConfiguration('datasource') == 'ejp' || $ecowatt->getConfiguration('datasource') == 'tempo') {
				if (date('H') != 1 && date('H') != 18 && date('H') != 23) {
					continue;
				}
			}
			$ecowatt->updateInfo();
		}
	}

	/*
	 * Fonction exécutée automatiquement tous les jours par Jeedom
	public static function cronDayly() {

	}
	 */

	/*     * *********************Méthodes d'instance************************* */

	public function preSave() {
		$this->setCategory('energy', 1);
	}

	public function postSave() {
		$cmd_list = array();
		if ($this->getConfiguration('datasource') == 'ecowatt') {
			$cmd_list = array(
				'today' => array(
					'name' => __('Aujourd\'hui', __FILE__),
					'subtype' => 'string',
					'order' => 1,
				),
				'tomorrow' => array(
					'name' => __('Demain', __FILE__),
					'subtype' => 'string',
					'order' => 2,
				),
			);
		}
		if ($this->getConfiguration('datasource') == 'ejp') {
			$cmd_list = array(
				'today' => array(
					'name' => __('Aujourd\'hui', __FILE__),
					'subtype' => 'string',
					'order' => 1,
				),
				'tomorrow' => array(
					'name' => __('Demain', __FILE__),
					'subtype' => 'string',
					'order' => 2,
				),
				'remainingDays' => array(
					'name' => __('Jours restants', __FILE__),
					'subtype' => 'numeric',
					'order' => 3,
				),
				'totalDays' => array(
					'name' => __('Total EJP', __FILE__),
					'subtype' => 'numeric',
					'order' => 4,
				),
			);
		}
		if ($this->getConfiguration('datasource') == 'tempo') {
			$cmd_list = array(
				'today' => array(
					'name' => __('Aujourd\'hui', __FILE__),
					'subtype' => 'string',
					'order' => 1,
				),
				'tomorrow' => array(
					'name' => __('Demain', __FILE__),
					'subtype' => 'string',
					'order' => 2,
				),
				'blue-remainingDays' => array(
					'name' => __('Jours Bleus restants', __FILE__),
					'subtype' => 'numeric',
					'order' => 3,
				),
				'blue-totalDays' => array(
					'name' => __('Total jours Bleus', __FILE__),
					'subtype' => 'numeric',
					'order' => 4,
				),
				'white-remainingDays' => array(
					'name' => __('Jours Blancs restants', __FILE__),
					'subtype' => 'numeric',
					'order' => 5,
				),
				'white-totalDays' => array(
					'name' => __('Total jours Blancs', __FILE__),
					'subtype' => 'numeric',
					'order' => 6,
				),
				'red-remainingDays' => array(
					'name' => __('Jours Rouges restants', __FILE__),
					'subtype' => 'numeric',
					'order' => 7,
				),
				'red-totalDays' => array(
					'name' => __('Total jours Rouges', __FILE__),
					'subtype' => 'numeric',
					'order' => 8,
				),
			);
		}

		if ($this->getConfiguration('datasource') == 'eco2mix') {

		}

		foreach ($this->getCmd() as $cmd) {
			if (!isset($cmd_list[$cmd->getLogicalId()])) {
				$cmd->remove();
			}
		}
		foreach ($cmd_list as $key => $cmd_info) {
			$cmd = $this->getCmd(null, $key);
			if (!is_object($cmd)) {
				$cmd = new ecowattCmd();
				$cmd->setLogicalId($key);
				$cmd->setIsVisible(1);
				$cmd->setName($cmd_info['name']);
				$cmd->setOrder($cmd_info['order']);
			}
			$cmd->setType('info');
			$cmd->setSubType($cmd_info['subtype']);
			$cmd->setEqLogic_id($this->getId());
			$cmd->setEventOnly(1);
			$cmd->save();
		}

		$this->updateInfo();
	}

	public function updateInfo() {
		switch ($this->getConfiguration('datasource')) {
			case 'ecowatt':
				$url = '';
				switch ($this->getConfiguration('region-ew')) {
					case 'bretagne':
						$url = 'http://www.ecowatt-bretagne.fr/restez-au-courant/alertes-2/';
						break;
					case 'paca':
						$url = 'http://www.ecowatt-paca.fr/restez-au-courant/alertes-2/';
						break;
				}
				if ($url == '') {
					return;
				}
				$request_http = new com_http($url);
				$html = $request_http->exec();
				phpQuery::newDocumentHTML($html);
				$result = pq('div.alertes.small')->html();
				$result = substr($result, strpos($result, 'alt="Alerte ') + 12);
				$result = substr($result, 0, strpos($result, '"'));
				$result = explode(' ', $result);
				$today = $this->getCmd(null, 'today');
				if (is_object($today) && $today->execCmd(null, 2) != $today->formatValue($result[0])) {
					$today->event($result[0]);
				}
				$tomorrow = $this->getCmd(null, 'tomorrow');
				if (is_object($tomorrow) && $tomorrow->execCmd(null, 2) != $tomorrow->formatValue($result[1])) {
					$tomorrow->event($result[1]);
				}
				break;

			case 'ejp':
				$request_http = new com_http('https://particulier.edf.fr/bin/edf_rc/servlets/ejptemponew?Date_a_remonter=' . date('Y-m-d') . '&TypeAlerte=EJP');
				$ejpdays = $request_http->exec();
				if (!is_json($ejpdays)) {
					return;
				}
				$ejpdays = json_decode($ejpdays, true);
				$region = 'Ejp' . ucfirst(strtolower(str_replace(array('_', 'EJP'), '', $this->getConfiguration('region-ejp'))));
				$value = 'Non déterminé';
				if (isset($ejpdays['JourJ'][$region])) {
					if ($ejpdays['JourJ'][$region] == 'NON_EJP') {
						$value = 'Pas d\'EJP';
					} else {
						$value = 'EJP';
					}
				}
				$today = $this->getCmd(null, 'today');
				if (is_object($today) && $today->execCmd(null, 2) != $today->formatValue($value)) {
					$today->event($value);
				}
				$value = 'Non déterminé';
				if (isset($ejpdays['JourJ1'][$region])) {
					if ($ejpdays['JourJ1'][$region] == 'NON_EJP') {
						$value = 'Pas d\'EJP';
					} else {
						$value = 'EJP';
					}
				}
				$tomorrow = $this->getCmd(null, 'tomorrow');
				if (is_object($tomorrow) && $tomorrow->execCmd(null, 2) != $tomorrow->formatValue($value)) {
					$tomorrow->event($value);
				}

				$request_http = new com_http('https://particulier.edf.fr/bin/edf_rc/servlets/ejptempodays?searchType=ejp');
				$ejptotaldays = $request_http->exec();
				if (!is_json($ejptotaldays)) {
					return;
				}
				$ejptotaldays = json_decode($ejptotaldays, true);
				if (!isset($ejptotaldays['success']) || $ejptotaldays['success'] != 1) {
					return;
				}
				$ejptotaldays['data'] = json_decode($ejptotaldays['data'], true);
				$found_region = null;

				foreach ($ejptotaldays['data']['dtos'] as $region) {
					if ($region['region'] == $this->getConfiguration('region-ejp')) {
						$found_region = $region;
						break;
					}
				}
				if (isset($found_region['remainingDays'])) {
					$value = $found_region['remainingDays'];
				} else {
					$value = 'error::N/A';
				}
				$remainingDays = $this->getCmd(null, 'remainingDays');
				if (is_object($remainingDays) && $remainingDays->execCmd(null, 2) !== $remainingDays->formatValue($value)) {
					$remainingDays->event($value);
				}
				if (isset($found_region['totalDays'])) {
					$value = $found_region['totalDays'];
				} else {
					$value = 'error::N/A';
				}

				$totalDays = $this->getCmd(null, 'totalDays');
				if (is_object($totalDays) && $totalDays->execCmd(null, 2) !== $totalDays->formatValue($value)) {
					$totalDays->event($value);
				}
				break;

			case 'tempo':
				$request_http = new com_http('https://particulier.edf.fr/bin/edf_rc/servlets/ejptemponew?Date_a_remonter=' . date('Y-m-d') . '&TypeAlerte=TEMPO');
				$tempodays = $request_http->exec();
				if (!is_json($tempodays)) {
					return;
				}
				$tempodays = json_decode($tempodays, true);
				$this->fillValue('today', 'JourJ::Tempo', $tempodays);
				$this->fillValue('tomorrow', 'JourJ1::Tempo', $tempodays);

				$request_http = new com_http('https://particulier.edf.fr/bin/edf_rc/servlets/ejptempodays?searchType=tempo');
				$tempodays = $request_http->exec();
				if (!is_json($tempodays)) {
					return;
				}
				$tempodays = json_decode($tempodays, true);
				if (!isset($tempodays['success']) || $tempodays['success'] != 1) {
					return;
				}
				$tempodays['data'] = json_decode($tempodays['data'], true);

				$this->fillValue('white-remainingDays', 'data::dtos::0::remainingDays', $tempodays);
				$this->fillValue('white-totalDays', 'data::dtos::0::totalDays', $tempodays);
				$this->fillValue('blue-remainingDays', 'data::dtos::1::remainingDays', $tempodays);
				$this->fillValue('blue-totalDays', 'data::dtos::1::totalDays', $tempodays);
				$this->fillValue('red-remainingDays', 'data::dtos::2::remainingDays', $tempodays);
				$this->fillValue('red-totalDays', 'data::dtos::2::totalDays', $tempodays);

				break;
			case 'eco2mix':
				# code...
				break;
		}
	}

	public function fillValue($_logicalId, $_value, $_data) {
		$result = 'Non déterminé';
		foreach (explode('::', $_value) as $key) {
			if (isset($_data[$key])) {
				$_data = $_data[$key];
			} else {
				$_data = null;
				break;
			}
		}

		if (!is_array($_data) && $_data !== null) {
			$result = $_data;
		}
		$cmd = $this->getCmd(null, $_logicalId);
		if (is_object($cmd) && $cmd->execCmd(null, 2) !== $cmd->formatValue($result)) {
			$cmd->event($result);
		}
	}

	public function toHtml($_version = 'dashboard') {
		if ($this->getIsEnable() != 1) {
			return '';
		}
		if (!$this->hasRight('r')) {
			return '';
		}

		$_version = jeedom::versionAlias($_version);
		if ($this->getDisplay('hideOn' . $_version) == 1) {
			return '';
		}
		$replace = array(
			'#name#' => $this->getName(),
			'#id#' => $this->getId(),
			'#eqLink#' => $this->getLinkToConfiguration(),
		);
		foreach ($this->getCmd('info') as $cmd) {
			$replace['#' . $cmd->getLogicalId() . '_history#'] = '';
			$replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
			$replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd(null, 2);
			$replace['#' . $cmd->getLogicalId() . '_collect#'] = $cmd->getCollectDate();
			if ($cmd->getIsHistorized() == 1) {
				$replace['#' . $cmd->getLogicalId() . '_history#'] = 'history cursor';
			}

		}
		$parameters = $this->getDisplay('parameters');
		if (is_array($parameters)) {
			foreach ($parameters as $key => $value) {
				$replace['#' . $key . '#'] = $value;
			}
		}
		if ($this->getConfiguration('datasource') == 'ecowatt') {
			$html = template_replace($replace, getTemplate('core', $_version, 'ecowatt_ecowatt', 'ecowatt'));
			return $html;
		}
		if ($this->getConfiguration('datasource') == 'ejp') {
			$html = template_replace($replace, getTemplate('core', $_version, 'ecowatt_ejp', 'ecowatt'));
			return $html;
		}
		if ($this->getConfiguration('datasource') == 'tempo') {
			$html = template_replace($replace, getTemplate('core', $_version, 'ecowatt_tempo', 'ecowatt'));
			return $html;
		}
	}
	/*
	 * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
	public function toHtml($_version = 'dashboard') {

	}
	 */

	/*     * **********************Getteur Setteur*************************** */
}

class ecowattCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	/*
	 * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
	public function dontRemoveCmd() {
	return true;
	}
	 */

	public function execute($_options = array()) {

	}

	/*     * **********************Getteur Setteur*************************** */
}

?>
