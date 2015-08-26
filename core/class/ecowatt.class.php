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
			if ($ecowatt->getConfiguration('datasource') == 'ecowatt' || $ecowatt->getConfiguration('datasource') == 'ejp') {
				if (date('H') != 1 && date('H') != 5) {
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

	public function postSave() {
		if ($this->getConfiguration('datasource') == 'ecowatt') {
			$remainingDays = $this->getCmd(null, 'remainingDays');
			if (is_object($remainingDays)) {
				$remainingDays->remove();
			}
			$totalDays = $this->getCmd(null, 'totalDays');
			if (is_object($today)) {
				$totalDays->remove();
			}

			$today = $this->getCmd(null, 'today');
			if (!is_object($today)) {
				$today = new ecowattCmd();
				$today->setLogicalId('today');
				$today->setIsVisible(1);
				$today->setName(__('Aujourd\'hui', __FILE__));
				$today->setOrder(1);
			}
			$today->setType('info');
			$today->setSubType('string');
			$today->setEqLogic_id($this->getId());
			$today->setEventOnly(1);
			$today->save();

			$tomorrow = $this->getCmd(null, 'tomorrow');
			if (!is_object($tomorrow)) {
				$tomorrow = new ecowattCmd();
				$tomorrow->setLogicalId('tomorrow');
				$tomorrow->setIsVisible(1);
				$tomorrow->setName(__('Demain', __FILE__));
				$tomorrow->setOrder(2);
			}
			$tomorrow->setType('info');
			$tomorrow->setSubType('string');
			$tomorrow->setEqLogic_id($this->getId());
			$tomorrow->setEventOnly(1);
			$tomorrow->save();
		}
		if ($this->getConfiguration('datasource') == 'ejp') {
			$today = $this->getCmd(null, 'today');
			if (!is_object($today)) {
				$today = new ecowattCmd();
				$today->setLogicalId('today');
				$today->setIsVisible(1);
				$today->setName(__('Aujourd\'hui', __FILE__));
				$today->setOrder(1);
			}
			$today->setType('info');
			$today->setSubType('string');
			$today->setEqLogic_id($this->getId());
			$today->setEventOnly(1);
			$today->save();

			$tomorrow = $this->getCmd(null, 'tomorrow');
			if (!is_object($tomorrow)) {
				$tomorrow = new ecowattCmd();
				$tomorrow->setLogicalId('tomorrow');
				$tomorrow->setIsVisible(1);
				$tomorrow->setName(__('Demain', __FILE__));
				$tomorrow->setOrder(2);
			}
			$tomorrow->setType('info');
			$tomorrow->setSubType('string');
			$tomorrow->setEqLogic_id($this->getId());
			$tomorrow->setEventOnly(1);
			$tomorrow->save();

			$remainingDays = $this->getCmd(null, 'remainingDays');
			if (!is_object($remainingDays)) {
				$remainingDays = new ecowattCmd();
				$remainingDays->setLogicalId('remainingDays');
				$remainingDays->setIsVisible(1);
				$remainingDays->setName(__('Jours EJP restants', __FILE__));
				$remainingDays->setOrder(3);
			}
			$remainingDays->setType('info');
			$remainingDays->setSubType('numeric');
			$remainingDays->setEqLogic_id($this->getId());
			$remainingDays->setEventOnly(1);
			$remainingDays->save();

			$totalDays = $this->getCmd(null, 'totalDays');
			if (!is_object($totalDays)) {
				$totalDays = new ecowattCmd();
				$totalDays->setLogicalId('totalDays');
				$totalDays->setIsVisible(1);
				$totalDays->setName(__('Total de jours EJP', __FILE__));
				$totalDays->setOrder(4);
			}
			$totalDays->setType('info');
			$totalDays->setSubType('numeric');
			$totalDays->setEqLogic_id($this->getId());
			$totalDays->setEventOnly(1);
			$totalDays->save();
		}

		if ($this->getConfiguration('datasource') == 'eco2mix') {
			$today = $this->getCmd(null, 'today');
			if (is_object($today)) {
				$today->remove();
			}
			$tomorrow = $this->getCmd(null, 'tomorrow');
			if (is_object($tomorrow)) {
				$tomorrow->remove();
			}
			$remainingDays = $this->getCmd(null, 'remainingDays');
			if (is_object($remainingDays)) {
				$remainingDays->remove();
			}
			$totalDays = $this->getCmd(null, 'totalDays');
			if (is_object($today)) {
				$totalDays->remove();
			}
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
				$request_http = new com_http('https://particulier.edf.fr/bin/edf_rc/servlets/ejptempo?searchType=ejp');
				$ejpdays = $request_http->exec();
				if (!is_json($ejpdays)) {
					return;
				}
				$ejpdays = json_decode($ejpdays, true);
				if (!isset($ejpdays['success']) || $ejpdays['success'] != 1) {
					return;
				}
				$ejpdays['data'] = json_decode($ejpdays['data'], true);
				$found_region = null;
				foreach ($ejpdays['data']['dtos'] as $region) {
					if ($region['region'] == $this->getConfiguration('region-ejp')) {
						$found_region = $region;
						break;
					}
				}
				$value = 'Non déterminé';
				if (isset($found_region['values'][0])) {
					if (($found_region['values'][0]) == 'NON') {
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
				if (isset($found_region['values'][1])) {
					if (($found_region['values'][1]) == 'NON') {
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
				print_r($ejptotaldays);
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

			case 'eco2mix':
				# code...
				break;
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
