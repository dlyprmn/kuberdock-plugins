<?php

namespace controllers;

use base\CL_Controller;
use base\CL_Base;
use base\CL_Csrf;
use base\CL_Tools;
use base\models\CL_Currency;
use base\models\CL_Order;
use Exception;
use \exceptions\CException;

class KuberDock_OrderController extends CL_Controller {
    public $action = 'orderApp';
    /**
     * @var string
     */
    public $layout = 'addon';

    public function init()
    {
    }

    public function orderAppAction()
    {
        $predefinedApp = \KuberDock_Addon_PredefinedApp::model();
        $kdProductId = CL_Base::model()->getParam($predefinedApp::KUBERDOCK_PRODUCT_ID_FIELD);
        $yaml = html_entity_decode(urldecode(CL_Base::model()->getParam($predefinedApp::KUBERDOCK_YAML_FIELD)), ENT_QUOTES);
        $referer = CL_Base::model()->getParam($predefinedApp::KUBERDOCK_REFERER_FIELD);
        $parsedYaml = \Spyc::YAMLLoadString($yaml);
        $userId = $_SESSION['uid'];

        try {
            if(isset($parsedYaml['kuberdock']['packageID'])) {
                $kdProductId = $parsedYaml['kuberdock']['packageID'];
            }

            if(!$referer) {
                if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']) {
                    $referer = $_SERVER['HTTP_REFERER'];
                } elseif(isset($parsedYaml['kuberdock']['server'])) {
                    $referer = $parsedYaml['kuberdock']['server'];
                } elseif($server = \KuberDock_Server::model()->getActive()) {
                    $referer = $server->getApiServerUrl();
                } else {
                    throw new CException('Cannot get KuberDock server url');
                }
            }

            $kdProduct = \KuberDock_Addon_Product::model()->getByKuberId($kdProductId, $referer);
            $product = \KuberDock_Product::model()->loadById($kdProduct->product_id);

            $predefinedApp = $predefinedApp->loadBySessionId();
            if(!$predefinedApp) {
                $predefinedApp = new \KuberDock_Addon_PredefinedApp();
            }

            $predefinedApp->setAttributes(array(
                'session_id' => session_id(),
                'kuber_product_id' => $kdProductId,
                'product_id' => $product->id,
                'data' => $yaml,
            ));

            $predefinedApp->save();

            $data = \KuberDock_Hosting::model()->getByUser($userId, $referer);
            $service = \KuberDock_Hosting::model()->loadByParams(current($data));

            if($product->isFixedPrice()) {
                if(!$service) {
                    $result = CL_Order::model()->createOrder($userId, $product->id);
                    CL_Order::model()->acceptOrder($result['orderid']);
                    $service = \KuberDock_Hosting::model()->loadById($result['productids']);
                }
                $item = $product->addBillableApp($userId);
                if(!($pod = $predefinedApp->isPodExists($service->id))) {
                    $pod = $predefinedApp->create($service->id, 'unpaid');
                }
                $item->pod_id = $pod['id'];
                $item->save();

                if($item->isPayed() && $service->isActive()) {
                    $product->startPodAndRedirect($item->service_id, $item->pod_id);
                } else {
                    header('Location: viewinvoice.php?id=' . $item->invoice_id);
                }
            } else {
                if($service) {
                    $product->createPodAndRedirect($service->id);
                } else {
                    $product->addToCart();
                    header('Location: cart.php?a=view');
                }
            }
        } catch(Exception $e) {
            // product not founded
            CException::log($e);
            CException::displayError($e);
        }
    }

    public function orderPodAction()
    {
        $predefinedApp = \KuberDock_Addon_PredefinedApp::model();
        $pod = json_decode(html_entity_decode(urldecode(CL_Base::model()->getParam($predefinedApp::KUBERDOCK_POD_FIELD)), ENT_QUOTES));
        $user = json_decode(html_entity_decode(urldecode(CL_Base::model()->getParam('user')), ENT_QUOTES));

        $predefinedApp = $predefinedApp->loadBySessionId();
        if(!$predefinedApp) {
            $predefinedApp = new \KuberDock_Addon_PredefinedApp();
        }

        try {
            $userId = $_SESSION['uid'];

            if(isset($user->package_id)) {
                $data = \KuberDock_Addon_Product::model()->loadByAttributes(array(
                    'kuber_product_id' => $user->package_id,
                ));
                $addonProduct = \KuberDock_Addon_Product::model()->loadByParams(current($data));
                $product = \KuberDock_Product::model()->loadById($addonProduct->product_id);
            } else {
                $data = \KuberDock_Product::model()->getByUser($userId);
                $product = \KuberDock_Product::model()->loadByParams(current($data));
                $addonProduct = \KuberDock_Addon_Product::model()->loadById($product->id);
            }

            if(!$product->id) {
                throw new Exception('Product not founded');
            }

            $predefinedApp->setAttributes(array(
                'session_id' => session_id(),
                'kuber_product_id' => $addonProduct->kuber_product_id,
                'product_id' => $product->id,
                'data' => json_encode($pod),
            ));

            $predefinedApp->save();

            $item = $product->addBillableApp($userId);
            if($item->isPayed()) {
                $product->startPodAndRedirect($item->service_id, $item->pod_id);
            } else {
                header('Location: viewinvoice.php?id=' . $item->invoice_id);
            }
        } catch(Exception $e) {
            // product not founded
            CException::log($e);
            CException::displayError($e);
        }
    }

    public function redirectAction()
    {
        $serviceId = \base\CL_Base::model()->getParam('sid');
        $podId = \base\CL_Base::model()->getParam('podId');

        if($serviceId && $podId) {
            $service = \KuberDock_Hosting::model()->loadById($serviceId);
            $view = new \base\CL_View();
            $predefinedApp = \KuberDock_Addon_PredefinedApp::model()->loadBySessionId();
            $postDescription = htmlentities($predefinedApp->getPostDescription(), ENT_QUOTES);
            try {
                $pod = $service->getApi()->getPod($podId);
                $view->renderPartial('client/preapp_complete', array(
                    'serverLink' => $service->getServer()->getLoginPageLink(),
                    'token' => $service->getToken(),
                    'podId' => $podId,
                    'postDescription' => $postDescription ? $postDescription : 'You successfully make payment for application',
                ));
                exit;
            } catch (Exception $e) {
                CException::log($e);
                CException::displayError($e);
            }
        }
    }
} 