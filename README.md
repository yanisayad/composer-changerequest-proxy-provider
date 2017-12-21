# composer-changerequest-proxy-provider
Permets aux differentes applications d'avoir un proxy vers changerequest-api

## Installation

Modifier `composer.json` :

```
{
    // ...
    "require": {
        "etna/changerequest-proxy-provider": "~1.0.x"
    },
    "repositories": [
       {
           "type": "composer",
           "url": "https://blu-composer.herokuapp.com"
       }
   ]
}
```

## Utilisation

### Déclarer le composant

Le composant `etna/config-provider` met à disposition une classe permettant de faire utiliser ce proxy à notre application.

Lors de la configuration de l'application il faut donc utiliser la classe `ETNA\Silex\Provider\Config\ChangeRequestProxy` :

```
use ETNA\Silex\Provider\Config as ETNAProvider;

class EtnaConfig implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        ...

        $my_controller = new ConversationController();
        $app->register(new ETNAProvider\ChangeRequestProxy());

        ...
    }
}
```

### Le contenu de ce composant

Ce provider met a disposition :
- L'objet ChangeTodos, qui est une "entité" qui se comporte comme une entité doctrine le ferait
 - On peut la remplir avec un array grace à `$change_todos->fromArray($array)` (notamment le résultat Elasticsearch)
 - On peut la serializer en array grace à `$change_todos->toArray()`
- L'objet ChangeRequestManager ($app["changerequest"]) se comporte comme l'entity manager de doctrine, sauf qu'il permet aussi de récupérer des changerequest.

Il inclut des méthodes, pour la recherche:
 - `findByQueryString` qui prend en paramètre une query string ElasticSearch (exemple: +id:42 +request_type:company_change_request) et qui retourne un tableau de ChangeTodos
 - `findOneByQueryString` qui prends aussi en paramètre une query string mais retourne l'objet le plus pertinent

Pour le changement de status d'une request
 - `validate` qui prend en paramètre un changeTodos et le passe en validé.
 - `invalidate` qui prend en paramètre un changeTodos et le passe en refusé.

Pour la création d'une change request
 - `save` qui prend en paramètre un ChangeTodos et effectue les requetes necessaires pour la création
