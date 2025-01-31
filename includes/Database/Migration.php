<?php

namespace DailyMenuManager\Database;

/**
 * Class Migration
 * 
 * This abstract class serves as the base for all database migrations.
 * Each migration should extend this class and implement the up() and down() methods.
 */
abstract class Migration
{
    /**
     * Apply the migration.
     * 
     * This method should contain the logic to apply the migration, such as creating tables or adding columns.
     */
    abstract public function up();

    /**
     * Revert the migration.
     * 
     * This method should contain the logic to revert the migration, such as dropping tables or removing columns.
     */
    abstract public function down();
}
