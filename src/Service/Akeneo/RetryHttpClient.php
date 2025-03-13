<?php declare(strict_types=1);
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\Service\Akeneo;

use AmphiBee\AkeneoConnector\Service\LoggerService;
use AmphiBee\AkeneoConnector\Service\RetryConfigService;
use Monolog\Logger;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Client HTTP avec gestion des tentatives multiples
 *
 * @package    AmphiBee/AkeneoConnector
 * @author     Amphibee
 * @license    MIT
 * @copyright  (c) Amphibee <hello@amphibee.fr>
 * @since      1.1
 * @access     public
 */
class RetryHttpClient implements ClientInterface
{
    /** @var ClientInterface */
    private $httpClient;

    /**
     * RetryHttpClient constructor.
     *
     * @param ClientInterface $httpClient
     */
    public function __construct(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Envoie une requête HTTP avec gestion des tentatives
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        if (!RetryConfigService::isRetryEnabled()) {
            return $this->httpClient->sendRequest($request);
        }

        $maxRetries = RetryConfigService::getMaxRetries();
        $retryDelay = RetryConfigService::getRetryDelay();
        $retryStatusCodes = RetryConfigService::getRetryStatusCodes();

        $attempts = 0;
        $lastException = null;

        while ($attempts <= $maxRetries) {
            try {
                $attempts++;
                $response = $this->httpClient->sendRequest($request);

                // Si la réponse est un succès ou si le code d'erreur n'est pas dans la liste des codes à réessayer
                if (!in_array($response->getStatusCode(), $retryStatusCodes)) {
                    return $response;
                }

                // Si on a atteint le nombre maximum de tentatives, on retourne la dernière réponse
                if ($attempts > $maxRetries) {
                    LoggerService::log(
                        Logger::WARNING,
                        sprintf(
                            'Nombre maximum de tentatives atteint (%d) pour la requête %s %s. Dernier code de statut: %d',
                            $maxRetries,
                            $request->getMethod(),
                            $request->getUri(),
                            $response->getStatusCode()
                        )
                    );
                    return $response;
                }

                // Sinon, on attend et on réessaie
                LoggerService::log(
                    Logger::INFO,
                    sprintf(
                        'Tentative %d/%d échouée pour la requête %s %s. Code de statut: %d. Nouvelle tentative dans %d secondes...',
                        $attempts,
                        $maxRetries + 1,
                        $request->getMethod(),
                        $request->getUri(),
                        $response->getStatusCode(),
                        $retryDelay
                    )
                );

                sleep($retryDelay);

            } catch (ClientExceptionInterface $e) {
                $lastException = $e;

                // Si on a atteint le nombre maximum de tentatives, on lance l'exception
                if ($attempts > $maxRetries) {
                    LoggerService::log(
                        Logger::ERROR,
                        sprintf(
                            'Nombre maximum de tentatives atteint (%d) pour la requête %s %s. Dernière erreur: %s',
                            $maxRetries,
                            $request->getMethod(),
                            $request->getUri(),
                            $e->getMessage()
                        )
                    );
                    throw $e;
                }

                // Sinon, on attend et on réessaie
                LoggerService::log(
                    Logger::INFO,
                    sprintf(
                        'Tentative %d/%d échouée pour la requête %s %s. Erreur: %s. Nouvelle tentative dans %d secondes...',
                        $attempts,
                        $maxRetries + 1,
                        $request->getMethod(),
                        $request->getUri(),
                        $e->getMessage(),
                        $retryDelay
                    )
                );

                sleep($retryDelay);
            }
        }

        // Si on arrive ici, c'est qu'on a eu une exception à chaque tentative
        throw $lastException;
    }
}
