<?php

namespace Kuberdock\classes\exceptions;

class PaymentRequiredException extends \Exception
{
    private $redirect;

    public function __construct(array $response){
        parent::__construct($response['redirect'], 0);
        $this->redirect = $response['redirect'];
    }

    /**
     * @return string
     * @throws CException
     */
    public function getJSON()
    {
        return json_encode(array(
            'error' => 'Payment required',
            'redirect' => $this->redirect,
        ));
    }
}