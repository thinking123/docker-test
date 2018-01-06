<?php

namespace App\Models;

class Layer extends Base
{
    const STATUS_DELETED = '0';
    const STATUS_NORMAL = '1';

    const TYPES = [
        'SCREEN' => '1',
        'TEXT'   => '2',
        'IMAGE'  => '3',
        'BOX'    => '4',
        'ICON'   => '5',
        'SLOT'   => '6'
    ];

    protected $table = 'Layer';

    /**
     * 根据类型名称获取对应数值
     *
     * @param $typeName
     * @return int|null
     */
    public static function getTypeIdByName($typeName)
    {
        $typeName = strtoupper($typeName);

        return isset(static::TYPES[$typeName]) ? static::TYPES[$typeName] : null;
    }

    /**
     * 根据类型 ID 获取对应名称
     *
     * @param int $id
     * @return string|null
     */
    public static function getTypeNameById($id)
    {
        $types = array_flip(static::TYPES);

        return isset($types[$id]) ? $types[$id] : null;
    }

    /**
     * 根据文件 id 获取其 layer
     *
     * @param int $id
     * @param int $depth
     * @return array
     */
    public static function getFileLayers($id, $depth = 5)
    {
        if ($depth < 1) {
            return [];
        }

        $layers = Layer::where('fileId', $id)->where('parentId', 0)->where('status', Layer::STATUS_NORMAL)
            ->orderBy('position', 'DESC')->get()->toArray();

        if ($depth == 1 || empty($layers)) {
            return static::filterLayers($layers);
        }

        $ids = [];
        $referenceIds = [];

        foreach ($layers as $layer) {
            if ($layer['type'] == Layer::getTypeIdByName('SLOT')) {
                if (is_numeric($layer['referenceTo'])) {
                    $referenceIds[] = $layer['referenceTo'];
                }
            } else {
                $ids[] = $layer['id'];
            }
        }

        $children = static::getLayerChildren($ids, $depth - 1);

        if (!empty($children)) {
            foreach ($layers as & $layer) {
                foreach ($children as $parentId => $child) {
                    if ($layer['type'] != Layer::getTypeIdByName('slot') && $layer['id'] == $parentId) {
                        $layer['children'] = $child;
                    }
                }
            }
        }

        $children = static::getComponentLayers($referenceIds, $depth - 1);

        if (!empty($children)) {
            foreach ($layers as & $layer) {
                foreach ($children as $child) {
                    if ($layer['type'] == Layer::getTypeIdByName('slot') && $layer['referenceTo'] == $child['componentId']) {
                        if (!isset($layer['children'])) {
                            $layer['children'] = [];
                        }

                        $layer['children'][] = $child;
                    }
                }
            }
        }

        return $layers;
    }

    /**
     * 根据组件 id 获取其 layer
     *
     * @param int|array $id
     * @param int $depth
     * @return array
     */
    public static function getComponentLayers($id, $depth = 5)
    {
        if ($depth < 1) {
            return [];
        }

        $componentIds = is_array($id) ? $id : [$id];

        if (empty($componentIds)) {
            return [];
        }

        $layers = Layer::where('componentId', $componentIds)->where('parentId', 0)->where('status',
            Layer::STATUS_NORMAL)->orderBy('position', 'DESC')->get()->toArray();

        if ($depth == 1 || empty($layers)) {
            return $layers;
        }

        $ids = [];

        foreach ($layers as $layer) {
            $ids[] = $layer['id'];
        }

        $children = static::getLayerChildren($ids, $depth - 1);

        if (!empty($children)) {
            foreach ($layers as & $layer) {
                foreach ($children as $parentId => $child) {
                    if ($layer['id'] == $parentId) {
                        $layer['children'] = $child;
                    }
                }
            }
        }

        return $layers;
    }

    /**
     * 获取 Layer 的后代
     *
     * @param array $layerIds
     * @param int $depth
     * @return array
     */
    public static function getLayerChildren(array $layerIds, $depth = 5)
    {
        if (empty($layerIds) && $depth < 1) {
            return [];
        }

        $topLayers = Layer::whereIn('id', $layerIds)->where('status', static::STATUS_NORMAL)->get()->toArray();
        if (empty($topLayers)) {
            return [];
        }

        $layerIds = [];
        $referenceIds = [];

        foreach ($topLayers as $layer) {
            if ($layer['type'] == Layer::getTypeIdByName('slot')) {
                if (is_numeric($layer['referenceTo'])) {
                    $referenceIds[] = $layer['referenceTo'];
                }
            } else {
                $layerIds[] = $layer['id'];
            }
        }

        if (empty($layerIds) && empty($referenceIds)) {
            return [];
        }

        $data = [];

        if (!empty($layerIds)) {
            $layers = Layer::whereIn('parentId', $layerIds)->where('status', static::STATUS_NORMAL)
                ->orderBy('position', 'DESC')->get()->toArray();

            if ($depth > 1) {
                $layers = static::getLayerMoreChildren($layers, $depth - 1);
            }

            foreach ($layers as $layer) {
                if (!isset($data[$layer['parentId']])) {
                    $data[$layer['parentId']] = [];
                }
                $data[$layer['parentId']][] = $layer;
            }
        }

        $layers = static::getComponentLayers($referenceIds, $depth - 1);

        if (!empty($layers)) {
            foreach ($topLayers as $topLayer) {
                foreach ($layers as $layer) {
                    if ($topLayer['referenceTo'] == $layer['componentId']) {
                        $data[$topLayer['id']] = [$layer];
                    }
                }
            }
        }

        return $data;
    }

    /**
     * 递归获取 layer 的后代
     *
     * @param array $layers
     * @param int $depth
     * @param bool $init
     * @return array
     */
    protected static function getLayerMoreChildren($layers, $depth, $init = false)
    {
        if (empty($layers) || $depth < 1) {
            return $layers;
        }

        $ids = [];
        $componentIds = [];

        foreach ($layers as $layer) {
            if ($layer['type'] == Layer::getTypeIdByName('slot')) {
                $componentIds[] = $layer['referenceTo'];
            } else {
                $ids[] = $layer['id'];
            }
        }

        if (!empty($ids)) {
            $children = Layer::whereIn('parentId', $ids)->where('status', static::STATUS_NORMAL)
                ->orderBy('position', 'DESC')->get()->toArray();

            $children = static::getLayerMoreChildren($children, $depth - 1);

            foreach ($layers as & $layer) {
                foreach ($children as $child) {
                    if ($child['parentId'] == $layer['id']) {
                        if (isset($layer['children'])) {
                            $layer['children'] = [];
                        }

                        $layer['children'][] = $child;
                    }
                }
            }
        }

        if (!empty($componentIds)) {
            $children = Layer::getComponentLayers($componentIds, $depth - 1);

            foreach ($layers as & $layer) {
                foreach ($children as $child) {
                    if ($layer['referenceTo'] == $child['componentId']) {
                        $layer['children'] = [$child];
                    }
                }
            }
        }

        return $layers;
    }

    /**
     * 格式化 layer
     *
     * @param array $layer
     * @return array
     */
    public static function filter($layer)
    {
        $layer['type'] = static::getTypeNameById($layer['type']);

        unset($layer['status'], $layer['referenceTo']);

        $layer['createdAt'] = strtotime($layer['createdAt']);

        if (!is_null($layer['updatedAt'])) {
            $layer['updatedAt'] = strtotime($layer['updatedAt']);
        }

        if (isset($layer['children']) && !empty($layer['children'])) {
            $layer['children'] = static::filterLayers($layer['children']);
        }

        return $layer;
    }

    /**
     * 格式化 layers
     *
     * @param array $layers
     * @return array
     */
    public static function filterLayers($layers)
    {
        foreach ($layers as & $layer) {
            $layer = static::filter($layer);
        }

        return $layers;
    }

    /**
     * 复制 layer 为 component
     *
     * @param object $layer
     * @param object $component
     * @param int $parentId
     * @return bool
     */
    public static function layerToComponent($layer, $component, $parentId = 0)
    {
        $componentLayer = new static;

        $componentLayer->name = $layer->name;
        $componentLayer->type = $layer->type;
        $componentLayer->componentId = $component->id;
        $componentLayer->parentId = $parentId;
        $componentLayer->position = $layer->position;
        $componentLayer->referenceTo = $layer->referenceTo;
        $componentLayer->data = $layer->data;
        $componentLayer->styles = $layer->styles;
        $componentLayer->status = static::STATUS_NORMAL;
        $componentLayer->createdAt = date('Y-m-d H:i:s');

        if ($saved = $componentLayer->save()) {
            if ($componentLayer->type != static::getTypeIdByName('SLOT')) {
                $data = static::getLayerChildren([$layer->id], 1);

                if (isset($data[$layer->id]) && !empty($data[$layer->id])) {
                    foreach ($data[$layer->id] as $layer) {
                        $childSaved = static::layerToComponent($layer, $component, $componentLayer->id);

                        if (!$childSaved) {
                            return false;
                        }
                    }
                }
            }

            return true;
        }

        return false;
    }
}