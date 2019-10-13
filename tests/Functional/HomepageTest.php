<?php
namespace Tests\Functional;

class HomepageTest extends BaseTestCase
{

    /**
     * Test that the index route returns a rendered response containing the text 'SlimFramework' but not a greeting
     */
    public function testGetHomepageWithoutName()
    {
        $response = $this->runApp('GET', '/');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('SlimFramework', (string) $response->getBody());
    }

    /**
     * Test sign in route
     */
    public function testGetHomepageSignIn()
    {
        $response = $this->runApp('GET', '/auth/signin');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Sign in', (string) $response->getBody());
    }

    /**
     * Test sign up route
     */
    public function testGetHomepageSignUp()
    {
        $response = $this->runApp('GET', '/auth/signup');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Sign up', (string) $response->getBody());
    }

    /**
     * Test that the index route won't accept a post request
     */
    public function testPostHomepageNotAllowed()
    {
        $response = $this->runApp('POST', '/', [
            'test'
        ]);

        $this->assertEquals(405, $response->getStatusCode());
        $this->assertContains('Method Not Allowed', (string) $response->getBody());
    }
}
