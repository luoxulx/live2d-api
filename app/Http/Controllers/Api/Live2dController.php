<?php
/**
 * Created by PhpStorm.
 * User: luoxulx
 * Date: 2019/8/22
 * Time: 下午5:37
 */

namespace App\Http\Controllers\Api;


use App\Repositories\Live2dRepository;
// bug待修复
class Live2dController extends BaseController
{

    protected $live2d;

    public function __construct(Live2dRepository $live2dRepository)
    {
        parent::__construct();
        $this->live2d = $live2dRepository;
    }


    /**
     * 检测 新增皮肤 并更新 缓存列表
     * /add/
     */
    public function checkUpdate(): \Illuminate\Http\JsonResponse
    {
        $modelList = $this->live2d->getModelList();
        $return = [];

        foreach ($modelList['models'] as $name) {
            if (!is_array($name) && file_exists($this->live2d->modelPath . $name . '/textures.cache')) {
                $textures = $texturesNew = array();
                $modelTexturesList = $this->live2d->getTextureList($name);
                $modelNameTextures = $this->live2d->getTextures($name);

                if (is_array($modelTexturesList)) {
                    foreach ($modelTexturesList['textures'] as $v) {
                        $textures[] = str_replace('\/', '/', json_encode($v));
                    }
                }

                if (is_array($modelNameTextures)) {
                    foreach ($modelNameTextures as $v) {
                        $texturesNew[] = str_replace('\/', '/', json_encode($v));
                    }
                }

                $texturesDiff = array_diff($texturesNew, $textures);

                if (empty($textures)) {
                    continue;
                } elseif (empty($texturesDiff)) {
                    $return[$name] = $name . ' / textures.cache / No Update.';
                } else {
                    foreach (array_values(array_unique(array_merge($textures, $texturesNew))) as $v) {
                        $texturesMerge[] = json_decode($v, 1);
                    }

                    file_put_contents($this->live2d->modelPath . $name . '/textures.cache', str_replace('\/', '/', json_encode($texturesMerge)));
                    $return[$name] = $name . ' / textures.cache / Updated.';
                }
            } elseif (is_array($name)) {
                continue;
            } elseif ($this->live2d->getTextureList($name)) {
                $return[$name] = $name . ' / textures.cache / Updated.';
            }
        }

        return $this->response->json($return);
    }

    /**
     * 获取 分组 1 的 第 23 号 皮肤
     * /get/?id=1-23;;;获取的链接会多一个host地址
     */
    public function getSkin(): \Illuminate\Http\JsonResponse
    {
        $id = $_GET['id'];
        if (! $id) {
            return $this->response->setStatusCode(422)->json(['message' => 'param miss']);
        }

        $id = explode('-', $id);
        $modelId = (int) $id[0];
        $modelTexturesId = isset($id[1]) ? (integer) $id[1] : 0;
        $modelName = $this->live2d->idToName($modelId);

        if (is_array($modelName)) {
            $modelName = $modelTexturesId > 0 ? $modelName[$modelTexturesId - 1] : $modelName[0];
            $json = json_decode(file_get_contents($this->live2d->modelPath . $modelName . '/index.json'), 1);
        } else {
            $json = json_decode(file_get_contents($this->live2d->modelPath . $modelName . '/index.json'), 1);
            if ($modelTexturesId > 0) {
                $modelTexturesName = $this->live2d->getTextureName($modelName, $modelTexturesId);
                if (isset($modelTexturesName)) {
                    $json['textures'] = is_array($modelTexturesName) ? $modelTexturesName : array($modelTexturesName);
                }
            }
        }

        $textures = json_encode($json['textures']);
        $textures = str_replace('texture', $this->live2d->modelPath . $modelName . '/texture', $textures);
        $textures = json_decode($textures, 1);
        $json['textures'] = $textures;

        $json['model'] = $this->live2d->modelPath . $modelName . '/' . $json['model'];
        if (isset($json['pose'])) {
            $json['pose'] = $this->live2d->modelPath . $modelName . '/' . $json['pose'];
        }

        if (isset($json['physics'])) {
            $json['physics'] = $this->live2d->modelPath . $modelName . '/' . $json['physics'];
        }

        if (isset($json['motions'])) {
            $motions = json_encode($json['motions']);
            $motions = str_replace('sounds', $this->live2d->modelPath . $modelName . '/sounds', $motions);
            $motions = str_replace('motions', $this->live2d->modelPath . $modelName . '/motions', $motions);
            $motions = json_decode($motions, 1);
            $json['motions'] = $motions;
        }

        if (isset($json['expressions'])) {
            $expressions = json_encode($json['expressions']);
            $expressions = str_replace('expressions', $this->live2d->modelPath . $modelName . '/expressions', $expressions);
            $expressions = json_decode($expressions, 1);
            $json['expressions'] = $expressions;
        }

        return $this->response->json($json);
    }

    /**
     * 根据 上一分组 随机切换
     * /rand/?id=1
     */
    public function randByParent()
    {
        $id = intval($_GET['id']);
        if (! $id) {
            return $this->response->setStatusCode(422)->json(['message' => 'param miss']);
        }

        $modelList = $this->live2d->getModelList();

        $modelRandNewId = true;
        while ($modelRandNewId) {
            $modelRandId = random_int(0, count($modelList['models']) - 1) + 1;
            $modelRandNewId = $modelRandId == $id ? true : false;
        }

        $data = array('model' => array(
            'id' => $modelRandId,
            'name' => $modelList['models'][$modelRandId - 1],
            'message' => $modelList['messages'][$modelRandId - 1],
        ));

        return $this->response->json($data);
    }

    /**
     * 根据 上一分组 顺序切换
     * /switch/?id=1
     */
    public function switchByParent()
    {
        $id = intval($_GET['id']);
        if (! $id) {
            return $this->response->setStatusCode(422)->json(['message' => 'param miss']);
        }

        $modelList = $this->live2d->getModelList();
        $modelSwitchId = $id + 1;

        if (!isset($modelList['models'][$modelSwitchId-1])) $modelSwitchId = 1;

        $data = array('model' => array(
            'id' => $modelSwitchId,
            'name' => $modelList['models'][$modelSwitchId-1],
            'message' => $modelList['messages'][$modelSwitchId-1]
        ));

        return $this->response->json($data);
    }

    /**
     * 根据 上一皮肤 随机切换 同分组其他皮肤
     * /rand_textures/?id=1-23
     */
    public function randTexturesByParent()
    {
        $id = $_GET['id'];
        if (! $id) {
            return $this->response->setStatusCode(422)->json(['message' => 'param miss']);
        }

        $id = explode('-', $id);
        $modelId = (int)$id[0];
        $modelTexturesId = isset($id[1]) ? (int)$id[1] : false;

        $modelName = $this->live2d->idToName($modelId);
        $modelTexturesList = is_array($modelName) ? array('textures' => $modelName) : $this->live2d->getTextureList($modelName);

        if (count($modelTexturesList['textures']) <= 1) {
            $modelTexturesNewId = 1;
        } else {
            $modelTexturesGenNewId = true;
            if ($modelTexturesId == 0) $modelTexturesId = 1;
            while ($modelTexturesGenNewId) {
                $modelTexturesNewId = random_int(0, count($modelTexturesList['textures'])-1)+1;
                $modelTexturesGenNewId = $modelTexturesNewId == $modelTexturesId ? true : false;
            }
        }

        $data = array('textures' => array(
            'id' => $modelTexturesNewId,
            'name' => $modelTexturesList['textures'][$modelTexturesNewId-1],
            'model' => is_array($modelName) ? $modelName[$modelTexturesNewId-1] : $modelName
        ));

        return $this->response->json($data);
    }

    /**
     * 根据 上一皮肤 顺序切换 同分组其他皮肤
     * /switch_textures/?id=1-23
     */
    public function switchTexturesByParent()
    {
        $id = $_GET['id'];
        if (! $id) {
            return $this->response->setStatusCode(422)->json(['message' => 'param miss']);
        }

        $id = explode('-', $id);
        $modelId = (int)$id[0];
        $modelTexturesId = isset($id[1]) ? (int)$id[1] : 0;

        $modelName = $this->live2d->idToName($modelId);
        $modelTexturesList = is_array($modelName) ? array('textures' => $modelName) : $this->live2d->getTextureList($modelName);
        $modelTexturesNewId = $modelTexturesId == 0 ? 2 : $modelTexturesId + 1;
        if (!isset($modelTexturesList['textures'][$modelTexturesNewId-1])) $modelTexturesNewId = 1;

        $data = array('textures' => array(
            'id' => $modelTexturesNewId,
            'name' => $modelTexturesList['textures'][$modelTexturesNewId-1],
            'model' => is_array($modelName) ? $modelName[$modelTexturesNewId-1] : $modelName
        ));

        return $this->response->json($data);
    }
}
