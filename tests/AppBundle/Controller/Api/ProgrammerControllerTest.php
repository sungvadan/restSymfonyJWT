<?php

namespace Tests\AppBundle\Controller\Api;

use AppBundle\Battle\BattleManager;
use AppBundle\Test\ApiTestCase;

class ProgrammerControllerTest extends ApiTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->createUser('weaverryan');
    }

    public function testPOSTProgrammerWorks()
    {
        $data = array(
            'nickname' => 'ObjectOrienter',
            'avatarNumber' => 5,
            'tagLine' => 'a test dev!'
        );

        // 1) Create a programmer resource
        $response = $this->client->post('/api/programmers', [
            'body' => json_encode($data),
            'headers' => $this->getAuthorizedHeaders('weaverryan')
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Location'));
        $this->assertEquals('application/hal+json',$response->getHeader('Content-Type')[0]);
        $this->assertStringEndsWith('/api/programmers/ObjectOrienter', $response->getHeader('Location')[0]);
        $finishedData = json_decode($response->getBody(true), true);
        $this->assertArrayHasKey('nickname', $finishedData);
        $this->assertEquals('ObjectOrienter', $finishedData['nickname']);
    }

    public function testGETProgrammer()
    {
        $this->createProgrammer(array(
            'nickname' => 'UnitTester',
            'avatarNumber' => 3,
        ));

        $response = $this->client->get('/api/programmers/UnitTester', [
            'headers' => $this->getAuthorizedHeaders('weaverryan')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'nickname',
            'avatarNumber',
            'powerLevel',
            'tagLine'
        ));
        $this->asserter()->assertResponsePropertyEquals($response, 'nickname', 'UnitTester');
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            '_links.self',
            $this->adjustUri('/api/programmers/UnitTester')
        );
    }

    public function testFollowProgrammerBattlesLink()
    {
        $programmer = $this->createProgrammer(array(
            'nickname' => 'UnitTester',
            'avatarNumber' => 3,
        ));

        $project = $this->createProject('cool_project');
        /** @var BattleManager $battleManager */
        $battleManager = $this->getService('battle.battle_manager');
        $battleManager->battle($programmer, $project);
        $battleManager->battle($programmer, $project);
        $battleManager->battle($programmer, $project);

        $response = $this->client->get('/api/programmers/UnitTester', [
            'headers' => $this->getAuthorizedHeaders('weaverryan')
        ]);

        $uri = $this->asserter()
                    ->readResponseProperty($response, '_links.battles');
        $response = $this->client->get($uri, [
            'headers' => $this->getAuthorizedHeaders('weaverryan')
        ]);
        $this->asserter()->assertResponsePropertyExists($response, 'items');
        $this->debugResponse($response);
    }

    public function testGETProgrammerDeep()
    {
        $this->createProgrammer(array(
            'nickname' => 'UnitTester',
            'avatarNumber' => 3,
        ));

        $response = $this->client->get('/api/programmers/UnitTester?deep=1', [
            'headers' => $this->getAuthorizedHeaders('weaverryan')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'user.username'
        ));
    }

    public function testGETProgrammersCollection()
    {
        $this->createProgrammer(array(
            'nickname' => 'UnitTester',
            'avatarNumber' => 3,
        ));
        $this->createProgrammer(array(
            'nickname' => 'CowboyCoder',
            'avatarNumber' => 5,
        ));

        $response = $this->client->get('/api/programmers', [
            'headers' => $this->getAuthorizedHeaders('weaverryan')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyIsArray($response, 'items');
        $this->asserter()->assertResponsePropertyCount($response, 'items', 2);
        $this->asserter()->assertResponsePropertyEquals($response, 'items[1].nickname', 'CowboyCoder');
    }

    public function testGETProgrammersCollectionPaginated()
    {
        $this->createProgrammer(array(
            'nickname' => 'willnotmatch',
            'avatarNumber' => 5,
        ));

        for ($i = 0; $i < 25; $i++) {
            $this->createProgrammer(array(
                'nickname' => 'Programmer'.$i,
                'avatarNumber' => 3,
            ));
        }

        // page 1
        $response = $this->client->get('/api/programmers?filter=programmer', [
            'headers' => $this->getAuthorizedHeaders('weaverryan')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'items[5].nickname',
            'Programmer5'
        );

        $this->asserter()->assertResponsePropertyEquals($response, 'count', 10);
        $this->asserter()->assertResponsePropertyEquals($response, 'total', 25);
        $this->asserter()->assertResponsePropertyExists($response, '_links.next');

        // page 2
        $nextLink = $this->asserter()->readResponseProperty($response, '_links.next');
        $response = $this->client->get($nextLink, [
            'headers' => $this->getAuthorizedHeaders('weaverryan')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'items[5].nickname',
            'Programmer15'
        );
        $this->asserter()->assertResponsePropertyEquals($response, 'count', 10);

        $lastLink = $this->asserter()->readResponseProperty($response, '_links.last');
        $response = $this->client->get($lastLink, [
            'headers' => $this->getAuthorizedHeaders('weaverryan')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'items[4].nickname',
            'Programmer24'
        );

        $this->asserter()->assertResponsePropertyDoesNotExist($response, 'items[5].name');
        $this->asserter()->assertResponsePropertyEquals($response, 'count', 5);
    }

    public function testPUTProgrammer()
    {
        $this->createProgrammer(array(
            'nickname' => 'CowboyCoder',
            'avatarNumber' => 5,
            'tagLine' => 'foo',
        ));

        $data = array(
            'nickname' => 'CowgirlCoder',
            'avatarNumber' => 2,
            'tagLine' => 'foo',
        );
        $response = $this->client->put('/api/programmers/CowboyCoder', [
            'body' => json_encode($data),
            'headers' => $this->getAuthorizedHeaders('weaverryan')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals($response, 'avatarNumber', 2);
        // the nickname is immutable on edit
        $this->asserter()->assertResponsePropertyEquals($response, 'nickname', 'CowboyCoder');
    }

    public function testPATCHProgrammer()
    {
        $this->createProgrammer(array(
            'nickname' => 'CowboyCoder',
            'avatarNumber' => 5,
            'tagLine' => 'foo',
        ));

        $data = array(
            'tagLine' => 'bar',
        );
        $response = $this->client->patch('/api/programmers/CowboyCoder', [
            'body' => json_encode($data),
            'headers' => $this->getAuthorizedHeaders('weaverryan')
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyEquals($response, 'avatarNumber', 5);
        $this->asserter()->assertResponsePropertyEquals($response, 'tagLine', 'bar');
    }

    public function testDELETEProgrammer()
    {
        $this->createProgrammer(array(
            'nickname' => 'UnitTester',
            'avatarNumber' => 3,
        ));

        $response = $this->client->delete('/api/programmers/UnitTester', [
            'headers' => $this->getAuthorizedHeaders('weaverryan')
        ]);
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testValidationErrors()
    {
        $data = array(
            'avatarNumber' => 2,
            'tagLine' => 'I\'m from a test!'
        );

        // 1) Create a programmer resource
        $response = $this->client->post('/api/programmers', [
            'body' => json_encode($data),
            'headers' => $this->getAuthorizedHeaders('weaverryan')
        ]);

        $this->assertEquals(400, $response->getStatusCode());
        $this->asserter()->assertResponsePropertiesExist($response, array(
            'type',
            'title',
            'errors',
        ));
        $this->asserter()->assertResponsePropertyExists($response, 'errors.nickname');
        $this->asserter()->assertResponsePropertyEquals($response, 'errors.nickname[0]', 'Please enter a clever nickname');
        $this->asserter()->assertResponsePropertyDoesNotExist($response, 'errors.avatarNumber');
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type')[0]);
    }

    public function testInvalidJson()
    {
        $invalidBody = <<<EOF
{
    "nickname": "JohnnyRobot",
    "avatarNumber" : "2
    "tagLine": "I'm from a test!"
}
EOF;

        $response = $this->client->post('/api/programmers', [
            'body' => $invalidBody,
            'headers' => $this->getAuthorizedHeaders('weaverryan')
        ]);

        $this->assertEquals(400, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyContains($response, 'type', 'invalid_body_format');
    }

    public function test404Exception()
    {
        $response = $this->client->get('/api/programmers/fake', [
            'headers' => $this->getAuthorizedHeaders('weaverryan')
        ]);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type')[0]);
        $this->asserter()->assertResponsePropertyEquals($response, 'type', 'about:blank');
        $this->asserter()->assertResponsePropertyEquals($response, 'title', 'Not Found');
        $this->asserter()->assertResponsePropertyEquals($response, 'detail', 'No programmer found with nickname "fake"');
    }

    public function testRequiresAuthentication()
    {
        $response = $this->client->post('/api/programmers', [
            'body' => '[]'
            // do not send auth!
        ]);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testBadToken()
    {
        $response = $this->client->post('/api/programmers', [
            'body' => '[]',
            'headers' => [
                'Authorization' => 'Bearer WRONG'
            ]
        ]);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type')[0]);
    }

    public function testEditTagLine()
    {
        $this->createProgrammer(array(
            'nickname' => 'CowboyCoder',
            'avatarNumber' => 5,
            'tagLine' => 'The original CowboyCoder',
        ));


        $response = $this->client->put('/api/programmers/CowboyCoder/tagline',[
            'headers' => $this->getAuthorizedHeaders('weaverryan'),
            'body' => "New Tag Line"
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('New Tag Line', (string) $response->getBody());
    }

    public function testPowerUp()
    {
        $this->createProgrammer(array(
            'nickname' => 'CowboyCoder',
            'avatarNumber' => 5,
            'powerLevel' => 10,
        ));


        $response = $this->client->post('/api/programmers/CowboyCoder/powerup',[
            'headers' => $this->getAuthorizedHeaders('weaverryan'),
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $powerLevel = $this->asserter()->readResponseProperty($response, 'powerLevel');

    }
}
