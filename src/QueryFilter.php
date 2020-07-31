<?php

namespace Core\Filter;

use Closure;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use InvalidArgumentException;

abstract class QueryFilter implements Filterable
{

    /**
     * Minimal pagination length.
     * @var integer
     */
    public $min_items = 1;

    /**
     * Maximum pagination length.
     * @var integer
     */
    public $max_items = 100;

    /**
     * Input data.
     * @var Input
     */
    protected $input;

    /**
     * Query builder instance.
     * @var Builder
     */
    protected $query;

    /**
     * Custom variables.
     * @var array
     */
    protected $vars = [];

    /**
     * Debug only.
     * @var array
     */
    protected $applied = [];

    /**
     * Filters that always applied. Contains key and default value.
     * @var array
     */
    protected $always_apply = [
        'order_by' => 'created_at',
        'limit' => 15
    ];

    /**
     * Set the query being sent through the filter.
     * @param Builder $query
     * @return QueryFilter
     */
    public function send(Builder $query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * Set the array of filters.
     * @param array|iterable|Request $filters
     * @return QueryFilter
     */
    public function through($filters = [])
    {
        $this->input = new Input($filters);
        return $this;
    }

    /**
     * Add custom variables.
     * @param mixed $key
     * @param mixed $value
     * @return QueryFilter
     * @throws Exception
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
     * @return QueryFilter
     */
    public function apply()
    {
        foreach ($this->filters() as $filter_name => $value_or_default) {
            $this->applyFilter($filter_name, $value_or_default);
        }
        return $this;
    }

    /**
     * Run a final destination callback.
     * @param Closure $destination
     * @return mixed
     */
    public function then(Closure $destination)
    {
        call_user_func_array($destination, [$this->query]);
        return $this;
    }

    /**
     * Return the modified query builder instance.
     * @return Builder
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * Execute the query as a "select" statement.
     * @param array $columns
     * @return Collection|static[]
     */
    public function get($columns = ['*'])
    {
        return $this->query->get($columns);
    }

    /**
     * Paginate the given query.
     * @param int $per_page
     * @param array $columns
     * @param string $page_name
     * @param int|null $page
     * @return LengthAwarePaginator
     * @throws InvalidArgumentException
     */
    public function paginate($per_page = null, $columns = ['*'], $page_name = 'page', $page = null)
    {
        if (!$per_page) {
            $per_page = $this->input->getItemLimit('limit', 15, $this->min_items, $this->max_items);
        }
        return $this->query->paginate($per_page, $columns, $page_name, $page);
    }

    /**
     * Get list of applicable filters
     * @return array
     */
    public function filters()
    {
        $applicable_always = $this->always_apply;
        $applicable_from_input = $this->input->all();
        return array_merge($applicable_always, $applicable_from_input);
    }

    /**
     * Set a variable available during process.
     * @param $key
     * @param $value
     */
    protected function pushVariable($key, $value)
    {
        $reserved = array_keys(get_object_vars($this));
        if (in_array($key, $reserved)) {
            throw new InvalidArgumentException('Cannot redeclare variable.');
        }
        $this->vars[$key] = $value;
    }

    /**
     * Apply a single filter.
     * @param string $name
     * @param mixed $value
     * @return void
     */
    protected function applyFilter($name, $value = null)
    {
        $method_name = $this->resolveFilterMethodName($name);
        if ($this->methodIsApplicable($method_name)) {
            $this->applyMethod($method_name, $value, $name);
        }
    }

    /**
     * Get handler method name.
     * @param string $name
     * @return string
     */
    protected function resolveFilterMethodName($name)
    {
        return 'apply' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));
    }

    /**
     * Determine method is applicable.
     * @param string $method
     * @return boolean
     */
    protected function methodIsApplicable(string $method)
    {
        return method_exists($this, $method);
    }

    /**
     * Apply filter method.
     * @param string $method
     * @param mixed $value
     * @param $real_key
     * @return void
     */
    protected function applyMethod(string $method, $value, string $real_key)
    {
        // If the input like empty, we make a little silly cleanup.
        $args = array_map(function ($i) {
            return ($i === '') ? null : $i;
        }, [$value]);

        // Append the real key to arguments when using non-named method.
        $args[] = $real_key;

        $this->applied[$real_key] = [
            'method' => $method,
            'value' => $value
        ];

        call_user_func_array([$this, $method], $args);
    }

    /**
     * Dynamically retrieve props.
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        if (isset($this->vars[$key])) {
            return $this->vars[$key];
        }
        return null;
    }
}