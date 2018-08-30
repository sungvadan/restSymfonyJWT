<?php
/**
 * Created by PhpStorm.
 * User: vtphan
 * Date: 28/08/2018
 * Time: 13:27
 */

namespace Tests\AppBundle\Controller\Api;


use AppBundle\Test\ApiTestCase;

class BattleControllerTest extends ApiTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->createUser('weaverryan');
    }

    public function testPOSTCreateBattle()
    {
        $project = $this->createProject('my_project');
        $programmer = $this->createProgrammer([
            'nickname'  => 'Fred'
        ], 'weaverryan');

        $data = array(
            'projectId' => $project->getId(),
            'programmerId' => $programmer->getId()
        );

        $response = $this->client->post('/api/battles',[
            'body' => json_encode($data),
            'headers' => $this->getAuthorizedHeaders('weaverryan')
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyExists($response, 'didProgrammerWin');
        $this->asserter()->assertResponsePropertyEquals($response, 'project',$project->getId());
        $this->asserter()->assertResponsePropertyEquals($response, 'programmer','Fred');
        $this->debugResponse($response);
        // todo later
//        $this->assertTrue($response->hasHeader('Location'));

    }

}