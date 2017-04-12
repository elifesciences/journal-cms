<?php

namespace Drupal\jcms_rest\Tests\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\jcms_rest\Plugin\rest\resource\SubjectListRestResource;
use \Mockery as m;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Class SubjectListRestResourceTest
 *
 * @package Drupal\Tests\jcms_rest\Unit
 */
class SubjectListRestResourceTest extends UnitTestCase {

  /**
   * @var \Drupal\jcms_rest\Plugin\rest\resource\SubjectListRestResource
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
    $this->resource = new SubjectListRestResource(['configuration'], 'plugin_id', 'plugin_definition', ['serializer_formats'], m::mock('Psr\Log\LoggerInterface'));
  }

  /**
   * @test
   * @covers \Drupal\jcms_rest\Plugin\rest\resource\SubjectListRestResource::get
   * @group  journal-cms-tests
   */
  public function testGetNoSubjects() {
    // Make the query.
    $this->query->shouldReceive('condition')->andReturn($this->query);
    $this->query->shouldReceive('count')->andReturn($this->query);
    $this->query->shouldReceive('execute');
    $this->entityTypeManager->shouldReceive('getStorage')->andReturnSelf();
    $this->entityTypeManager->shouldReceive('getQuery')->andReturn($this->query);
    $this->entityTypeManager->shouldReceive('execute')->andReturn(0);
    // Run the method.
    $response = $this->resource->get();
    // Test we have the correct response.
    $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
    // Don't just test the response, test the data.
    $actual = $response->getContent();
    $expected = '{"total":0,"items":[]}';
    $this->assertEquals($expected, $actual);
  }

}
