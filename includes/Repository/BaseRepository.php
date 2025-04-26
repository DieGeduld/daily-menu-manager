<?php

namespace DailyMenuManager\Repository;

use DailyMenuManager\Interface\RepositoryInterface;

abstract class BaseRepository implements RepositoryInterface
{
    protected $wpdb;
    protected $table_name;
    protected $entity_class;

    /**
     * Constructor
     *
     * @param string $table_name The name of the table without prefix
     * @param string $entity_class The fully qualified class name of the entity
     */
    public function __construct($table_name, $entity_class)
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . $table_name;
        $this->entity_class = $entity_class;
    }

    /**
     * Find an entity by ID
     *
     * @param int $id The entity ID
     * @return mixed The entity or null if not found
     */
    public function findById($id)
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        );

        $result = $this->wpdb->get_row($query, ARRAY_A);

        if (!$result) {
            return null;
        }

        return new $this->entity_class($result);
    }

    /**
     * Find all entities
     *
     * @return array Array of entities
     */
    public function findAll()
    {
        $results = $this->wpdb->get_results(
            "SELECT * FROM {$this->table_name}",
            ARRAY_A
        );

        $entities = [];
        foreach ($results as $row) {
            $entities[] = new $this->entity_class($row);
        }

        return $entities;
    }

    /**
     * Delete an entity
     *
     * @param mixed $entity The entity to delete
     * @return bool Whether the deletion was successful
     */
    public function delete($entity)
    {
        return $this->deleteById($entity->id);
    }

    /**
     * Delete an entity by ID
     *
     * @param int $id The entity ID to delete
     * @return bool Whether the deletion was successful
     */
    public function deleteById($id)
    {
        $result = $this->wpdb->delete(
            $this->table_name,
            ['id' => $id],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Get the next available ID
     *
     * @return int The next available ID
     */
    protected function getNextId()
    {
        $query = "SELECT MAX(id) FROM {$this->table_name}";
        $max_id = $this->wpdb->get_var($query);

        return is_null($max_id) ? 1 : $max_id + 1;
    }

    /**
     * Find entities by a field value
     *
     * @param string $field The field name
     * @param mixed $value The field value
     * @return array Array of entities
     */
    public function findBy($field, $value)
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE {$field} = %s",
            $value
        );

        $results = $this->wpdb->get_results($query, ARRAY_A);

        $entities = [];
        foreach ($results as $row) {
            $entities[] = new $this->entity_class($row);
        }

        return $entities;
    }

    /**
     * Find a single entity by a field value
     *
     * @param string $field The field name
     * @param mixed $value The field value
     * @return mixed The entity or null if not found
     */
    public function findOneBy($field, $value)
    {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE {$field} = %s LIMIT 1",
            $value
        );

        $result = $this->wpdb->get_row($query, ARRAY_A);

        if (!$result) {
            return null;
        }

        return new $this->entity_class($result);
    }

    /**
     * Count entities by a field value
     *
     * @param string $field The field name
     * @param mixed $value The field value
     * @return int The count
     */
    public function countBy($field, $value)
    {
        $query = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE {$field} = %s",
            $value
        );

        return (int) $this->wpdb->get_var($query);
    }

    /**
     * Check if an entity exists by a field value
     *
     * @param string $field The field name
     * @param mixed $value The field value
     * @return bool Whether the entity exists
     */
    public function existsBy($field, $value)
    {
        return $this->countBy($field, $value) > 0;
    }

    /**
     * Save method to be implemented by child classes
     *
     * @param mixed $entity The entity to save
     * @return mixed The saved entity
     */
    abstract public function save($entity);
}
