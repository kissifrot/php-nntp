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
use Rvdv\Nntp\Response\MultiLineResponse;
use Rvdv\Nntp\Response\Response;

/**
 * BodyCommand.
 *
 * @author thebandit
 */
class BodyCommand extends Command implements CommandInterface
{
    /**
     * @var string
     */
    private $article;

    /**
     * Constructor.
     *
     * @param string $article The number or msg-id of the article.
     */
    public function __construct($article)
    {
        $this->article = $article;

        parent::__construct([], true);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        return sprintf('BODY %s', $this->article);
    }

    /**
     * {@inheritdoc}
     */
    public function getExpectedResponseCodes()
    {
        return [
            Response::BODY_FOLLOWS                  => 'onBodyFollows',
            Response::NO_NEWSGROUP_CURRENT_SELECTED => 'onNoNewsGroupCurrentSelected',
            Response::NO_SUCH_ARTICLE_NUMBER        => 'onNoSuchArticleNumber',
            Response::NO_SUCH_ARTICLE_ID            => 'onNoSuchArticleId',
        ];
    }

    public function onBodyFollows(MultiLineResponse $response)
    {
        $lines = $response->getLines();
        $this->result = implode("\r\n", $lines->toArray());
    }

    public function getYencDecodedResult()
    {
        $encoded = [];

        // Extract the yEnc string itself.
        preg_match("/^(=ybegin.*=yend[^$]*)$/ims", $this->result, $encoded);
        if (!isset($encoded[1])) {
            return false;
        }

        $encoded = $encoded[1];

        // Remove header and trailer
        $encoded = preg_replace("/(^=ybegin.*\\r\\n)/im", "", $encoded, 1);
        $encoded = preg_replace("/(^=ypart.*\\r\\n)/im", "", $encoded, 1);
        $encoded = preg_replace("/(^=yend.*)/im", "", $encoded, 1);

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

    public function onNoNewsGroupCurrentSelected(Response $response)
    {
        throw new RuntimeException('A group must be selected first before getting an article body.',
            $response->getStatusCode());
    }

    public function onNoSuchArticleNumber(Response $response)
    {
        throw new RuntimeException('No article with that number.', $response->getStatusCode());
    }

    public function onNoSuchArticleId(Response $response)
    {
        throw new RuntimeException('No article with that message-id.', $response->getStatusCode());
    }
}
