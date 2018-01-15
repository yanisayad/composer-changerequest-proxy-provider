<?php

namespace ETNA\Silex\Provider\ChangeRequestProxy;

use GuzzleHttp\Client;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ChangeRequestProxy implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        $app["changerequest_proxy"] = function ($app) {
            $changerequest_api_url = getenv("CHANGEREQUEST_API_URL");
            if (false === $changerequest_api_url) {
                throw new \Exception("ChangeRequestProxyProvider needs env var CHANGEREQUEST_API_URL");
            }
            if (false === getenv("TRUSTED_DOMAIN")) {
                throw new \Exception("ChangeRequestProxyProvider needs env var TRUSTED_DOMAIN");
            }

            return new Client([
                "base_uri" => $changerequest_api_url
            ]);
        };

        $app["changerequest"] = function ($app) {
            return new ChangeRequestManager($app);
        };
    }
}
