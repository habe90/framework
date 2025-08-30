<?php

namespace TrueFrame\Database\Migrations;

use TrueFrame\Database\Schema;

abstract class Migration
{
    /**
     * The schema builder instance.
     *
     * @var Schema
     */
    protected Schema $schema;

    /**
     * Create a new migration instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->schema = new Schema();
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    abstract public function up(): void;

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    abstract public function down(): void;
}