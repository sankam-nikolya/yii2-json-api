<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */
namespace tuyakhov\jsonapi\tests;

use tuyakhov\jsonapi\tests\data\ResourceModel;
use tuyakhov\jsonapi\Serializer;
use yii\data\ArrayDataProvider;

class SerializerTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        ResourceModel::$fields = ['field1', 'field2'];
        ResourceModel::$extraFields = [];
    }

    public function testSerializeModelErrors()
    {
        $serializer = new Serializer();
        $model = new ResourceModel();
        $model->addError('field1', 'Test error');
        $model->addError('field2', 'Multiple error 1');
        $model->addError('field2', 'Multiple error 2');
        $this->assertEquals([
            [
                'source' => ['pointer' => "/data/attributes/field1"],
                'detail' => 'Test error',
            ],
            [
                'source' => ['pointer' => "/data/attributes/field2"],
                'detail' => 'Multiple error 1',
            ]
        ], $serializer->serialize($model));
    }

    public function testSerializeModelData()
    {
        $serializer = new Serializer();
        $model = new ResourceModel();
        $this->assertSame([
            'data' => [
                'id' => '123',
                'type' => 'resource-models',
                'attributes' => [
                    'field1' => 'test',
                    'field2' => 2,
                ],
                'links' => [
                    'self' => ['href' => 'http://example.com/resource/123']
                ]
            ]
        ], $serializer->serialize($model));

        ResourceModel::$fields = ['field1'];
        ResourceModel::$extraFields = [];
        $this->assertSame([
            'data' => [
                'id' => '123',
                'type' => 'resource-models',
                'attributes' => [
                    'field1' => 'test',
                ],
                'links' => [
                    'self' => ['href' => 'http://example.com/resource/123']
                ]
            ]
        ], $serializer->serialize($model));

        ResourceModel::$fields = ['field1'];
        ResourceModel::$extraFields = ['field2'];
        $this->assertSame([
            'data' => [
                'id' => '123',
                'type' => 'resource-models',
                'attributes' => [
                    'field1' => 'test',
                ],
                'links' => [
                    'self' => ['href' => 'http://example.com/resource/123']
                ]
            ]
        ], $serializer->serialize($model));
    }

    public function testExpand()
    {
        $serializer = new Serializer();
        $serializedModel = [
            'id' => '123',
            'type' => 'resource-models',
            'attributes' => [
                'field1' => 'test',
                'field2' => 2,
            ],
            'relationships' => [
                'extraField1' => [
                    'data' => ['id' => '123', 'type' => 'resource-models'],
                    'links' => [
                        'self' => ['href' => 'http://example.com/resource/123/relationships/extraField1'],
                        'related' => ['href' => 'http://example.com/resource/123/extraField1'],
                    ]
                ]
            ],
            'links' => [
                'self' => ['href' => 'http://example.com/resource/123']
            ]
        ];
        $model = new ResourceModel();
        ResourceModel::$fields = ['field1', 'field2'];
        ResourceModel::$extraFields = ['extraField1'];
        $model->extraField1 = new ResourceModel();
        $this->assertSame([
            'data' => $serializedModel
        ], $serializer->serialize($model));

        \Yii::$app->request->setQueryParams(['include' => 'extraField1']);
        $this->assertSame([
            'data' => $serializedModel,
            'included' => [
                [
                    'id' => '123',
                    'type' => 'resource-models',
                    'attributes' => [
                        'field1' => 'test',
                        'field2' => 2,
                    ],
                    'links' => [
                        'self' => ['href' => 'http://example.com/resource/123']
                    ]
                ]
            ]
        ], $serializer->serialize($model));

        \Yii::$app->request->setQueryParams(['include' => 'extraField1,extraField2']);
        $this->assertSame([
            'data' => $serializedModel,
            'included' => [
                [
                    'id' => '123',
                    'type' => 'resource-models',
                    'attributes' => [
                        'field1' => 'test',
                        'field2' => 2,
                    ],
                    'links' => [
                        'self' => ['href' => 'http://example.com/resource/123']
                    ]
                ]
            ]
        ], $serializer->serialize($model));

        \Yii::$app->request->setQueryParams(['include' => 'field1,extraField2']);
        $this->assertSame([
            'data' => $serializedModel
        ], $serializer->serialize($model));
    }

    public function dataProviderSerializeDataProvider()
    {
        $bob = new ResourceModel();
        $bob->username = 'Bob';
        $bob->extraField1 = new ResourceModel();
        $expectedBob = ['id' => '123', 'type' => 'resource-models', 
            'attributes' => ['username' => 'Bob'],
            'links' => ['self' => ['href' => 'http://example.com/resource/123']],
            'relationships' => ['extraField1' => [
                'data' => ['id' => '123', 'type' => 'resource-models'],
                'links' => [
                    'related' => ['href' => 'http://example.com/resource/123/extraField1'],
                    'self' => ['href' => 'http://example.com/resource/123/relationships/extraField1']
                ]
            ]]];
        $tom = new ResourceModel();
        $tom->username = 'Tom';
        $tom->extraField1 = new ResourceModel();
        $expectedTom = [
            'id' => '123', 'type' => 'resource-models',
            'attributes' => ['username' => 'Tom'],
            'links' => ['self' => ['href' => 'http://example.com/resource/123']],
            'relationships' => ['extraField1' => [
                'data' => ['id' => '123', 'type' => 'resource-models'],
                'links' => [
                    'related' => ['href' => 'http://example.com/resource/123/extraField1'],
                    'self' => ['href' => 'http://example.com/resource/123/relationships/extraField1']
                ]
            ]]];
        return [
            [
                new ArrayDataProvider([
                    'allModels' => [
                        $bob,
                        $tom
                    ],
                    'pagination' => [
                        'route' => '/',
                    ],
                ]),
                [
                    'data' => [
                        $expectedBob,
                        $expectedTom
                    ],
                    'meta' => [
                        'total-count' => 2,
                        'page-count' => 1,
                        'current-page' => 1,
                        'per-page' => 20
                    ],
                    'links' => [
                        'self' => ['href' => '/index.php?r=&page=1']
                    ]
                ]
            ],
            [
                new ArrayDataProvider([
                    'allModels' => [
                        $bob,
                        $tom
                    ],
                    'pagination' => [
                        'route' => '/',
                        'pageSize' => 1,
                        'page' => 0
                    ],
                ]),
                [
                    'data' => [
                        $expectedBob
                    ],
                    'meta' => [
                        'total-count' => 2,
                        'page-count' => 2,
                        'current-page' => 1,
                        'per-page' => 1
                    ],
                    'links' => [
                        'self' => ['href' => '/index.php?r=&page=1&per-page=1'],
                        'next' => ['href' => '/index.php?r=&page=2&per-page=1'],
                        'last' => ['href' => '/index.php?r=&page=2&per-page=1']
                    ]
                ]
            ],
            [
                new ArrayDataProvider([
                    'allModels' => [
                        $bob,
                        $tom
                    ],
                    'pagination' => [
                        'route' => '/',
                        'pageSize' => 1,
                        'page' => 1
                    ],
                ]),
                [
                    'data' => [
                        $expectedTom
                    ],
                    'meta' => [
                        'total-count' => 2,
                        'page-count' => 2,
                        'current-page' => 2,
                        'per-page' => 1
                    ],
                    'links' => [
                        'self' => ['href' => '/index.php?r=&page=2&per-page=1'],
                        'first' => ['href' => '/index.php?r=&page=1&per-page=1'],
                        'prev' => ['href' => '/index.php?r=&page=1&per-page=1']
                    ]
                ]
            ],
            [
                new ArrayDataProvider([
                    'allModels' => [
                        $bob,
                        $tom
                    ],
                    'pagination' => false,
                ]),
                [
                    'data' => [
                        $expectedBob,
                        $expectedTom
                    ]
                ]
            ]
        ];
    }
    /**
     * @dataProvider dataProviderSerializeDataProvider
     *
     * @param \yii\data\DataProviderInterface $dataProvider
     * @param array $expectedResult
     */
    public function testSerializeDataProvider($dataProvider, $expectedResult)
    {
        $serializer = new Serializer();
        ResourceModel::$extraFields = ['extraField1'];
        ResourceModel::$fields = ['username'];
        $this->assertEquals($expectedResult, $serializer->serialize($dataProvider));
    }
}
