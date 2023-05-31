<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/BacklinkChecker/Backlink.php';
require_once __DIR__ . '/../src/BacklinkChecker/BacklinkData.php';
require_once __DIR__ . '/../src/BacklinkChecker/BacklinkChecker.php';
require_once __DIR__ . '/../src/BacklinkChecker/HttpResponse.php';
require_once __DIR__ . '/../src/BacklinkChecker/SimpleBacklinkChecker.php';
require_once __DIR__ . '/../src/BacklinkChecker/ChromeBacklinkChecker.php';

use PHPUnit\Framework\TestCase;
use Valitov\BacklinkChecker;

final class simpleModeTest extends TestCase
{
    /**
     * @var BacklinkChecker\SimpleBacklinkChecker
     */
    private $checker;

    public function __construct()
    {
        parent::__construct();
        $this->checker = new BacklinkChecker\SimpleBacklinkChecker();
    }

    public function testLinks()
    {
        $check_list = [
            [
                "url" => "http://localhost/noLinks.html",
                "pattern" => "@^http(s)?://(www\.)?walitoff\.com.*@",
                "backlinks" => 0,
                "scanLinks" => true,
                "scanImages" => false,
                "emptyAnchor" => false,
            ],
            [
                "url" => "http://localhost/simple.html",
                "pattern" => "@^https://(www\.)?walitoff\.com.*@",
                "backlinks" => 1,
                "scanLinks" => true,
                "scanImages" => false,
                "emptyAnchor" => false,
            ],
            [
                "url" => "http://localhost/simple.html",
                "pattern" => "@^http(s)?://(www\.)?walitoff\.com.*@",
                "backlinks" => 2,
                "scanLinks" => true,
                "scanImages" => false,
                "emptyAnchor" => false,
            ],
            [
                "url" => "http://localhost/emptyAnchor.html",
                "pattern" => "@^http(s)?://(www\.)?walitoff\.com.*@",
                "backlinks" => 1,
                "scanLinks" => true,
                "scanImages" => false,
                "emptyAnchor" => true,
            ],
            [
                "url" => "http://localhost/images.html",
                "pattern" => "@^http(s)?://(www\.)?walitoff\.com.*@",
                "backlinks" => 1,
                "scanLinks" => true,
                "scanImages" => true,
                "emptyAnchor" => true,
            ],
            [
                "url" => "http://localhost/images.html",
                "pattern" => "@^http(s)?://(www\.)?walitoff\.com.*@",
                "backlinks" => 0,
                "scanLinks" => true,
                "scanImages" => false,
                "emptyAnchor" => true,
            ],
            [
                "url" => "http://localhost/images.html",
                "pattern" => "@^http(s)?://(www\.)?walitoff\.com.*@",
                "backlinks" => 1,
                "scanLinks" => false,
                "scanImages" => true,
                "emptyAnchor" => true,
            ],
            [
                "url" => "http://localhost/noLinks.html",
                "pattern" => "@^http(s)?://(www\.)?walitoff\.com.*@",
                "backlinks" => 0,
                "scanLinks" => true,
                "scanImages" => false,
                "emptyAnchor" => false,
            ],
        ];
        $this->assertNotEmpty($check_list);

        $properties = [
            "Backlink",
            "Anchor",
            "NoFollow",
            "Target",
            "Tag",
        ];

        foreach ($check_list as $check) {
            $url = $check["url"];
            $pattern = $check["pattern"];
            $backlinks_count = $check["backlinks"];
            $this->assertNotEmpty($url);
            $this->assertNotEmpty($pattern);
            $this->assertGreaterThanOrEqual(0, $backlinks_count);
            $result = $this->checker->getBacklinks($url, $pattern, $check["scanLinks"], $check["scanImages"], false);
            $response = $result->getResponse();
            $this->assertTrue($response->getSuccess(), "Failed to read webpage $url");
            $this->assertNotEmpty($response->getResponse(), "Failed to get response from $url");
            $this->assertNotEmpty($response->getHeaders(), "Failed to get headers from $url");
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals($url, $response->getUrl());

            $json = $result->jsonSerialize();
            $this->assertNotEmpty($json, "Failed to get jsonSerialize for $url");
            foreach ([
                         "backlinks",
                         "response",
                     ] as $property) {
                $this->assertArrayHasKey($property, $json, "Serialize for $url must contain '$property' property");
            }
            $backlinks = $result->getBacklinks();
            $this->assertCount($backlinks_count, $backlinks, "Expected $backlinks_count backlinks for $url but got " . count($backlinks));
            if ($backlinks_count > 0) {
                foreach ($backlinks as $id => $backlink) {
                    $this->assertNotEmpty($backlink->getBacklink(), "Failed to get backlink $id for $url");
                    if (!$check["emptyAnchor"]) {
                        $this->assertNotEmpty($backlink->getAnchor(), "Failed to get anchor $id for $url");
                    } else {
                        $this->assertEmpty($backlink->getAnchor(), "Failed to get empty anchor $id for $url");
                    }

                    $this->assertNotEmpty($backlink->getTag(), "Failed to get tag $id for $url");
                    $array = $backlink->jsonSerialize();
                    $this->assertNotEmpty($array, "Failed to get jsonSerialize for $url");

                    foreach ($properties as $property) {
                        $this->assertArrayHasKey($property, $array, "Serialize for $url must contain '$property' property");
                    }
                }
            }
        }
    }
}