<?php
namespace CDS\Hook;
class Rule implements \CDS\Hook {
	private $rule;

	private $dataStorage;

	public function __construct($rule, $data, $objectStorage) {
		$this->rule = $rule;
		$this->dataStorage = $objectStorage;
		$this->dataFunction = new DataFunction($objectStorage, $data);
	}

	public function run(\DomElement $element) {
		foreach ($this->rule->rules as $name => $value) {
			if ($this->$name($value, $element) === false) break;
		}
		return $element;
	}

	public function content($val, $element) {
		$value = $this->parseValue($val, $element);
		if ($element instanceof \DomElement) {
			$element->firstChild->nodeValue = implode('', $value);
		}
	}

	private function findMatchingPos($string, $char, $start = 0, $escape = '\\') {
		$pos = $start+1;

		while (true) {
			$end = strpos($string, $char, $pos);
			if ($string[$end-1] === $escape) $pos = $end+1;
			else return $end;
		}

	}

	private function parseValue($function, $element) {
		$function = trim($function);

		$result = [];
		$finalPos = 0;

		if ($function[0] == '\'' || $function[0] == '"') {
			$finalPos = $this->findMatchingPos($function, $function[0]);
			$string = substr($function, 1, $finalPos-1);
			//Now remove escape characters
			$result[] = str_replace('\\' . $function[0], $function[0], $string);
		}
		else {
			$open = strpos($function, '(');
			$close = strpos($function, ')', $open);
			$finalPos = $close;
			$name = substr($function, 0, $open);
			$params = substr($function, $open+1, $close-$open-1);

			if (is_callable([$this->dataFunction, $name])) {
				$data = $this->dataFunction->$name($params, $element);	
				if (is_array($data)) $result += $data;
				else $result[] = $data;
			} 
		}

		$remaining = trim(substr($function, $finalPos+1));
		if (strlen($remaining) > 0 && $remaining[0] == ',') $result = array_merge($result, $this->parseValue(trim(substr($remaining, 1)), $element));

		return $result;
	}


	public function repeat($val, $element) {		
		$data = $this->parseValue($val, $element);
		//$this->dataStorage[$element] = $data;

		foreach ($data as $iteration) {
			$clone = $element->cloneNode(true);
			$this->dataStorage[$clone] = $iteration;
			$element->parentNode->insertBefore($clone, $element);

			//Re-run the hook on the new element, but use the iterated data
			$newRule = clone $this->rule;

			//Don't run repeat on the clones element or it will loop forever
			unset($newRule->rules['repeat']);

			$hook = new Rule($newRule, $iteration, $this->dataStorage);
			$hook->run($clone);

		}

		//Remove the original element so only the ones that have been looped over will show
		$element->parentNode->removeChild($element);

		return false;
	}

	public function display($val, $element) {
		if (strtolower($val) === 'none') $element->parentNode->removeChild($element);
	}

}

