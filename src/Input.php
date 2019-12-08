<?php 

namespace Core\Filter;

use ArrayAccess;
use IteratorAggregate;
use ArrayIterator;
use Illuminate\Http\Request;
use JsonSerializable;
use Closure;

class Input implements IteratorAggregate, ArrayAccess, JsonSerializable
{

    /**
     * The input contained in the collection.
     * @var array
     */
    protected $input = [];


    /**
     * Create a new collection.
     * @param  mixed  $input
     * @return void
     */
    public function __construct($input = [])
    {
        if (is_array($input)) {
            $this->input = $input;
        } elseif ($input instanceof Request) {
            $this->input = $input->all();
        } else {
            $this->input = (array) $input;
        }
    }


    /**
     * Determine if an item exists in the collection by key.
     * @param  mixed  $key
     * @return bool
     */
    public function has($key)
    {
        $keys = is_array($key) ? $key : func_get_args();
        foreach ($keys as $value) {
            if (!$this->offsetExists($value)) return false;
        }
        return true;
    }


    /**
     * Get an item from the collection by key.
     * @param  mixed  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ($this->offsetExists($key)) {
            return $this->input[$key];
        }

        return $default instanceof Closure ? $default() : $default;
    }   


    /**
     * Get range.
     * @param  string $key     
     * @param  string $min_key 
     * @param  string $max_key 
     * @return array
     */
    public function getRange($key, $min_key = 'min', $max_key = 'max')
    {
        $output = [];
        $output[$min_key] = null;
        $output[$max_key] = null;

        if (!$this->has($key)) return null;

        $range = $this->get($key);
        if (isset($range[$min_key])) {
            $output[$min_key] = floatval($range[$min_key]);
        }
        if (isset($range[$max_key])) {
            $output[$max_key] = floatval($range[$max_key]);
        }
        return $output;
    }


    /**
     * Input with no value
     * @param  mixed  $key 
     * @return boolean
     */
    public function isNull($key)
    {
        if (!$this->has($key)) return true;
        $value = $this->get($key);
        if ($value === null) return true;
        return false;
    }


    /**
     * Determine that input value is like true
     * @param  mixed  $key 
     * @return boolean
     */
    public function isTrue($key)
    {
        if (!$this->has($key)) return false;
        $value = $this->get($key);
        if (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) == true) {
            return true;
        }
        return false;
    }


    /**
     * Determine that input value is like false
     * @param  mixed  $key 
     * @return boolean
     */
    public function isFalse($key)
    {
        if (!$this->has($key)) return false;
        $value = $this->get($key);
        if (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) == false) {
            return true;
        }
        return false;
    }


    /**
     * Get sorting column.
     * @param  string $key     
     * @param  string $default 
     * @return string
     */
    public function getOrdering($key = 'order_by', $default = 'created_at')
    {
        $ordering = $this->get($key, $default);
        return $ordering;
    }


    /**
     * Get sorting direction.
     * @param  string $key     
     * @param  string $default 
     * @return string
     */
    public function getDirection($key = 'dir', $default = 'desc')
    {
        $dir = $this->get($key, $default);
        if (in_array($dir, ['desc','asc'])) {
            return $dir;
        } else {
            return $default;
        }
    }


    /**
     * Get the keys of the collection input.
     * @return static
     */
    public function keys()
    {
        return array_keys($this->input);
    }


    /**
     * Get all of the items in the collection.
     * @return array
     */
    public function all()
    {
        return $this->input;
    }


    /**
     * Determine if an item exists at an offset.
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->input);
    }


    /**
     * Get an item at a given offset.
     * @param  mixed  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->input[$key];
    }


    /**
     * Set the item at a given offset.
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->input[] = $value;
        } else {
            $this->input[$key] = $value;
        }
    }


    /**
     * Unset the item at a given offset.
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->input[$key]);
    } 


    /**
     * Get an iterator for the input.
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->input);
    }


    /**
     * Serializes the input to a value that can be serialized natively by json_encode(). 
     * @return array
     */
    public function jsonSerialize() {
        return $this->input;
    }
}