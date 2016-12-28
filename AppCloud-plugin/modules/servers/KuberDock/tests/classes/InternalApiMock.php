<?php

namespace tests;


use Carbon\Carbon;
use models\billing\Invoice;
use models\billing\InvoiceItem;
use phpmock\phpunit\PHPMock;
use tests\fixtures\DatabaseFixture;


trait InternalApiMock
{
    use PHPMock;

    public $calledTimes = [];

    public function internalApiMock()
    {
        $this->getFunctionMock('components', 'localAPI')
            ->expects($this->any())
            ->willReturnCallback(function () {
                $args = func_get_args();

                switch ($args[0]) {
                    case 'createinvoice':
                        return $this->createInvoice($args[1]);
                    case 'modulesuspend':
                        return $this->moduleSuspend($args[1]);
                    case 'sendemail':
                        return $this->sendEmail($args[1]);
                }
            });
    }

    public function createInvoice($data)
    {
        $items = [];
        $sum = 0;

        foreach ($data as $k => $v) {
            if (preg_match('/(itemdescription|itemamount)(\d+)/', $k, $match)) {
                $items[$match[2]-1][substr($match[1], 4)] = $v;
                if ($match[1] === 'itemamount') {
                    $sum += $v;
                }
            }
        }

        $invoice = Invoice::create([
            'userid' => DatabaseFixture::$userId,
            'date' => new Carbon(),
            'duedate' => new Carbon(),
            'subtotal' => $sum,
            'paymentmethod' => $data['paymentmethod'],
            'status' => 'Unpaid',
        ]);

        foreach ($items as $k => &$row) {
            $row['invoiceid'] = $invoice->id;
            $row['userid'] = DatabaseFixture::$userId;
        }

        InvoiceItem::insert($items);

        $this->calledTimes['createinvoice']++;

        return ['result' => 'success', 'invoiceid' => $invoice->id];
    }

    public function moduleSuspend($data)
    {
        $this->calledTimes['suspendmodule']++;
        return ['result' => 'success', 'data' => $data];
    }

    public function sendEmail($data)
    {
        $this->calledTimes['sendemail']++;
        return ['result' => 'success', 'data' => $data];
    }
}