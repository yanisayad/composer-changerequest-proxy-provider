<?php

namespace ETNA\Silex\Provider\ChangeRequestProxy;

use GuzzleHttp\Cookie\CookieJar;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;

use Symfony\Component\HttpFoundation\Request;

class DumbMethodsProxy implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        if (!isset($app["changerequest_proxy"])) {
            throw new \Exception("The guzzle client changerequest_proxy is not set");
        }

        $controllers = $app["controllers_factory"];

         // Dumb proxy for status change of request
        $controllers->put("/change_todos/{change_request}/status", [$this, "changeRequestStatus"]);
    }

    public function changeRequestStatus(Application $app, Request $req)
    {
        return $this->fireRequest($app, $req);
    }

    public function fireRequest(Application $app, Request $req, $remove_prefix = "")
    {
        $method = $req->getMethod();
        if (false === in_array($method, ["GET", "POST", "PUT", "DELETE", "OPTIONS"])) {
            return $app->abort(405, "ChangeRequestProxy can not fire request of method : {$method}");
        }

        $path_info = $req->getPathInfo();
        $path_info = str_replace($remove_prefix, "", $path_info);
        $domain    = getenv("TRUSTED_DOMAIN");

        try {
            $jar      = CookieJar::fromArray(["authenticator" => $req->cookies->get("authenticator")], $domain);
            $response = $app["changerequest_proxy"]->request(
                $method,
                "{$path_info}?{$req->getQueryString()}",
                [
                    "cookies" => $jar,
                    "json"    => $req->request->all()
                ]
            );

            $headers = array_filter(
                $response->getHeaders(),
                function ($value, $name) {
                    return 0 === preg_match("/^.*-encoding|connection(?:-.*)*?/i", $name);
                },
                ARRAY_FILTER_USE_BOTH
            );

            return $app->json(json_decode($response->getBody()), 200, $headers);
        } catch (\GuzzleHttp\Exception\RequestException $client_error) {
            return $app->abort(
                $client_error->getResponse()->getStatusCode(),
                $client_error->getResponse()->getReasonPhrase()
            );
        }
}
