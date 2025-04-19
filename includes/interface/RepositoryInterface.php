<?php

namespace DailyMenuManager\Interface;

interface RepositoryInterface
{
    /**
     * Find an entity by ID
     *
     * @param int $id The entity ID
     * @return mixed The entity or null if not found
     */
    public function findById($id);

    /**
     * Find all entities
     *
     * @return array Array of entities
     */
    public function findAll();

    /**
     * Save an entity
     *
     * @param mixed $entity The entity to save
     * @return mixed The saved entity with updated ID
     */
    public function save($entity);

    /**
     * Delete an entity
     *
     * @param mixed $entity The entity to delete
     * @return bool Whether the deletion was successful
     */
    public function delete($entity);

    /**
     * Delete an entity by ID
     *
     * @param int $id The entity ID to delete
     * @return bool Whether the deletion was successful
     */
    public function deleteById($id);
}
