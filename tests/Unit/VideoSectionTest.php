<?php

namespace Tests\Unit;

use app\models\db\MotionSection;
use app\models\sectionTypes\VideoEmbed;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Support\Helper\TestBase;

class VideoSectionTest extends TestBase
{
    private function createVideo(string $data): VideoEmbed
    {
        /** @var MockObject|MotionSection $section */
        $section = $this->createMock(MotionSection::class);
        $section->method('getData')->willReturn($data);

        return new VideoEmbed($section);
    }

    public function testYoutube1(): void
    {
        $section = $this->createVideo('Here is the video: https://www.youtube.com/watch?v=4Y1lZQsyuSQ!');
        $html = $section->getSimple(false);
        $this->assertEquals('<div class="videoHolder"><div class="videoSizer"><iframe src="https://www.youtube.com/embed/4Y1lZQsyuSQ" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div></div>', $html);
    }

    public function testYoutube2(): void
    {
        $section = $this->createVideo('Here is the video: http://youtu.be/4Y1lZQsyuSQ!');
        $html = $section->getSimple(false);
        $this->assertEquals('<div class="videoHolder"><div class="videoSizer"><iframe src="https://www.youtube.com/embed/4Y1lZQsyuSQ" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div></div>', $html);
    }

    public function testYoutube3(): void
    {
        $section = $this->createVideo('Here is the video: youtube.com/embed/4Y1lZQsyuSQ!');
        $html = $section->getSimple(false);
        $this->assertEquals('<div class="videoHolder"><div class="videoSizer"><iframe src="https://www.youtube.com/embed/4Y1lZQsyuSQ" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div></div>', $html);
    }

    public function testVimeo1(): void
    {
        $section = $this->createVideo('Here is the video: https://vimeo.com/123456789?autoplay=1!');
        $html = $section->getSimple(false);
        $this->assertEquals('<div class="videoHolder"><div class="videoSizer"><iframe src="https://player.vimeo.com/video/123456789" allow="autoplay; fullscreen" allowfullscreen></iframe></div></div>', $html);
    }

    public function testVimeo2(): void
    {
        $section = $this->createVideo('Here is the video: https://vimeo.com/channels/staffpicks/123456789?autoplay=1!');
        $html = $section->getSimple(false);
        $this->assertEquals('<div class="videoHolder"><div class="videoSizer"><iframe src="https://player.vimeo.com/video/123456789" allow="autoplay; fullscreen" allowfullscreen></iframe></div></div>', $html);
    }

    public function testFacebook1(): void
    {
        $section = $this->createVideo('Here is the video: https://www.facebook.com/watch/?v=123456789123456!');
        $html = $section->getSimple(false);
        $this->assertEquals('<div class="videoHolder"><div class="videoSizer"><iframe src="https://www.facebook.com/plugins/video.php?href=Here+is+the+video%3A+https%3A%2F%2Fwww.facebook.com%2Fwatch%2F%3Fv%3D123456789123456%21&show_text=0&width=476" allowTransparency="true" allowFullScreen="true"></iframe></div></div>', $html);
    }
}
