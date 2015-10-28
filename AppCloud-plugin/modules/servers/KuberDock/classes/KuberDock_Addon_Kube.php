<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

class KuberDock_Addon_Kube extends CL_Model {
    const STANDARD_TYPE = 0;
    const NON_STANDARD_TYPE = 1;

    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'KuberDock_kubes';
    }

    /**
     * @return bool
     */
    public function createKube() {
        $api = $this->getApi();
        $kubeName = $this->getKubeName();
        $addonProduct = KuberDock_Addon_Product::model()->loadById($this->product_id);

        if($addonProduct) {
            $this->setAttributes($addonProduct->getAttributes());
        }

        $attributes = array(
            'name' => $kubeName,
            'cpu' => $this->cpu_limit,
            'cpu_units' => 'Cores',
            'disk_space' => $this->hdd_limit,
            'memory' => $this->memory_limit,
            'memory_units' => 'MB',
            'included_traffic' => (int) $this->traffic_limit,
        );

        try {
            $response = $api->createKube($attributes);
            $data = $response->getData();
            $this->kuber_kube_id = $data['id'];
        } catch(ExistException $e) {
            if($kube = $api->getKubesByName($kubeName)) {
                $this->kuber_kube_id = $kube['id'];
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function updateKube() {
        $api = $this->getApi();
        $kube = new KuberDock_Addon_Kube();
        $kube = $kube->loadById($this->id);

        if(empty($kube->product_id) || is_null($kube->product_id) && !empty($this->kube_price)) {
            // Add kube to package
            unset($this->id);
            $this->action = self::ACTION_INSERT;
            $api->addKubeToPackage($this->kuber_product_id, $this->kuber_kube_id, $this->kube_price);
        } elseif(empty($kube->product_id) && trim($this->kube_price) === '') {
            return true;
        } elseif($kube->product_id && empty($this->kube_price) && trim($this->kube_price) === '') {
            // Delete kube from package
            $this->delete();
            return false;
        } elseif(!empty($kube->product_id) && !empty($this->kube_price)) {
            // Update price
            $api->addKubeToPackage($this->kuber_product_id, $this->kuber_kube_id, $this->kube_price);
        }

        return true;
    }

    /**
     *
     */
    public function deleteKube()
    {
        $api = $this->getApi();
        $api->deleteKube($this->kuber_kube_id);
    }

    /**
     *
     */
    public function deleteKubeFromPackage()
    {
        $api = $this->getApi();
        $kubeIds = $api->getPackageKubesById($this->kuber_product_id)->getData();

        if(in_array($this->kuber_kube_id, array_values($kubeIds))) {
            $api->deletePackageKube($this->kuber_product_id, $this->kuber_kube_id);
        }
    }

    /**
     * @param $productId
     * @return $this
     * @throws CException
     */
    public function getStandartKube($productId)
    {
        $row = $this->loadByAttributes(array(
            'product_id' => $productId,
            'kube_type' => self::STANDARD_TYPE,
        ));

        if(!$row) {
            throw new CException('Standart kube not exist for product: ' . $productId);
        }

        $attributes = current($row);

        return $this->setAttributes($attributes);
    }

    /**
     * @return string
     */
    public function getKubeName()
    {
        return JTransliteration::transliterate($this->kube_name);
    }

    /**
     * @return bool
     */
    public function isStandart()
    {
        return $this->kube_type == self::STANDARD_TYPE;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getServerKubes()
    {
        $api = $this->getApi();

        $kubes = $api->getKubes()->getData();

        return CL_Tools::getKeyAsField($kubes, 'id');
    }

    /**
     * @param int $packageId
     * @return array
     * @throws Exception
     */
    public function getServerPackageKubes($packageId)
    {
        $api = $this->getApi();

        $kubes = $api->getPackageKubes($packageId)->getData();

        return CL_Tools::getKeyAsField($kubes, 'id');
    }

    /**
     *
     */
    public function beforeSave()
    {
        if($this->action == 'update') {
            return $this->updateKube();
        } else {
            return $this->createKube();
        }
    }

    /**
     *
     */
    public function afterSave()
    {
    }

    /**
     *
     */
    public function beforeDelete()
    {
        if(empty($this->kube_price) && trim($this->kube_price) === '' && !is_null($this->kuber_product_id)) {
            $this->deleteKubeFromPackage();
        }

        if(is_null($this->kuber_product_id)) {
            $this->deleteKube();
        }

        return true;
    }

    /**
     *
     */
    public function afterDelete()
    {
    }

    /**
     * @return KuberDock_Api
     */
    private function getApi()
    {
        if($this->server_id) {
            return KuberDock_Server::model()->loadById($this->server_id)->getApi();
        } elseif($this->product_id) {
            return KuberDock_Product::model()->loadById($this->product_id)->getApi();
        } else {
            return KuberDock_Server::model()->getActive()->getApi();
        }
    }

    /**
     * Class loader
     *
     * @param string $className
     * @return $this
     */
    public static function model($className = __CLASS__)
    {
        if(isset(self::$_models[$className])) {
            return self::$_models[$className];
        } else {
            self::$_models[$className] = new $className;
            return self::$_models[$className];
        }
    }
} 