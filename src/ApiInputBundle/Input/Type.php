<?php

namespace ApiInputBundle\Input;

/**
 * Input
 */
abstract class Type
{
    /** @var string */
    protected $idName = 'id';

    /** @var string */
    protected $idFieldName = 'id';

    /** @var TypeField[] */
    protected $fields = [];

    /** @var Type[] */
    protected $childTypes = [];

    /** @var object[] */
    protected $toAdd = [];

    /** @var object[] */
    protected $toEdit = [];

    /** @var object[] */
    protected $toDelete = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->buildForm();
    }

    /**
     * Update object
     *
     * @param object $object object
     * @param array  $data   data
     *
     * @return self
     */
    public function updateObject($object, $data)
    {
        foreach ($this->fields as $name => $field) {
            $field->updateObject($object, array_key_exists($name, $data) ? $data[$name] : null);
        }

        return $this;
    }

    /**
     * Create entity/document object
     *
     * @return object
     */
    abstract public function createObject();

    /**
     * Build form
     */
    abstract protected function buildForm();

    /**
     * Add
     *
     * @param string $name    name
     * @param string $type    type
     * @param array  $options options
     *
     * @return self
     */
    protected function add($name, $type = TypeField::TYPE_MIXED, array $options = [])
    {
        $fieldName = !empty($options['field_name']) && is_string($options['field_name']) ?
            $options['field_name'] : $name;

        $child = $this->fields[$name] = new TypeField($fieldName, $type);

        if ($type == TypeField::TYPE_COLLECTION) {
            $child->setChildType(array_key_exists('child_type', $options) ? $options['child_type'] : null);
            if ($options['child_type'] instanceof Type) {
                $this->childTypes[] = $options['child_type'];
            }
        }
        if (array_key_exists('required', $options)) {
            $child->setRequired($options['required']);
        }
        if (array_key_exists('allow_add', $options)) {
            $child->setAllowAdd($options['allow_add']);
        }
        if (array_key_exists('allow_delete', $options)) {
            $child->setAllowDelete($options['allow_delete']);
        }

        return $this;
    }

    /**
     * Add ID
     *
     * @param string      $name      name
     * @param string|null $fieldName field name
     *
     * @return self
     */
    protected function addId($name, $fieldName = null)
    {
        $this->idName = $name;
        $this->idFieldName = empty($fieldName) ? $name : $fieldName;

        return $this;
    }

    /**
     * Get ID name
     *
     * @return string
     */
    public function getIdName()
    {
        return $this->idName;
    }

    /**
     * Get ID field name
     *
     * @return string
     */
    public function getIdFieldName()
    {
        return $this->idFieldName;
    }

    /**
     * Add object to add
     *
     * @param object $object object
     *
     * @return self
     */
    public function addToAdd($object)
    {
        $this->toAdd = $this->addObject($this->toAdd, $object);

        return $this;
    }

    /**
     * Get objects to add
     *
     * @return object[]
     */
    public function getToAdd()
    {
        $objects = $this->toAdd;
        foreach ($this->childTypes as $childType) {
            foreach ($childType->getToAdd() as $object) {
                $objects = $this->addObject($objects, $object);
            }
        }

        return $objects;
    }

    /**
     * Add object to edit
     *
     * @param object $object object
     *
     * @return self
     */
    public function addToEdit($object)
    {
        $this->toEdit = $this->addObject($this->toEdit, $object);

        return $this;
    }

    /**
     * Get objects to edit
     *
     * @return object[]
     */
    public function getToEdit()
    {
        $objects = $this->toEdit;
        foreach ($this->childTypes as $childType) {
            foreach ($childType->getToEdit() as $object) {
                $objects = $this->addObject($objects, $object);
            }
        }

        return $objects;
    }

    /**
     * Add object to delete
     *
     * @param object $object object
     *
     * @return self
     */
    public function addToDelete($object)
    {
        $this->toDelete = $this->addObject($this->toDelete, $object);

        return $this;
    }

    /**
     * Get objects to delete
     *
     * @return object[]
     */
    public function getToDelete()
    {
        $objects = $this->toDelete;
        foreach ($this->childTypes as $childType) {
            foreach ($childType->getToDelete() as $object) {
                $objects = $this->addObject($objects, $object);
            }
        }

        return $objects;
    }

    /**
     * Add object
     *
     * @param object[] $objects objects
     * @param object   $object  object
     *
     * @return array
     */
    protected function addObject($objects, $object)
    {
        if (!in_array($object, $objects, true)) {
            $objects[] = $object;
        }

        return $objects;
    }
}
