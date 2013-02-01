<?php
/**
 * The grammar class for MSSQL
 */
class QxGrammarMs extends QxGrammar {

	protected $components = array(
		'selects', 'limit', 'from', 'joins', 'wheres', 'groupings', 'havings', 'orderings', 'offset'
	);

	protected function limit(QxQuery $query)
	{
		return 'TOP '.$query->limit;
	}

	protected function offset(QxQuery $query)
	{
		return 'OFFSET '.$query->offset . ' ROWS ';
	}
}
