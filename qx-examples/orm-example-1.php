<?php
// An example using one of the static aggregation ORM methods
echo InnovateTest::Total();

// An example using the ORM to return a QXQuery with additional conditions applied
$test = new InnovateTest();
echo $test->query()->where('user_id', '=', 1)->where_in('id', QxQuery::I()->select('id')->from('foo')->where('x', '=', 2));