<?php

namespace iHomefinder\Api;

abstract class Resource {
	
	protected $auth;
	private $hydratedFields = [];
	private $dirtyFields = [];
	
	protected abstract function getFieldNames(): array;
	
	public function __construct(Authentication $auth) {
		$this->auth = $auth;
		ResourceManager::getInstance()->addResource($this);
	}

	protected function getter($name, $class_) {
		if(!$this->isHydratedField($name)) {
			$this->init($this->getUrl());
		}
		$value = $this->getHydratedFieldValue($name);
		if(is_a($value, $class_)) {
			$value = ResourceManager::getInstance()->getOrCreateInstance($auth, $class_, $value);
			$this->setHydratedField($name, $value);
		}
		return $value;
	}
	
	protected function setter($name, $value) {
		if($this->getHydratedFieldValue($name) === null || ($this->getHydratedFieldValue($name) !== null && !$this->getHydratedFieldValue($name)->equals(value))) {
			$this->addDirtyField($name);
			$this->setHydratedField($name, $value);
		}
	}
	
	protected function init($url) {
		if($url !== null) {
			$data = (new HttpRequest($this->auth))
				->setUrl($url)
				->setMethod("GET")
				->getResponse()
				->getData()
			;
			$this->hydrate($data);
			//Hydrate known fields with null. This prevents repeated attempts to hydrate object.
			if($this->getFieldNames() !== null) {
				foreach($this->getFieldNames() as $name) {
					if(!$this->isDirtyField($name)) {
						if(!property_exists($data, $name)) {
							$this->setHydratedField($name, null);
						}
					}
				}
			}
		}
	}
	
	public function hydrate($data) {
		if($data !== null) {
			foreach($data as $name => $value) {
				if(!$this->isDirtyField($name)) {
					$this->setHydratedField($name, $value);
				}
			}
		}
	}
	
	private function getDirtyFields(): array {
		return $this->dirtyFields;
	}
	
	private function hasDirtyFields(): bool {
		return !empty($this->dirtyFields);
	}
	
	private function addDirtyField($name) {
		$this->dirtyFields[] = $name;
	}
	
	private function isDirtyField($name): bool {
		return in_array($name, $this->dirtyFields);
	}
	
	private function getDirtyFieldsValues(): array {
		$results = [];
		$names = $this->getDirtyFields();
		foreach($names as $name) {
			$value = $this->getHydratedFieldValue($name);
			$results[$name] = $value;
		}
		return $results;
	}
	
	private function getHydratedFields(): array {
		return $this->hydratedFields;
	}
	
	private function setHydratedField(string $name, $value) {
		$this->hydratedFields[$name] = $value;
	}
	
	private function getHydratedFieldValue(string $name) {
		$result = null;
		if($this->isHydratedField($name)) {
			$result = $this->hydratedFields[$name];
		}
		return $result;
	}
	
	private function isHydratedField(string $name): bool {
		return array_key_exists($name, $this->getHydratedFields());
	}
	
	private function getLinks() {
		return $this->getHydratedFieldValue("links");
	}
	
	public function getUrl() {
		$result = null;
		$links = $this->getLinks();
		if($links !== null) {
			foreach($links as $link) {
				if($link->rel === "self") {
					$result = $link->href;
					break;
				}
			}
		}
		return $result;
	}
	
	public function isTransient(): bool {
		return getUrl() === null;
	}
	
	protected function saveHelper(string $createUrl) {
		if(hasDirtyFields()) {
			$url = null;
			$method = null;
			if(isTransient()) {
				$url = $createUrl;
				$method = "POST";
			} else {
				$url = $this->getUrl();
				$method = "PUT";
			}
			$query = (new Query())
				->select($this->getDirtyFields())
				->equal($this->getDirtyFieldsValues())
			;
			$data = (new HttpRequest($auth))
				->setUrl($url)
				->setMethod($method)
				->addQuery($query)
				->getResponse()
				->getData()
			;
			hydrate($data);
		}
	}
	
}