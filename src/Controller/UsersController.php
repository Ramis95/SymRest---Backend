<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

use App\Security\UserAuthenticator;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


//use App\Services\LanguageController;

//use Psr\Log\LoggerInterface; //Если понадобится что-то логировать


class UsersController extends AbstractController
{
    private $manager;
    private $user;
    private $request;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
        $this->user    = new User();
        $this->request = Request::createFromGlobals();
    }


    /**
     * @Route ("/", name="api_home")
     */
    public function api_home()
    {
        return $this->json(
            [
                'success' => true
            ],
            200
        );
    }

    /**
     * @Route("/api/users", name="get users", methods="GET")
     */
    public function getUsers(Request $request)
    {
        $rep  = $this->manager->getRepository(User::class);
        $user = $rep->findAll();

        $encoders    = array(new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());

        $serializer = new Serializer($normalizers, $encoders);
        $json_users = $serializer->normalize($user);

        return $this->json($json_users);
    }

    /**
     * @Route("/api/users/{id}", name="getUserById", methods="GET")
     */
    public function getUserById($id)
    {
        $rep  = $this->manager->getRepository(User::class);
        $user = $rep->findOneBy(['id' => $id]);

        if ( ! $user) {
            return $this->json([
                'message' => 'Error! Not Found',
            ]);
        }

        return $this->json([
            $user,
        ]);
    }

    /**
     * @Route("/api/add_user", name="add_user", methods="POST")
     */
    public function add_user()
    {
        $this->users->setName('User_06');
        $this->users->setText('Еще один текст');
        $this->users->setStatus(7);

        $this->manager->persist($this->users);
        $this->manager->flush();

        return $this->json([
            'message' => 'Все гуд, объект на месте',
            'path'    => 'src/Controller/UsersController.php',
        ]);
    }

    /**
     * @Route("/api/registration", name="registration", methods="POST")
     */
    public function registration(UserPasswordEncoderInterface $passwordEncoder)
    {
        $email    = $this->request->get('email');
        $password = $passwordEncoder->encodePassword($this->user,
            $this->request->get('password'));
        $errors   = [];

        try {
            $this->user->setEmail($email);
            $this->user->setPassword($password);
//        $this->user->setRoles(1);

            $this->manager = $this->getDoctrine()->getManager();
            $this->manager->persist($this->user);
            $this->manager->flush();

            //$password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
//        $user->setPassword($password);

            //$user = new User();

            return $this->json([
                'status'  => 'success',
                'massage' => 'Пользователь добавлен',
                'user'    => $this->user,
            ], 200);
        } catch (UniqueConstraintViolationException $e) {
            $errors[] = "The email provided already has an account!";
        } catch (\Exception $e) {
            $errors[] = "Unable to save new user at this time.";
        }

        return $this->json([
            'status'  => $errors,
            'massage' => 'Пользователь добавлен',
        ], 400);

    }

    /**
     * @Route("/api/login", name="api_login")
     */
    public function login()
    {
    }

    /**
     * @Route("/api/logout", name="api_logout")
     */
    public function api_logout()
    {
    }

    /**
     * @Route("/api/profile", name="api_profile")
     * @IsGranted("ROLE_USER")
     */
    public function api_profile()
    {
        return $this->json(
            [
            'user' => $this->getUser(), //Тело (основная информация)
            ],
            200, //Код ответа
            [], //массив дополнительных заголовков для использования при выводе
            [
                'groups' => ['api']  //Контекст. В этом параметре мы можем передать массив с ключом групп
            ]);
    }


}
