<?php

namespace App\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;


class JwtAuthenticator extends AbstractGuardAuthenticator
{
    public function supports(Request $request)
    {
        return $request->cookies->get("jwt") ? true : false;
    }

    public function getCredentials(Request $request)
    {


//	    return new JsonResponse([
//	    	'jwt' => $request->get('jwt')
//	    ]);

	    $cookie = $request->get('jwt');
//	    $cookie = $request->cookies->get("jwt");

//	    $cookie = $request->get('jwt');
	    $error = "Невозможно проверить сессию"; // Текст ошибки для стандартного исключения

        try
        {
            $decodedJwt = JWT::decode($cookie, $_ENV['JWT_SECRET'], ['HS256']);
            return [
                'user_id' => $decodedJwt->user_id,
                'email' => $decodedJwt->email
            ];
        }
        catch(ExpiredException $e)
        {
            $error = "Сессия истекла";
        }
        catch(SignatureInvalidException $e)
        {
            // Используется неправильная подпись
            // Возможно попытка взлома
            $error = "Неправильная подпись";
        }
        catch(\Exception $e)
        {
            // Стработало стандартное исключение
        }

        throw new CustomUserMessageAuthenticationException($error);

    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return $userProvider->loadUserByUsername($credentials['email']);
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return $user->getId() === $credentials['user_id'];
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return new JsonResponse([
            'error' => $exception->getMessageKey()
        ], 400);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // Allow request to continue

    }

    public function start(Request $request, AuthenticationException $authException = null)
    {

    }

    public function supportsRememberMe()
    {

    }
}
