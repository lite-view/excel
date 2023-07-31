<?php

require_once __DIR__ . '/vendor/autoload.php';

$title = ['id' => '编号', 'name' => '姓名'];
$data = [['id' => '1', 'name' => '张三'], ['id' => '2', 'name' => '李四']];
\LiteView\Excel\Excel::export($title, $data)->save('./');
