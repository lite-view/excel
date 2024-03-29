<?php


namespace App\Backend\Services;


use LiteView\Excel\Excel;

class ImpService
{
    public static function import()
    {
        list($error, $validated, $data) = Excel::read($_FILES['file']['tmp_name'], function ($row, $ln) {
            if ($ln <= 1) {
                return null;
            }
            $data = self::rowCollect($row);
            //验证通过的数据
            return ['data' => $data];
        });
        if ($error) {
            return implode(';', $error);
        }
        if (empty($validated)) {
            return '没有可导入的数据';
        }
        return 0;
    }

    private static function rowCollect($row)
    {
        $data = [];
        $title = self::getExcelTitle();
        $keys = array_keys($title);
        foreach ($keys as $i => $field) {
            $data[$field] = $row[Excel::columnConvert($i)];
        }
        return $data;
    }

    private static function getExcelTitle()
    {
        return [
            'name' => '姓名',
            'tel' => '电话',
            'rank' => '总决赛排名',
            'weight_total' => '总重量',
            'sbc_weight' => '上半场重量',
            'sbc_rank' => '上半场排名',
            'xbc_weight' => '下半场重量',
            'xbc_rank' => '下半场排名',
        ];
    }

    public static function template()
    {
        $title = self::getExcelTitle();
        Excel::export($title, [])->down('jinjishai');
    }
}