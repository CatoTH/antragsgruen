<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\components\Tools;
use app\models\api\agenda\AgendaItem;
use app\models\api\agenda\AgendaItemType;
use app\models\api\agenda\AgendaList;
use Tests\Support\Helper\TestBase;

class AgendaSerializationTest extends TestBase
{
    const INPUT_JSON = '{
      "items": [
        {
            "id": 15,
            "type": "item",
            "code": null,
            "title": "Tagesordnungspunkt 23",
            "date": null,
            "time": "15:34",
            "settings": {
                "has_speaking_list": true,
                "in_proposed_procedures": true,
                "motion_types": [10],
                "speaking_lists": []
            },
            "children": [
              {
                "id": 16,
                "type": "item",
                "code": null,
                "title": "Tagesordnungspunkt 2.1",
                "time": "15:34",
                "date": null,
                "children": [],
                "settings": {
                    "has_speaking_list": true,
                    "in_proposed_procedures": true,
                    "motion_types": [],
                    "speaking_lists": [10]
                }
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
            "children": [],
            "settings": {
                "has_speaking_list": true,
                "in_proposed_procedures": true,
                "motion_types": [10],
                "speaking_lists": null
            }
          }
        ]
      }';
    public function testSerialization(): void
    {
        $serializer = Tools::getSerializer();
        /** @var AgendaList $data */
        $data = $serializer->deserialize(self::INPUT_JSON, AgendaList::class, 'json');
        $this->assertInstanceOf(AgendaItem::class, $data->items[0]);
        $this->assertEquals(AgendaItemType::ITEM, $data->items[0]->type);
        $reSerialized = $serializer->serialize($data, 'json');
        $this->assertJsonStringEqualsJsonString(self::INPUT_JSON, $reSerialized);
    }
}
