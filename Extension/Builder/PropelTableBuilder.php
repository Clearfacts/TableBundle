<?php

namespace Tactics\TableBundle\Extension\Builder;

use \Criteria;

use Tactics\TableBundle\TableBuilder;
use Tactics\TableBundle\TableFactoryInterface;
use Tactics\TableBundle\Extension\Type\SortableColumnHeader;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Description of PropelTableBuilder
 *
 * @author Gert Vrebos    <gert.vrebos at tactics.be>
 * @author Aaron Muylaert <aaron.muylaert at tactics.be>
 */
class PropelTableBuilder extends TableBuilder
{
    protected $modelCriteria;
    protected $objectPeer;
    protected $reflector;
    protected $columns = array();

    /**
     * @inheritDoc
     */    
    public function __construct($name, $type = '', TableFactoryInterface $factory, array $options = array())
    {
        parent::__construct($name, $type, $factory, $options);
        
        $this->modelCriteria = $this->options['model_criteria'];
        
        $peerName = $this->modelCriteria->getModelPeerName();

        $this->objectPeer = new $peerName();
        $this->reflector  = new \ReflectionClass($this->modelCriteria->getModelName());
        // todo clean up.
        $this->dummy      = $this->modelCriteria->find()->getFirst();
    }
    
    /**
     * Retrieves all the fieldnames from ModelCriteria and adds them.
     * Todo: All fields have to be added, fields can be set to visible or 
     * invisible.
     *
     * @param array $exclude Names of fields to exclude.
     *
     * @return PropelTableBuilder $this The PropelTableBuilder instance. 
     */ 
    public function addAll(array $exclude = array())
    {
        foreach (array_diff($this->getFieldnames(), $exclude) as $key => $fieldName)
        {
            // todo clean this up?
            $options['header']['value'] = ucfirst(strtolower(str_replace('_', ' ', substr($fieldName, (strpos($fieldName, '.')+1), strlen($fieldName)))));
            
            // todo ColumnHeader extensions should fix this.
            $options['header']['type'] = 'sortable';

            $request = $this->getTableFactory()->getContainer()->get('request');
            $route = $request->attributes->get('_route'); 
            $options['header']['route'] = $route;

            $this->add($fieldName, null, $options);
        }
        
        return $this;
    }
    
    /**
     * @inheritDoc
     */     
    public function create($name, $type = null, $headerType = null, array $options = array())
    {
        // todo Method should not be an option but a Column Extension.
        // getMethod throws exception when method is not found.
        if (! isset($options['column']['method'])) {
            $method = $this->reflector->getMethod($this->translateColnameToMethod($name));
        }
        else {
            $method = $this->reflector->getMethod($options['column']['method']);
        }

        $options['column']['method'] = $method->getName();
      
        // guess type based on modelcriteria properties.
        if (null === $type) {
            // Guess the method.
            // Throws exception when method not found.
            $methodName = $options['column']['method'];

            // todo don't use dummy.
            $val = $this->dummy->$methodName(); 

            if (is_object($val) && get_class($val) === 'DateTime') {
                $type = 'date_time';
            }
            else {
                $type = 'text';
            }
        }
        
        return parent::create($name, $type, $headerType, $options);
    }

    /**
     * Proxy for modelPeer::getFieldnames.
     *
     * @return array
     */
    private function getFieldnames()
    {
        return $this->objectPeer->getFieldNames(\BasePeer::TYPE_COLNAME);
    }

    /**
     * Translate raw colname to method.
     *
     * @param $rawColname string 
     * @return string
     */
    private function translateColnameToMethod($colname)
    {
        if (array_search($colname, $this->getFieldnames()) === false) {
            throw new \Exception('Unknown column '.$colname);
        }

        return 'get'.$this->objectPeer->translateFieldName(
            $colname, 
            \BasePeer::TYPE_COLNAME,
            \BasePeer::TYPE_PHPNAME
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTable()
    {
        // todo: create!
        $table = $this->factory->createTable($this->name, $this->type, array());
        
        foreach ($this as $column) {
            $table->add($column);
        }
        
        // todo
        // All of this is a bit weird since we don't really know we're dealing
        // with sortable columns, well, I know, but .. 
        $factory = $this->getTableFactory();
        $request = $factory->getContainer()->get('request');
        $orderBy = $request->get('order_by'); 

        // todo
        // At time of testing, a new table was made each request.
        // Need to find a way to store table settings into session.
        if ($orderBy)
        {
            $column = $table->offsetGet($orderBy);
            $header = $column->getHeader();

            switch ($header->getState()) {
                case SortableColumnHeader::ASC:
                    $header->setState(SortableColumnHeader::DESC);
                    $this->modelCriteria->orderBy($orderBy, Criteria::DESC);
                    break;
                case SortableColumnHeader::DESC:
                    $header->setState(SortableColumnHeader::NO_SORT);
                    break;
                default:
                    $header->setState(SortableColumnHeader::ASC);
                    $this->modelCriteria->orderBy($orderBy, Criteria::ASC);
                    break;
            }
        }

        $rows = array();

        foreach ($this->modelCriteria->find() as $object) {
            $rowArr = array();
            foreach ($table as $column) {
                $options = $column->getOptions();
                $method  = $options['method'];

                $rowArr[$column->getName()] = array('value' => $object->$method());
            }

            $rows[] = $rowArr;
        }

        $table->setRows($rows);
        
        return $table;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        
        $resolver->setRequired(array('model_criteria'));
    }
}

