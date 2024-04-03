<?php


namespace App\Backend\Services;

/*
 * 导入目录层级约定如下所示：
 *      新建文件夹/3_测试商品导入图片/1_橱窗图
 *      新建文件夹/3_测试商品导入图片/2_详情图
 *          3为商品ID
 *          1为橱窗图
 *          2为详情图
 * */


use ZipArchive;

class ImpZipService
{
    public $root;

    public function import()
    {
        if (empty($_FILES['file'])) {
            return '获取文件失败';
        }
        if (!empty($_FILES['file']['error'])) {
            $err = [
                '文件上传成功',//其值为 0，没有错误发生，文件上传成功。
                '超过 upload_max_filesize 值',//其值为 1，上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值。
                '超过 MAX_FILE_SIZE 值',//其值为 2，上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值。
                '文件只有部分被上传',//其值为 3，文件只有部分被上传。
                '没有文件被上传',//其值为 4，没有文件被上传。
                '找不到临时文件夹',//其值为 6，找不到临时文件夹。PHP 4.3.10 和 PHP 5.0.3 引进。
                '文件写入失败',//其值为 7，文件写入失败。PHP 5.1.0 引进。
            ];
            return $err[$_FILES['file']['error']];
        }

        $unzip = new ZipArchive();
        $unzip->open($_FILES['file']['tmp_name']);
        $this->root = root_path('storage/tmp/' . date('His') . mt_rand(1000, 9999));
        $unzip->extractTo($this->root); //解压保存到某个位置

        $data = [];
        for ($i = 0; $i < $unzip->numFiles; $i++) {
            $info = pathinfo($unzip->getNameIndex($i));
            if (in_array($info['dirname'], ['.', '..'])) {
                continue;
            }
            if (isset($info['extension'])) {
                $data[] = $info;
            }
        }
        $unzip->close();
        return $this->handle($data);
    }

    private function handle($data)
    {
        $imgs = [];
        foreach ($data as $one) {
            if (!in_array($one['extension'], ['png', 'jpg'])) {
                return '只能导入png和jpg图片';
            }

            $arr = explode('/', $one['dirname']);
            if (count($arr) != 3) {
                return '文件格不符合约定规则：目录应为3层';
            }

            $goods_id = (int)explode('_', $arr[1])[0];
            $type = (int)explode('_', $arr[2])[0];
            if (!in_array($type, [1, 2])) {
                return '文件格不符合约定规则：无法获取图片分类';
            }

            $imgs[$goods_id][$type][] = $one;
        }
        if (empty($imgs)) {
            return '没有可导入的图片';
        }

        //echo json_encode($imgs);
        foreach ($imgs as $goods_id => $item) {
            $this->save_cc($goods_id, $item[1]); // 橱窗图
            $this->save_xq($goods_id, $item[2]); // 详情图
        }

        $this->del_tmp($this->root); //删除临时文件
        return null; //成功
    }

    private function save_cc($goods_id, $data)
    {
        $imgs = [];
        foreach ($data as $one) {
            $full_path = "{$this->root}/{$one['dirname']}/{$one['basename']}";
            $file_path = Img::file_name_oss($one['extension']);
            (new OssHelper())->uploadFile($full_path, $file_path);
            Img::add($file_path, $full_path, $one['filename']);
            $imgs[] = $file_path;
        }
        Crud::db()->update('sc_goods', ['img' => implode(',', $imgs)], 'id = ' . $goods_id);
    }

    private function save_xq($goods_id, $data)
    {
        $detail = '';
        foreach ($data as $one) {
            $full_path = "{$this->root}/{$one['dirname']}/{$one['basename']}";
            $file_path = Img::file_name_oss($one['extension']);
            (new OssHelper())->uploadFile($full_path, $file_path);
            //<p></p>
            //<img src="" title="1712124196901822.jpg" alt="20160627212646493.jpg"/>
            //<img src="" title="1712124163847434.jpg" alt="2022012810ocvyrxghwxjmtqfukiladspebn.jpg"/></p>
            $detail .= sprintf('<img src="%s" title="%s" alt=""/>', Img::show($file_path), $one['filename']);
        }
        Crud::db()->update('sc_goods', ['detail' => "<p>$detail</p>"], 'id = ' . $goods_id);
    }

    private function del_tmp($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!$this->del_tmp($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        return rmdir($dir);
    }
}