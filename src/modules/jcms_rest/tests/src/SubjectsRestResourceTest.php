<?php

namespace Drupal\Tests\jcms_rest\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\jcms_rest\Plugin\rest\resource\SubjectsRestResource;
use \Mockery as m;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SubjectsRestResourceTest
 *
 * @package Drupal\Tests\jcms_rest\Unit
 */
class SubjectsRestResourceTest extends UnitTestCase {

  /**
   * @var \Drupal\jcms_rest\Plugin\rest\resource\SubjectsRestResource
   *   The class we're testing.
   */
  public $resource;

  protected $queryFactory;

  protected $query;

  protected $requestStack;

  protected $entityManager;

  protected $entityTypeManager;

  public function setUp() {
    //$this->term = m::mock('Drupal\taxonomy\Entity\Term');
    // Set the container.
    $container = new ContainerBuilder();
    // Entity field query.
    $this->queryFactory = m::mock('Drupal\Core\Entity\Query\QueryFactory');
    // Returned query (normally mock Drupal\Core\Entity\Query\Sql\Query but this
    // causes the test to error).
    $this->query = m::mock('stdClass');
    $container->set('entity.query', $this->queryFactory);
    $this->requestStack = m::mock('Symfony\Component\HttpFoundation\RequestStack');
    $container->set('request_stack', $this->requestStack);
    $this->entityManager = m::mock('Drupal\Core\Entity\EntityManager');
    $container->set('entity.manager', $this->entityManager);
    $this->entityTypeManager = m::mock('Drupal\Core\Entity\EntityTypeManager');
    $container->set('entity_type.manager', $this->entityTypeManager);
    \Drupal::setContainer($container);
    // Add the parent constructor parameters as we're testing a class that
    // extends another class.
    $this->resource = new SubjectsRestResource(['configuration'], 'plugin_id', 'plugin_definition', ['serializer_formats'], m::mock('Psr\Log\LoggerInterface'));
  }

  public function tearDown() {
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
    m::close();
  }

  /**
   * Helper method to return a mocked term entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|static
   */
  protected function createItem() {
    $term_values = [
      'tid' => '15',
      'vid' => 'subjects',
      'uuid' => '2c4c9ae3-548d-4be9-8677-71f50039f55c',
      'langcode' => 'en',
      'name' => 'Biochemistry',
      'description' => [
        'value' => NULL,
        'format' => NULL,
      ],
      'weight' => '0',
      'changed' => '1472133100',
      'default_langcode' => '1',
      'field_image' => [
        [
          'target_id' => '34',
          'alt' => 'Biochemistry alt',
          'title' => NULL,
          'width' => '1800',
          'height' => '1350',
        ],
      ],
      'field_impact_statement' => [
        [
          'value' => 'Biochemistry impact statement',
          'format' => 'basic_html',
        ],
      ],
      'field_subject_id' => [
        [
          'value' => 'biochemistry',
        ],
      ],
    ];
    $term = m::mock('Drupal\Core\Entity\EntityInterface')->makePartial();
    // TID.
    $term->shouldReceive('get')->andReturnSelf();
    $term->shouldReceive('first')->andReturnSelf();
    $term->shouldReceive('getValue')
      ->once()
      ->andReturn(['value' => $term_values['tid']]);
    // Name.
    $term->shouldReceive('toLink')->once()->andReturnSelf();
    $term->shouldReceive('getText')->once()->andReturn($term_values['name']);
    // Image.
    $term->shouldReceive('getValue')
      ->once()
      ->andReturn(['alt' => $term_values['field_image'][0]['alt']]);
    // Image URI.
    $term->shouldReceive('getTarget')->andReturnSelf();
    $term->shouldReceive('getValue')
      ->once()
      ->andReturn(['value' => 'public://plant-biology.png']);
    // Image style.
    $this->entityManager->shouldReceive('getEntityTypeFromClass')
      ->andReturnSelf();
    $this->entityManager->shouldReceive('getStorage')->andReturnSelf();
    $this->entityManager->shouldReceive('load')->andReturnSelf();
    $this->entityManager->shouldReceive('buildUrl')
      ->andReturn('"http://journal-cms.local/sites/default/files/styles/crop_2x1_1800x900/public/plant-biology.png?itok=c-fmlMss');
    // Impact statement.
    $term->shouldReceive('count')->once()->andReturn(1);
    $term->shouldReceive('getValue')
      ->once()
      ->andReturn(['value' => $term_values['field_impact_statement'][0]['value']]);
    return $term;
  }

  /**
   * @test
   * @covers \Drupal\jcms_rest\Plugin\rest\resource\SubjectsRestResource::get
   * @group  journal-cms-tests
   */
  public function testGetNoSubjects() {
    // Make the query.
    $this->queryFactory->shouldReceive('get')->andReturn($this->query);
    $this->query->shouldReceive('condition')->andReturn($this->query);
    $this->query->shouldReceive('count')->andReturn($this->query);
    $this->query->shouldReceive('execute')->andReturn(0);
    // Run the method.
    $response = $this->resource->get();
    // Test we have the correct response.
    $this->assertInstanceOf('Drupal\rest\ResourceResponse', $response);
    // Don't just test the response, test the data.
    $actual = $response->getResponseData();
    $expected = [
      'total' => 0,
      'items' => [],
    ];
    $this->assertEquals($expected, $actual);
  }

  /**
   * @test
   * @covers \Drupal\jcms_rest\Plugin\rest\resource\SubjectsRestResource::get
   * @covers \Drupal\jcms_rest\Plugin\rest\resource\SubjectsRestResource::getRequestOptions
   * @group  journal-cms-tests
   */
  public function testGetHasSubjectsDefaultOptions() {
    // Make the query,
    $this->queryFactory->shouldReceive('get')->andReturn($this->query);
    $this->query->shouldReceive('condition')->andReturn($this->query);
    $this->query->shouldReceive('count')->andReturn($this->query);
    $this->query->shouldReceive('execute')->once()->andReturn(1);
    // The request should return a Request object.
    $this->requestStack->shouldReceive('getCurrentRequest')
      ->andReturn(new Request());
    $this->query->shouldReceive('range')->andReturn($this->query);
    $this->query->shouldReceive('sort')->andReturn($this->query);
    $this->query->shouldReceive('execute')->once()->andReturn([15]);
    // Load the terms.
    $this->entityManager->shouldReceive('loadMultiple')
      ->once()
      ->andReturn([15 => $this->createItem()]);
    // MAke the call.
    $response = $this->resource->get();
    // Test we have the correct response.
    $this->assertInstanceOf('Drupal\rest\ResourceResponse', $response);
    // Don't just test the response, test the data.
    $actual = $response->getResponseData();
    $expected = [
      'total' => 1,
      'items' => [
        [
          'id' => '15',
          'name' => 'Biochemistry',
          'image' => [
            'alt' => 'Biochemistry alt',
            'sizes' => [
              '2:1' => [
                900 => '"http://journal-cms.local/sites/default/files/styles/crop_2x1_1800x900/public/plant-biology.png?itok=c-fmlMss',
                1800 => '"http://journal-cms.local/sites/default/files/styles/crop_2x1_1800x900/public/plant-biology.png?itok=c-fmlMss',
              ],
              '16:9' => [
                250 => '"http://journal-cms.local/sites/default/files/styles/crop_2x1_1800x900/public/plant-biology.png?itok=c-fmlMss',
                500 => '"http://journal-cms.local/sites/default/files/styles/crop_2x1_1800x900/public/plant-biology.png?itok=c-fmlMss',
              ],
              '1:1' => [
                70 => '"http://journal-cms.local/sites/default/files/styles/crop_2x1_1800x900/public/plant-biology.png?itok=c-fmlMss',
                140 => '"http://journal-cms.local/sites/default/files/styles/crop_2x1_1800x900/public/plant-biology.png?itok=c-fmlMss',
              ],
            ],
          ],
          'impactStatement' => 'Biochemistry impact statement',
        ],
      ],
    ];
    $this->assertEquals($expected, $actual);
  }

  /**
   * @test
   * @covers \Drupal\jcms_rest\Plugin\rest\resource\SubjectsRestResource::getRequestOptions
   * @group  journal-cms-tests
   */
  public function testGetRequestOptionsDefault() {
    // The request should return a default Request object.
    $this->requestStack->shouldReceive('getCurrentRequest')
      ->andReturn(new Request);
    $expected = [
      'page' => 1,
      'per-page' => 20,
      'order' => 'desc',
    ];
    $actual = $this->resource->getRequestOptions();
    $this->assertEquals($expected, $actual);
  }

  /**
   * @test
   * @covers \Drupal\jcms_rest\Plugin\rest\resource\SubjectsRestResource::getRequestOptions
   * @group  journal-cms-tests
   */
  public function testGetRequestOptionsDefaultModified() {
    $query = [
      'page' => 1888,
      'per-page' => 1967,
      'order' => 'asc',
    ];
    // The request should return a Request object with different query values.
    $this->requestStack->shouldReceive('getCurrentRequest')
      ->andReturn(new Request($query));
    $expected = [
      'page' => 1888,
      'per-page' => 1967,
      'order' => 'asc',
    ];
    $actual = $this->resource->getRequestOptions();
    $this->assertEquals($expected, $actual);
  }

  /**
   * @test
   * @covers \Drupal\jcms_rest\Plugin\rest\resource\SubjectsRestResource::getItem
   * @group  journal-cms-tests
   */
  public function testGetItem() {
    $expected = [
      'id' => '15',
      'name' => 'Biochemistry',
      'image' => [
        'alt' => 'Biochemistry alt',
        'sizes' => [
          '2:1' => [
            900 => '"http://journal-cms.local/sites/default/files/styles/crop_2x1_1800x900/public/plant-biology.png?itok=c-fmlMss',
            1800 => '"http://journal-cms.local/sites/default/files/styles/crop_2x1_1800x900/public/plant-biology.png?itok=c-fmlMss',
          ],
          '16:9' => [
            250 => '"http://journal-cms.local/sites/default/files/styles/crop_2x1_1800x900/public/plant-biology.png?itok=c-fmlMss',
            500 => '"http://journal-cms.local/sites/default/files/styles/crop_2x1_1800x900/public/plant-biology.png?itok=c-fmlMss',
          ],
          '1:1' => [
            70 => '"http://journal-cms.local/sites/default/files/styles/crop_2x1_1800x900/public/plant-biology.png?itok=c-fmlMss',
            140 => '"http://journal-cms.local/sites/default/files/styles/crop_2x1_1800x900/public/plant-biology.png?itok=c-fmlMss',
          ],
        ],
      ],
      'impactStatement' => 'Biochemistry impact statement',
    ];
    $actual = $this->resource->getItem($this->createItem());
    $this->assertEquals($expected, $actual);
  }

}
