<?php
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\BaseDocumentClass
 * @api
 */
class BaseDocumentClass
{
	/**
	 * @var \Change\Documents\Generators\Compiler
	 */
	protected $compiler;

	/**
	 * @param \Change\Documents\Generators\Compiler $compiler
	 * @param \Change\Documents\Generators\Model $model
	 * @param string $compilationPath
	 * @return boolean
	 */
	public function savePHPCode(\Change\Documents\Generators\Compiler $compiler, \Change\Documents\Generators\Model $model, $compilationPath)
	{
		$code = $this->getPHPCode($compiler, $model);
		$nsParts = explode('\\', $model->getNameSpace());
		$nsParts[] = $model->getShortBaseDocumentClassName() . '.php';
		array_unshift($nsParts, $compilationPath);
		\Change\Stdlib\File::write(implode(DIRECTORY_SEPARATOR, $nsParts), $code);
		return true;
	}

	/**
	 * @param \Change\Documents\Generators\Compiler $compiler
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	public function getPHPCode(\Change\Documents\Generators\Compiler $compiler, \Change\Documents\Generators\Model $model)
	{
		$this->compiler = $compiler;
		$code = '<' . '?php' . PHP_EOL . 'namespace ' . $model->getCompilationNameSpace() . ';' . PHP_EOL;
		if (!$model->getInject())
		{
			$code .= '/**
 * @name ' . $model->getBaseDocumentClassName() . '
 * @method ' . $model->getModelClassName() . ' getDocumentModel()
 */' . PHP_EOL;
		}

		$extendModel = $model->getExtendModel();
		$extend = $extendModel ? $extendModel->getDocumentClassName() : '\Change\Documents\AbstractDocument';

		$interfaces = array();

		// implements , 
		if ($model->getLocalized())
		{
			$interfaces[] = '\Change\Documents\Interfaces\Localizable';
		}
		if ($model->getEditable())
		{
			$interfaces[] = '\Change\Documents\Interfaces\Editable';
		}
		if ($model->getPublishable())
		{
			$interfaces[] = '\Change\Documents\Interfaces\Publishable';
		}
		if ($model->getUseVersion())
		{
			$interfaces[] = '\Change\Documents\Interfaces\Versionable';
		}

		if (count($interfaces))
		{
			$extend .= ' implements ' . implode(', ', $interfaces);
		}

		$code .= 'abstract class ' . $model->getShortBaseDocumentClassName() . ' extends ' . $extend . PHP_EOL;
		$code .= '{' . PHP_EOL;
		$properties = $this->getMemberProperties($model);


		if (count($properties))
		{
			if (!$model->checkStateless())
			{
				$code .= $this->getMembers($model, $properties);
			}

			foreach ($properties as $property)
			{
				/* @var $property \Change\Documents\Generators\Property */
				if ($property->getStateless() || $model->checkStateless())
				{
					$code .= $this->getPropertyStatelessCode($model, $property);
				}
				elseif ($property->getType() === 'DocumentArray')
				{
					$code .= $this->getPropertyDocumentArrayAccessors($model, $property);
				}
				elseif ($property->getType() === 'Document')
				{
					$code .= $this->getPropertyDocumentAccessors($model, $property);
				}
				elseif ($property->getLocalized())
				{
					$code .= $this->getPropertyLocalizedCode($model, $property);
				}
				else
				{
					$code .= $this->getPropertyAccessors($model, $property);
				}
			}
		}

		if ($model->getLocalized())
		{
			$code .= $this->getLocalizableInterface($model);
		}

		if ($model->getPublishable())
		{
			$code .= $this->getPublishableInterface($model);
		}

		if ($model->getEditable())
		{
			$code .= $this->getEditableInterface($model);
		}

		$code .= '}' . PHP_EOL;
		$this->compiler = null;
		return $code;
	}


	/**
	 * @param mixed $value
	 * @param boolean $removeSpace
	 * @return string
	 */
	protected function escapePHPValue($value, $removeSpace = true)
	{
		if ($removeSpace)
		{
			return str_replace(array(PHP_EOL, ' ', "\t"), '', var_export($value, true));
		}
		return var_export($value, true);
	}


	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getEditableInterface($model)
	{
		$code = '
	/**
	 * @return integer
	 */
	public function nextDocumentVersion()
	{
		$next = max(0, $this->getDocumentVersion()) + 1;
		$this->setDocumentVersion($next);
		return $next;
	}' . PHP_EOL;

		return $code;
	}

	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getPublishableInterface($model)
	{
		$code = '
	/**
	 * @var \Change\Documents\PublishableFunctions
	 */
	protected $publishableFunctions;

	/**
	 * @api
	 * @return \Change\Documents\PublishableFunctions
	 */
	public function getPublishableFunctions()
	{
		if ($this->publishableFunctions === null)
		{
			$this->publishableFunctions = new \Change\Documents\PublishableFunctions($this);
		}
		return $this->publishableFunctions;
	}' . PHP_EOL;

		return $code;
	}

	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getLocalizableInterface($model)
	{
		$class = $model->getDocumentLocalizedClassName();
		$code = '
	/**
	 * @var \Change\Documents\LocalizableFunctions
	 */
	protected $localizableFunctions;

	/**
	 * @return \Change\Documents\LocalizableFunctions
	 */
	public function getLocalizableFunctions()
	{
		if ($this->localizableFunctions === null)
		{
			$this->localizableFunctions = new \Change\Documents\LocalizableFunctions($this);
		}
		return $this->localizableFunctions;
	}

	/**
	 * @return ' . $class . '
	 */
	public function getCurrentLocalizedPart()
	{
	 	return $this->getLocalizableFunctions()->getCurrent();
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function isDeleted()
	{
		return $this->getCurrentLocalizedPart()->isDeleted();
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function isNew()
	{
		return $this->getCurrentLocalizedPart()->isNew();
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function hasModifiedProperties()
	{
		return parent::hasModifiedProperties() || $this->getCurrentLocalizedPart()->hasModifiedProperties();
	}

	/**
	 * @api
	 * @param string $propertyName
	 * @return boolean
	 */
	public function isPropertyModified($propertyName)
	{
		return parent::isPropertyModified($propertyName) || $this->getCurrentLocalizedPart()->isPropertyModified($propertyName);
	}

	/**
	 * @api
	 * @return string[]
	 */
	public function getModifiedPropertyNames()
	{
		return array_merge(parent::getModifiedPropertyNames(), $this->getCurrentLocalizedPart()->getModifiedPropertyNames());
	}

	/**
	 * @api
	 * @param string $propertyName
	 */
	public function removeOldPropertyValue($propertyName)
	{
		$localizedPart = $this->getCurrentLocalizedPart();
		if ($localizedPart->isPropertyModified($propertyName))
		{
			$localizedPart->removeOldPropertyValue($propertyName);
		}
		else
		{
			parent::removeOldPropertyValue($propertyName);
		}
	}' . PHP_EOL;
		return $code;
	}


	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return \Change\Documents\Generators\Property[]
	 */
	protected function getMemberProperties($model)
	{
		$properties = array();
		foreach ($model->getProperties() as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			if (!$property->getParent())
			{
				$properties[$property->getName()] = $property;
			}
		}
		return $properties;
	}

	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property[] $properties
	 * @return string
	 */
	protected function getMembers($model, $properties)
	{
		$resetProperties = array();
		if ($model->getLocalized())
		{
			$resetProperties[] = '		$this->getLocalizableFunctions()->reset();';
		}
		$code = '';
		foreach ($properties as $property)
		{
			/* @var $property \Change\Documents\Generators\Property */
			if ($property->getLocalized() || $property->getStateless())
			{
				continue;
			}
			$resetProperties[] = '		$this->' . $property->getName() . ' = null;';
			$code .= '
	/**
	 * @var ' . $this->getCommentaryMemberType($property) . '
	 */	
	private $' . $property->getName() . ';' . PHP_EOL;
		}

		$code .= '		
	/**
	 * @api
	 */
	public function reset()
	{
		parent::reset();' . PHP_EOL . implode(PHP_EOL, $resetProperties) . '
	}' . PHP_EOL;

		return $code;
	}

	/**
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	public function getCommentaryType($property)
	{
		switch ($property->getComputedType())
		{
			case 'Boolean' :
				return 'boolean';
			case 'Float' :
			case 'Decimal' :
				return 'float';
			case 'Integer' :
			case 'DocumentId' :
				return 'integer';
			case 'Date' :
			case 'DateTime' :
				return '\DateTime';
			case 'Document' :
			case 'DocumentArray' :
				if ($property->getDocumentType() === null)
				{
					return '\Change\Documents\AbstractDocument';
				}
				else
				{
					return $this->compiler->getModelByName($property->getDocumentType())->getDocumentClassName();
				}
			default:
				return 'string';
		}
	}


	/**
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	public function getCommentaryMemberType($property)
	{
		switch ($property->getType())
		{
			case 'Boolean' :
				return 'boolean';
			case 'Float' :
			case 'Decimal' :
				return 'float';
			case 'Integer' :
			case 'DocumentId' :
			case 'Document' :
			case 'DocumentArray' :
				return 'integer';
			case 'Date' :
			case 'DateTime' :
				return '\DateTime';
			default:
				return 'string';
		}
	}

	/**
	 * @param \Change\Documents\Generators\Property $property
	 * @param string $varName
	 * @return string
	 */
	protected function buildValConverter($property, $varName)
	{
		return $varName . ' = $this->convertToInternalValue(' . $varName . ', '.$this->escapePHPValue($property->getType()).')';
	}

	/**
	 * @param string $oldVarName
	 * @param string $newVarName
	 * @param string $type
	 * @return string
	 */
	protected function buildEqualsProperty($oldVarName, $newVarName, $type)
	{
		if ($type === 'Float' || $type === 'Decimal')
		{
			return 'abs(floatval(' . $oldVarName . ') - ' . $newVarName . ') <= 0.0001';
		}
		elseif ($type === 'Date' || $type === 'DateTime')
		{
			return $oldVarName . ' == ' . $newVarName;
		}
		else
		{
			return $oldVarName . ' === ' . $newVarName;
		}
	}

	/**
	 * @param string $oldVarName
	 * @param string $newVarName
	 * @param string $type
	 * @return string
	 */
	protected function buildNotEqualsProperty($oldVarName, $newVarName, $type)
	{
		if ($type === 'Float' || $type === 'Decimal')
		{
			return 'abs(floatval(' . $oldVarName . ') - ' . $newVarName . ') > 0.0001';
		}
		elseif ($type === 'Date' || $type === 'DateTime')
		{
			return $oldVarName . ' != ' . $newVarName;
		}
		else
		{
			return $oldVarName . ' !== ' . $newVarName;
		}
	}


	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function getPropertyAccessors($model, $property)
	{
		$code = '';
		$name = $property->getName();
		$var = '$' . $name;
		$mn = '$this->' . $name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);
		$code .= '
	/**
	 * @return ' . $ct . '
	 */
	public function get' . $un . '()
	{
		$this->load();
		return ' . $mn . ';
	}
	
	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . 'OldValue()
	{
		return $this->getOldPropertyValue(' . $en . ');
	}
	
	/**
	 * @param ' . $ct . ' ' . $var . '
	 */
	public function set' . $un . '(' . $var . ')
	{
		' . $this->buildValConverter($property, $var) . ';
		if ($this->getPersistentState() == \Change\Documents\DocumentManager::STATE_LOADING)
		{
			' . $mn . ' = ' . $var . ';
			return;
		}
		$this->load();
		if ($this->getPersistentState() != \Change\Documents\DocumentManager::STATE_LOADED)
		{
			' . $mn . ' = ' . $var . ';
		}
		elseif (' . $this->buildNotEqualsProperty($mn, $var, $property->getType()) . ')
		{
			if ($this->isPropertyModified(' . $en . '))
			{
				$loadedVal = $this->getOldPropertyValue(' . $en . ');
				if (' . $this->buildEqualsProperty('$loadedVal', $var, $property->getType()) . ')
				{
					$this->removeOldPropertyValue(' . $en . ');
				}
			}
			else
			{
				$this->setOldPropertyValue(' . $en . ', ' . $mn . ');
			}
			' . $mn . ' = ' . $var . ';
			$this->propertyChanged(' . $en . ');
		}
	}' . PHP_EOL;
		$code .= $this->getPropertyExtraGetters($model, $property);
		return $code;
	}



	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function getPropertyLocalizedCode($model, $property)
	{
		$code = array();
		$name = $property->getName();
		$var = '$' . $name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);
		$code[] = '
	/**
	 * @return ' . $ct . '
	 */
	public function get' . $un . '()
	{
		$localizedPart = $this->getCurrentLocalizedPart();
		return $localizedPart->get' . $un . '();
	}

	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . 'OldValue()
	{
		return $this->getCurrentLocalizedPart()->get' . $un . 'OldValue();
	}';

		if ($name === 'LCID')
		{
			$code[] = '
	/**
	 * Has no effect.
	 * @see \Change\Documents\DocumentManager::pushLCID()
	 * @param ' . $ct . ' ' . $var . '
	 */
	public function set' . $un . '(' . $var . ')
	{
		return;
	}';
		}
		else
		{
			$code[] = '
	/**
	 * @param ' . $ct . ' ' . $var . '
	 */
	public function set' . $un . '(' . $var . ')
	{
		$localizedPart = $this->getCurrentLocalizedPart();
		if ($localizedPart->set' . $un . '(' . $var . '))
		{
			$this->propertyChanged(' . $en . ');
		}
	}';
		}
		$code[] = $this->getPropertyExtraGetters($model, $property);
		return implode(PHP_EOL, $code);
	}

	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function getPropertyStatelessCode($model, $property)
	{
		if (in_array($property->getName(), array('creationDate', 'modificationDate')))
		{
			return '';
		}
		$code = array();
		$name = $property->getName();
		$var = '$' . $name;
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);
		$code[] = '
	/**
	 * @return ' . $ct . '
	 */
	abstract public function get' . $un . '();

	/**
	 * @param ' . $ct . ' ' . $var . '
	 */
	abstract public function set' . $un . '(' . $var . ');';

		if ($property->getType() === 'Document')
		{
			$code[] = '
	/**
	 * @return integer|null
	 */
	public function get' . $un . 'Id()
	{
		' . $var . ' = $this->get' . $un . '();
		return ' . $var . ' instanceof \Change\Documents\AbstractDocument ? ' . $var . '->getId() : null;
	}';
		}
		elseif ($property->getType() === 'DocumentArray')
		{
			$code[] = '
	/**
	 * @return integer[]
	 */
	public function get' . $un . 'Ids()
	{
		$result = array();
		' . $var . ' = $this->get' . $un . '();
		if (is_array(' . $var . '))
		{
			foreach (' . $var . ' as $o)
			{
				if ($o instanceof \Change\Documents\AbstractDocument) {$result[] = $o->getId();}
			}
		}
		return $result;
	}';
		}

		$code[] = $this->getPropertyExtraGetters($model, $property);
		return implode(PHP_EOL, $code);
	}

	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function getPropertyExtraGetters($model, $property)
	{
		$code = '';
		$name = $property->getName();
		$var = '$' . $name;
		$un = ucfirst($name);
		$ct = $this->getCommentaryType($property);

		if ($property->getType() === 'XML')
		{
			$code .= '
	/**
	 * @return \DOMDocument
	 */
	public function get' . $un . 'DOMDocument()
	{
		$document = new \DOMDocument("1.0", "UTF-8");
		if ($this->get' . $un . '() !== null) {$document->loadXML($this->get' . $un . '());}
		return $document;
	}
		
	/**
	 * @param \DOMDocument $document
	 */
	public function set' . $un . 'DOMDocument($document)
	{
		 $this->set' . $un . '($document && $document->documentElement ? $document->saveXML() : null);
	}' . PHP_EOL;
		}
		elseif ($property->getType() === 'JSON')
		{
			$code .= '	
	/**
	 * @return array
	 */
	public function getDecoded' . $un . '()
	{
		' . $var . ' = $this->get' . $un . '();
		return ' . $var . ' === null ? ' . $var . ' : json_decode(' . $var . ', true);
	}' . PHP_EOL;
		}
		elseif ($property->getType() === 'Object')
		{
			$code .= '	
	/**
	 * @return mixed
	 */
	public function getDecoded' . $un . '()
	{
		' . $var . ' = $this->get' . $un . '();
		return ' . $var . ' === null ? ' . $var . ' : unserialize(' . $var . ');
	}' . PHP_EOL;
		}
		elseif ($property->getType() === 'DocumentId')
		{
			$code .= '
	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . 'Instance()
	{
		return $this->getDocumentManager()->getDocumentInstance($this->get' . $un . '());
	}' . PHP_EOL;
		}
		return $code;
	}

	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function getPropertyDocumentAccessors($model, $property)
	{
		$code = '';
		$name = $property->getName();
		$mn = '$this->' . $name;
		$var = '$' . $name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);

		$code .= '	
	/**
	 * @return integer|null
	 */
	public function get' . $un . 'OldValueId()
	{
		return $this->getOldPropertyValue(' . $en . ');
	}
	
	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . 'OldValue()
	{
		$oldId = $this->get' . $un . 'OldValueId();
		return ($oldId !== null) ? $this->getDocumentManager()->getDocumentInstance($oldId) : null;
	}
			
	/**
	 * @param ' . $ct . ' ' . $var . '
	 */
	public function set' . $un . '(' . $var . ' = null)
	{
		if ($this->getPersistentState() == \Change\Documents\DocumentManager::STATE_LOADING)
		{
			' . $mn . ' = ' . $var . ' === null ? null : intval(' . $var . ');
			return;
		}
		if (' . $var . ' !== null && !(' . $var . ' instanceof ' . $ct . '))
		{
			throw new \InvalidArgumentException(\'Argument 1 passed to __METHOD__ must be an ' . $ct . '\', 52005);
		}
		$this->load();
		$newId = (' . $var . ' !== null) ? ' . $var . '->getId() : null;
		if ($this->getPersistentState() != \Change\Documents\DocumentManager::STATE_LOADED)
		{
			' . $mn . ' = $newId;
		}
		elseif (' . $mn . ' !== $newId)
		{
			if ($this->isPropertyModified(' . $en . '))
			{
				$loadedVal = $this->getOldPropertyValue(' . $en . ');
				if ($loadedVal !== $newId)
				{
					$this->removeOldPropertyValue(' . $en . ');
				}
			}
			else
			{
				$this->setOldPropertyValue(' . $en . ', ' . $mn . ');
			}
			' . $mn . ' = $newId;
			$this->propertyChanged(' . $en . ');
		}
	}

	/**
	 * @return integer
	 */
	public function get' . $un . 'Id()
	{
		$this->load();
		return ' . $mn . ';
	}
	
	/**
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . '()
	{
		if ($this->getPersistentState() == \Change\Documents\DocumentManager::STATE_SAVING)
		{
			return ' . $mn . ';
		}
		$this->load();
		return (' . $mn . ') ? $this->getDocumentManager()->getDocumentInstance(' . $mn . ') : null;
	}' . PHP_EOL;

		return $code;
	}

	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param \Change\Documents\Generators\Property $property
	 * @return string
	 */
	protected function getPropertyDocumentArrayAccessors($model, $property)
	{
		$code = '';
		$name = $property->getName();
		$var = '$' . $name;
		$mn = '$this->' . $name;
		$en = $this->escapePHPValue($name);
		$ct = $this->getCommentaryType($property);
		$un = ucfirst($name);
		$code .= '
	protected function checkLoaded' . $un . '()
	{
		$this->load();
		if (!is_array(' . $mn . '))
		{
			if (' . $mn . ')
			{
				' . $mn . ' = $this->getDocumentManager()->getPropertyDocumentIds($this, ' . $en . ');
			}
			else
			{
				' . $mn . ' = array();
			}
		}
	}
					
	/**
	 * @return integer[]
	 */
	public function get' . $un . 'OldValueIds()
	{
		$result = $this->getOldPropertyValue(' . $en . ');
		return (is_array($result)) ? $result : array();
	}
						
	/**
	 * @return ' . $ct . '[]
	 */
	public function get' . $un . 'OldValue()
	{
		$dm = $this->getDocumentManager();
		return array_map(function ($documentId) use ($dm) {return $dm->getDocumentInstance($documentId);}, $this->get' . $un . 'OldValueIds());
	}

	/**
	 * @return ' . $ct . '[]
	 */
	public function get' . $un . '()
	{
		if ($this->getPersistentState() == \Change\Documents\DocumentManager::STATE_SAVING)
		{
			return is_array(' . $mn . ') ? count(' . $mn . ') : ' . $mn . ';
		}
		$this->checkLoaded' . $un . '();
		$dm = $this->getDocumentManager();
		$documents = array();
		foreach(' . $mn . ' as $documentId)
		{
			$document = $dm->getDocumentInstance($documentId);
			if ($document instanceof ' . $ct . ')
			{
				$documents[] = $document;
			}
		}
		return $documents;
	}

	/**
	 * @param ' . $ct . '[] $newValueArray
	 * @throws \InvalidArgumentException
	 * @return void
	 */
	public function set' . $un . '($newValueArray)
	{
		if ($this->getPersistentState() == \Change\Documents\DocumentManager::STATE_LOADING)
		{
			' . $mn . ' = intval($newValueArray);
			return;
		}
		if (!is_array($newValueArray))
		{
			throw new \InvalidArgumentException(\'Argument 1 passed to __METHOD__ must be an array\', 52005);
		}
		$this->checkLoaded' . $un . '();

		$newValueIds = array_map(function($newValue) {
			if ($newValue instanceof ' . $ct . ')
			{
				return $newValue->getId();
			}
			else
			{
				throw new \InvalidArgumentException(\'Argument 1 passed to __METHOD__ must be an ' . $ct . '[]\', 52005);
			}
		}, $newValueArray);			
		$this->setInternal' . $un . 'Ids($newValueIds);
	}
			
			
	/**
	 * @param ' . $ct . ' ' . $var . '
	 */
	public function add' . $un . '(' . $ct . ' ' . $var . ')
	{
		$this->set' . $un . 'AtIndex(' . $var . ', -1);
	}	

	/**
	 * @param ' . $ct . ' ' . $var . '
	 * @param integer $index
	 */
	public function set' . $un . 'AtIndex(' . $ct . ' ' . $var . ', $index = 0)
	{
		$this->checkLoaded' . $un . '();
		$newId = ' . $var . '->getId();
		if (!in_array($newId, ' . $mn . '))
		{
			$newValueIds = ' . $mn . ';
			$index = intval($index);
			if ($index < 0 || $index > count($newValueIds))
			{
				$index = count($newValueIds);
			}
			$newValueIds[$index] = $newId;		
			$this->setInternal' . $un . 'Ids($newValueIds);
		}	
	}

	/**
	 * @param ' . $ct . ' ' . $var . '
	 * @return boolean
	 */
	public function remove' . $un . '(' . $ct . ' ' . $var . ')
	{
		$index = $this->getIndexof' . $un . '(' . $var . ');
		if ($index !== -1)
		{
			return $this->remove' . $un . 'ByIndex($index);
		}
		return false;
	}

	/**
	 * @param integer $index
	 * @return boolean
	 */
	public function remove' . $un . 'ByIndex($index)
	{
		$this->checkLoaded' . $un . '();
		if (isset(' . $mn . '[$index]))
		{
			$newValueIds = ' . $mn . ';
			unset($newValueIds[$index]);	
			$this->setInternal' . $un . 'Ids($newValueIds);
			return true;
		}
		return false;
	}

	public function removeAll' . $un . '()
	{
		$this->checkLoaded' . $un . '();
		$this->setInternal' . $un . 'Ids(array());
	}

	/**
	 * @param integer[] $newValueIds
	 */
	protected function setInternal' . $un . 'Ids(array $newValueIds)
	{
		if ($this->getPersistentState() != \Change\Documents\DocumentManager::STATE_LOADED)
		{
			' . $mn . ' = $newValueIds;
		}
		elseif (' . $mn . ' != $newValueIds)
		{
			if ($this->isPropertyModified(' . $en . '))
			{
				$loadedVal = $this->getOldPropertyValue(' . $en . ');
				if ($loadedVal == $newValueIds)
				{
					$this->removeOldPropertyValue(' . $en . ');
				}
			}
			else
			{
				$this->setOldPropertyValue(' . $en . ', ' . $mn . ');
			}
			' . $mn . ' = $newValueIds;
			$this->propertyChanged(' . $en . ');
		}
	}

	/**
	 * @param integer $index
	 * @return ' . $ct . '|null
	 */
	public function get' . $un . 'ByIndex($index)
	{
		$this->checkLoaded' . $un . '();
		return isset(' . $mn . '[$index]) ?  $this->getDocumentManager()->getDocumentInstance(' . $mn . '[$index]) : null;
	}
	
	/**
	 * @return integer[]
	 */
	public function get' . $un . 'Ids()
	{
		$this->checkLoaded' . $un . '();
		return ' . $mn . ';
	}

	/**
	 * @param ' . $ct . ' ' . $var . '
	 * @return integer
	 */
	public function getIndexof' . $un . '(' . $ct . ' ' . $var . ')
	{
		$this->checkLoaded' . $un . '();
		$valueId = ' . $var . '->getId();
		$index = array_search($valueId, ' . $mn . ');
		return $index !== false ? $index : -1;
	}' . PHP_EOL;
		return $code;
	}
}