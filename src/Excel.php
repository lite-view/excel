<?php
/**
 * PHP excel 操作
 * 依赖：https://github.com/PHPOffice/PhpSpreadsheet
 * 安装：composer require phpoffice/phpspreadsheet
 * ***************************
 * 导出示例：
 * $title = ['id' => '编号', 'name' => '姓名'];
 * $data = [['id' => '1', 'name' => '张三'], ['id' => '2', 'name' => '李四']];
 * ExcelHelper::exportExcel($title, $data);
 */

namespace LiteView\Excel;


use LiteView\Excel\Intents\Export;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Excel
{
    public static function export($title, $data, $default = [], $filter = null)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        //表头
        $column = 1;
        foreach ($title as $nil => $value) {
            if (is_array($value)) {
                // 设置列宽度
                if (empty($value['width']) && 2 === count($value)) {
                    list($value, $width) = $value;
                } else {
                    $value = $value['value'];
                    $width = $value['width'];
                }
                $sheet->getColumnDimensionByColumn($column)->setWidth($width);
            }
            $sheet->setCellValueByColumnAndRow($column, 1, $value);
            $column++;
        }
        //表格数据
        $row = 2; // 从第二行开始
        foreach ($data as $item) {
            $column = 1;
            foreach ($title as $field => $nil) {
                if (isset($item[$field])) {
                    $value = $item[$field];
                } else {
                    $value = $default[$field] ?? '';
                }
                if (is_callable($filter)) {
                    //$value .= "\t";
                    $value = $filter($value);
                }
                $sheet->setCellValueByColumnAndRow($column, $row, $value);
                $column++;
            }
            $row++;
        }
        return new Export($spreadsheet);
    }

    public static function read($path, $callback = null)
    {
        $currSheet = IOFactory::createReader('Xlsx')->load($path)->getSheet(0);
        $rowCnt = $currSheet->getHighestRow();
        $data = [];
        $error = null;
        $validated = [];
        for ($row = 1; $row <= $rowCnt; $row++) {
            $columnCnt = Coordinate::columnIndexFromString($currSheet->getHighestColumn());
            for ($column = 1; $column <= $columnCnt; $column++) {
                $cellName = Coordinate::stringFromColumnIndex($column);
                $data[$row][$cellName] = $currSheet->getCell($cellName . $row)->getFormattedValue();
            }
            if (is_callable($callback)) {
                $result = $callback($data[$row], $row);
                if (!empty($result['error'])) {
                    $error[] = $result['error'];
                }
                if (!empty($result['data'])) {
                    $validated[] = $result['data'];
                }
            }
        }
        return [$error, $validated, $data];
    }
}