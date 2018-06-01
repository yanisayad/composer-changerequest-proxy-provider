<?php

namespace ETNA\Silex\Provider\ChangeRequestProxy;

use Guzzle\Http\Message\Request as GuzzleRequest;

use GuzzleHttp\Cookie\CookieJar;
use Silex\Application;

use Symfony\Component\HttpFoundation\Request;

use ETNA\Silex\Provider\ChangeRequestProxy\ChangeTodos;

class ChangeRequestManager
{
    private $app;

    public function __construct(Application $app = null)
    {
        if (null === $app) {
            throw new \Exception("ChangeRequestManager requires $app to be set");
        }

        $this->app = $app;
    }

    public function findByQueryString($query, $from = 0, $size = 99999, $sort = "")
    {
        $query = urlencode($query);

        $response = $this->fireRequest("GET", "/search?q={$query}&from={$from}&size={$size}&sort={$sort}");

        $response["hits"] = array_map(
            function ($hit) {
                $change_todos = new ChangeTodos();
                $change_todos->fromArray($hit);
                return $change_todos;
            },
            $response["hits"]
        );

        return $response;
    }

    public function findOneByQueryString($query)
    {
        $matching = $this->findByQueryString($query, 0, 1);

        if (0 === count($matching["hits"])) {
            return null;
        }

        $change_request = $matching["hits"][0];

        return $change_request;
    }

    public function validate(ChangeTodos $change_request)
    {
        $response_comment = null !== $change_request->getResponseComment() ?
                            $change_request->getResponseComment() : null;

        $body = [
            "status"           => "validate",
            "response_comment" => $response_comment
        ];

        $response = $this->fireRequest("PUT", "/change_todos/{$change_request->getId()}/status", $body);

        return $response;
    }

    public function invalidate(ChangeTodos $change_request)
    {
        $response_comment = null !== $change_request->getResponseComment() ?
                            $change_request->getResponseComment() : null;

        $body = [
            "status"           => "invalidate",
            "response_comment" => $response_comment
        ];

        $response = $this->fireRequest("PUT", "/change_todos/{$change_request->getId()}/status", $body);

        return $response;
    }

    public function save(ChangeTodos $change_todos)
    {
        $body = $change_todos->toArray();

        $response = $this->fireRequest("POST", "/change_todos", $body);

        return $response;
    }

    private function fireRequest($method, $uri, $body = [])
    {
        $method = strtoupper($method);

        if (false === in_array($method, ["GET", "POST", "PUT", "DELETE", "OPTIONS"])) {
            return $this->app->abort(405, "ChangeRequestProxy can not fire request of method : {$method}");
        }

        $domain = getenv("TRUSTED_DOMAIN");
        $jar    = CookieJar::fromArray(["authenticator" => $this->app["cookies.authenticator"]], $domain);

        try {
            $response = $this->app["changerequest_proxy"]->request($method, $uri, [
                "cookies" => $jar,
                "json"    => $body
            ]);
            return json_decode($response->getBody(), true);
        } catch (\GuzzleHttp\Exception\RequestException $client_error) {
            return $this->app->abort(
                $client_error->getResponse()->getStatusCode(),
                $client_error->getResponse()->getReasonPhrase()
            );
        }
    }
}
