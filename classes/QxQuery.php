<?php
class QxQuery {

	public $selects = array('*');

	public $from;

	public $wheres;

	public $having;

	public $groupings = null;

	public $distinct = false;

	public $limit;

	public $offset = null;

	public $orderings = null;

	public $bindings = array();


	protected $grammar;

	public function __construct()
	{
		// TODO: Check for DB type and instantiate the correct object
		$this->grammar = new QxGrammar();
	}

	public static function I()
	{
		return new static();
	}

	public function Select($columns = array('*'))
	{
		// Been given a string? Explode it!
		if(is_string($columns))
		{
			// Reset the columns, use only those we want
			$this->selects = array();
			$cols = explode(',', $columns);
			foreach($cols as $col)
			{
				$this->selects[] = trim($col);
			}
		}
		else
		{
			$this->selects = $columns;
		}

		return $this;
	}

	public function From($from, $alias = null)
	{
		$this->from = compact('from', 'alias');
		return $this;
	}

	public function Where($column, $operator = null, $value = null, $connector = 'AND')
	{
		$type = 'where';

		$this->wheres[] = compact('type', 'column', 'operator', 'value', 'connector');

		$this->bindings[] = $value;

		return $this;
	}

	public function AndWhere($column, $operator = null, $value = null)
	{
		return $this->where($column, $operator, $value, 'AND');
	}

	public function ORWhere($column, $operator = null, $value = null)
	{
		return $this->where($column, $operator, $value, 'OR');
	}

	public function WhereNested($callback, $connector = 'AND')
	{
		$type = 'whereNested';

		// To handle a nested where statement, we will actually instantiate a new
		// Query instance and run the callback over that instance, which will
		// allow the developer to have a fresh query instance
		$query = new static($this->connection, $this->grammar, $this->from);

		call_user_func($callback, $query);

		// Once the callback has been run on the query, we will store the nested
		// query instance on the where clause array so that it's passed to the
		// query's query grammar instance when building.
		if ($query->wheres !== null)
		{
			$this->wheres[] = compact('type', 'query', 'connector');
		}

		$this->bindings = array_merge($this->bindings, $query->bindings);

		return $this;
	}

	/**
	* Apply a WHERE NOT NULL
	*
	* @param  string $column The column name
	* @param  string $connector The Connector AND/OR
	* @return QxQuery
	*/
	public function WhereNotNull($column, $connector = 'AND')
	{
		return $this->where($column, 'IS NOT', 'NULL', $connector);
	}

	/**
	* Apply a WHERE IS NULL
	*
	* @param  string $column The column name
	* @param  string $connector The Connector AND/OR
	* @return QxQuery
	*/
	public function WhereNull($column, $connector = 'AND')
	{
		return $this->where($column, 'IS', 'NULL', $connector);
	}

	/**
	* Apply a WHERE IN()
	*
	* @param  string $column The column name
	* @param  mixed 	$value The value. Can be either a string, array or QxQuery
	* @param  string $connector The Connector AND/OR
	* @param  bool $not NOT IN
	* @return QxQuery
	*/
	public function WhereIn($column, $value, $connector = 'AND', $not = false)
	{
		$type = 'where_in';

		$this->wheres[] = compact('type', 'column', 'value', 'connector', 'not');

		return $this;
	}

	/**
	 * Add a QxQuery as join clause to the main query.
	 *
	 * @param  QxQuery $table
	 * @param  mixed   $column1 Either a string column name or Closure to perform multiple ON's
	 * @param  string  $operator
	 * @param  string  $column2
	 * @param  string  $type
	 * @param  string  $alias
	 * @return QxQuery
	 */
	public function JoinQx($table, $column1, $operator = null, $column2 = null, $type = 'INNER', $alias = null)
	{

		// Check if alias exists
		if(!is_string($alias)) throw new Exception('joinQx:: Alias for a sub-query has not been specified!');

		if ($table instanceof QxQuery)
		{
			$table = '(' . (string)$table . ') ' . $alias;
			$this->join($table, $column1, $operator, $column2, $type);
		}
		else
		{
			throw new Exception('joinQx:: $table is not an instance of QxQuery!');
		}
		return $this;
	}

	/**
	 * Add a join clause to the query.
	 *
	 * @param  mixed   $table Either a string table name or array('name', 'alias')
	 * @param  mixed   $column1 Either a string column name or Closure to perform multiple ON's
	 * @param  string  $operator
	 * @param  string  $column2
	 * @param  string  $type
	 * @return QxQuery
	 */
	public function Join($table, $column1, $operator = null, $column2 = null, $type = 'INNER')
	{
		// If the "column" is really an instance of a Closure, the developer is
		// trying to create a join with a complex "ON" clause. So, we will add
		// the join, and then call the Closure with the join/
		if ($column1 instanceof Closure)
		{
			$this->joins[] = new QxJoin($type, $table);

			call_user_func($column1, end($this->joins));
		}

		// If the column is just a string, we can assume that the join just
		// has a simple on clause, and we'll create the join instance and
		// add the clause automatically for the develoepr.
		else
		{
			$join = new QxJoin($type, $table);

			$join->on($column1, $operator, $column2);

			$this->joins[] = $join;
		}

		return $this;
	}

	public function GroupBy($column)
	{
		if(is_array($column)) $this->groupings = array_merge((array)$this->groupings, $column);
		if(is_string($column)) $this->groupings[] = $column;
		return $this;
	}

	public function OrderBy($column, $direction = 'asc')
	{
		$this->orderings[] = compact('column', 'direction');
		return $this;
	}

	public function Limit($limit)
	{
		$this->limit = $limit;
		return $this;
	}

	public function Offset($offset = 0)
	{
		$this->offset = $offset;
		return $this;
	}

	public function First($column = array('*'))
	{
		$res = $this->limit(1)->get();
		return $res[0] ?: null;
	}

	public function Get($columns = array('*'))
	{
		global $db;

		if(is_null($this->selects))
			$this->selects = $column;

		$res = $db->query((string)$this->grammar->select($this));

		$rows = array();
		while($row = $res->fetchArray())
		{
			$rows[] = $row;
		}
		return $rows;
	}

	public function __toString()
	{
		return $this->grammar->select($this);
	}
}
