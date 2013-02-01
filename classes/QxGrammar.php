<?php
/**
 * The grammar class for MySQL
 */
class QxGrammar {

	protected $components = array(
		'selects', 'from', 'joins', 'wheres', 'groupings', 'havings', 'orderings', 'limit', 'offset'
	);

	public function select(QxQuery $query)
	{
		return $this->concatenate($this->components($query));
	}

	public function selects(QxQuery $query)
	{
		$select = ($query->distinct) ? 'SELECT DISTINCT ' : 'SELECT ';

		return $select.$this->columnize($query->selects);
	}

	public function from(QxQuery $query)
	{
		return 'FROM '. $query->from['from']. ' ' .$query->from['alias'] ?: '';
	}

	public function wheres(QxQuery $query)
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
	 * A basic where clause
	 */
	protected function Where($where)
	{
		return "{$where['column']} {$where['operator']} {$where['value']}";
	}

	protected function WhereNested($where)
	{
		return '('.substr($this->wheres($where['query']), 6).')';
	}

	protected function where_in($where)
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

	protected function groupings(QxQuery $query)
	{
		return 'GROUP BY ' . $this->columnize($query->groupings);
	}

	protected function orderings(QxQuery $query)
	{
		foreach($query->orderings as $order)
		{
			$sql[] = $order['column'].' '.$order['direction'];
		}

		return 'ORDER BY ' . implode(', ', $sql);
	}

	protected function limit(QxQuery $query)
	{
		return 'LIMIT '.$query->limit;
	}

	protected function offset(QxQuery $query)
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
	final protected function concatenate($components)
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
	final protected function components(QxQuery $query)
	{
		$sql = array();
		foreach($this->components as $component)
		{
			if( ! is_null($query->$component) )
			{
				$sql[$component] = call_user_func(array($this, $component), $query);
			}
		}

		return $sql;
	}

	final protected function columnize($columns)
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

	public static function ToUpperCase($value)
	{
		
	}
}
