<?php

namespace FatturaElettronica;

abstract class AbstractNotice extends AbstractDocument
{
    /**
     * Constants for root notice element
     */
    const ROOT_TAG_PREFIX = 'types';
    const ROOT_NAMESPACE  = 'http://www.fatturapa.gov.it/sdi/messaggi/v1.0';
    const SCHEMA_LOCATION = 'http://www.fatturapa.gov.it/sdi/messaggi/v1.0 MessaggiTypes_v1.0.xsd ';

    /**
     * Default destination dir where to save documents
     */
    protected static $defaultPrefixPath = null;

    /**
     * Constructor
     */
    public function __construct( $file = null )
    {
        parent::__construct($file);

        if (null === $file) {
            $this->dom->documentElement->setAttribute('versione', '1.0');
        }
    }

    public function setFilenameFromInvoice( FatturaElettronica $invoice, string $suffix )
    {
        $filename = basename($invoice->getFilename(), '.xml') . $suffix . '.xml';
        return $this->setFilename($filename);
    }

    /**
     * Populate some notice values from invoice
     */
    abstract public function setValuesFromInvoice( FatturaElettronica $invoice, $body = 1 );
}
