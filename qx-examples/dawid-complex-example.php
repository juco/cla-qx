<?php
$sub = QxQuery::I()
	->select(array(
		'aa.id',
		'aa.score',
		'aa.question_version_id',
		'qs.capability_bit' => 'capability',
		'qs.position' => 'qs_position',
		'qs.name question_set',
		'q.position' => 'q_position'
		)
	)
	->from('oat_assurance_answer', 'aa')
	->join('oat_assurance a', 'aa.assurance_id', '=', 'a.id', 'LEFT')
	->join('oat_exercise e', 'e.id', '=', 'a.object_id', 'LEFT')
	->join('groups g', 'e.region', '=', 'g.groupid', 'LEFT')
	->join('oat_incident i', 'i.id', '=', 'a.object_id', 'LEFT')
	->join('groups g1', 'i.region', '=', 'g1.groupid', 'LEFT')
	->join('oat_question_set qs', 'aa.question_set_id', '=', 'qs.id', 'LEFT')
	->join('oat_question q', 'qs.id', '=', 'q.question_set_id', 'LEFT')
	->where('aa.score', '>', 0)
	->where('a.status', '=', 1)
	->where('a.date_completed', '>=', 20111122235900)
	->where('a.date_completed', '<', 20121122235900)
	->group_by(array('aa.id', 'qs.capability_bit'));

$q = QxQuery::I()->select(array(
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
	->from('oat_question_version', 'qv')
	->joinQx($sub, 'qv.id', '=', 'daw.question_version_id', 'LEFT', 'daw')
	->where_not_null('daw.id')
	->group_by('qv.id');

echo $q;
my_print_r($q->get());