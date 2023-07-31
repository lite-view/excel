<?php

require_once __DIR__ . '/vendor/autoload.php';


// 在浏览器中测试 php -S 127.0.0.1:666
$title = ['id' => '编号', 'name' => '姓名'];
$data = [['id' => '1', 'name' => '张三'], ['id' => '2', 'name' => '李四']];
\LiteView\Excel\Excel::export($title, $data)->down();