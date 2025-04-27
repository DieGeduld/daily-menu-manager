<?php

namespace DailyMenuManager\Entity;

abstract class AbstractEntity
{
    protected $id;
    protected $created_at;
    protected $updated_at;

    /**
     * Get entity ID
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set entity ID
     *
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get creation timestamp
     *
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Get update timestamp
     *
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Magic method for getting properties
     *
     * @param string $name Property name
     * @return mixed|null Property value or null if not found
     */
    public function __get($name)
    {
        // First try the standard getter (e.g., getName for $name)
        $method = 'get' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        // For snake_case properties, convert to camelCase getter
        // e.g., menu_item -> getMenuItem
        if (strpos($name, '_') !== false) {
            $parts = explode('_', $name);
            $camelCase = $parts[0];
            for ($i = 1; $i < count($parts); $i++) {
                $camelCase .= ucfirst($parts[$i]);
            }

            $method = 'get' . ucfirst($camelCase);
            if (method_exists($this, $method)) {
                return $this->$method();
            }
        }

        // Fall back to direct property access
        return isset($this->$name) ? $this->$name : null;
    }

    public function __set($name, $value)
    {
        // First try the standard setter (e.g., setName for $name)
        $method = 'set' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->$method($value);
        }

        // For snake_case properties, convert to camelCase setter
        // e.g., menu_item -> setMenuItem
        if (strpos($name, '_') !== false) {
            $parts = explode('_', $name);
            $camelCase = $parts[0];
            for ($i = 1; $i < count($parts); $i++) {
                $camelCase .= ucfirst($parts[$i]);
            }

            $method = 'set' . ucfirst($camelCase);
            if (method_exists($this, $method)) {
                return $this->$method($value);
            }
        }

        // Fall back to direct property assignment
        $this->$name = $value;

        return $this;
    }
    /**
     * Convert entity to array
     *
     * @return array Entity data as array
     */
    public function toArray()
    {
        $data = [];
        $reflect = new \ReflectionClass($this);
        $properties = $reflect->getProperties(\ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE);

        foreach ($properties as $property) {
            $property->setAccessible(true);
            $propertyName = $property->getName();
            $data[$propertyName] = $property->getValue($this);
        }

        return $data;
    }
}
