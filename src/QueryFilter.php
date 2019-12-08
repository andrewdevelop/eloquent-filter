<?php 

namespace Core\Filter;

use Illuminate\Database\Eloquent\Builder;
use Core\Filter\Filterable;
use Closure;
use Exception;


abstract class QueryFilter implements Filterable
{

	/**
	 * Input data.
	 * @var Core\Filter\Input
	 */
	protected $filters;

	/**
	 * Query builder instance.
	 * @var \Illuminate\Database\Eloquent\Builder
	 */
	protected $query;

	/**
	 * Custom variables.
	 * @var array
	 */
	protected $vars = [];

	/**
	 * Debug only
	 */
	protected $applyed = [];

	/**
	 * Filters that always applied. Contains key and default value.
	 * @var array
	 */
	protected $always_apply = [
		'order_by' => 'created_at',
	];


    /**
     * Set the query being sent through the filter.
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return $this
     */
    public function send(Builder $query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Set the array of filters.
     * @param  array|mixed  $filters
     * @return $this
     */
    public function through($filters)
    {
        $this->input = new Input($filters);

        return $this;
    }

	/**
	 * Add custom variables.
	 * @param  mixed $key   
	 * @param  mixed $value 
	 * @return \Core\Filter\Filterable
	 */
	public function with($key, $value = null)
	{
		if (is_array($key)) {
			foreach ($key as $k => $v) {
				$this->pushVariable($k, $v);
			}
		} else {
			$this->pushVariable($key, $value);
		}

		return $this;
	}



	/**
	 * Apply all existing filter methods.
	 * @return Illuminate\Database\Eloquent\Builder
	 */
	public function apply()
	{
		foreach ($this->filters() as $filter_name => $value_or_default) {
			$this->applyFilter($filter_name, $value_or_default);
		}

		return $this;
	}


	/**
	 * Return the modified query builder instance.
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function query()
	{
		return $this->query;
	}


	/**
     * Execute the query as a "select" statement.
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
	public function get($columns = ['*'])
	{
		return $this->query->get($column);
	}


	/**
     * Paginate the given query.
     * @param  int  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @throws \InvalidArgumentException
     */
	public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
	{
		return $this->query->paginate($perPage, $columns, $pageName, $page);
	}


    /**
     * Run a final destination callback.
     * @param  \Closure  $destination
     * @return mixed
     */
    public function then(Closure $destination)
    {
        call_user_func_array($destination, [$this->query]);

        return $this;
    }


	/**
	 * Get list of applyable filters
	 * @return array
	 */
	public function filters()
	{
		$applyable_always = $this->always_apply;
		$applyable_from_input = $this->input->all();

		return array_merge($applyable_always, $applyable_from_input);
	}


	/**
	 * Apply single filter.
	 * @param  string $name
	 * @param  mixed $value 
	 * @return void
	 */
	protected function applyFilter($name, $value = null)
	{
		$method_name = $this->resolveFilterMethod($name);
		if ($this->methodIsApplyable($method_name)) {
			$this->applyMethod($method_name, $value, $name);
		}
	}


    /**
     * Determine method is applyable.
     * @param  string $method
     * @return boolean
     */
	protected function methodIsApplyable($method)
	{
		return method_exists($this, $method);
	}


	/**
	 * Apply filter method.
	 * @param  string $name  
	 * @param  mixed $value 
	 * @return void
	 */
	protected function applyMethod($method, $value, $real_key)
	{
		// If the input like empty, we make a little silly cleanup.
		$args = array_map(function($i) {
			return ($i === '') ? null : $i;
		}, [$value]);
		
		// Append the real key to arguments when using non-named method.
		$args[] = $real_key;

		$this->applyed[$real_key] = [
			'method' => $method,
			'value' => $value
		];
		
		call_user_func_array([$this, $method], $args);
	}


	/**
	 * Get handler method name
	 * @param  string $name 
	 * @return void
	 */
	protected function resolveFilterMethod($name)
	{
		return 'apply'.str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));
	}


	protected function pushVariable($key, $value)
	{
		$reserved = array_keys(get_object_vars($this));
		if (in_array($key, $reserved)) {
			throw new Exception('Cannot redeclare variable.');
		}
		$this->vars[$key] = $value;
	}

    /**
     * Dynamically retrieve props.
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
    	if (isset($this->vars[$key])) {
    		return $this->vars[$key];
    	}
    }
}