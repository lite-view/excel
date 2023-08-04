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
        //打开后报告部分内容有问题，是否让我们修复尽量尝试恢复
        //只要在header前面加上ob_end_clean();这句代码，清除缓冲区
        //结尾肯定加了的东西，最后在save后面加上die或者exit就行了。
        // so
        exit();
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