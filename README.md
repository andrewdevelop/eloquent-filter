# A basic filtering package for Laravel's query builder
### Installation
```text
composer require andrewdevelop/eloquent-filter
```
### Usage example
Some command handler, controller, etc.
```php
<?php

use App\Task;
use App\TaskFilter;
use Illuminate\Http\Request;

class SomeHandlerClass
{
    /** 
     * An instance of Illuminate\Database\Eloquent\Model.
     * @var Task 
     */
    private $tasks;
    
    /**
     * The instance of the custom filter (see bellow).
     * @var TaskFilter
     */
    private $filter; 

    /**
     * Some constructor with dependencies for example.
     * @param Task $tasks
     * @param TaskFilter $filter
     */
    public function __construct(Task $tasks, TaskFilter $filter)
    {
        $this->tasks = $tasks;
        $this->filter = $filter;
    }
    
    /** Some place for the business logic */
    public function handle(Request $request) 
    {
        /** @var Illuminate\Database\Eloquent\Builder */
        $query = $this->tasks->query();
        return $this->filter
            // Put the builder here...
            ->send($query) 
            // Put the request or an array here...
            ->through($request) 
            // Also can use some variables...
            ->with('role', 'admin')
            // Run apply() to do the magic!..
            ->apply()
            // paginate(), get(), or query().
            ->paginate();
    }
}
```
The custom filter's code:
```php
<?php

class TaskFilter extends QueryFilter
{
    /**
     * Filters that always applied. Contains key and default value.
     * @var array
     */
    protected $always_apply = [
        'order_by' => 'created_at',
    ];

    /**
     * Filter "order_by" & "dir"
     * @return void
     */
    protected function applyOrderBy()
    {
        $order_by = $this->input->getOrdering();
        $direction = $this->input->getDirection();
        $this->query->orderBy($order_by, $direction);
    }

    /**
     * Follow the naming convention.
     * "snake_case" params will be converted to "apply"+"PascalCase". 
     * E.g. "state" query param will be call the applyState method.
     * @param null|array|string $choices
     */
    protected function applyState($choices = null)
    {
        if (!is_array($choices)) $choices = [$choices];
        $choices = array_filter($choices, function($e) {
            return in_array($e, TaskStates::values());
        });
        if (count($choices)) $this->query->whereIn('state', $choices);
    }
    
    /** Und so weiter... */
}
```