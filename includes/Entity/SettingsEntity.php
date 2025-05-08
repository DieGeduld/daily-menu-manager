<?php

namespace DailyMenuManager\Entity;

/**
 * Class SettingsEntity
 * 
 * Represents a settings entry in the database
 */
class SettingsEntity extends AbstractEntity
{
    protected $key;
    protected $value;

    /**
     * Constructor to create a Settings entity from array data
     *
     * @param array $data Array of settings data
     */
    public function __construct(array $data = [])
    {
        $this->key = $data['key'] ?? null;
        $this->value = $data['value'] ?? null;
    }

    /**
     * Get the setting key
     *
     * @return string|null
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * Set the setting key
     *
     * @param string $key
     * @return self
     */
    public function setKey(string $key): self
    {
        $this->key = $key;
        return $this;
    }

    /**
     * Get the setting value
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the setting value
     *
     * @param mixed $value
     * @return self
     */
    public function setValue($value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Convert entity to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
        ];
    }
}
