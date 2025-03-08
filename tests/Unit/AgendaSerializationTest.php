<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\components\Tools;
use Tests\Support\Helper\TestBase;

class AgendaSerializationTest extends TestBase
{
    const INPUT_JSON = '[
      {
        "id": 15,
        "type": "item",
        "code": null,
        "title": "Tagesordnungspunkt 23",
        "date": null,
        "time": "15:34",
        "children": [
          {
            "id": 16,
            "type": "item",
            "code": null,
            "title": "Tagesordnungspunkt 2.1",
            "time": "15:34",
            "date": null,
            "children": []
          }
        ]
      },
      {
        "id": 22,
        "type": "date_separator",
        "code": null,
        "title": "Test",
        "date": "2025-03-01",
        "time": "15:34",
        "children": []
      }
    ]';
    public function testSerialization(): void
    {
        $serializer = Tools::getSerializer();
        $data = $serializer->deserialize(self::INPUT_JSON, \app\models\api\AgendaItem::class . '[]', 'json');

        $reSerialized = $serializer->serialize($data, 'json');
        $this->assertJsonStringEqualsJsonString(self::INPUT_JSON, $reSerialized);
    }
}
