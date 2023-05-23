<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Nzm\AmpCacheUrl\Generator;
use Nzm\AmpCacheUrl\ServingMode;

#[CoversClass(Generator::class)]
class GeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $tests = [
            [
                'url' => 'https://www.example.com',
                'cache' => 'https://www-example-com.cdn.ampproject.org/c/s/www.example.com',
            ],
            [
                'url' => 'http://www.example.com',
                'cache' => 'https://www-example-com.cdn.ampproject.org/c/www.example.com',
            ],
            [
                'url' => 'https://www.example.com/index.html',
                'cache' => 'https://www-example-com.cdn.ampproject.org/c/s/www.example.com/index.html',
            ],
            [
                'url' => 'http://www.example.com/index.html',
                'cache' => 'https://www-example-com.cdn.ampproject.org/c/www.example.com/index.html',
            ],
            [
                'url' => 'https://www.example.com/image.png',
                'cache' => 'https://www-example-com.cdn.ampproject.org/i/s/www.example.com/image.png',
            ],
            [
                'url' => 'http://www.example.com/image.png',
                'cache' => 'https://www-example-com.cdn.ampproject.org/i/www.example.com/image.png',
            ],
            [
                'url' => 'https://www.example.com/font.woff2',
                'cache' => 'https://www-example-com.cdn.ampproject.org/r/s/www.example.com/font.woff2',
            ],
            [
                'url' => 'http://www.example.com/font.woff2',
                'cache' => 'https://www-example-com.cdn.ampproject.org/r/www.example.com/font.woff2',
            ],
            [
                'url' => 'https://example.com/g?value=Hello%20World',
                'cache' => 'https://example-com.cdn.ampproject.org/c/s/example.com/g?value=Hello%20World',
            ],
            [
                'url' => 'https://點看.com',
                'cache' => 'https://xn---com-k47jg78q.cdn.ampproject.org/c/s/點看.com',
            ],
            [
                'url' => 'http://localhost:3000',
                'cache' => 'https://jgla3zmib2ggq5buc4hwi5taloh6jlvzukddfr4zltz3vay5s5rq.cdn.ampproject.org/c/localhost',
            ],
            [
                'url' => 'http://cn--57hw060o.com',
                'cache' => 'https://u2n7nddp4hct24xboe5njbiapyync7mrzdj6gtx7prmtamvd5lwq.cdn.ampproject.org/c/cn--57hw060o.com',
            ],
            [
                'url' => 'https://مثال.واحد',
                'cache' => 'https://xn----ymcbnnm7lf5a.cdn.ampproject.org/c/s/مثال.واحد'
            ],
            [
                'url' => 'https://this-is-another-very-long-domain-name-with-75-chars-example.com',
                'cache' => 'https://pduwp3oh2b36edcmfn7oyu4fzbaecngaqh2vxccbna4ch3wzjzxq.cdn.ampproject.org/c/s/this-is-another-very-long-domain-name-with-75-chars-example.com'
            ]
        ];

        $domainSuffix = 'cdn.ampproject.org';
        $generator = new Generator();

        foreach ($tests as $test) {
            $cacheUrl = $generator->generate($domainSuffix, $test['url']);
            $this->assertEquals($test['cache'], $cacheUrl);
        }

        $cacheUrl = $generator->generate($domainSuffix, 'https://www.example.com', ServingMode::Viewer);
        $this->assertEquals('https://www-example-com.cdn.ampproject.org/v/s/www.example.com', $cacheUrl);
    }

    public function testEmptyUrl()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('URL cannot be empty.');

        $domainSuffix = 'cdn.ampproject.org';
        $generator = new Generator();
        $cacheUrl = $generator->generate($domainSuffix, '');
    }

    public function testCreateCurlsSubdomain()
    {
        $tests = [
            [
                'url' => 'https://something.com',
                'curlsSubdomain' => 'something-com',
            ],
            [
                'url' => 'https://SOMETHING.COM',
                'curlsSubdomain' => 'something-com',
            ],
            [
                'url' => 'https://hello-world.com',
                'curlsSubdomain' => 'hello--world-com',
            ],
            [
                'url' => 'https://hello--world.com',
                'curlsSubdomain' => 'hello----world-com',
            ],
            [
                'url' => 'https://toplevelnohyphens',
                'curlsSubdomain' => 'qsgpfjzulvuaxb66z77vlhb5gu2irvcnyp6t67cz6tqo5ae6fysa',
            ],
            [
                'url' => 'https://no-dot-domain',
                'curlsSubdomain' => '4lxc7wqq7b25walg4rdiil62veijrmqui5z3ept2lyfqqwpowryq',
            ],
            [
                'url' => 'https://itwasadarkandstormynight.therainfellintorrents.exceptatoccasionalintervalswhenitwascheckedby.aviolentgustofwindwhichsweptupthestreets.com',
                'curlsSubdomain' => 'dgz4cnrxufaulnwku4ow5biptyqnenjievjht56hd7wqinbdbteq',
            ],
            [
                'url' => 'https://xn--bcher-kva.ch',
                'curlsSubdomain' => 'xn--bcher-ch-65a',
            ],
            [
                'url' => 'https://xn--4gbrim.xn----rmckbbajlc6dj7bxne2c.xn--wgbh1c',
                'curlsSubdomain' => 'xn-------i5fvcbaopc6fkc0de0d9jybegt6cd',
            ],
            [
                'url' => 'https://hello.xn--4gbrim.xn----rmckbbajlc6dj7bxne2c.xn--wgbh1c',
                'curlsSubdomain' => 'a6h5moukddengbsjm77rvbosevwuduec2blkjva4223o4bgafgla',
            ],
            [
                'url' => 'https://en-us.example.com',
                'curlsSubdomain' => '0-en--us-example-com-0',
            ],
        ];
        
        $generator = new Generator();
        foreach ($tests as $test) {
            $domain = parse_url($test['url'])['host'];
            $curlsSubdomain = $generator->createCurlsSubdomain($domain);
            $this->assertEquals($test['curlsSubdomain'], $curlsSubdomain);
        }
    }
}