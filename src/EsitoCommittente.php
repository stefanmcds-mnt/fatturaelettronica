<?php

namespace FatturaElettronica;

use FatturaElettronica\FatturaElettronica;

class EsitoCommittente extends AbstractNotice
{

    /**
     * Constants for root element ("NotificaEsitoCommittente")
     */
    const ROOT_TAG_NAME = 'NotificaEsitoCommittente';

    /**
     * Constants for "Esito"
     */
    const EC01 = 'EC01';
    const EC02 = 'EC02';

    /**
     * Notice elements
     */
    public static $templateArray = [
        'IdentificativoSdI' => '',
        'RiferimentoFattura' => [
            'NumeroFattura' => '',
            'AnnoFattura' => '',
            'PosizioneFattura' => ''
        ],
        'Esito' => '',
        'Descrizione' => '',
        'MessageIdCommittente' => '',

    ];

    /**
     * Populate notice values from invoice
     */
    public function setValuesFromInvoice(FatturaElettronica $invoice, $body = 1)
    {
        $body = $invoice->getBody($body);
        $this->setValue('NumeroFattura', $invoice->getValue(".//DatiGeneraliDocumento/Numero", $body));

        $anno = substr($invoice->getValue('.//DatiGeneraliDocumento/Data', $body), 0, 4);
        $this->setValue('AnnoFattura', $anno);

        return $this;
    }
}
