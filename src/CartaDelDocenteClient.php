<?php

namespace Datomatic\CartaDelDocente;

use Datomatic\CartaDelDocente\Exceptions\RequestException;
use Exception;
use SoapClient;
use SoapFault;

class CartaDelDocenteClient
{
    protected SoapClient $client;

    /**
     * @throws SoapFault
     */
    public function __construct(string $certificatePath = '', string $certificatePassword = '')
    {
        $this->setSoapClient($certificatePath, $certificatePassword);
    }

    /**
     * @param string $certificatePath
     * @param string $certificatePassword
     * @return void
     * @throws SoapFault
     */
    protected function setSoapClient(string $certificatePath, string $certificatePassword): void
    {
        $production = ! empty($certificatePath) && ! empty($certificatePassword);

        if ($production) {
            $location = 'https://ws.cartadeldocente.istruzione.it/VerificaVoucherDocWEB/VerificaVoucher';
        } else {
            $location = 'https://wstest.cartadeldocente.istruzione.it/VerificaVoucherDocWEB/VerificaVoucher';
            $certificatePath = __DIR__.'/Resources/AAAAAA00H01H501P.pem';
            $certificatePassword = 'm3D0T4aM';
        }

        $this->client = new SoapClient(
            __DIR__.'/Resources/VerificaVoucher.wsdl',
            [
                'local_cert' => $certificatePath,
                'location' => $location,
                'passphrase' => $certificatePassword,
                'stream_context' => stream_context_create(
                    [
                        'http' => [
                            'user_agent' => 'PHP/SOAP',
                        ],
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true,
                        ],
                    ]
                ),
            ]
        );
    }

    public function client(): SoapClient
    {
        return $this->client;
    }

    /**
     * @param string|int $operationType
     * @param string $voucher
     * @return CartaDelDocenteResponse
     */
    public function check(string|int $operationType, string $voucher): CartaDelDocenteResponse
    {
        $response = $this->request('Check', [
            'checkReq' => [
                'tipoOperazione' => strval($operationType),
                'codiceVoucher' => $voucher,
            ],
        ]);

        return new CartaDelDocenteResponse(
            name: $response->checkResp->nominativoBeneficiario,
            vatId: $response->checkResp->partitaIvaEsercente,
            scope: $response->checkResp->ambito,
            good: $response->checkResp->bene,
            amount: floatval($response->checkResp->importo),
        );
    }

    /**
     * @return CartaDelDocenteResponse
     */
    public function merchantActivation(): CartaDelDocenteResponse
    {
        return $this->check('1', '11aa22bb');
    }

    /**
     * @param string|int $operationType
     * @param string $voucher
     * @param float $amount
     * @return bool
     */
    public function confirm(string|int $operationType, string $voucher, float $amount): bool
    {
        $response = $this->request('Confirm', [
            'checkReq' => [
                'tipoOperazione' => strval($operationType),
                'codiceVoucher' => $voucher,
                'importo' => $amount,
            ],
        ]);

        return $response->checkResp->esito === "OK";
    }

    /**
     * @param string $function
     * @param array $options
     * @return object
     */
    protected function request(string $function, array $options): object
    {
        try {
            return $this->client()->$function($options);
        } catch (Exception $e) {
            throw new RequestException(
                code: $e->detail ? $e->detail->FaultVoucher->exceptionCode : $e->faultcode,
                message: $e->detail ? $e->detail->FaultVoucher->exceptionMessage : $e->faultstring
            );
        }
    }
}
