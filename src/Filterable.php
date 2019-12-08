<?php 

namespace Core\Filter;

interface Filterable
{
	/**
	 * Get list of applyable filters
	 * @return array
	 */
	public function filters();

	/**
	 * Apply all existing filter methods.
	 * @return Core\Filtering\Filterable
	 */
	public function apply();
}