<?php

namespace Drupal\Tests\jcms_rest\Unit;

use Drupal\jcms_rest\JCMSHtmlHelperTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Class JCMSHtmlHelperTraitTest.
 *
 * @package Drupal\Tests\jcms_rest\Unit
 */
class JCMSHtmlHelperTraitTest extends UnitTestCase {

  use JCMSHtmlHelperTrait;

  /**
   * Provider for split paragraphs tests.
   */
  public function providerSplitParagraphs() {
    return [
      'single-list-among-paragraphs' => [
        "Unlike relational databases, IIIF servers are not a commodity, and the choice of server implementation is going to constrain other parameters such as the image storage and the supported formats. Writing a server from scratch was not the appropriate solution for us as the work that it performs is non-trivial: cutting, resizing, rotating and especially converting images between different formats. Unlike for simpler domains like indexing text, image-related software has a huge amount of test cases to be verified, corresponding to images of all sizes, formats and colors. .\n\nThe IIIF Image API 2.0 allows several levels of compliance:\n\n<ul><li>Level 0 only allows full portions of the image, at predefined sizes</li>\n  <li>Level 1 adds the capability to request image portions, at any size</li>\n  <li>Level 2 adds rotation, and multiple output options such as grayscale and the PNG format</li>\n</ul>\n\nFor our own implementation of IIIF on eLife, we chose <a href=\"https://github.com/loris-imageserver/loris\">Loris</a>, an image server providing IIIF Image API Level 2. Loris is a small Python web application, backed by many Python and C libraries. We run Loris in <a href=\"https://uwsgi-docs.readthedocs.io/en/latest/\">uWSGI</a>, as we would do with any standard Python web application.\n\nWe expose Loris to the outside world through an Nginx server, which is capable of solving the standard problems of HTTP traffic such as setting cache headers and performing redirects. Nginx can scale to thousands of concurrent connections, and adds minimal overhead on top of the computationally intensive work of image manipulation.",
        [
          "Unlike relational databases, IIIF servers are not a commodity, and the choice of server implementation is going to constrain other parameters such as the image storage and the supported formats. Writing a server from scratch was not the appropriate solution for us as the work that it performs is non-trivial: cutting, resizing, rotating and especially converting images between different formats. Unlike for simpler domains like indexing text, image-related software has a huge amount of test cases to be verified, corresponding to images of all sizes, formats and colors. .",
          "The IIIF Image API 2.0 allows several levels of compliance:",
          [
            'type' => 'list',
            'prefix' => 'bullet',
            'items' => [
              'Level 0 only allows full portions of the image, at predefined sizes',
              'Level 1 adds the capability to request image portions, at any size',
              'Level 2 adds rotation, and multiple output options such as grayscale and the PNG format',
            ],
          ],
          "For our own implementation of IIIF on eLife, we chose <a href=\"https://github.com/loris-imageserver/loris\">Loris</a>, an image server providing IIIF Image API Level 2. Loris is a small Python web application, backed by many Python and C libraries. We run Loris in <a href=\"https://uwsgi-docs.readthedocs.io/en/latest/\">uWSGI</a>, as we would do with any standard Python web application.",
          "We expose Loris to the outside world through an Nginx server, which is capable of solving the standard problems of HTTP traffic such as setting cache headers and performing redirects. Nginx can scale to thousands of concurrent connections, and adds minimal overhead on top of the computationally intensive work of image manipulation.",
        ],
      ],
      'single-list-leading-paragraph' => [
        "Infrastructure in the real world is often referred to as the fundamental facilities of a country. In the computing world it consists of all the (now virtualized) hardware components that connect users with the information they want to obtain. In the case of IIIF, the laundry list for the required infrastructure consists of:\n\n<ul><li>Two or more virtual machines: `t2.medium` EC2 instances are inexpensive and good for bursts of increased compute power when there are peaks in traffic.</li>\n  <li>Their volumes: not-particularly-fast hard drives can be used as a cache of original and generated images. The standard storage provided by EC2 instances is only ~7 GB, of which most is occupied by the operating system. Additional volumes can be used as the second level of storage, to avoid cleaning the cache every half an hour.</li>\n  <li>Load balancing: servers can be detached one at a time from <a href=\"https://aws.amazon.com/elasticloadbalancing/\">ELBs</a>, in order to perform maintenance or cleaning operations like cache pruning without interrupting traffic. ELBs can also perform HTTPS termination, making the single IIIF servers easier to set up as they don't need to be configured with SSL certificates.</li>\n  <li>Content Delivery Networks (CDNs): CloudFront can be used for edge caching, storing cached versions of popular images near the user’s location to reduce latency. The benefit of CloudFront is its simplicity, although the lack of protection from cache stampedes and the fairly long time it takes to invalidate content and update its configurations are drawbacks.<br />\n   </li>\n</ul>",
        [
          "Infrastructure in the real world is often referred to as the fundamental facilities of a country. In the computing world it consists of all the (now virtualized) hardware components that connect users with the information they want to obtain. In the case of IIIF, the laundry list for the required infrastructure consists of:",
          [
            'type' => 'list',
            'prefix' => 'bullet',
            'items' => [
              "Two or more virtual machines: `t2.medium` EC2 instances are inexpensive and good for bursts of increased compute power when there are peaks in traffic.",
              "Their volumes: not-particularly-fast hard drives can be used as a cache of original and generated images. The standard storage provided by EC2 instances is only ~7 GB, of which most is occupied by the operating system. Additional volumes can be used as the second level of storage, to avoid cleaning the cache every half an hour.",
              "Load balancing: servers can be detached one at a time from <a href=\"https://aws.amazon.com/elasticloadbalancing/\">ELBs</a>, in order to perform maintenance or cleaning operations like cache pruning without interrupting traffic. ELBs can also perform HTTPS termination, making the single IIIF servers easier to set up as they don't need to be configured with SSL certificates.",
              "Content Delivery Networks (CDNs): CloudFront can be used for edge caching, storing cached versions of popular images near the user’s location to reduce latency. The benefit of CloudFront is its simplicity, although the lack of protection from cache stampedes and the fairly long time it takes to invalidate content and update its configurations are drawbacks.",
            ],
          ],
        ],
      ],
      'multiple-lists' => [
        "Paragraph one:\n\n<ol>\n  <li>Item 1</li>\n  <li>Item 2\n    <ul>\n      <li>Item 2.1  <li>\n      <li>Item 2.2</li>\n    </ul>\n  </li><li>\n  Item 3\n  </li>\n</ol>\n\nParagraph two\n\nParagraph three:\n\n<ul>\n  <li>Item 1</li>\n  <li>Item 2</li>\n  <li>Item 3</li>\n</ul>\n\nParagraph four\n\n",
        [
          "Paragraph one:",
          [
            'type' => 'list',
            'prefix' => 'number',
            'items' => [
              "Item 1",
              "Item 2",
              [
                [
                  'type' => 'list',
                  'prefix' => 'bullet',
                  'items' => [
                    "Item 2.1",
                    "Item 2.2",
                  ],
                ],
              ],
              "Item 3",
            ],
          ],
          "Paragraph two",
          "Paragraph three:",
          [
            'type' => 'list',
            'prefix' => 'bullet',
            'items' => [
              "Item 1",
              "Item 2",
              "Item 3",
            ],
          ],
          "Paragraph four",
        ],
      ],
      'paragraphs-with-table-single-line' => [
        "Paragraph one\n\n<table><tr><td>Cell one</td></tr></table>",
        [
          "Paragraph one",
          [
            'type' => 'table',
            'tables' => [
              "<table><tr><td>Cell one</td></tr></table>",
            ],
          ],
        ],
      ],
      'paragraphs-with-table-multi-line-with-whitespace' => [
        "Paragraph one\n\n<table>\n<tr>\n<td>Cell one</td>\n\t</tr></table>\n\nParagraph two",
        [
          "Paragraph one",
          [
            'type' => 'table',
            'tables' => [
              "<table><tr><td>Cell one</td></tr></table>",
            ],
          ],
          "Paragraph two",
        ],
      ],
    ];
  }

  /**
   * Test output of split paragraphs method.
   *
   * @test
   * @dataProvider providerSplitParagraphs
   * @covers \Drupal\jcms_rest\JCMSHtmlHelperTrait::splitParagraphs
   * @covers \Drupal\jcms_rest\JCMSHtmlHelperTrait::convertHtmlListToSchema
   * @group journal-cms-tests
   */
  public function testSplitParagraphs($paragraphs, $expected) {
    $this->assertEquals($expected, $this->splitParagraphs($paragraphs));
  }

}
