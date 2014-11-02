<?php
require_once('ReplicationDatabase.php');

class WikidataNoLabelsQuery {
	///
	/// Properties
	///

	/**
	 * Query results
	 * @var array
	 */
	public $results;

	/**
	 * Query
	 * @var string
	 */
	public $query;


	/**
	 * Language
	 * @var string
	 */
	public $language;

	/**
	 * The languages to print labels
	 * @var array
	 */
	public $labelsToFetchLanguages;

	/**
	 * Initializes a new instance of the WikidataNoLabelsQuery class
	 *
	 * @param string $query The WDQ query to run
	 * @param string $language The language to select items without labels in
	 * @param array $labelsToFetchLanguages The languages to print labels
	 */
	function __construct ($query, $language, $labelsToFetchLanguages) {
		$this->query = $query;
		$this->language = $language;
		$this->labelsToFetchLanguages = $labelsToFetchLanguages;
	}

	///
	/// Query logic
	///

	/**
	 * Runs the queries
	 *
	 * After this method has ben called, $this->results is populated.
	 */
	function run () {
		if (!self::isValidWDQ($this->query)) {
			throw new Exception("WDQ isn't valid.");
		}

		//Computes the difference between the items of the WDQ query and the items having a label in the target language
		$items = self::queryWDQ($this->query);
		$itemsInTargetLanguage = $this->getItemsWithLabelIn($this->language, $items);
		$items = array_diff($items, $itemsInTargetLanguage);

		//Prepare a bare results array
		foreach ($items as $item) {
			$result = array('id' => $item);
			foreach ($this->labelsToFetchLanguages as $labelLanguage) {
				$result['label' . $labelLanguage] = '';
			}
			$this->results[$item] = $result;
		}

		//Fills label in extra languages
		foreach ($this->labelsToFetchLanguages as $labelLanguage) {
			$labelLanguageKey = 'label' . $labelLanguage;
			$labels = $this->getItemsWithLabelIn($labelLanguage, $items, true);
			foreach ($labels as $label) {
				$key = $label['id'];
				$this->results[$key][$labelLanguageKey] = $label['label'];
			}
		}
	}

	///
	/// Replication databases label information helper methods
	///

	/**
	 * Get entities with label in the specified language
	 *
	 * @param string $language the language to gets labels defined in
	 * @param string $itemsHaystack the items haystack
	 * @param bool $queryLabels queries also the labels
	 * @return array
	 */
	function getItemsWithLabelIn ($language, $itemsHaystack, $queryLabels = false) {
		//TODO: sanitize the language, should be a valid language code
		//TODO: sanitize the haystack, should only contain numeric id
		$what = $queryLabels ? 'term_entity_id, term_text' : 'term_entity_id';
		$clauseIn = join(', ', $itemsHaystack);
		$sql = "SELECT $what
			FROM wb_terms
		        WHERE term_type = 'label' AND term_language = '$language' AND term_entity_id IN ($clauseIn)";

		$db = ReplicationDatabaseFactory::get('wikidatawiki');
		$result = $db->query($sql);
		$items = array();
		while ($row = $result->fetch_assoc()) {
			if ($queryLabels) {
				$items[] = array(
					'id' => $row['term_entity_id'],
					'label' => $row['term_text']
				);
			} else {
				$items[] = $row['term_entity_id'];
			}
		}
		return $items;
	}

	///
	/// WDQ helper methods
	///

	/**
	 * Determines if the specified query is a valid one.
	 *
	 * @param string $query the WDQ
	 * @return bool true if the query is valid; otherwise, false.
	 */
	static function isValidWDQ ($query) {
		return true;
	}

	/**
	 * Queries the WDQ server
	 *
	 * @param string $query the WDQ
	 * @return array WDQ result
	 */
	static function queryWDQ ($query) {
		$url = 'http://wdq.wmflabs.org/api?q=' . urlencode($query);
		$data = json_decode(file_get_contents($url));
		if ($data->status->error != 'OK') {
			throw new Exception($data->status->error);
		}
		return $data->items;
	}
}
