<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'init.php';

function KuberDock_ConfigOptions() {
    $view = new CL_View();
    $id = CL_Base::model()->getParam('id');
    $product = KuberDock_Product::model()->loadById($id);

    $view->renderPartial('admin/module_settings', array(
        'product' => $product,
    ));

    return array();
}

function KuberDock_CreateAccount($params) {
    /*if(!KuberDock_User::model()->isClient()) {
        return 'ERROR: This action is not enabled for KuberDock';
    }*/

    try {
        $product = KuberDock_Product::model()->loadById($params['pid']);
        $client = CL_Client::model()->loadByParams($params['clientsdetails']);
        $product->setClient($client);
        $product->create($params['serviceid']);

        if($product->getConfigOption('enableTrial')) {
            $trial = KuberDock_Addon_Trial::model();
            if(!$trial->loadById($params['userid'])) {
                $trial->insert(array(
                    'user_id' => $params['userid'],
                    'service_id' => $params['serviceid'],
                ));
            }
        }

        return 'success';
    } catch(Exception $e) {
        return 'ERROR: ' . $e->getMessage();
    }
}

function KuberDock_TerminateAccount($params) {
    try {
        $product = KuberDock_Product::model()->loadById($params['pid']);
        $product->terminate($params['serviceid']);

        return 'success';
    } catch(Exception $e) {
        return 'ERROR: ' . $e->getMessage();
    }
}

function KuberDock_SuspendAccount($params) {
    try {
        $product = KuberDock_Product::model()->loadById($params['pid']);
        $product->suspend($params['serviceid']);

        return 'success';
    } catch(Exception $e) {
        return 'ERROR: ' . $e->getMessage();
    }
}

function KuberDock_UnsuspendAccount($params) {
    try {
        $product = KuberDock_Product::model()->loadById($params['pid']);
        $product->unSuspend($params['serviceid']);

        return 'success';
    } catch(Exception $e) {
        return 'ERROR: ' . $e->getMessage();
    }
}

function KuberDock_AdminServicesTabFields($params) {
    $view = new CL_View();
    $product = KuberDock_Product::model()->loadByParams($params);
    $currency = CL_Currency::model()->getDefaultCurrency();
    $service = KuberDock_Hosting::model()->loadById($params['serviceid']);
    $trialTime = $product->getConfigOption('trialTime');
    $regDate = new DateTime($service->regdate);
    $trialExpired = '';

    if($trialTime && $service->isTrialExpired($regDate, $trialTime)) {
        $trialExpired = $regDate->modify('+'.$trialTime.' day')->format('Y-m-d');
    }

    try {
        $adminApi = $service->getAdminApi();
        $stat = $adminApi->getUsage($service->username);
        $stat = $stat->getData();
    } catch(Exception $e) {
        $stat = $e->getMessage();
    }

    $kubes = KuberDock_Addon_Kube::model()->loadByAttributes(array(
        'product_id' => $product->pid,
    ));

    $productInfo = $view->renderPartial('admin/product_info', array(
        'currency' => $currency,
        'product' => $product,
        'kubes' => $kubes,
        'trialExpired' => $trialExpired,
    ), false);

    $productStatistic = $view->renderPartial('admin/product_statistic', array(
        'stat' => $stat,
    ), false);

    return array(
        'Package Kubes' => $productInfo,
        'Statistic' => $productStatistic,
    );
}

function KuberDock_ClientArea($params) {
    $view = new CL_View();
    $product = KuberDock_Product::model()->loadByParams($params);
    $currency = CL_Currency::model()->getDefaultCurrency();
    $service = KuberDock_Hosting::model()->loadById($params['serviceid']);
    $server = KuberDock_Server::model()->loadById($service->server);
    $trialTime = $product->getConfigOption('trialTime');
    $regDate = new DateTime($service->regdate);
    $trialExpired = '';

    if($trialTime && $service->isTrialExpired($regDate, $trialTime)) {
        $trialExpired = $regDate->modify('+'.$trialTime.' day')->format('Y-m-d');
    }

    try {
        $adminApi = $service->getAdminApi();
        $stat = $adminApi->getUsage($service->username);
        $stat = $stat->getData();
    } catch(Exception $e) {
        $stat = $e->getMessage();
    }

    $kubes = KuberDock_Addon_Kube::model()->loadByAttributes(array(
        'product_id' => $product->pid,
    ));

    $productInfo = $view->renderPartial('client/product_info', array(
        'currency' => $currency,
        'product' => $product,
        'kubes' => $kubes,
        'server' => $server,
        'trialExpired' => $trialExpired,
    ), false);

    $productStatistic = $view->renderPartial('client/product_statistic', array(
        'stat' => $stat,
    ), false);

    return $productInfo . $productStatistic;
}

function KuberDock_LoginLink($params) {
    $server = KuberDock_Server::model()->loadById($params['serverid']);

    return sprintf('<a href="%s" target="_blank">Login into KuberDock</a>', $server->getLoginPageLink());
}

/**
 * Run create module command while clicking "Save Changes" in admin area
 * @param $params
 */
function KuberDock_AdminServicesTabFieldsSave($params) {
    try {
        $product = KuberDock_Product::model()->loadById($params['pid']);
        $client = CL_Client::model()->loadByParams($params['clientsdetails']);
        $product->setClient($client);
        $product->create($params['serviceid']);
    } catch(Exception $e) {
        // do nothing
    }
}

/**
 * This function is used for upgrading and downgrading of products.
 * @param array $params
 * @return bool
 */
function KuberDock_ChangePackage($params) {
    try {
        $service = KuberDock_Hosting::model()->loadById($params['serviceid']);
        $service->getAdminApi()->updateUser(array(
            'rolename' => KuberDock_User::ROLE_USER,
        ), $service->username);

        $service = KuberDock_Hosting::model()->loadById($params['serviceid']);
        $service->amount = 0;
        $service->save();

        return true;
    } catch(Exception $e) {
        return false;
    }
}

function KuberDock_TestConnection($params) {
    try {
        $protocol = $params['serversecure'] ? KuberDock_Api::PROTOCOL_HTTPS : KuberDock_Api::PROTOCOL_HTTP;
        $url = sprintf('%s://%s', $protocol, $params['serverip']);
        $api = new KuberDock_Api($params['serverusername'], $params['serverpassword'], $url);
        $response = $api->getToken();
        return array(
            'success' => true,
        );
    } catch(Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}