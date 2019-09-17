<?php
/**
 * Created by PhpStorm.
 * User: luoxulx
 * Date: 2019/8/22
 * Time: 下午5:41
 */

namespace App\Repositories;


class Live2dRepository
{

    public $modelPath;
    public $modelList;

    public function __construct()
    {
        $this->modelPath = 'https://net.lnmpa.top/l2d/model/';//暂时不能放到 CDN
        //$this->modelPath = env('APP_URL') . '/model/';
        /* 获取模型列表 */
        $this->setModelList();
    }

    /* 获取模组名称 */
    public function idToName(int $id)
    {
        $list = $this->getModelList();
        return $list['models'][$id-1];
    }

    /* 转换模型名称 */
    public function nameToId(string $name)
    {
        $list = $this->getModelList();
        $id = array_search($name, $list['models']);
        return is_numeric($id) ? $id + 1 : false;
    }

    /* 获取材质名称 */
    public function getTextureName($modelName, $id)
    {
        $list = $this->getTextureList($modelName);
        return $list['textures'][(int)$id - 1];
    }

    /* 获取列表缓存 */
    public function getTextureList(string $name)
    {
        if (file_exists($this->modelPath . $name . '/textures.cache')) {
            $textures = json_decode(file_get_contents($this->modelPath . $name . '/textures.cache'), true);
        } else {
            $textures = $this->getTextures($name);
            if (!empty($textures)) file_put_contents($this->modelPath . $name . '/textures.cache', str_replace('\/', '/', json_encode($textures)));
        }
        return isset($textures) ? array('textures' => $textures) : false;
    }

    /* 获取材质列表 */
    /**
     * @param string $name
     * @return array|null
     */
    public function getTextures(string $name)
    {
        if (file_exists($this->modelPath . $name . '/textures_order.json')) {
            // 读取材质组合规则
            $tmp = array();
            foreach (json_decode(file_get_contents($this->modelPath . $name . '/textures_order.json'), true) as $k => $v) {
                $tmp2 = array();
                foreach ($v as $textures_dir) {
                    $tmp3 = array();
                    foreach (glob($this->modelPath . $name . '/' . $textures_dir . '/*') as $n => $m)
                        $tmp3['merge' . $n] = str_replace($this->modelPath . $name . '/', '', $m);
                    $tmp2 = array_merge_recursive($tmp2, $tmp3);
                }
                foreach ($tmp2 as $v4) $tmp4[$k][] = str_replace('\/', '/', json_encode($v4));
                $tmp = $this->array_exhaustive($tmp, $tmp4[$k]);
            }
            foreach ($tmp as $v) $textures[] = json_decode('[' . $v . ']', true);
            return $textures;
        } else {
            foreach (glob($this->modelPath . $name . '/textures/*') as $v)
                $textures[] = str_replace($this->modelPath . $name . '/', '', $v);
            return empty($textures) ? null : $textures;
        }
    }


    /* 数组穷举合并 */
    private function array_exhaustive($arr1, $arr2)
    {
        foreach ($arr2 as $k => $v) {
            if (empty($arr1)) $out[] = $v;
            else foreach ($arr1 as $k2 => $v2) $out[] = str_replace('"["', '","', str_replace('"]"', '","', $v2 . $v));
        }
        return $out;
    }

    /**
     * 获取模型列表
     * @return mixed
     */
    public function getModelList()
    {
        return $this->modelList;
    }

    protected function setModelList(): void
    {
        $this->modelList = json_decode(file_get_contents(base_path('model_list.json')), true);
    }
}
