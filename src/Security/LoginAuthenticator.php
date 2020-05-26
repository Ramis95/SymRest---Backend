<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Firebase\JWT\JWT;

class LoginAuthenticator extends AbstractGuardAuthenticator
{

    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function supports(Request $request)
    {
        return $request->get("_route") === "api_login" && $request->isMethod("POST");

        /* Описание метода: Возвращает true или false.
        Когда он возвращает true, это говорит Symfony, что этот аутентификатор следует использовать.
        Он будет использоваться, когда пользователь отправляет сообщения в конечную точку /api/login
        Проверяем, что путь равен api_login и метод отправки POST
        */
    }

    public function getCredentials(Request $request)
    {
        return[
            'email' => $request->get('email'),
            'password' => $request->get('password')
        ];

        /* Описание метода: Возращает пользовательские данные при авторизации
        */

    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return $userProvider->loadUserByUsername($credentials['email']);

        /* Описание метода: Возвращает пользователя, который пытается войти в систему по "Username" в нашем случае это email.
         username - универсальная штука то, что человек использует в качестве имени при входе в систему
        */
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);

        /* Описание метода: Проверяет данные пользователя
        */
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return new JsonResponse([
           'error' => $exception->getMessageKey()
        ], 400);
        /* Описание метода: Эта функция будет вызываться каждый раз, когда возникает ошибка (когда checkCredentials = false).
            Возвращает ошибку клиенту
        */
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $expireTime = time() + 3600;
        $tokenPayload = [
            'user_id' => $token->getUser()->getId(),
            'email' => $token->getUser()->getEmail(),
            'exp' => $expireTime
        ];

        $jwt = JWT::encode($tokenPayload, $_ENV['JWT_SECRET']);

        // Если разрабатывается на не https сервере, нужно будет установить переменную как false
        $useHttps = false;
        setcookie ("jwt", $jwt, $expireTime, '/',  "", $useHttps, true );

        return new JsonResponse([
            'result auth' => true
        ]);

        /* Описание метода: Эта функция будет вызываться при успешной авторизации (когда checkCredentials = true)
        */
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new JsonResponse([
            'error' => 'Access denied',
        ], 403);

        /* Описание метода: Этот метод вызывается всякий раз, когда достигается конечная точка, требующая аутентификации.
            Если бы мы создавали традиционное веб-приложение, в котором сервер отображал представления, мы перенаправляли бы на страницу формы входа
        */
    }

    public function supportsRememberMe()
    {
        // todo
    }
}
