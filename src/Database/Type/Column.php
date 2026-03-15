<?php
namespace Framework\Database\Type;

/**
 * The Column interface
 */
interface Column {

    /**
     * Get the name of the column
     * @return string
     */
    public function name(): string;

    /**
     * Get the key of the column
     * @return string
     */
    public function key(): string;

    /**
     * Get the name of the column without the table
     * @return string
     */
    public function base(): string;
}
