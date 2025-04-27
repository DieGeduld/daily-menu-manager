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

    public function __call($method, $arguments)
    {
        // Handle setters (methods starting with "set")
        if (strpos($method, 'set') === 0) {
            $property = lcfirst(substr($method, 3)); // Remove "set" and lowercase first char

            // Check if property exists directly
            if (property_exists($this, $property)) {
                $this->$property = $arguments[0];

                return $this;
            }

            // Convert camelCase to snake_case for property lookup
            // e.g., setMenuId -> menu_id
            $snakeCase = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $property));
            if (property_exists($this, $snakeCase)) {
                $this->$snakeCase = $arguments[0];

                return $this;
            }
        }

        // Handle getters (methods starting with "get")
        if (strpos($method, 'get') === 0) {
            $property = lcfirst(substr($method, 3)); // Remove "get" and lowercase first char

            // Check if property exists directly
            if (property_exists($this, $property)) {
                return $this->$property;
            }

            // Convert camelCase to snake_case for property lookup
            // e.g., getMenuId -> menu_id
            $snakeCase = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $property));
            if (property_exists($this, $snakeCase)) {
                return $this->$snakeCase;
            }
        }

        throw new \BadMethodCallException("Method $method does not exist.");
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
