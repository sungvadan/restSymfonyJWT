<?php

namespace Tests\AppBundle\Controller\Api;


use AppBundle\Test\ApiTestCase;

class TokenControllerTest extends ApiTestCase
{
    public function testPOSTCreateToken()
    {
        $this->createUser('weaverryan','foo');
        $reponse = $this->client->post('/api/tokens',[
           'auth' => ['weaverryan','foo']
        ]);

        $this->assertEquals(200, $reponse->getStatusCode());
        $this->asserter()->assertResponsePropertyExists(
            $reponse,
            'token'
        );
    }

    public function testPOSTCreateTokenInvalidCredentials()
    {
        $this->createUser('weaverryan','foo');
        $reponse = $this->client->post('/api/tokens',[
            'auth' => ['weaverryan','wrongPassword']
        ]);

        $this->assertEquals(401, $reponse->getStatusCode());
    }

}