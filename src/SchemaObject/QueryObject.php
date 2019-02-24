<?php

namespace GraphQL\SchemaObject;

use GraphQL\Exception\EmptySelectionSetException;
use GraphQL\Query;

/**
 * An abstract class that acts as the base for all schema query objects generated by the SchemaScanner
 *
 * Class QueryObject
 *
 * @package GraphQL\SchemaObject
 */
abstract class QueryObject
{
    /**
     * This constant stores the name of the object name in the API definition
     *
     * @var string
     */
    const OBJECT_NAME = '';

    /**
     * This string attribute stores the name alias to be used in queries for this object
     *
     * @var
     */
    private $nameAlias;

    /**
     * This array stores the set of selected fields' names for this object
     *
     * @var array
     */
    private $selectionSet;

    /**
     * This array stores a map of argument name to argument value for this object
     *
     * @var array
     */
    private $arguments;

    /**
     * SchemaObject constructor.
     *
     * @param string $nameAlias
     */
    public function __construct($nameAlias = '')
    {
        $this->selectionSet = [];
        $this->arguments    = [];
        $this->nameAlias    = !empty($nameAlias) ? $nameAlias : static::OBJECT_NAME;
    }

    /**
     * @return Query
     * @throws EmptySelectionSetException
     */
	protected function toQuery()
	{
        if (empty($this->selectionSet)) {
            throw new EmptySelectionSetException(static::class);
        }

        $this->constructArgumentsList();

        // Convert nested query objects to string queries
        foreach ($this->selectionSet as $key => $field) {
            if ($field instanceof QueryObject) {
                $this->selectionSet[$key] = $field->toQuery();
            }
        }

        // Create and return query for this object
        $query = new Query($this->nameAlias);
        $query->setArguments($this->arguments);
        $query->setSelectionSet($this->selectionSet);

        return $query;
	}

    /**
     * Constructs the object's arguments list from its attributes
     */
	protected function constructArgumentsList()
    {
        foreach ($this as $name => $value) {
            // TODO: Use annotations to avoid having to check on specific keys
            if (empty($value) || in_array($name, ['nameAlias', 'selectionSet', 'arguments'])) continue;

            // Handle input objects before adding them to the arguments list
            if ($value instanceof InputObject) {
                $value = $value->getRawObject();
            }

            $this->arguments[$name] = $value;
        }
    }

    /**
     * @param string|QueryObject $selectedField
     */
	protected function selectField($selectedField)
    {
        if (is_string($selectedField) || $selectedField instanceof QueryObject) {
            $this->selectionSet[] = $selectedField;
        }
    }

    /**
     * @return string
     * @throws EmptySelectionSetException
     */
    public function getQueryString()
    {
        return (string) $this->toQuery();
    }
}