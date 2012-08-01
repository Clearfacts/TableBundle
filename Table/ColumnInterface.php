<?php

namespace Tactics\TableBundle\Table;

interface ColumnInterface
{
    /**
     * @return String The name of the column.
     */
    function getName();

    /**
     * @param mixed $value The value.
     *
     * @return mixed $value The formatted value.
     */
    function getValue($value);


    /**
     * @return string The celltype of the column.
     */
    function getCellType();
}
