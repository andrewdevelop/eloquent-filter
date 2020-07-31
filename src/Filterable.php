<?php 

namespace Core\Filter;

interface Filterable
{
	/**
	 * Get list of applicable filters.
	 * @return array
	 */
	public function filters();

	/**
	 * Apply all existing filter methods.
	 * @return Filterable
	 */
	public function apply();
}