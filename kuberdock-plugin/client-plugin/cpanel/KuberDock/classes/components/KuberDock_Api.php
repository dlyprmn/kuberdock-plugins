<?php


/**
 * Class KuberDock_Api
 */
class KuberDock_Api {
    /**
     *
     */
    const PROTOCOL_HTTP = 'http';
    /**
     *
     */
    const PROTOCOL_HTTPS = 'https';
    /**
     * @var string
     */
    const DATA_TYPE_JSON = 'json';
    /**
     * @var string
     */
    const DATA_TYPE_PLAIN = 'plain';
    /**
     * seconds
     */
    const API_CONNECTION_TIMEOUT = 150;

    /**
     * @var string
     */
    protected $dataType;
    /**
     * @var string
     */
    protected $serverUrl;
    /**
     * @var string
     */
    protected $url;
    /**
     * @var string
     */
    protected $requestUrl;
    /**
     * @var string
     */
    protected $requestType = 'GET';
    /**
     * Request arguments
     * @var array
     */
    protected $arguments;
    /**
     * @var string
     */
    protected $username;
    /**
     * @var string
     */
    protected $password;
    /**
     * @var string
     */
    protected $token;
    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * @var KuberDock_ApiResponse
     */
    protected $response;

    public function __construct()
    {
        $this->dataType = self::DATA_TYPE_JSON;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        if($this->token && !$this->username) {
            $this->username = explode('|', $this->token)[0];
        }

        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getRequestUrl()
    {
        return $this->requestUrl;
    }

    /**
     * @return bool
     */
    public function getDebugMode()
    {
        return $this->debug;
    }

    /**
     * @param bool $debug
     * @return $this
     */
    public function setDebugMode($debug = false)
    {
        $this->debug = (bool) $debug;

        return $this;
    }

    /**
     * @param $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return array
     */
    public function getRequestArguments()
    {
        return $this->arguments;
    }

    public function initUser()
    {
        $config = KcliCommand::getConfig();
        $this->token = isset($config['token']) ? $config['token'] : '';
        $this->serverUrl = $config['url'];
    }

    public function initAdmin($username = '', $password = '', $token = '')
    {
        $this->username = $username;
        $this->password = $password;
        $this->token = $token;
        $config = KcliCommand::getConfig();
        $this->serverUrl = $config['url'];
    }

    /**
     * @param array $params
     * @param string $type
     * @return KuberDock_ApiResponse
     * @throws CException
     * @throws WithoutBillingException
     */
    public function call($params = array(), $type = 'GET')
    {
        if(!in_array($type, array('GET', 'POST', 'PUT', 'DELETE'))) {
            throw new CException('Undefined request type: '.$type);
        }

        $ch = curl_init();
        $this->arguments = $params;
        $this->requestType = $type;

        switch($type) {
            case 'POST':
            case 'PUT':
                $this->requestUrl = $this->url;
                if($this->token) {
                    $this->requestUrl .= '?token=' . $this->token;
                }
                $strData = json_encode($params);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $strData);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($strData)
                ));
                break;
            default:
                if($this->token) {
                    $this->requestUrl = $params ? $this->url .'?token='. $this->token .'&' . http_build_query($params)
                        : $this->url . '?token=' . $this->token;
                } else {
                    $this->requestUrl = $params ? $this->url .'?'. http_build_query($params) : $this->url;
                }

                break;
        }

        curl_setopt($ch, CURLOPT_URL, $this->requestUrl);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::API_CONNECTION_TIMEOUT);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        if(!$this->token) {
            curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        $status = curl_getinfo($ch);

        $this->parseResponse($response);

        if($status['http_code'] != KuberDock_ApiStatusCode::HTTP_OK) {
            $err = ucwords(curl_error($ch));
            curl_close($ch);
            $responseData = $this->response->getData();

            switch($status['http_code']) {
                case KuberDock_ApiStatusCode::HTTP_BAD_REQUEST:
                case KuberDock_ApiStatusCode::HTTP_NOT_FOUND:
                    if(filter_var($responseData, FILTER_VALIDATE_URL)) {
                        throw new CException(
                            sprintf('You have no billing account, please buy product at <a href="%s">%s</a>'
                                , $responseData, $responseData));
                    } elseif($responseData == 'Without billing') {
                        throw new WithoutBillingException();
                    } else {
                        throw new CException($responseData);
                    }
                    break;
                case KuberDock_ApiStatusCode::HTTP_FORBIDDEN:
                    throw new CException(sprintf('Invalid credential for KuberDock server %s', $this->url));
                default:
                    if($err) {
                        $msg = sprintf('%s (%s): %s', KuberDock_ApiStatusCode::getMessageByCode($status['http_code']),
                            $err, $this->url);
                    } else {
                        $msg = sprintf('%s: %s', KuberDock_ApiStatusCode::getMessageByCode($status['http_code']), $this->url);
                    }
                    throw new CException($msg);
            }
        }

        curl_close($ch);


        return $this->response;
    }

    /**
     * @param $response
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    private function parseResponse($response)
    {
        $this->response = new KuberDock_ApiResponse();
        $this->response->raw = $response;

        if($this->dataType == self::DATA_TYPE_JSON) {
            $this->response->parsed = json_decode($response, true);
        } elseif($this->dataType == self::DATA_TYPE_PLAIN) {
            if(preg_match_all('/(.+)/m', $response, $match)) {
                foreach($match[1] as $row) {
                    list($key, $value) = explode(':', $row);
                    $this->response->parsed[$key] = $value;
                }
            } else {
                $error = 'Unable to parse plain data';
                throw new CException($error);
            }
        } else {
            $error = 'Unknown API data type';
            throw new CException($error);
        }

        return $this->response;
    }

    /**
     * @param string $user
     * @param string $domain
     * @return KuberDock_ApiResponse
     * @throws Exception
     * @throws WithoutBillingException
     */
    public function getInfo($user, $domain)
    {
        $this->url = $this->serverUrl . '/api/billing/info';

        $response = $this->call(array(
            'user' => $user,
            'userDomains' => $domain,
        ), 'GET');

        if(!$response->getStatus()) {
            throw new CException($response->getMessage());
        }

        return $response->getData();
    }

    /**
     * @param null $key
     * @return array
     * @throws CException
     * @throws WithoutBillingException
     */
    public function getSysApi($key = null)
    {
        $this->url = $this->serverUrl . '/api/settings/sysapi';

        $response = $this->call();

        if(!$response->getStatus()) {
            throw new CException($response->getMessage());
        }

        $data = $response->getData();

        return $key
            ? Tools::getKeyAsField($data, $key)
            : $data;
    }

    /**
     * @param $user
     * @param $domain
     * @param $packageId
     * @return array
     * @throws CException
     * @throws WithoutBillingException
     */
    public function order($user, $domain, $packageId)
    {
        $this->url = $this->serverUrl . '/api/billing/order';

        $response = $this->call(array(
            'user' => $user,
            'userDomains' => $domain,
            'package_id' => $packageId,
        ), 'POST');

        if(!$response->getStatus()) {
            throw new CException($response->getMessage());
        }

        $data = current($response->getData());
        $command = new KcliCommand('', '', $data['token']);
        $command->setConfig();

        return $data;
    }

    /**
     * @param int $podId
     * @param bool $wipeOut
     * @return array
     * @throws CException
     * @throws WithoutBillingException
     */
    public function redeployPod($podId, $wipeOut)
    {
        $data['command'] = 'redeploy';
        if($wipeOut) {
            $data['commandOptions'] = array(
                'wipeOut' => true,
            );
        }

        $this->url = $this->serverUrl . '/api/podapi/' . $podId;
        $response = $this->call($data, 'PUT');

        if(!$response->getStatus()) {
            throw new CException($response->getMessage());
        }

        return $response->getData();
    }

    /**
     * @param string $username
     * @param string $password
     * @return array
     * @throws CException
     * @throws WithoutBillingException
     */
    public function getUserToken($username, $password)
    {
        $this->username = $username;
        $this->password = $password;

        $this->url = $this->serverUrl . '/api/auth/token';
        $response = $this->call();

        if(!$response->getStatus()) {
            throw new CException($response->getMessage());
        }

        return $response->parsed['token'];
    }

    /**
     * @param array $pod
     * @param string $referer
     * @return array
     * @throws CException
     * @throws WithoutBillingException
     */
    public function orderPod($pod, $referer = '')
    {
        $this->url = $this->serverUrl . '/api/billing/order';
        $response = $this->call(array(
            'pod' => json_encode($pod),
            'referer' => urldecode($referer),
        ), 'POST');

        if(!$response->getStatus()) {
            throw new CException($response->getMessage());
        }

        return $response->getData();
    }

    /**
     * @param array $params
     * @param string $referer
     * @return array
     * @throws CException
     * @throws WithoutBillingException
     */
    public function orderKubes($params, $referer = '')
    {
        $this->url = $this->serverUrl . '/api/billing/orderKubes';
        $response = $this->call(array(
            'pod' => json_encode($params),
            'referer' => urldecode($referer),
        ), 'POST');

        if(!$response->getStatus()) {
            throw new CException($response->getMessage());
        }

        return $response->getData();
    }

    /**
     * @param string $podId
     * @param array $attributes
     * @return KuberDock_ApiResponse
     * @throws Exception
     */
    public function updatePod($podId, $attributes)
    {
        $data['id'] = $podId;
        $data['command'] = 'set';
        $data['commandOptions'] = $attributes;

        Base::model()->getPanel()->updatePod($data);
    }

    /**
     * @param string $podId
     * @param array $containers
     */
    public function addKubes($podId, $containers)
    {
        $data['id'] = $podId;
        $data['command'] = 'redeploy';
        $data['containers'] = $containers;

        Base::model()->getPanel()->updatePod($data);
    }

    public function getTemplates($origin, $page = 1)
    {
        $this->url = $this->serverUrl . '/api/predefined-apps';
        $response = $this->call(array(
            'page' => $page,
        ), 'GET');

        if(!$response->getStatus()) {
            throw new CException($response->getMessage());
        }

        return array_filter($response->getData(), function ($e) use ($origin) {
            if ($e['origin'] == $origin) {
                return true;
            }
        });
    }

    public function getTemplate($id)
    {
        $this->url = $this->serverUrl . '/api/predefined-apps/' . $id;
        $response = $this->call(array(), 'GET');

        if(!$response->getStatus()) {
            throw new CException($response->getMessage());
        }

        return $response->getData();
    }

    public function getImages($name, $page = 1)
    {
        if (!$name) {
            return array();
        }

        $registryUrl = Base::model()->getPanel()->getCommand()->getRegistryUrl();

        $this->url = $this->serverUrl . '/api/images';
        $response = $this->call(array(
            'searchkey' => $name,
            'page' => $page,
            'url' => $registryUrl,
        ), 'GET');

        if(!$response->getStatus()) {
            throw new CException($response->getMessage());
        }

        return $response->getData();
    }

    public function getImage($name)
    {
        $this->url = $this->serverUrl . '/api/images/new';
        $response = $this->call(array(
            'image' => $name,
        ), 'POST');

        if(!$response->getStatus()) {
            throw new CException($response->getMessage());
        }

        return $response->getData();
    }
}