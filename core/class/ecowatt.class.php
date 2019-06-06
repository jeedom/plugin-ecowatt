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
require_once dirname(__FILE__) . '/../../vendor/autoload.php';

class ecowatt extends eqLogic {
	/*     * *************************Attributs****************************** */
	public static $_widgetPossibility = array('custom' => true, 'custom::layout' => false);

	/*     * ***********************Methode static*************************** */

	public static function cronHourly() {
		$hour = array(
			'ejp' => array(1, 6, 12, 16, 19, 23),
			'ecowatt' => array(6, 10, 13, 16, 19, 23),
			'tempo' => array(6, 10, 13, 16, 19, 23),
		);
		foreach (self::byType('ecowatt',true) as $ecowatt) {
			if (isset($hour[$ecowatt->getConfiguration('datasource')]) && !in_array(date('H'), $hour[$ecowatt->getConfiguration('datasource')])) {
				continue;
			}
			$ecowatt->updateInfo();
		}
	}

	public static function valueFromUrl($_url) {
		$request_http = new com_http($_url);
		$dataUrl = $request_http->exec();
		if (!is_json($dataUrl)) {
			return;
		}
		return json_decode($dataUrl, true);
	}

	/*     * *********************Méthodes d'instance************************* */

	public function preSave() {
		$this->setCategory('energy', 1);
	}

	public function postSave() {
		$cmd_list = array();
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
					'name' => __('EJP restants', __FILE__),
					'subtype' => 'numeric',
					'order' => 3,
				),
				'totalDays' => array(
					'name' => __('EJP écoulés', __FILE__),
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
		foreach ($this->getCmd() as $cmd) {
			if (!isset($cmd_list[$cmd->getLogicalId()]) && $cmd->getLogicalId() != 'refresh') {
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

		$refresh = $this->getCmd(null, 'refresh');
		if (!is_object($refresh)) {
			$refresh = new ecowattCmd();
			$refresh->setName(__('Rafraichir', __FILE__));
		}
		$refresh->setEqLogic_id($this->getId());
		$refresh->setLogicalId('refresh');
		$refresh->setType('action');
		$refresh->setSubType('other');
		$refresh->setOrder(99);
		$refresh->save();

		$this->updateInfo();
	}

	public function updateInfo() {
		switch ($this->getConfiguration('datasource')) {
			case 'ejp':
				$ejpdays = self::valueFromUrl('https://particulier.edf.fr/bin/edf_rc/servlets/ejptemponew?Date_a_remonter=' . date('Y-m-d') . '&TypeAlerte=EJP');
				$region = 'Ejp' . ucfirst(strtolower(str_replace(array('_', 'EJP'), '', $this->getConfiguration('region-ejp'))));

				$value = 'Non déterminé';
				if (isset($ejpdays['JourJ'][$region])) {
					if ($ejpdays['JourJ'][$region] == 'NON_EJP') {
						$value = 'Pas d\'EJP';
					} elseif ($ejpdays['JourJ'][$region] == 'EST_EJP') {
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
					} elseif ($ejpdays['JourJ1'][$region] == 'EST_EJP') {
						$value = 'EJP';
					}
				}
				$this->checkAndUpdateCmd('tomorrow', $value);

				$ejptotaldays = self::valueFromUrl('https://particulier.edf.fr/services/rest/referentiel/historicEJPStore?searchType=ejp');
				$region = str_replace(array('_', 'EJP'), '', $this->getConfiguration('region-ejp'));
				$this->fillValue('totalDays', $region . '::Total', $ejptotaldays, -1);
				$totalDays = $this->getCmd(null, 'totalDays');
				$remainingDays = $this->getCmd(null, 'remainingDays')->event(22 - $totalDays->execCmd(null, 2));
				break;
			case 'tempo':
				$tempodays = self::valueFromUrl('https://particulier.edf.fr/bin/edf_rc/servlets/ejptemponew?Date_a_remonter=' . date('Y-m-d') . '&TypeAlerte=TEMPO');
				$this->fillValue('today', 'JourJ::Tempo', $tempodays);
				$this->fillValue('tomorrow', 'JourJ1::Tempo', $tempodays);

				$tempodays = self::valueFromUrl('https://particulier.edf.fr/bin/edf_rc/servlets/ejptempodaysnew?TypeAlerte=TEMPO');
				$this->fillValue('white-remainingDays', 'PARAM_NB_J_BLANC', $tempodays);
				$this->fillValue('blue-remainingDays', 'PARAM_NB_J_BLEU', $tempodays);
				$this->fillValue('red-remainingDays', 'PARAM_NB_J_ROUGE', $tempodays);

				$tempodays = self::valueFromUrl('https://particulier.edf.fr/services/rest/referentiel/getConfigProperty?PARAM_CONFIG_PROPERTY=param.nb.bleu.periode');
				$this->fillValue('blue-totalDays', 'param.nb.bleu.periode', $tempodays);
				$tempodays = self::valueFromUrl('https://particulier.edf.fr/services/rest/referentiel/getConfigProperty?PARAM_CONFIG_PROPERTY=param.nb.blanc.periode');
				$this->fillValue('white-totalDays', 'param.nb.blanc.periode', $tempodays);
				$tempodays = self::valueFromUrl('https://particulier.edf.fr/services/rest/referentiel/getConfigProperty?PARAM_CONFIG_PROPERTY=param.nb.rouge.periode');
				$this->fillValue('red-totalDays', 'param.nb.rouge.periode', $tempodays);
				break;
		}
		$this->refreshWidget();
	}

	public function fillValue($_logicalId, $_value, $_data, $_default = 'N/A') {
		$result = $_default;
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
		$this->checkAndUpdateCmd($_logicalId, $result);
	}

	public function toHtml($_version = 'dashboard') {
		$replace = $this->preToHtml($_version, array('#background-color#' => '#bdc3c7'));
		if (!is_array($replace)) {
			return $replace;
		}
		$version = jeedom::versionAlias($_version);
		foreach ($this->getCmd('info') as $cmd) {
			$replace['#' . $cmd->getLogicalId() . '_history#'] = '';
			$replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
			$replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd(null, 2);
			$replace['#' . $cmd->getLogicalId() . '_collect#'] = $cmd->getCollectDate();
			if ($cmd->getIsHistorized() == 1) {
				$replace['#' . $cmd->getLogicalId() . '_history#'] = 'history cursor';
			}

		}
		$refresh = $this->getCmd(null, 'refresh');
		if (is_object($refresh)) {
			$replace['#refresh_id#'] = $refresh->getId();
		}
		if ($this->getConfiguration('datasource') == 'ecowatt') {
			return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'ecowatt_ecowatt', 'ecowatt')));
		}
		if ($this->getConfiguration('datasource') == 'ejp') {
			return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'ecowatt_ejp', 'ecowatt')));
		}
		if ($this->getConfiguration('datasource') == 'tempo') {
			return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'ecowatt_tempo', 'ecowatt')));
		}
	}

	/*     * **********************Getteur Setteur*************************** */
}

class ecowattCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	public function execute($_options = array()) {
		if ($this->getLogicalId() == 'refresh') {
			$eqLogic = $this->getEqLogic();
			$eqLogic->updateInfo();
		}

	}

	/*     * **********************Getteur Setteur*************************** */
}

?>
