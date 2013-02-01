<?php
// A SELECT with JOIN's and nested WHERE clases
$q = QxQuery::I()->select(array('c.*', 'COUNT(il.id)', 'attatchment.name' => 'attachment_name'))
	->from('innovate_comments', 'c')
	->join('innovate_likes', function($join) {
		$join->on('aggregation', '=', 77);
		$join->on('object_id', '=', 'c.id');
	})
	->join('innovate_attachment', 'innovate_attachment.id', 'c.attachment_id')
	->WhereNested(function($query) {
		$query->Where('c.aggregation', '=', 77);
		$query->Where('c.object_id', '=', 1);
	})
	->WhereNested(function($query) {
		$query->Where('c.aggregation', '=', 78);
		$query->Where('c.object_id', '=', 1);
	});
// Return the SQL as a string
echo $q;

// Returns the first entry from the previous query
my_print_r($q->first());