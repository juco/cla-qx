<?php
/**
 * The QxModel ORM Class
 */
abstract class QxModel extends ErrorHandler {

	public $exists;

	public $use_table_alias = true;

	public static $table_name;

	public static $table_alias;

	public function __construct($attributes = array(), $exists = false)
	{
		// TODO: Fill attributes

		$this->exists = $exists;
	}

	public function Fill($attributes)
	{
		if(!is_array($attributes))
			throw new Exception("Trying to fill __CLASS__ with unknown type: ".gettype($attributes));

		foreach($attributes as $att)
		{
			$this->$att = $att;
		}

		return $this;
	}

	public function Query()
	{
		return QxQuery::I()->from($this->table(), $this->use_table_alias ? $this->TableAlias() : null);
	}

	public function Table()
	{
		return static::$table_name ?: strtolower(trim(preg_replace("/([A-Z])/", '_$1', get_class($this)), "_"));
	}

	/**
	 * Get the table alias
	 *
	 * @return string
	 */
	public function TableAlias()
	{
		// Definied one? Use that instead
		if($this->table_alias) return $this->table_alias;
		// We will simply join the first, and each char preceeding an _ as the alias
		if(!preg_match_all('/^[a-z]|_[a-z]/', $this->table(), $matches))
			return 'xxx';
		return str_replace('_', '', implode('', $matches[0]));
	}

	public function __set($name, $value)
	{
		if(method_exists($this, 'Set'.DBObject::ToClass($name)))
		{
			call_user_func('Set'.DBObject::ToClass($name), $value);
		}
		else
		{
			$this->{DBObject::ToClass($name)} = $value;
		}

		return $this;
	}

	/**********************************************************
	 *
	 *		STATIC AGGREGATION METHODS
	 *
	 */
	 public static function Total()
	 {
	 	$self = new static();
	 	$res = $self->Query()->select('COUNT(id)')->first();
	 	return (int)array_shift($res);
	 }
	 public static function Max()
	 {
	 	$self = new static();
	 	$res = $self->Query()->select('MAX(id)')->first();
	 	return (int)array_shift($res);
	 }
	 public static function Min()
	 {
	 	$self = new static();
	 	$res = $self->Query()->select('MIN(id)')->first();
	 	return (int)array_shift($res);
	 }
}
