<?php

namespace Tests\AppBundle\Controller\Api;


use AppBundle\Test\ApiTestCase;

class TokenControllerTest extends ApiTestCase
{
    public function testPOSTCreateTokenValidCredentials()
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
        $response = $this->client->post('/api/tokens',[
            'auth' => ['weaverryan','wrongPassword']
        ]);

        $this->assertEquals(401, $response->getStatusCode());

        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type')[0]);
        $this->asserter()->assertResponsePropertyEquals($response, 'type', 'about:blank');
        $this->asserter()->assertResponsePropertyEquals($response, 'title', 'Unauthorized');
        $this->asserter()->assertResponsePropertyEquals($response, 'detail', 'Invalid credentials.');
    }

}