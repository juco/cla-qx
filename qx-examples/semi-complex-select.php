<?php
// A SELECT with JOIN's and nested WHERE clases
$q = QxQuery::I()->Select(array('c.*', 'COUNT(il.id)' => 'like_count', 'attatchment.name' => 'attachment_name'))
	->From('innovate_comments', 'c')
	->Join('innovate_likes', function($join) {
		$join->On('aggregation', '=', 77);
		$join->On('object_id', '=', 'c.id');
	}, null, null, 'LEFT')
	->Join('innovate_attachment', 'innovate_attachment.id', 'c.attachment_id')
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