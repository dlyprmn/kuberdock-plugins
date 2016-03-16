<?php

namespace Kuberdock\classes\api;

use Kuberdock\classes\exceptions\CException;

abstract class API
{
    /**
     * Property: method
     * The HTTP method this request was made in, either GET, POST, PUT or DELETE
     */
    protected $method = '';

    /**
     * Property: endpoint
     * The Model requested in the URI. eg: /files
     */
    protected $endpoint = '';

    /**
     * Property: args
     * Any additional URI components after the endpoint have been removed, in our
     * or /<endpoint>/<arg0>
     */
    protected $args = array();

    /**
     * Property: file
     * Stores the input of the PUT request
     */
    protected $file = null;

    /**
     * Constructor: __construct
     * Allow for CORS, assemble and pre-process the data
     * @param $request string
     * @throws \Exception
     */
    public function __construct($request)
    {
        header("Content-Type: application/json");

        $this->args = explode('/', rtrim($request, '/'));
        $this->endpoint = array_shift($this->args);

        $this->method = $_SERVER['REQUEST_METHOD'];
        if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
                $this->method = 'DELETE';
            } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
                $this->method = 'PUT';
            } else {
                throw new \Exception("Unexpected Header");
            }
        }

        switch($this->method) {
            case 'DELETE':
            case 'POST':
                $this->request = $this->cleanInputs($_POST);
                break;
            case 'GET':
                $this->request = $this->cleanInputs($_GET);
                break;
            case 'PUT':
                $this->request = $this->cleanInputs($_GET);
                $this->file = file_get_contents("php://input");
                break;
            default:
                $this->response('Invalid Method', 405);
                break;
        }
    }

    public function processAPI()
    {
        $endpoint = strtolower($this->method) . '_' . $this->endpoint;

        // additional parameter, like pods/search/nginx
        if (array_key_exists(0, $this->args) && method_exists($this, $endpoint . '_' . $this->args[0])) {
            $endpoint .= '_' . array_shift($this->args);
        }

        if (!method_exists($this, $endpoint)) {
            return $this->response("No Endpoint: $endpoint", 404);
        }

        set_error_handler(array($this, "warningHandler"), E_WARNING);
        $result = call_user_func_array(array($this, $endpoint), $this->args);
        restore_error_handler();

        return $this->response($result);
    }

    private function response($data, $status = 200)
    {
        header("HTTP/1.1 " . $status . " " . $this->requestStatus($status));

        return json_encode($data);
    }

    private function cleanInputs($data)
    {
        $clean_input = array();
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $clean_input[$k] = $this->cleanInputs($v);
            }
        } else {
            $clean_input = trim(strip_tags($data));
        }

        return $clean_input;
    }

    private function requestStatus($code)
    {
        $status = array(
            200 => 'OK',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        );

        return ($status[$code])?$status[$code]:$status[500];
    }

    protected function checkNumeric($arg)
    {
        if (!is_numeric($arg) && !is_null($arg)) {
            throw new CException('Argument must be numeric');
        }
    }

    /**
     * To catch 'Missing argument' errors
     *
     * @param $errno
     * @param $errstr
     * @throws CException
     */
    public function warningHandler($errno, $errstr)
    {
        if (strpos($errstr, 'Missing argument') == 0) {
            throw new CException('Missing argument', $errno);
        }
    }
}