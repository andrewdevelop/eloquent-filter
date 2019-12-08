<?php 

namespace Core\Filter;

use Core\Filter\QueryFilter;
use Core\Filter\Filterable;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use ReflectionClass;

class Factory
{

	/**
	 * DI container instance.
	 * @var \Illuminate\Container\Container
	 */
	protected $container;


	/**
	 * Class Constructor
	 * @param \Illuminate\Container\Container $container   
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}


	/**
	 * Create a new filter instance.
	 * @param  string $filter_class 
	 * @param  \Illuminate\Database\Eloquent\Builder $query
	 * @param  array $input
	 * @return \Core\Filter\Filterable
	 */
    public function make($filter_class, Builder $query, array $input = [])
    {
    	$args = compact('query', 'input');
        return $this->container
        	->make($filter_class)
        	->send($query)
        	->through($input);
    }



}