<?php

namespace ApiInputBundle\Input;

use ApiInputBundle\Exception\BadMethodCallException;
use ApiInputBundle\Exception\InvalidArgumentException;
use ApiInputBundle\Exception\UnexpectedValueException;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Input
 */
class TypeField
{
    /** @var string */
    const TYPE_COLLECTION = 'collection';

    /** @var string */
    const TYPE_MIXED = 'mixed';

    /** @var string */
    const TYPE_NUMBER = 'number';

    /** @var string */
    const TYPE_TEXT = 'text';

    /** @var array */
    protected static $fieldTypes = [
        self::TYPE_COLLECTION,
        self::TYPE_MIXED,
        self::TYPE_NUMBER,
        self::TYPE_TEXT,
    ];

    /** @var string */
    protected $name;

    /** @var string|Type */
    protected $type;

    /** @var string|Type|null */
    protected $childType;

    /** @var bool */
    protected $isRequired = false;

    /** @var bool */
    protected $allowAdd = false;

    /** @var bool */
    protected $allowDelete = false;

    /**
     * Constructor
     *
     * @param string      $name name
     * @param string|Type $type type
     *
     * @throws UnexpectedValueException
     */
    public function __construct($name, $type)
    {
        $this->name = $name;

        if (!in_array($type, self::$fieldTypes)) {
            throw new UnexpectedValueException(sprintf('Type "%s" doesn\'t exist', $type));
        }
        $this->type = $type;
    }

    /**
     * Set child type
     *
     * @param string|Type|null $childType child type
     *
     * @return self
     *
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function setChildType($childType)
    {
        if ($this->type != self::TYPE_COLLECTION) {
            throw new InvalidArgumentException('This field isn\'t a collection');
        }
        if (!in_array($childType, self::$fieldTypes) && !($childType instanceof Type)) {
            throw new UnexpectedValueException(sprintf('Child type "%s" doesn\'t exist', $childType));
        }
        $this->childType = $childType;

        return $this;
    }

    /**
     * Update object
     *
     * @param object $object object
     * @param array  $data   data
     *
     * @return self
     *
     * @throws BadMethodCallException
     */
    public function updateObject($object, $data)
    {
        $setMethodName = 'set' . ucfirst($this->name);
        $this->checkMethodName($object, $setMethodName);
        $getMethodName = 'get' . ucfirst($this->name);
        $this->checkMethodName($object, $getMethodName);

        if ($this->isRequired()) {
            // @TODO: add an error (field is required)
        }

        if ($this->type == self::TYPE_COLLECTION) {
            if (!is_array($data)) {
                // @TODO: add an error (bad structure - array required for collection type)
                $data = [];
            }
            if ($this->childType instanceof Type) {
                $currentData = $object->$getMethodName();

                $currentItems = [];
                $getIdMethodName = 'get' . ucfirst($this->type->getIdFieldName());
                foreach ($currentData as $currentItem) {
                    $this->checkMethodName($currentItem, $getIdMethodName);
                    $currentItems[$currentItem->$getIdMethodName()] = $currentItem;
                }

                $idName = $this->type->getIdName();
                $childObjects = new ArrayCollection();
                foreach ($data as $item) {
                    if (array_key_exists($idName, $item)) {
                        $id = $item[$idName];
                        if (array_key_exists($id, $currentItems)) {
                            $childObject = $currentItems[$id];
                            unset($currentItems[$id]);
                            $this->childType->updateObject($childObject, $item);
                            $this->childType->addToEdit($childObject);
                        } else {
                            $childObject = $this->childType->createObject();
                            $this->childType->updateObject($childObject, $item);
                            $this->childType->addToAdd($childObject);
                            // @TODO: add an error (there is no such children item in DB with this ID)
                        }
                    } else {
                        $childObject = $this->childType->createObject();
                        $this->childType->updateObject($childObject, $item);
                        $this->childType->addToAdd($childObject);
                    }
                    $childObjects->add($childObject);
                }

                foreach ($currentItems as $childObject) {
                    $this->childType->addToDelete($childObject);
                }

                $data = $childObjects;
            } else {
                foreach ($data as $key => $value) {
                    if ($this->childType == self::TYPE_NUMBER && is_numeric($value)) {
                        $data[$key] = + $value;
                    }
                }
            }
        } else {
            if ($this->type == self::TYPE_NUMBER && is_numeric($data)) {
                $data = + $data;
            }
        }
        $object->$setMethodName($data);

        return $this;
    }

    /**
     * Set required
     *
     * @param bool $isRequired is required
     *
     * @return self
     */
    public function setRequired($isRequired = true)
    {
        $this->isRequired = (bool) $isRequired;

        return $this;
    }

    /**
     * Is required
     *
     * @return bool
     */
    public function isRequired()
    {
        return $this->isRequired;
    }

    /**
     * Set allow add
     *
     * @param bool $allowAdd allow add
     *
     * @return self
     */
    public function setAllowAdd($allowAdd = true)
    {
        $this->allowAdd = (bool) $allowAdd;

        return $this;
    }

    /**
     * Allow add
     *
     * @return bool
     */
    public function allowAdd()
    {
        return $this->allowAdd;
    }

    /**
     * Set allow delete
     *
     * @param bool $allowDelete allow delete
     *
     * @return self
     */
    public function setAllowDelete($allowDelete = true)
    {
        $this->allowDelete = (bool) $allowDelete;

        return $this;
    }

    /**
     * Allow delete
     *
     * @return bool
     */
    public function allowDelete()
    {
        return $this->allowDelete;
    }

    /**
     * Check method name
     *
     * @param object $object     object
     * @param string $methodName method name
     *
     * @return self
     *
     * @throws BadMethodCallException
     */
    protected function checkMethodName($object, $methodName)
    {
        if (!method_exists($object, $methodName)) {
            throw new BadMethodCallException(sprintf('Class "%s" doesn\'t have "%s" method', get_class($object),
                $methodName));
        }

        return $this;
    }
}
