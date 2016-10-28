<?php

/*
 * This file is part of the NNTP library.
 *
 * (c) Robin van der Vleuten <robin@webstronauts.co>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rvdv\Nntp\Command;

use Rvdv\Nntp\Exception\RuntimeException;
use Rvdv\Nntp\Response\ResponseInterface;

/**
 * @author Robin van der Vleuten <robin@webstronauts.co>
 */
abstract class Command implements CommandInterface
{
    /**
     * @var bool
     */
    protected $isMultiLine;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var mixed
     */
    protected $result;

    /**
     * Constructor.
     *
     * @param mixed $result      The default result for this command.
     * @param bool  $isMultiLine A bool indicating the response is multiline or not.
     */
    public function __construct($result = null, $isMultiLine = false)
    {
        $this->result = $result;
        $this->isMultiLine = $isMultiLine;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * {@inheritdoc}
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * {@inheritdoc}
     */
    public function getYencDecodedResult()
    {
        $encoded = [];

        // Extract the yEnc string itself.
        preg_match("/^(=ybegin.*=yend[^$]*)$/ims", $this->result, $encoded);
        if (!isset($encoded[1])) {
            throw new RuntimeException('It seems there the result is not yEnc encoded');
        }

        $encoded = $encoded[1];

        // Remove header and trailer
        $encoded = preg_replace("/(^=ybegin.*\\r\\n)/im", "", $encoded, 1);
        $encoded = preg_replace("/(^=ypart.*\\r\\n)/im", "", $encoded, 1);
        $encoded = preg_replace("/(^=yend.*)/im", "", $encoded, 1);

        // Remove linebreaks from the string.
        $encoded = trim(str_replace("\r\n", "", $encoded));

        $decoded = '';
        for ($i = 0; $i < strlen($encoded); $i++) {
            if ($encoded{$i} == "=") {
                $i++;
                $decoded .= chr((ord($encoded{$i}) - 64) - 42);
            } else {
                $decoded .= chr(ord($encoded{$i}) - 42);
            }
        }

        return $decoded;
    }

    /**
     * {@inheritdoc}
     */
    public function isMultiLine()
    {
        return $this->isMultiLine;
    }

    /**
     * {@inheritdoc}
     */
    public function isCompressed()
    {
        return false;
    }
}
