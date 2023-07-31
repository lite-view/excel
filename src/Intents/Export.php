<?php

namespace LiteView\Excel\Intents;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Export
{
    private $spreadsheet;

    public function __construct(Spreadsheet $spreadsheet)
    {
        $this->spreadsheet = $spreadsheet;
    }

    public function down($filename = null)
    {
        if (empty($filename)) {
            $filename = date("Y-m-d-") . time();
        }
        $filename .= '.xlsx';
        $filename = urlencode($filename); //防止中文文件名乱码
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=UTF-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        IOFactory::createWriter($this->spreadsheet, 'Xlsx')->save('php://output');
    }

    public function save($path, $filename = null)
    {
        if (empty($filename)) {
            $filename = date("Y-m-d-") . time();
        }
        $filename .= '.xlsx';
        $filename = urlencode($filename); //防止中文文件名乱码
        $filename = rtrim($path, '/') . '/' . $filename;
        IOFactory::createWriter($this->spreadsheet, 'Xlsx')->save($filename); //保存到本地
        return $filename;
    }
}