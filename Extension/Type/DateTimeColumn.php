<?php

namespace Tactics\TableBundle\Extension\Type;

use Tactics\TableBundle\Column;

/**
 * @author Aaron Muylaert <aaron.muylaert at tactics.be>
 */
class DateTimeColumn extends Column
{
    /**
     * {@inheritdoc}
     */
    public function getValue($value)
    {
        return $value->format('d/m/Y'); 
    }
}
