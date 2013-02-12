<?php
/**
 * The grammar class for MySQL
 */
class QxGrammar {

	protected $components = array(
		'selects', 'from', 'joins', 'wheres', 'groupings', 'havings',
		'orderings', 'limit', 'offset'
	);

	/**
	 * Perform a SELECT statement
	 *
	 * @param  QxQuery $query The query
	 * @return string
	 */
	public function Select(QxQuery $query)
	{
		return $this->Concatenate($this->Components($query));
	}

	public function Insert(QxQuery $query) {
		 // TODO
	}

	public function Update(QxQuery $query) {
		// TODO
	}

	public function Selects(QxQuery $query)
	{
		$select = ($query->distinct) ? 'SELECT DISTINCT ' : 'SELECT ';

		return $select.$this->columnize($query->selects);
	}

	/**
	 * From where?
	 *
	 * @param  QxQuery $query 
	 * @return string
	 */
	public function From(QxQuery $query)
	{
		return 'FROM '. $query->from['from']. ' ' .$query->from['alias'] ?: '';
	}

	/**
	 * Process WHERE clauses
	 *
	 * @string  QxQuery $query The query
	 * @return string
	 */
	public function Wheres(QxQuery $query)
	{
		if(is_null($query->wheres)) return '';

		// TODO: Check for table alias's

		foreach($query->wheres as $where)
		{
			$sql[] = $where['connector']. ' ' .$this->{$where['type']}($where);
		}

		return 'WHERE ' . preg_replace('/AND |OR /', '', implode(' ', $sql), 1);
	}

	/**
	 * A basic Where clause
	 *
	 * @param  array $where Where conditions
	 * @return string
	 */
	protected function Where($where)
	{
		return "{$where['column']} {$where['operator']} {$where['value']}";
	}

	/**
	 * A nested Where
	 *
	 * @param  array $where Where conditions
	 * @return string
	 */
	protected function WhereNested($where)
	{
		return '('.substr($this->wheres($where['query']), 6).')';
	}

	/**
	 * WHERE IN()
	 *
	 * @param  array $where Conditions
	 * @return string
	 */
	protected function WhereIn($where)
	{
		$ret = $where['column'] . ($where['not'] ? ' NOT': '') . ' IN (';
		switch(gettype($where['value'])) {
			case 'array':
				$ret .= implode(', ', $where['value']);
				break;
			case 'string':
				$ret .= $where['value'];
				break;
			case 'object':
				// Don't know what this is, but it's no good to us
				if(!$where['value'] instanceof QxQuery)
					throw new Exception('Invalid Object passed to QxQuery::where_in()');

				$ret .= (string)$where['value'];
				break;
		}
		return $ret.')';
	}

	/**
	 * Process Join conditions
	 *
	 * @param  QxQuery $query
	 * @return string
	 */
	protected function Joins(QxQuery $query)
	{
		// We need to iterate through each JOIN clause that is attached to the
		// query and translate it into SQL. The table and the columns will be
		// wrapped in identifiers to avoid naming collisions.
		foreach ($query->joins as $join)
		{
			$table = $join->table;

			$clauses = array();

			// Each JOIN statement may have multiple clauses, so we will iterate
			// through each clause creating the conditions then we'll join all
			// of them together at the end to build the clause.
			foreach ($join->clauses as $clause)
			{
				extract($clause);

				// $column1 = $this->wrap($column1);

				// $column2 = $this->wrap($column2);

				$clauses[] = "{$connector} {$column1} {$operator} {$column2}";
			}

			// The first clause will have a connector on the front, but it is
			// not needed on the first condition, so we will strip it off of
			// the condition before adding it to the array of joins.
			$search = array('AND ', 'OR ');

			$clauses[0] = str_replace($search, '', $clauses[0]);

			$clauses = implode(' ', $clauses);

			$alias = '';
			if(is_array($table))
			{
				list($table, $alias) = $table;
			}

			$sql[] = "{$join->type} JOIN {$table} {$alias} ON {$clauses}";
		}

		// Finally, we should have an array of JOIN clauses that we can
		// implode together and return as the complete SQL for the
		// join clause of the query under construction.
		return implode(' ', $sql);
	}

	protected function Groupings(QxQuery $query)
	{
		return 'GROUP BY ' . $this->columnize($query->groupings);
	}

	protected function Orderings(QxQuery $query)
	{
		foreach($query->orderings as $order)
		{
			$sql[] = $order['column'].' '.$order['direction'];
		}

		return 'ORDER BY ' . implode(', ', $sql);
	}

	protected function Limit(QxQuery $query)
	{
		return 'LIMIT '.$query->limit;
	}

	protected function Offset(QxQuery $query)
	{
		return 'OFFSET '.$query->offset;
	}

	protected function Escape($value)
	{
		// TODO!!
	}

	/**
	 * Concatenate all parts of the query together, resulting in a string
	 *
	 * @param 	array $components Components received from $this->components()
	 * @return 	string
	 */
	final protected function Concatenate($components)
	{
		return implode(' ', array_filter($components, function($value) {
			return (string) $value !== '';
		}));
	}

	/**
	 * Generate SQL for each segment
	 *
	 * @param 	array 	component
	 * @return 	array
	 */
	final protected function Components(QxQuery $query)
	{
		$sql = array();
		foreach($this->components as $component)
		{
			if( ! is_null($query->$component) )
			{
				$sql[$component] = call_user_func(array($this, self::UnderscoreToCamel($component)), $query);
			}
		}

		return $sql;
	}

	final protected function Columnize($columns)
	{
		// Do our best to check if this is non-associative
		if(array_keys($columns) === range(0, count($columns) - 1))
		{
			return implode(', ', $columns); // TODO: Check for keywords (USE: wrap)
		}

		// Here we'll assume it is, so the values must be aliases
		else
		{
			$select = '';
			foreach($columns as $k=>$v)
			{
				// If there's already stuff there, suffix it with a comma
				if(!empty($select)) $select .= ', ';

				// If the key is an interger, it's not a column name, likely something like x.*
				if(is_int($k))
				{
					$select .= "$v";
					continue;
				}

				// Otherwise, we'll append the column and alias
				$select .= "$k $v";
			}

			return $select;
		}
	}

	/**
	 * Convert strings_with_underscores to CamelCase
	 *
	 * @param  string $value The string
	 * @return string
	 */
	public static function UnderscoreToCamel($value)
	{
		return str_replace(" ", "", ucwords(str_replace("_", " ", $value)));
	}
	/**
	 * Convert UpperCamelCase to an_underscored_string
	 *
	 * @param  string  $value The string
	 * @return string
	 */
	public static function CamelToUnderscore($value)
	{
		return strtolower(trim(preg_replace("/([A-Z])/", '_$1', $class), "_"));
	}
}
