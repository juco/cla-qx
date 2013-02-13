<?php
$sub = QxQuery::I()
	->Select(array(
		'aa.id',
		'aa.score',
		'aa.question_version_id',
		'qs.capability_bit' => 'capability',
		'qs.position' => 'qs_position',
		'qs.name question_set',
		'q.position' => 'q_position'
		)
	)
	->From('oat_assurance_answer', 'aa')
	->Join('oat_assurance a', 'aa.assurance_id', '=', 'a.id', 'LEFT')
	->Join('oat_exercise e', 'e.id', '=', 'a.object_id', 'LEFT')
	->Join('groups g', 'e.region', '=', 'g.groupid', 'LEFT')
	->Join('oat_incident i', 'i.id', '=', 'a.object_id', 'LEFT')
	->Join('groups g1', 'i.region', '=', 'g1.groupid', 'LEFT')
	->Join('oat_question_set qs', 'aa.question_set_id', '=', 'qs.id', 'LEFT')
	->Join('oat_question q', 'qs.id', '=', 'q.question_set_id', 'LEFT')
	->Where('aa.score', '>', 0)
	->Where('a.status', '=', 1)
	->Where('a.date_completed', '>=', 20111122235900)
	->Where('a.date_completed', '<', 20121122235900)
	->GroupBy(array('aa.id', 'qs.capability_bit'));

$q = QxQuery::I()->Select(array(
		'SUM(IF(daw.score > 0,1,0))' => 'total',
		'AVG(daw.score)' => 'avg',
		'SUM(IF(daw.score = 1,1,0))' => 's_1',
		'SUM(IF(daw.score = 2,1,0))' => 's_2',
		'SUM(IF(daw.score = 3,1,0))' => 's_3',
		'SUM(IF(daw.score = 4,1,0))' => 's_4',
		'daw.score',
		'capability',
		'qs_position',
		'question_set',
		'q_position',
		'qv.name',
		'daw.question_version_id'
		))
	->From('oat_question_version', 'qv')
	->JoinQx($sub, 'qv.id', '=', 'daw.question_version_id', 'LEFT', 'daw')
	->Where_not_null('daw.id')
	->GroupBy('qv.id');

// Print out the query..
echo $q;

// And execute it :-)
my_print_r($q->Get());