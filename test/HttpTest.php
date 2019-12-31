<?php

/**
 * @see       https://github.com/laminas/laminas-uri for the canonical source repository
 * @copyright https://github.com/laminas/laminas-uri/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-uri/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Uri;

use Laminas\Uri\Http as HttpUri;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @group      Laminas_Uri
 * @group      Laminas_Uri_Http
 * @group      Laminas_Http
 */
class HttpTest extends TestCase
{
    /**
     * Data Providers
     */

    /**
     * Valid HTTP schemes
     *
     * @return array
     */
    public function validSchemeProvider()
    {
        return array(
            array('http'),
            array('https'),
            array('HTTP'),
            array('Https'),
        );
    }

    public function validHostProvider()
    {
        return array(
            array('',                                   false),
            array('http',                               true),
            array('http:',                              false),
            array('http:/',                             false),
            array('http://',                            false),
            array('http:///',                           false),
            array('http://www.example.org/',            false),
            array('www.example.org:80',                 false),
            array('www.example.org',                    true),
            array('plekitööd.ee',                       true),
            array('http://foo',                         false),
            array('foo',                                true),
            array('ftp://user:pass@example.org/',       false),
            array('www.fi/',                            false),
            array('http://1.1.1.1/',                    false),
            array('1.1.1.1',                            true),
            array('1.256.1.1',                          true), // Hostnames can be only numbers
            array('http://[::1]/',                      false),
            array('[::1]',                              true),
            array('http://[2620:0:1cfe:face:b00c::3]/', false),
            array('[2620:0:1cfe:face:b00c::3]:80',      false),
            array('[2620:0:1cfe:face:b00c::3]',         true),
        );
    }

    /**
     * Invalid HTTP schemes
     *
     * @return array
     */
    public function invalidSchemeProvider()
    {
        return array(
            array('file'),
            array('mailto'),
            array('g'),
            array('http:')
        );
    }

    public function portNormalizationTestsProvider()
    {
        return array(
            array('http://www.example.com:80/foo/bar', 'http://www.example.com/foo/bar'),
            array('http://www.example.com:1234/foo/bar', 'http://www.example.com:1234/foo/bar'),
            array('https://www.example.com:443/foo/bar', 'https://www.example.com/foo/bar'),
            array('https://www.example.com:80/foo/bar', 'https://www.example.com:80/foo/bar'),
            array('http://www.example.com:443/foo/bar', 'http://www.example.com:443/foo/bar'),
        );
    }

    /**
     * Tests
     */

    /**
     * Test that specific schemes are valid for this class
     *
     * @param string $scheme
     * @dataProvider validSchemeProvider
     */
    public function testValidScheme($scheme)
    {
        $uri = new HttpUri;
        $uri->setScheme($scheme);
        $this->assertEquals($scheme, $uri->getScheme());
    }

    /**
     * Test that specific schemes are invalid for this class
     *
     * @param string $scheme
     * @dataProvider invalidSchemeProvider
     */
    public function testInvalidScheme($scheme)
    {
        $uri = new HttpUri;
        $this->setExpectedException('Laminas\Uri\Exception\InvalidUriPartException');
        $uri->setScheme($scheme);
    }

    /**
     * Test that validateScheme returns false for schemes not valid for use
     * with the HTTP class
     *
     * @param string $scheme
     * @dataProvider invalidSchemeProvider
     */
    public function testValidateSchemeInvalid($scheme)
    {
        $this->assertFalse(HttpUri::validateScheme($scheme));
    }

    /**
     * Test the validity of the hosts
     *
     * @param string  $host
     * @param  bool $expected
     * @return void
     * @dataProvider validHostProvider
     */
    public function testValidateHost($host, $expected)
    {
        $this->assertEquals($expected, HttpUri::validateHost($host), "Wrong Host validation $host");
    }

    /**
     * Test that normalizing an HTTP URL removes the port depending on scheme
     *
     * @param string $orig
     * @param string $expected
     * @dataProvider portNormalizationTestsProvider
     */
    public function testNormalizationRemovesPort($orig, $expected)
    {
        $uri = new HttpUri($orig);
        $uri->normalize();
        $this->assertEquals($expected, $uri->toString());
    }

    public function testUserIsNullByDefaultWhenNoUserInfoIsProvided()
    {
        $uri = new HttpUri('http://example.com/');
        $uri->normalize();
        $this->assertNull($uri->getUser());
    }

    public function testPasswordIsNullByDefaultWhenNoUserInfoIsProvided()
    {
        $uri = new HttpUri('http://example.com/');
        $uri->normalize();
        $this->assertNull($uri->getPassword());
    }

    public function testCanParseUsernameAndPasswordFromUriNotContainingPort()
    {
        $uri = new HttpUri('http://user:pass@example.com/');
        $uri->normalize();
        $this->assertEquals('user', $uri->getUser());
        $this->assertEquals('pass', $uri->getPassword());
        $this->assertEquals('example.com', $uri->getHost());
    }

    public function testCanParseUsernameAndPasswordFromUriContainingPort()
    {
        $uri = new HttpUri('http://user:pass@example.com:80/');
        $uri->normalize();
        $this->assertEquals('user', $uri->getUser());
        $this->assertEquals('pass', $uri->getPassword());
        $this->assertEquals('example.com', $uri->getHost());
    }

    public function testCanParseUsernameContainingAtMarkFromUri()
    {
        $uri = new HttpUri('http://user@foo.com:pass@example.com/');
        $uri->normalize();
        $this->assertEquals('user@foo.com', $uri->getUser());
        $this->assertEquals('pass', $uri->getPassword());
        $this->assertEquals('example.com', $uri->getHost());
    }

    public function testCanParsePasswordContainingAtMarkFromUri()
    {
        $uri = new HttpUri('http://user:p@ss@example.com/');
        $uri->normalize();
        $this->assertEquals('user', $uri->getUser());
        $this->assertEquals('p@ss', $uri->getPassword());
        $this->assertEquals('example.com', $uri->getHost());
    }

    public function testUserInfoCanOmitPassword()
    {
        $uri = new HttpUri('http://user@example.com@example.com/');
        $uri->normalize();
        $this->assertEquals('user@example.com', $uri->getUser());
        $this->assertNull($uri->getPassword());
        $this->assertEquals('example.com', $uri->getHost());
    }

    public function testGetPortReturnsDefaultPortHttp()
    {
        $uri = new HttpUri('http://www.example.com/');
        $this->assertEquals(80, $uri->getPort());
    }

    public function testGetPortReturnsDefaultPortHttps()
    {
        $uri = new HttpUri('https://www.example.com/');
        $this->assertEquals(443, $uri->getPort());
    }

    public function testGetPortDoesntModifyUrlHttp()
    {
        $origUri = 'http://www.example.com/foo';
        $uri = new HttpUri($origUri);
        $uri->getPort();
        $this->assertEquals($origUri, $uri->toString());
    }

    public function testGetPortDoesntModifyUrlHttps()
    {
        $origUri = 'https://www.example.com/foo';
        $uri = new HttpUri($origUri);
        $uri->getPort();
        $this->assertEquals($origUri, $uri->toString());
    }

    public function testValidHostTypesWithUnderScore()
    {
        $uri = new HttpUri('http://laminas_app.local');
        $this->assertTrue($uri->isValid());
    }

    /**
     * @group 6886
     */
    public function testCanSetUserAndPasswordWithUserInfo()
    {
        $uri = new HttpUri('http://www.example.com/');

        $uri->setUserInfo('user:pass');

        $this->assertSame('user', $uri->getUser());
        $this->assertSame('pass', $uri->getPassword());
    }

    /**
     * @group 6886
     */
    public function testCanSetUserWithUserInfo()
    {
        $uri = new HttpUri('http://www.example.com/');
        $uri->setUserInfo('user');

        $this->assertSame('user', $uri->getUser());
        $this->assertNull($uri->getPassword());
    }

    /**
     * @group 6886
     */
    public function testCanSetUserInfoWithUserAndPassword()
    {
        $uri = new HttpUri('http://www.example.com/');
        $uri->setUser('user');
        $uri->setPassword('pass');

        $this->assertSame('user', $uri->getUser());
        $this->assertSame('pass', $uri->getPassword());
        $this->assertSame('user:pass', $uri->getUserInfo());
    }

    /**
     * @group 6886
     */
    public function testCanSetUserInfoWithUser()
    {
        $uri = new HttpUri('http://www.example.com/');
        $uri->setUser('user');
        $this->assertSame('user', $uri->getUserInfo());
    }

    /**
     * @group 6886
     */
    public function testCanSetNullUser()
    {
        $uri = new HttpUri('http://www.example.com/');

        $uri->setUserInfo('user:password');
        $uri->setUser(null);

        $this->assertNull($uri->getUser());
        $this->assertSame('password', $uri->getPassword());
    }

    /**
     * @group 6886
     */
    public function testCanSetNullPassword()
    {
        $uri = new HttpUri('http://www.example.com/');

        $uri->setUserInfo('user:password');
        $uri->setPassword(null);

        $this->assertSame('user', $uri->getUser());
        $this->assertNull($uri->getPassword());
        $this->assertSame('user', $uri->getUserInfo());
    }

    /**
     * @group 6886
     */
    public function testCanSetNullUserInfo()
    {
        $uri = new HttpUri('http://www.example.com/');

        $uri->setUserInfo('user:password');
        $uri->setUserInfo(null);

        $this->assertNull($uri->getUser());
        $this->assertNull($uri->getPassword());
        $this->assertNull($uri->getUserInfo());
    }
}
