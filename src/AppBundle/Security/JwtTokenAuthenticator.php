<?php

namespace AppBundle\Security;


use AppBundle\Api\ApiProblem;
use AppBundle\Api\ResponseFactory;
use Behat\Mink\Exception\Exception;
use Doctrine\ORM\EntityManager;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\AuthorizationHeaderTokenExtractor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class JwtTokenAuthenticator extends AbstractGuardAuthenticator
{

    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var JWTEncoderInterface
     */
    private $jwtEncoder;
    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(JWTEncoderInterface $jwtEncoder, EntityManager $em, ResponseFactory $responseFactory)
    {

        $this->em = $em;
        $this->jwtEncoder = $jwtEncoder;
        $this->responseFactory = $responseFactory;
    }

    public function getCredentials(Request $request)
    {
        $extractor = new AuthorizationHeaderTokenExtractor(
            'Bearer',
            'Authorization'
        );

            $token = $extractor->extract($request);
            if(!$token){
                return;
            }
            return $token;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        try{
            $data = $this->jwtEncoder->decode($credentials);
            if($data === false){
                throw new CustomUserMessageAuthenticationException('Invalid token');
            }

            $username = $data['username'];
            return $this->em->getRepository('AppBundle:User')
                ->findOneBy(['username' => $username]);
        }catch (\Exception $exception){
            return;
        }
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $apiProblem = new ApiProblem(401);
        $apiProblem->set('detail', $exception->getMessageKey());

        return $this->responseFactory->createResponse($apiProblem);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // do nothing
    }

    public function supportsRememberMe()
    {
        return false;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        $apiProblem = new ApiProblem(401);
        $mesage = $authException ? $authException->getMessageKey() : 'Missing Credential';
        $apiProblem->set('detail', $mesage);

        return $this->responseFactory->createResponse($apiProblem);
    }


}