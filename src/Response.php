<?php

namespace Brandshopru\Smsc;

class Response
{
    private $content;
    private $httpCode;
    private $reasonPhrase;

    public function __construct($response)
    {
        $this->content = $response->getBody()->getContents();
        $this->httpCode = $response->getStatusCode();
        $this->reasonPhrase = $response->getReasonPhrase();
        if (!json_decode($this->content, true) || json_last_error()) {
            throw new \Exception("Unable to decode JSON: ".json_last_error_msg().".\n Http code: ".$this->httpCode."; Reason phrase: ".$this->reasonPhrase.";");
        }
    }

    public function isOk()
    {
        return $this->getHttpCode() >= 200 && $this->getHttpCode() <= 299 && $this->getErrorCode() === null;
    }

    public function getErrorCode()
    {
        return $this->getContentData("error_code") ? intval($this->getContentData("error_code")) : null;
    }

    public function getErrorMessage()
    {
        if ($this->getErrorCode() === null || $this->getContentData("error") === null) {
            return "";
        }
        return ucfirst($this->getContentData("error")).".";
    }

    public function getContent()
    {
        return json_decode($this->getRawContent(), true);
    }

    public function getRawContent()
    {
        return $this->content;
    }

    public function isEmpty()
    {
        return empty($this->getRawContent());
    }

    public function getHttpCode()
    {
        return $this->httpCode;
    }

    private function getContentData($key)
    {
        return array_key_exists($key, $this->getContent()) ? $this->getContent()[$key] : null;
    }
}