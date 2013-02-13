<?php
// An example using one of the static aggregation ORM methods
echo ExampleClass::Total();

// An example using the ORM to return a QXQuery with additional conditions applied
echo ExampleClass::I()->Query()
	->Where('user_id', '=', 1)
	->WhereIn('id', 
		QxQuery::I()->Select('id')->From('foo')->Where('x', '=', 2)
	);