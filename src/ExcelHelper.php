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

namespace App\Helpers;


use ArrayObject;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ExcelHelper
{
    /**
     */
    public static function exportExcel($title, $data, $filename = null, $df = [], $path = null)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        //表头
        $column = 1;
        foreach ($title as $key => $value) {
            if (is_array($value)) {
                list($value, $width) = $value;
                $sheet->setCellValueByColumnAndRow($column, 1, $value);
                $sheet->getColumnDimensionByColumn($column)->setWidth($width);
            } else {
                $sheet->setCellValueByColumnAndRow($column, 1, $value);
            }
            $column++;
        }
        //表格数据
        $row = 2; // 从第二行开始
        foreach ($data as $item) {
            $column = 1;
            $item = new ArrayObject($item, ArrayObject::ARRAY_AS_PROPS);
            foreach ($title as $field => $nil) {
                if (isset($item[$field])) {
                    $val = (string)$item[$field];
                } else {
                    $val = $df[$field] ?? '';
                }
                if (is_numeric($val) && strlen($val) > 8) {
                    $val .= "\t";
                }
                $sheet->setCellValueByColumnAndRow($column, $row, $val);
                $column++;
            }
            $row++;
        }
        //导出
        if (empty($filename)) $filename = date("Y-m-d-") . time();
        $filename .= '.xlsx';
        $filename = urlencode($filename); //防止中文文件名乱码
        try {
            if ($path) {
                $filename = rtrim($path, '/') . '/' . $filename;
                IOFactory::createWriter($spreadsheet, 'Xlsx')->save($filename); //保存到本地
                return $filename;
            } else {
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=UTF-8');
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                header('Cache-Control: max-age=0');
                IOFactory::createWriter($spreadsheet, 'Xlsx')->save('php://output');
                exit();
            }
        } catch (\Throwable $e) {
            var_dump($e->getMessage());
            exit();
        }
    }

    public static function readExcel($filePath, $callback = null, $needSheet = false)
    {
        if (!file_exists($filePath)) {
            $tmpPath = root_path() . '/' . time() . rand(10000, 99999) . '.xlsx';
            //file_put_contents($tmpPath, file_get_contents($filePath));
            file_put_contents($tmpPath, file_get_contents($_FILES['file']['tmp_name']));
            $filePath = $tmpPath;
        }

        $objRead = IOFactory::createReader('Xlsx');
        $obj = $objRead->load($filePath);
        if (!empty($tmpPath)) {
            unlink($tmpPath);
        }

        $currSheet = $obj->getSheet(0);
        if ($needSheet) {
            return $currSheet;
        }

        $rowCnt = $currSheet->getHighestRow();
        $data = [];
        for ($row = 1; $row <= $rowCnt; $row++) {
            $columnH = $currSheet->getHighestColumn();
            $columnCnt = Coordinate::columnIndexFromString($columnH);
            for ($column = 1; $column <= $columnCnt; $column++) {
                $cellName = Coordinate::stringFromColumnIndex($column);
                $data[$row][$cellName] = $currSheet->getCell($cellName . $row)->getFormattedValue();
            }
            if (is_callable($callback)) {
                $callback($data[$row], $row);
            }
        }
        return $data;
    }
}
