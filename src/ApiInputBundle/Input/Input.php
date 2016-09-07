<?php

namespace ApiInputBundle\Input;

use Symfony\Component\HttpFoundation\Request;

/**
 * Input
 */
class Input
{
    /** @var Type */
    protected $type;

    /** @var object */
    protected $object;

    /** @var array */
    protected $options;

    /** @var bool */
    protected $isSubmitted = false;

    /**
     * Constructor
     *
     * @param Type   $type    type
     * @param object $object  object
     * @param array  $options options
     */
    public function __construct(Type $type, $object, array $options = [])
    {
        $this->type = $type;
        $this->object = $object;
        $this->options = $options;
    }

    /**
     * Handle request
     *
     * @param Request $request
     *
     * @return self
     */
    public function handleRequest(Request $request)
    {
        $data = $this->getDataFromRequest($request);
        if (isset($data)) {
            $this->type->updateObject($this->object, $data);
            $this->isSubmitted = true;
        }

        return $this;
    }

    /**
     * Is valid
     *
     * @return bool
     */
    public function isValid()
    {
        $isValid = false;
        if ($this->isSubmitted) {
            // @TODO: add validation
            $isValid = true;
        }

        return $isValid;
    }

    /**
     * Get data from request
     *
     * @param Request $request
     *
     * @return array|null
     */
    protected function getDataFromRequest(Request $request)
    {
        if (!$request->isMethod('POST') && !$request->isMethod('PUT')) {
            return null;
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            $data = $request->request->all();
        }

        return $data;
    }

    /**
     * Get objects to add
     *
     * @return object[]
     */
    public function getToAdd()
    {
        $toAdd = $this->type->getToAdd();

        return array_reverse($toAdd);
    }

    /**
     * Get objects to edit
     *
     * @return object[]
     */
    public function getToEdit()
    {
        $toEdit = $this->type->getToEdit();

        return array_reverse($toEdit);
    }

    /**
     * Get objects to delete
     *
     * @return object[]
     */
    public function getToDelete()
    {
        $toDelete = $this->type->getToDelete();

        return array_reverse($toDelete);
    }
}
