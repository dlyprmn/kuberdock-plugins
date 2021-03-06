<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once dirname(__FILE__) . '/../../modules/servers/KuberDock/init.php';

try {
    $vars = get_defined_vars();
    $postFields = \components\BillingApi::model()->getApiParams($vars);

    // Used for local api call
    $params = (array) $postFields->params;
    if (empty($params) && isset($_POST['client_id']) && isset($_POST['pod'])) {
        $postFields->params->client_id = (int) $_POST['client_id'];
        $postFields->params->pod = $_POST['pod'];
        $postFields->params->referer = isset($_POST['referer']) ? $_POST['referer'] : '';
    }

    foreach (array('client_id', 'pod') as $attr) {
        if (!isset($postFields->params->{$attr}) || !$postFields->params->{$attr}) {
            throw new \exceptions\CException(sprintf("Field '%s' required", $attr));
        }
    }

    $service = \models\billing\Service::typeKuberDock()->where('userid', $postFields->params->client_id)->first();

    if (!$service) {
        throw new Exception('User has no KuberDock service');
    }

    $pod = new \models\addon\resource\Pod($service->package);
    $pod->load(html_entity_decode(urldecode($postFields->params->pod), ENT_QUOTES));
    $pod->setReferer($postFields->params->referer);

    $results = $service->package->getBilling()->processApiOrder($pod, $service, \models\addon\ItemInvoice::TYPE_ORDER);

    $apiresults = ['result' => 'success', 'results' => $results];
} catch (Exception $e) {
    $apiresults = ['result' => 'error', 'message' => $e->getMessage()];
}
