<?php

namespace Tactics\TableBundle\QueryBuilderFilter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\ORM\QueryBuilder;

use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;

class QueryBuilderPager implements QueryBuilderFilterInterface
{
    /**
      * @var $container ContainerInterface A ContainerInterface instance.
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null) {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(QueryBuilder $mc, $key = null, $options = array())
    {
        $request = $this->container->get('request'); 
        $session = $this->container->get('session');

        $resolver = new OptionsResolver();
        $this->setDefaultOptions($resolver);
        
        $options = $resolver->resolve($options);

        $key = null === $key ? 'pager/'.$request->attributes->get('_route') : $key;

        $page = $request->get('page');

        if ($page) {
            $session->set($key, $page);
        } elseif (! $page && $session->has($key)) {
            $page = $session->get($key);
        } else {
            $page = 1;
            $session->set($key, $page);
        }

        $pager = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pager->setPage($page)->setMaxPerPage($options['max_per_page']);

        return $pager;
    }

    /**
     * Sets the default options for this type.
     *
     * @param OptionsResolverInterface $resolver The resolver for the options.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults(array(
                'max_per_page' => 10
        ));
    }
}
