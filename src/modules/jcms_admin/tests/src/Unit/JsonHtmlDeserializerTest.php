<?php

namespace Drupal\Tests\jcms_admin\Unit;

use Drupal\jcms_admin\JsonHtmlDeserializer;
use eLife\ApiSdk\Model\Model;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Tests for JsonHtmlDeserializer.
 */
class JsonHtmlDeserializerTest extends TestCase {

  use Helper;

  /**
   * Denormalizer.
   *
   * @var \Drupal\jcms_admin\JsonHtmlDeserializer
   */
  private $denormalizer;

  /**
   * Setup.
   *
   * @before
   */
  protected function setUpDenormalizer() {
    $this->denormalizer = new JsonHtmlDeserializer();
  }

  /**
   * Provider.
   */
  public function denormalizeProvider() : array {
    return [
      'minimal' => [
        [
          'content' => [],
        ],
        '',
      ],
      'single section' => [
        [
          'content' => [
            [
              'type' => 'section',
              'title' => 'Section heading',
              'content' => [
                [
                  'type' => 'paragraph',
                  'text' => 'Single paragraph',
                ],
              ],
            ],
          ],
        ],
        $this->lines([
          '<h1>Section heading</h1>',
          '<p>Single paragraph</p>',
        ], 2),
      ],
      'questions' => [
        [
          'content' => [
            [
              'type' => 'question',
              'question' => 'Do you like my question?',
              'answer' => [
                [
                  'type' => 'paragraph',
                  'text' => 'This is an answer to the question.',
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'This is an extended answer.',
                ],
              ],
            ],
            [
              'type' => 'quote',
              'text' => [
                [
                  'type' => 'paragraph',
                  'text' => 'Quote',
                ],
              ],
            ],
            [
              'type' => 'paragraph',
              'text' => 'This is not an answer.',
            ],
            [
              'type' => 'question',
              'question' => 'Next question?',
              'answer' => [
                [
                  'type' => 'paragraph',
                  'text' => 'OK!',
                ],
              ],
            ],
          ],
        ],
        $this->lines([
          '<h1>Do you like my question?</h1>',
          '<p>This is an answer to the question.</p>',
          '<p>This is an extended answer.</p>',
          '<blockquote>Quote</blockquote>',
          '<p>This is not an answer.</p>',
          '<h1>Next question?</h1>',
          '<p>OK!</p>',
        ], 2),
      ],
      'span soup' => [
        [
          'content' => [
            [
              'type' => 'paragraph',
              'text' => '<span><span><span><span><span>Lotte Meteyard has been a lecturer at the University of Reading since 2010. She did her PhD from 2004 to 2007 at University College London, followed by a postdoc in 2008 at the Cognition and Brain Sciences Unit, Cambridge. From 2008 to 2010 she </span></span></span></span></span><span><span><span><span><span>retrained as a speech and language therapist. She got m</span></span></span></span></span>arried in 2012 and had her first child in 2015.',
            ],
          ],
        ],
        $this->lines([
          '<p>Lotte Meteyard has been a lecturer at the University of Reading since 2010. She did her PhD from 2004 to 2007 at University College London, followed by a postdoc in 2008 at the Cognition and Brain Sciences Unit, Cambridge. From 2008 to 2010 she retrained as a speech and language therapist. She got married in 2012 and had her first child in 2015.</p>',
        ]),
      ],
      'single paragraph' => [
        [
          'content' => [
            [
              'type' => 'paragraph',
              'text' => '<strong>Single</strong> paragraph',
            ],
          ],
        ],
        '<p><strong>Single</strong> paragraph</p>',
      ],
      'single table' => [
        [
          'content' => [
            [
              'type' => 'table',
              'tables' => [
                '<table><tr><td>Cell one</td></tr></table>',
              ],
            ],
          ],
        ],
        '<table><tr><td>Cell one</td></tr></table>',
      ],
      'simple image' => [
        [
          'content' => [
            [
              'type' => 'image',
              'image' => [
                'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180427145110-1.jpeg',
                'source' => [
                  'mediaType' => 'image/jpeg',
                  'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180427145110-1.jpeg/full/full/0/default.jpg',
                  'filename' => 'image-20180427145110-1.jpeg',
                ],
                'size' => [
                  'width' => 2000,
                  'height' => 2000,
                ],
                'focalPoint' => [
                  'x' => 50,
                  'y' => 50,
                ],
                'attribution' => [
                  'Image attribution',
                ],
              ],
              'title' => 'A nice picture of a field. Courtesy of <a href="https://www.pexels.com/photo/biology-blur-close-up-dragonflies-287361/">Pexels</a>.',
            ],
            [
              'type' => 'paragraph',
              'text' => 'Trailing paragraph',
            ],
          ],
        ],
        $this->lines([
          '<figure class="image align-center"><img alt="" data-fid="123" data-uuid="123-UUID" src="/sites/default/files/iiif/editor-images/image-20180427145110-1.jpeg" width="2000" height="2000" />',
          '<figcaption>A nice picture of a field. Courtesy of <a href="https://www.pexels.com/photo/biology-blur-close-up-dragonflies-287361/">Pexels</a>. Image attribution.</figcaption>',
          '</figure>' . PHP_EOL,
          '<p>Trailing paragraph</p>',
        ]),
        [
          'fids' => [
            'public://iiif/editor-images/image-20180427145110-1.jpeg' => [
              'fid' => 123,
              'uuid' => '123-UUID',
              'src' => '/sites/default/files/iiif/editor-images/image-20180427145110-1.jpeg',
            ],
          ],
        ],
      ],
      'another image' => [
        [
          'content' => [
            [
              'type' => 'image',
              'image' => [
                'alt' => 'Software Preservation Workshop by eLife',
                'uri' => 'https://iiif.elifesciences.org/journal-cms/content%2F2018-07%2F2018-07-11_ssi-software-preservation-workshop_elife_title.png',
                'source' => [
                  'mediaType' => 'image/jpeg',
                  'uri' => 'https://iiif.elifesciences.org/journal-cms/content%2F2018-07%2F2018-07-11_ssi-software-preservation-workshop_elife_title.png/full/full/0/default.jpg',
                  'filename' => '2018-07-11_ssi-software-preservation-workshop_elife_title.jpg',
                ],
                'size' => [
                  'width' => 960,
                  'height' => 720,
                ],
                'focalPoint' => [
                  'x' => 50,
                  'y' => 50,
                ],
              ],
              'title' => 'The slides are available on <a href="https://docs.google.com/presentation/d/1XC-ATE-HuKL8WQkaAE54vsmA7xNL41WQq92gXT4FPaI/edit?usp=sharing">Google Slides</a> and Figshare with DOI: <a href="https://doi.org/10.6084/m9.figshare.6799097">10.6084/m9.figshare.6799097</a>.',
            ],
          ],
        ],
        $this->lines([
          '<figure class="image align-center"><img alt="Software Preservation Workshop by eLife" data-fid="123" data-uuid="123-UUID" src="/sites/default/files/iiif/content/2018-07/2018-07-11_ssi-software-preservation-workshop_elife_title.png" width="960" height="720" />',
          '<figcaption>The slides are available on <a href="https://docs.google.com/presentation/d/1XC-ATE-HuKL8WQkaAE54vsmA7xNL41WQq92gXT4FPaI/edit?usp=sharing">Google Slides</a> and Figshare with DOI: <a href="https://doi.org/10.6084/m9.figshare.6799097">10.6084/m9.figshare.6799097</a>.</figcaption>',
          '</figure>',
        ]),
        [
          'fids' => [
            'public://iiif/content/2018-07/2018-07-11_ssi-software-preservation-workshop_elife_title.png' => [
              'fid' => 123,
              'uuid' => '123-UUID',
              'src' => '/sites/default/files/iiif/content/2018-07/2018-07-11_ssi-software-preservation-workshop_elife_title.png',
            ],
          ],
        ],
      ],
      'figure' => [
        [
          'content' => [
            [
              'type' => 'figure',
              'assets' => [
                [
                  'id' => 'image-1',
                  'type' => 'image',
                  'image' => [
                    'uri' => 'https://prod--iiif.elifesciences.org/journal-cms/content%2F2017-08%2F2017_09_27_ecr_webinar_panellists_long.png',
                    'source' => [
                      'mediaType' => 'image/jpeg',
                      'uri' => 'https://prod--iiif.elifesciences.org/journal-cms/content%2F2017-08%2F2017_09_27_ecr_webinar_panellists_long.png/full/full/0/default.jpg',
                      'filename' => '2017_09_27_ecr_webinar_panellists_long.jpg',
                    ],
                    'size' => [
                      'width' => 4396,
                      'height' => 1397,
                    ],
                    'focalPoint' => [
                      'x' => 50,
                      'y' => 50,
                    ],
                    'attribution' => [
                      'Image of Kevin Marsh is from https://www.ndm.ox.ac.uk/principal-investigators/researcher/kevin-marsh, image of Simon Kay is from http://www.malariaconsortium.org/board/trustees/47/simon-kay, image of Catherine Kyobutungi is from http://aphrc.org/post/team/catherine-kyobutungi',
                    ],
                  ],
                  'label' => 'Building Connections and Developing Research in Sub-Saharan Africa Panellists',
                  'title' => 'Building Connections and Developing Research in Sub-Saharan Africa chair and panellists',
                ],
              ],
            ],
          ],
        ],
        $this->lines([
          '<figure class="image align-center"><img alt="" data-fid="123" data-uuid="123-UUID" src="/sites/default/files/iiif/content/2017-08/2017_09_27_ecr_webinar_panellists_long.png" width="4396" height="1397" />',
          '<figcaption>Building Connections and Developing Research in Sub-Saharan Africa Panellists. Building Connections and Developing Research in Sub-Saharan Africa chair and panellists. Image of Kevin Marsh is from https://www.ndm.ox.ac.uk/principal-investigators/researcher/kevin-marsh, image of Simon Kay is from http://www.malariaconsortium.org/board/trustees/47/simon-kay, image of Catherine Kyobutungi is from http://aphrc.org/post/team/catherine-kyobutungi.</figcaption>',
          '</figure>',
        ]),
        [
          'fids' => [
            'public://iiif/content/2017-08/2017_09_27_ecr_webinar_panellists_long.png' => [
              'fid' => 123,
              'uuid' => '123-UUID',
              'src' => '/sites/default/files/iiif/content/2017-08/2017_09_27_ecr_webinar_panellists_long.png',
            ],
          ],
        ],
      ],
      'inline image' => [
        [
          'content' => [
            [
              'type' => 'image',
              'image' => [
                'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180427145110-1.jpeg',
                'alt' => '',
                'source' => [
                  'mediaType' => 'image/jpeg',
                  'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-20180427145110-1.jpeg/full/full/0/default.jpg',
                  'filename' => 'image-20180427145110-1.jpeg',
                ],
                'size' => [
                  'width' => 2000,
                  'height' => 2000,
                ],
                'focalPoint' => [
                  'x' => 50,
                  'y' => 50,
                ],
              ],
              'inline' => TRUE,
            ],
          ],
        ],
        $this->lines([
          '<figure class="image align-left"><img alt="" data-fid="123" data-uuid="123-UUID" src="/sites/default/files/iiif/editor-images/image-20180427145110-1.jpeg" width="2000" height="2000" />',
          '</figure>',
        ]),
        [
          'fids' => [
            'public://iiif/editor-images/image-20180427145110-1.jpeg' => [
              'fid' => 123,
              'uuid' => '123-UUID',
              'src' => '/sites/default/files/iiif/editor-images/image-20180427145110-1.jpeg',
            ],
          ],
        ],
      ],
      'multiple tables' => [
        [
          'content' => [
            [
              'type' => 'table',
              'tables' => [
                '<table><tr><td>Cell one</td></tr></table>',
              ],
            ],
            [
              'type' => 'table',
              'tables' => [
                '<table><tr><td>Cell two</td></tr></table>',
              ],
            ],
          ],
        ],
        $this->lines([
          '<table><tr><td>Cell one</td></tr></table>',
          '<table><tr><td>Cell two</td></tr></table>',
        ], 2),
      ],
      'simple list' => [
        [
          'content' => [
            [
              'type' => 'paragraph',
              'text' => 'Nested list:',
            ],
            [
              'type' => 'list',
              'prefix' => 'bullet',
              'items' => [
                'Item 1',
                'Item 2',
                [
                  [
                    'type' => 'list',
                    'prefix' => 'bullet',
                    'items' => [
                      'Item 2.1',
                      [
                        [
                          'type' => 'list',
                          'prefix' => 'number',
                          'items' => [
                            'Item 2.1.1',
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
        $this->lines([
          '<p>Nested list:</p>' . PHP_EOL,
          '<ul>',
          '<li>Item 1</li>',
          '<li>Item 2<ul><li>Item 2.1<ol><li>Item 2.1.1</li></ol></li></ul></li>',
          '</ul>',
        ]),
      ],
      'single blockquote' => [
        [
          'content' => [
            [
              'type' => 'quote',
              'text' => [
                [
                  'type' => 'paragraph',
                  'text' => 'Blockquote line 1',
                ],
              ],
            ],
          ],
        ],
        '<blockquote>Blockquote line 1</blockquote>',
      ],
      'complex content with code' => [
        [
          'content' => [
            [
              'type' => 'paragraph',
              'text' => 'By David Moulton, Senior Front-End Developer',
            ],
            [
              'type' => 'paragraph',
              'text' => 'I recently had the privilege of being involved in <a href="https://elifesciences.org/labs/c8e0dddf/welcome-to-elife-2-0">the ground-up rebuild of eLife</a>. The whole stack was rebuilt from scratch using a microservices approach. The journal is building a reputation for innovation in science publishing, and it was a great opportunity to get involved in a green-field project to build best web practice into this arena. In this post I’ll be focusing on how we built the front end, covering our design strategy (Atomic Design using PatternLab) and principles as well as the nitty gritty of our front-end development process. A companion post is planned about how we integrated the pattern library into the site.',
            ],
            [
              'type' => 'paragraph',
              'text' => 'Note that the code examples throughout have been simplified for clarity.',
            ],
            [
              'type' => 'section',
              'title' => 'Design systems and Atomic Design',
              'content' => [
                [
                  'type' => 'paragraph',
                  'text' => 'During the design phase, I had many constructive conversations with our User Experience Designer, including prototyping some ideas to help decide on an overall approach to various things. <a href="https://elifesciences.org/labs/fa9f0f5e/redesigning-an-online-scientific-journal-from-the-article-up-iii-design-deliverables">He decided we needed a design system</a> in order to retain both flexibility and design coherence not only for the initial build, but for what we might want to create in the future.',
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'Building a design system requires a modular, hierarchical approach, and this approach is well supported by using a pattern library. Brad Frost’s <a href="http://bradfrost.com/blog/post/atomic-web-design/">Atomic Design</a> principles are a natural fit with the designer’s concept for the design system, and so we chose Atomic Design as the mental model for our new site.',
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'Atomic Design considers reusable, composable design patterns in a hierarchy described in terms of ‘atoms’, ‘molecules’ and ‘organisms’. An atom is the smallest unit of the design system, for example a button or a link. A more complex molecule pattern may be composed by assembling a collection of atom-level patterns, for example a teaser within a listing. An organism is more complex again and may comprise a number of included atoms and molecules.',
                ],
                [
                  'type' => 'image',
                  'image' => [
                    'alt' => 'Pictorial diagram of atom (single dot) to molecules (three dots) to organisms (twelve dots)',
                    'uri' => 'https://iiif.elifesciences.org/journal-cms/content%2F2018-01%2Fimage3.png',
                    'size' => [
                      'width' => 1999,
                      'height' => 1385,
                    ],
                    'source' => [
                      'uri' => 'https://iiif.elifesciences.org/journal-cms/content%2F2018-01%2Fimage3.png/full/full/0/default.jpg',
                      'filename' => 'image3.jpg',
                      'mediaTyoe' => 'image/jpeg',
                    ],
                    'focalPoint' => [
                      'x' => 50,
                      'y' => 50,
                    ],
                    'attribution' => [
                      'Modified from Brad Frost’s <a href="http://bradfrost.com/blog/post/atomic-web-design/">blog</a> (<a href="https://creativecommons.org/licenses/by/4.0/">CC-BY 4.0</a>). This version of the image removes the mention of templates and pages.',
                    ],
                  ],
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'We discovered that it’s not really worth trying to impose a strict hierarchy to distinguish molecules from organisms. Whilst trying generally to maintain the distinction that organisms are higher-order patterns than molecules, it’s okay for molecules to contain other molecules as well as atoms, and for organisms to contain organisms as well as lower-order patterns. With only three hierarchy levels to play with, we found we got most benefit from a pragmatic interpretation of the Atomic Design hierarchy.',
                ],
              ],
            ],
            [
              'type' => 'section',
              'title' => 'PatternLab',
              'content' => [
                [
                  'type' => 'paragraph',
                  'text' => 'Having decided on Atomic Design, we chose Brad Frost’s <a href="http://patternlab.io/">PatternLab</a> as the natural tool to deliver it. PatternLab uses <a href="http://mustache.github.io/">mustache templating</a> and provides a web interface to display the patterns next to the markup that defines them, along with any optional annotations you may wish to supply.',
                ],
                [
                  'type' => 'image',
                  'image' => [
                    'alt' => 'Epidemiology and Global Health section header shown above Mustache template code',
                    'uri' => 'https://iiif.elifesciences.org/journal-cms/content%2F2018-01%2Fimage4.png',
                    'size' => [
                      'width' => 1999,
                      'height' => 998,
                    ],
                    'source' => [
                      'uri' => 'https://iiif.elifesciences.org/journal-cms/content%2F2018-01%2Fimage4.png/full/full/0/default.jpg',
                      'filename' => 'image4.jpg',
                      'mediaTyoe' => 'image/jpeg',
                    ],
                    'focalPoint' => [
                      'x' => 50,
                      'y' => 50,
                    ],
                  ],
                  'title' => 'A variant of the content header pattern within PatternLab, showing the mustache code that generates it',
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'Since we started the project, other pattern library candidates have appeared that may have served just as well, for example <a href="https://fractal.build/">Fractal</a>, but they weren’t available then; PatternLab was the the best available tool at the time, and it has served us well.',
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'Useful abilities of PatternLab include:',
                ],
                [
                  'type' => 'list',
                  'prefix' => 'bullet',
                  'items' => [
                    'You can view the template code and the compiled HTML next to its pattern.',
                    'You can annotate a pattern with style-guide level information.',
                    'You can search for patterns.',
                    'It includes the option to view any PatternLab page without the PatternLab user interface in case it’s getting in the way.',
                    'As well as dragging the viewport, you can set the effective viewport width in px or ems to see how patterns behave in that situation.',
                    'It includes more general one-click buttons to set a general viewport width.',
                    'DISCO MODE! This provides constant automatic changing of the viewport to random widths to see what breaks (music not included).',
                  ],
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'You can also break down Atomic Design levels to group patterns of a particular type within a level. This can make it easier to track down a pattern on the file system; for example, the molecule patterns might be grouped like this:',
                ],
                [
                  'type' => 'code',
                  'code' => $this->lines([
                    'molecules',
                    '├── general',
                    '│   ├── ...',
                    '│   └── ... ',
                    '├── navigation',
                    '│   ├── ...',
                    '│   └── ... ',
                    '└── teasers',
                    '    ├── ...',
                    '    └── ...  ',
                  ]),
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'PatternLab also has template and page-level composition. This enables you to build up sample pages, illustrating how the patterns might work together. From a technical perspective this is very useful for checking things, such as a baseline grid, that can’t be properly set on a pattern in isolation without observing it in a higher-level context. It’s also great for stakeholders’ engagement too! It can sometimes be difficult to communicate the value of a modular build approach to people who are used to thinking of the web in terms of pages, not patterns. Being able to mock up a page displaying real patterns can help communicate this.',
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'Before starting work, we agreed a set of principles that would guide our approach to decision making along the way.',
                ],
              ],
            ],
            [
              'type' => 'section',
              'title' => 'Principle: don’t lock out the readers',
              'content' => [
                [
                  'type' => 'list',
                  'prefix' => 'number',
                  'items' => [
                    '<b>Progressively enhance:</b> a reader could be on any platform anywhere in the world. It’s vital that the content (mainly results of scientific research) and core functionality be available to everyone, regardless of platform, so we couldn’t mandate a high technological baseline in order to read the journal. For this reason, and to be a good web citizen generally, we would use a <a href="https://alistapart.com/article/understandingprogressiveenhancement">progressive enhancement</a> approach to ensure that JavaScript is not required to use the site: you get an enhanced experience if it’s available, but content and core functionality does not require it.',
                    '<b>Make it responsive:</b> it should be a given these days, but it’s worth mentioning anyway that the website should be <a href="https://www.smashingmagazine.com/2011/01/guidelines-for-responsive-web-design/">responsive</a>, so it will display appropriately, whatever the size of the users’ screens.',
                    '<b>Make it performant:</b> no one likes waiting for a web page to load and, if it takes too long, users will bail. If a user is on a narrow bandwidth or a high-latency connection, then any performance problems are exacerbated. Data costs vary across the world, and we don’t want it to cost more in data charges than necessary to read our content. Performance was considered throughout the build, using ideas of a performance budget, techniques such as responsive images, allowing for the HTTP/2 serving of smaller resources, and not using a library unless we needed it.',
                    '<b>Make it accessible:</b> it’s vital that our site content is accessible to all to read and use.',
                  ],
                ],
              ],
            ],
            [
              'type' => 'section',
              'title' => 'Principle: maintain the value of the pattern library',
              'content' => [
                [
                  'type' => 'paragraph',
                  'text' => 'One of the aims of a pattern library is to be the canonical source of truth for the design and front-end implementation of the design patterns. This is the case at launch, but it’s common for the value of a pattern library to drop dramatically over time if it’s not easy to update, or it’s difficult to do so in a way that maintains the value of the underlying design system.',
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'After launch, pattern libraries are often susceptible to ‘pattern rot’ when, for whatever reason, the patterns used on the live website are updated but the pattern library is not. This usually occurs when there is some kind of copy/paste step necessary in order to apply an update from a pattern library pattern to its version on the live site. This step needs to be short-circuited only once with an update being applied only to the live site, then the pattern library and the live site would diverge. Once this happens, the pattern library is no longer the canonical source of truth, so you can no longer have complete confidence that what you see in the pattern library is what you get on the site. Much of the work that went into building the pattern library becomes lost.',
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'In summary, for a pattern library to retain its value, it must:',
                ],
                [
                  'type' => 'list',
                  'prefix' => 'number',
                  'items' => [
                    'Be built in such a way as to reflect the intent of the design system.',
                    'Be easy to maintain.',
                    'Be as easy as possible to integrate into the website it’s defining the patterns for (avoid ‘pattern rot’).',
                  ],
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'Integration is the hardest problem to solve and has been the holy grail for pattern libraries for years. We managed to crack this, but you’ll have to wait for the companion post to find out how!',
                ],
              ],
            ],
            [
              'type' => 'section',
              'title' => 'Our aims for the front-end build',
              'content' => [
                [
                  'type' => 'paragraph',
                  'text' => 'Before we started writing code, we documented our lower-level aims, based on the principles we’d already decided upon. Our priority of concerns, in decreasing order, were:',
                ],
                [
                  'type' => 'list',
                  'prefix' => 'number',
                  'items' => [
                    'Access.',
                    'Maintainability.',
                    'Performance (with the assumption that we would not let maintainability negatively affect it).',
                    'Taking advantage of browser capabilities.',
                    'Visual appeal.',
                  ],
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'We also documented some of the techniques we’d use:',
                ],
                [
                  'type' => 'list',
                  'prefix' => 'bullet',
                  'items' => [
                    'Progressive enhancement.',
                    'min-width media queries.',
                    'Small-screen-first responsive images.',
                    'Only add libraries as needed.',
                  ],
                ],
              ],
            ],
            [
              'type' => 'section',
              'title' => 'Identifying and naming the patterns',
              'content' => [
                [
                  'type' => 'paragraph',
                  'text' => 'Before we could build any patterns, we needed to identify what things we were building and decide how to talk about them: without a common vocabulary, things could fall apart very quickly. So, embarking on building a brand new look for an online-only journal, we took a large slice of irony pie and started by printing off wireframes of all the patterns.',
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'Having cut out each pattern, we took up most of the floor of the room we were in, laying them out to take stock of what we had. We grouped similar patterns together, enabling us to distinguish those that were essentially duplicates from those we could treat as variants of the same underlying pattern, and to confirm which were actually distinct patterns.',
                ],
                [
                  'type' => 'image',
                  'image' => [
                    'alt' => 'Paper cutouts of various size content headers laid on the floor',
                    'uri' => 'https://iiif.elifesciences.org/journal-cms/content%2F2018-01%2Fimage1.jpg',
                    'size' => [
                      'width' => 1999,
                      'height' => 1500,
                    ],
                    'source' => [
                      'uri' => 'https://iiif.elifesciences.org/journal-cms/content%2F2018-01%2Fimage1.jpg/full/full/0/default.jpg',
                      'filename' => 'image1.jpg',
                      'mediaTyoe' => 'image/jpeg',
                    ],
                    'focalPoint' => [
                      'x' => 50,
                      'y' => 50,
                    ],
                  ],
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'Once distinct patterns were identified, we opened up the room to anyone who wanted to help us agree names for each pattern. Fresh minds at this point helped us get better names.',
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'We thought the whole process would take a couple of hours, but it took most of a day to complete. The benefits were well worth the time: it was a great way to expose many hidden assumptions, identify gaps in thinking and discover inconsistencies that had crept in during the design process. If we hadn’t done the exercise, all those problems would still exist, but they’d have only manifested later when they’d be more expensive in time and effort to fix.',
                ],
              ],
            ],
            [
              'type' => 'section',
              'title' => 'Process',
              'content' => [
                [
                  'text' => 'With two front-end developers liaising closely with the Product team (the designer and the product owner), and facilitated by our tireless scrum master, we decided on two-week scrum sprints, managing the scrum board in Trello.',
                  'type' => 'paragraph',
                ],
                [
                  'text' => 'The fact that we were using progressive enhancement affected the tickets we created: each pattern had a <b>first pass</b> ticket for building its markup and CSS. We used a checklist to manage the work for each ticket:',
                  'type' => 'paragraph',
                ],
                [
                  'type' => 'list',
                  'items' => [
                    'Semantic HTML is built.',
                    'CSS is applied correctly.',
                    'Core content and functionality is available without JavaScript or CSS.',
                    'Accessibility testing performed.',
                    'Browser testing performed.',
                  ],
                  'prefix' => 'bullet',
                ],
                [
                  'text' => 'If a pattern had some behaviour, then a <b>second pass</b> ticket was created for building its JavaScript. Each second pass ticket had the checklist:',
                  'type' => 'paragraph',
                ],
                [
                  'type' => 'list',
                  'items' => [
                    'JavaScript tests written.',
                    'JavaScript behaviour written.',
                    'JavaScript tests pass.',
                  ],
                  'prefix' => 'bullet',
                ],
                [
                  'text' => 'Some patterns have variants (a variant is a different way the pattern could be displayed, depending on the context). For these, the one ticket described building all the variants. They would all have to be considered at the same time in order to build them in a way that works for all of the variants anyway, so it didn’t make sense to split them up.',
                  'type' => 'paragraph',
                ],
                [
                  'text' => 'Individual tickets had additional, pattern-specific checklist items as necessary.',
                  'type' => 'paragraph',
                ],
                [
                  'text' => 'The <a href="https://trello.com/">Trello</a> board was initially created with the columns:',
                  'type' => 'paragraph',
                ],
                [
                  'type' => 'list',
                  'items' => [
                    'Backlog (all tickets start here).',
                    'Sprint items (committed to in the current sprint).',
                    'In progress (in active development).',
                    'In testing (in browser/accessibility testing).',
                    'In review (being reviewed).',
                    'Done (finished).',
                  ],
                  'prefix' => 'bullet',
                ],
                [
                  'text' => 'We created a feature branch in the Git repository for each ticket as we started work on it. Once a ticket’s code was approved, it was merged into the GitHub master branch from the feature branch via pull request.',
                  'type' => 'paragraph',
                ],
                [
                  'text' => 'We discovered in the first retrospective that the Product team was not feeling sufficiently involved in the review process for a pattern, resulting in patterns sometimes being classed as done when they weren’t. This was quickly addressed in two ways:',
                  'type' => 'paragraph',
                ],
                [
                  'text' => '1. The addition of two more columns to separate out the product review from the technical review. The board columns were then:',
                  'type' => 'paragraph',
                ],
                [
                  'type' => 'list',
                  'items' => [
                    'Backlog (to be done).',
                    'Sprint items (committed to in the current sprint).',
                    'In progress (in active development).',
                    '<b>For feedback (ready for product review).</b>',
                    '<b>Feedback provided (reviewed by Product).</b>',
                    'In testing (in browser/accessibility testing).',
                    'In review (in <b>technical</b> review).',
                    'Done (finished).',
                  ],
                  'prefix' => 'bullet',
                ],
                [
                  'text' => '2. The addition of an approval checklist, with each Product team member having a dedicated box to check to indicate sign off.',
                  'type' => 'paragraph',
                ],
                [
                  'type' => 'image',
                  'image' => [
                    'alt' => 'Screenshot of eLife2 patterns trello board with columns as listed above',
                    'uri' => 'https://iiif.elifesciences.org/journal-cms/content%2F2018-01%2Ftrello-grouped.png',
                    'size' => [
                      'width' => 1999,
                      'height' => 974,
                    ],
                    'source' => [
                      'uri' => 'https://iiif.elifesciences.org/journal-cms/content%2F2018-01%2Ftrello-grouped.png/full/full/0/default.jpg',
                      'filename' => 'trello-grouped.jpg',
                      'mediaType' => 'image/jpeg',
                    ],
                    'focalPoint' => [
                      'x' => 50,
                      'y' => 50,
                    ],
                  ],
                ],
                [
                  'text' => 'With the revised board, once the pattern was built and ready for product approval it was moved from ‘in progress’ to ‘for feedback’. The Product team members then specified any changes to be made or checked their name off the checklist if they were happy with the pattern as it was. They then moved the cards to ‘feedback provided’. Tickets were able to go around the ‘for feedback’ -&gt; ‘feedback provided’ -&gt; ‘for feedback’ loop a number of times until the pattern was right. Once a ticket arrived in ‘feedback provided’ with all Product team members’ approval boxes checked, the pattern was ready for testing.',
                  'type' => 'paragraph',
                ],
              ],
            ],
            [
              'type' => 'section',
              'title' => 'Build scope',
              'content' => [
                [
                  'text' => 'Currently, the pattern library defines just under 100 patterns. Each pattern comprises:',
                  'type' => 'paragraph',
                ],
                [
                  'type' => 'list',
                  'items' => [
                    'Exactly one .scss file.',
                    'Exactly one .mustache file.',
                    'One or more .json files, one per pattern variant.',
                    'Zero or one .js files, built in the second pass.',
                    'Exactly one .yaml file to define the data structure. This is helpful when integrating the patterns into the site and will be expanded on in the forthcoming companion post.',
                  ],
                  'prefix' => 'bullet',
                ],
                [
                  'text' => 'Each pattern’s accessibility was tested using Khan Academy’s <a href="https://khan.github.io/tota11y/">tota11y</a> for accessibility testing in the browser. We used <a href="https://www.browserstack.com/">Browserstack</a> for browser/device testing.',
                  'type' => 'paragraph',
                ],
                [
                  'text' => 'We started off with a minimal build pipeline, originally in <a href="https://gruntjs.com/">Grunt</a> (we switched to <a href="https://gulpjs.com/">Gulp</a> when we introduced JavaScript, see below), knowing that we could add to it when we needed to.',
                  'type' => 'paragraph',
                ],
              ],
            ],
            [
              'type' => 'section',
              'title' => 'SCSS',
              'content' => [
                [
                  'text' => 'Although PatternLab can compile SCSS, we elected not to do this as we wanted to control the SCSS compilation with the build pipeline. The first iteration was only for linting and compiling SCSS and moving asset files where they needed to be for the PatternLab server to display them correctly.',
                  'type' => 'paragraph',
                ],
                [
                  'text' => 'We agreed a few simple style rules with the aim of keeping the SCSS maintainable in the longer term:',
                  'type' => 'paragraph',
                ],
                [
                  'type' => 'list',
                  'items' => [
                    'One SCSS file per pattern.',
                    'Use <a href="https://csswizardry.com/2013/01/mindbemding-getting-your-head-round-bem-syntax/">Harry Robert’s flavour of BEM</a> (Block, Element, Modifier) for CSS class naming. This works well with a pattern-based approach, as the root of each pattern can define a BEM block. Coupled with the decision to have one SCSS file per pattern, this namespacing kept the separation of the styles for individual patterns nice and clean.',
                    'Keep selector specificity as low as possible, and with a maximum selector nesting of three (not including pseudo elements). As selectors need only to start from the root of a pattern (or deeper), this seemed a pragmatic maximum. We agreed that we’d increase it if we really, really needed to, but up to now we haven’t had to.',
                    'Don’t use ‘&amp;’ as partial completion for a class name, as it makes searching/refactoring across a code base more error-prone.',
                    '<a href="https://www.sitepoint.com/avoid-sass-extend/">Avoid @extends</a>; use mixins instead for greater flexibility.',
                    'Only mixins that are completely pattern-specific may live in a pattern’s SCSS file; other mixins must live in higher-level files (see Architecture).',
                    'List property names alphabetically.',
                  ],
                  'prefix' => 'bullet',
                ],
                [
                  'text' => 'We implemented style linting with <a href="https://stylelint.io/">stylelint</a>.',
                  'type' => 'paragraph',
                ],
                [
                  'text' => 'We settled on the following architectural approach to try to keep the design system reasonably easily maintainable:',
                  'type' => 'paragraph',
                ],
                [
                  'type' => 'list',
                  'items' => [
                    'Meaningful values for the design system: colours, font sizes, quantities for spacing and element sizing, media query breakpoints, transition parameters and so on are defined in the variables partial.',
                    'For a sensible reset starting point we included <a href="http://nicolasgallagher.com/about-normalize-css/">Nicholas Gallagher’s normalise CSS stylesheet</a> as an SCSS partial.',
                    'Our own base styles and any necessary overrides to normalise are defined in the normalise-overrides partial.',
                    'The typography component of the design system is defined in the typographic-hierarchy partial: this contains numerous mixins responsible for enforcing a consistent typographic style across the site.',
                    'The grid partial contains all SCSS and mixins required for both the horizontal and baseline grid systems.',
                    'The mixins partial contains all other mixins.',
                  ],
                  'prefix' => 'bullet',
                ],
                [
                  'text' => 'These are imported along with the pattern-specific SCSS files to create the main build CSS file like this:',
                  'type' => 'paragraph',
                ],
                [
                  'code' => $this->lines([
                    'build.scss',
                    '├── base.scss',
                    '│   ├── _definitions',
                    '│   │   ├── _variables',
                    '│   │   ├── _mixins',
                    '│   │   └── _typographic-hierarchy',
                    '│   ├── reset',
                    '│   │   ├── _normalize',
                    '│   │   ├── _normalize-overrides',
                    '│   └── _grid',
                    '├── pattern-1.scss',
                    '├── pattern-2.scss',
                    '├── pattern-3.scss',
                    '├── ...',
                    '└── pattern-n.scss',
                  ]),
                  'type' => 'code',
                ],
                [
                  'text' => 'Each individual [pattern].scss file also imports the definitions partial so it has access to all the necessary shared knowledge of the design system as distilled into the variables and various mixins.',
                  'type' => 'paragraph',
                ],
                [
                  'text' => 'With an eye on delivery over HTTP/2, we ensured that we could produce individual pattern-level CSS files as well as one main stylesheet for traditional HTTP/1.1 delivery.',
                  'type' => 'paragraph',
                ],
              ],
            ],
            [
              'type' => 'section',
              'title' => 'Organising the typography',
              'content' => [
                [
                  'text' => 'The designer documented the typographical part of the design system as a hierarchy of definitions. The lowest level is used to set relevant variables in the variables partial, and set the base styles in normalise-overrides partial.',
                  'type' => 'paragraph',
                ],
                [
                  'type' => 'table',
                  'tables' => [
                    '<table><thead><tr><th>Style name</th><th>Default narrow screen</th><th>Media query</th></tr></thead><tbody><tr><td>Body</td><td>PT Serif 16px/1.5 space after 24px</td><td>None</td></tr><tr><td>H1</td><td>Avenir Next Demi Bold 24px/1.3</td><td>Font-size 36px</td></tr><tr><td>H2</td><td>Avenir Next Demi Bold 21px/1.3 space after 5px</td><td>None</td></tr><tr><td>...</td><td>...</td><td>...</td></tr></tbody></table>',
                  ],
                ],
                [
                  'text' => 'The media query column specifies what (if any) change occurs when the viewport is wider than the relevant site breakpoint. Note that the breakpoints themselves are not specified here, to keep things loosely coupled.',
                  'type' => 'paragraph',
                ],
                [
                  'text' => 'These base styles are then used as part of the specification for the next level up:',
                  'type' => 'paragraph',
                ],
                [
                  'type' => 'table',
                  'tables' => [
                    '<table><thead><tr><th>Style name</th><th>Default narrow screen</th><th>Media query</th></tr></thead><tbody><tr><td>Title-lg</td><td>PT Serif 16px/1.5 space after 24px</td><td>None</td></tr><tr><td>Title-md</td><td>h1 font-size 42px</td><td>Font-size 54px</td></tr><tr><td>Title-sm</td><td>h1 font-size 30px</td><td>Font-size 44px</td></tr><tr><td>Title-xs</td><td>h1</td><td>Font-size 36px</td></tr><tr><td>...</td><td>...</td><td>...</td></tr></tbody></table>',
                  ],
                ],
                [
                  'text' => 'These all specify different title sizes (which one gets applied depends on how long the title is).',
                  'type' => 'paragraph',
                ],
                [
                  'text' => 'Typographical styles are defined for all aspects of the design, like this tiny fraction of the set:',
                  'type' => 'paragraph',
                ],
                [
                  'type' => 'table',
                  'tables' => [
                    '<table><thead><tr><th>Style name</th><th>Default narrow screen</th><th>Media query</th></tr></thead><tbody><tr><td>CONTENT LABEL [grey]</td><td>Avenir Next Demi Bold, 11px/1</td><td>None</td></tr><tr><td>SUBJECT LABEL [blue]</td><td>Avenir Next Demi Bold, 11px/1</td><td>None</td></tr><tr><td>Article title (main list)</td><td>h2</td><td>None</td></tr><tr><td>Article title (side list)</td><td>PT Serif Bold 16px</td><td>None</td></tr><tr><td>...</td><td>...</td><td>...</td></tr></tbody></table>',
                  ],
                ],
                [
                  'text' => 'All these definitions are captured in mixins within typographical-hierarchy. For example, for the two labels, we abstract out the common aspects:',
                  'type' => 'paragraph',
                ],
                [
                  'code' => $this->lines([
                    ' @mixin _label-typeg() {',
                    '   font-family: $font-secondary;',
                    '   font-weight: normal;',
                    '   @include font-size-and-vertical-height(11);',
                    '   letter-spacing: 0.5px;',
                    '   text-transform: uppercase;',
                    ' }',
                  ]),
                  'type' => 'code',
                ],
                [
                  'text' => 'and then create a mixin for each label type, based on it:',
                  'type' => 'paragraph',
                ],
                [
                  'code' => $this->lines([
                    '@mixin label-content-typeg() {',
                    '   @include _label-typeg();',
                    '   color: $color-text-secondary; // grey',
                    ' }' . PHP_EOL,
                    ' @mixin label-subject-typeg() {',
                    '   @include _label-typeg();',
                    '   color: $color-primary; // blue',
                    ' }',
                  ]),
                  'type' => 'code',
                ],
                [
                  'text' => 'Note that all typographical style mixin names include \'typeg\' for clarity when viewed out of context.',
                  'type' => 'paragraph',
                ],
                [
                  'text' => 'In the design system specification, all the patterns have their typography defined in terms of these typographical style names. Often a particular style name is used for more than one pattern. With this approach, we can easily apply the correct typographical style to the pattern’s CSS via the mixins and, at the same time, keep the design system highly maintainable. If a particular named style is updated, then a simple update to one ‘typeg’ mixin will permeate to all parts of the system that use it.',
                  'type' => 'paragraph',
                ],
                [
                  'text' => '<i>Note: these examples are taken from an early draft of the spec and the final values used on the site may have changed – although the system defining them hasn’t, indicating that it’s working well!</i>',
                  'type' => 'paragraph',
                ],
              ],
            ],
            [
              'type' => 'section',
              'title' => 'Markup',
              'content' => [
                [
                  'text' => 'Compilation of the mustache templates with their JSON data files is handled by PatternLab, producing a user-friendly and, to some extent, configurable <a href="https://ui-patterns.elifesciences.org/">web view of the patterns</a>.',
                  'type' => 'paragraph',
                ],
                [
                  'text' => 'A basic pattern with no variants has exactly one mustache template and one corresponding JSON data file.',
                  'type' => 'paragraph',
                ],
                [
                  'text' => 'For a more complex pattern that has variants, an example of each variant can be produced by supplying a separate JSON file for each. For example, the teaser pattern has 13 variants (an extreme case; most variant patterns have fewer than a handful). It has only one mustache file, but 13 associated JSON files:',
                  'type' => 'paragraph',
                ],
                [
                  'code' => $this->lines([
                    '.',
                    '├── teaser.mustache',
                    '├── teaser~05-main.json',
                    '├── teaser~10-main-small-image.json',
                    '├── teaser~15-main-big-image.json',
                    '├── teaser~20-secondary.json',
                    '├── teaser~25-secondary-small-image.json',
                    '├── teaser~30-secondary-big-image.json',
                    '├── teaser~35-related-item.json',
                    '├── teaser~40-basic.json',
                    '├── teaser~45-main-event.json',
                    '├── teaser~50-secondary-event.json',
                    '├── teaser~55-grid-style--labs.json',
                    '├── teaser~60-grid-style--podcast.json',
                    '└── teaser~65-main--with-list.json',
                  ]),
                  'type' => 'code',
                ],
                [
                  'text' => 'PatternLab uses the ~ in a filename to identify a variant. The numerals in the filenames control the ordering of the <a href="https://ui-patterns.elifesciences.org/?p=viewall-molecules-teasers">presentation of the variants</a>.',
                  'type' => 'paragraph',
                ],
                [
                  'text' => 'Sometimes, when coming to build a pattern with variants, we discovered that one or more variants required a significant change to the markup of the main pattern, which suggested a new pattern rather than just a variant. In these cases, we created the ticket(s) for it and put them into the backlog, moving the appropriate spec from the old to the new ticket.',
                  'type' => 'paragraph',
                ],
              ],
            ],
            [
              'type' => 'section',
              'title' => 'Images',
              'content' => [
                [
                  'text' => 'Scholarly content contains a lot of figures, mainly in the form of images. We use <a href="https://responsiveimages.org/">responsive images techniques</a> (&lt;picture&gt;, srcset and sometimes sizes) to stop the browser downloading more image data than it needs. For example, the compiled HTML from the captioned-asset pattern’s mustache template looks like this:',
                  'type' => 'paragraph',
                ],
                [
                  'code' => $this->lines([
                    '&lt;figure class="captioned-asset"&gt;' . PHP_EOL,
                    '   &lt;picture class="captioned-asset__picture"&gt;',
                    '     &lt;source ',
                    '       srcset="',
                    '         /path-to-1076-px-wide-image 1076w, ',
                    '         /path-to-538-px-wide-image 538w" ',
                    '       type="image/jpeg" /&gt;',
                    '     &lt;img ',
                    '       src="/path-to-538-px-wide-image" ',
                    '       alt=""',
                    '       class="captioned-asset__image" /&gt;',
                    '   &lt;/picture&gt;         ' . PHP_EOL,
                    '   &lt;figcaption class="captioned-asset__caption"&gt;          ',
                    '     &lt;h6 class="caption-text__heading"&gt;Title of the figure caption&lt;/h6&gt;',
                    '     &lt;div class="caption-text__body"&gt;The figure caption&lt;/div&gt;',
                    '     &lt;span class="doi doi--asset"&gt;The DOI link&lt;/span&gt;',
                    '   &lt;/figcaption&gt;' . PHP_EOL,
                    ' &lt;/figure&gt;',
                  ]),
                  'type' => 'code',
                ],
                [
                  'text' => 'Note the empty alt attribute: as the image is within a &lt;figure&gt;, the &lt;figcaption&gt; provides the description.',
                  'type' => 'paragraph',
                ],
                [
                  'text' => 'To handle the large amount of image variants that can be required when implementing responsive images, we used the <a href="http://iiif.io/">International Image Interoperability Framework</a> (IIIF) <a href="https://elifesciences.org/labs/d6044799/dynamically-serving-scientific-images-using-iiif">to serve most of our images</a>.',
                  'type' => 'paragraph',
                ],
              ],
            ],
            [
              'type' => 'section',
              'title' => 'Extended build pipeline',
              'content' => [
                [
                  'text' => 'When we came to build the patterns’ behaviours, we needed to add JavaScript linting, transpiling and test running to the build pipeline. It quickly became apparent that Gulp was much more flexible than Grunt for this, so we switched from a wild boar to a huge caffeinated beverage (and the caffeine would come in handy).',
                  'type' => 'paragraph',
                ],
                [
                  'text' => 'We author in ES6, and transpire using Babel. Linting is performed with a mixture of <a href="http://jshint.com/">jshint</a> and <a href="http://jscs.info/">jscs</a>, although there is a pending upgrade to <a href="https://eslint.org/">eslint</a>.',
                  'type' => 'paragraph',
                ],
              ],
            ],
            [
              'type' => 'section',
              'title' => 'Pattern behaviour with JavaScript',
              'content' => [
                [
                  'text' => 'A pattern’s JavaScript behaviour is defined in a discrete component with the same name as the pattern. This JavaScript component is referenced from the root element of the pattern’s mustache template by the attribute:',
                  'type' => 'paragraph',
                ],
                [
                  'code' => $this->lines([
                    'data-behaviour="ComponentName".',
                  ]),
                  'type' => 'code',
                ],
                [
                  'text' => 'For example, the content-header pattern has its associated behaviour defined in the ContentHeader class, which is found in the ContentHeader.js file. The content-header.mustache template starts with:',
                  'type' => 'paragraph',
                ],
                [
                  'code' => $this->lines([
                    '<div... data-behaviour="ContentHeader">...',
                  ]),
                  'type' => 'code',
                ],
                [
                  'text' => 'which causes this HTML element to be passed as an argument to the class constructor in ContentHeader.js when the page’s JavaScript loads and runs:',
                  'type' => 'paragraph',
                ],
                [
                  'code' => $this->lines([
                    '// 1. Load the components',
                    ' const Components = {};',
                    ' Components.ContentHeader = require(\'./components/ContentHeader\');',
                    ' // ... load more components ...' . PHP_EOL,
                    ' // 4. This bit does the actual initialising',
                    ' function initialiseComponent($component) {',
                    '   const handler = $component.getAttribute(\'data-behaviour\');',
                    '   if (!!Components[handler] && typeof Components[handler] === \'function\') {',
                    '     new Components[handler]($component, window, window.document);',
                    '   }',
                    '      ',
                    '   $component.dataset.behaviourInitialised = true;',
                    ' }',
                    '    ',
                    ' // 2. Find all patterns in the document that have declared  a js component',
                    ' const components = document.querySelectorAll(\'[data-behaviour]\');',
                    '    ',
                    ' // 3. Initialise each component with the HTMLElement that declared it',
                    ' if (components) {',
                    '   [].forEach.call(components, (el) => initialiseComponent(el));  ',
                    ' }',
                  ]),
                  'type' => 'code',
                ],
                [
                  'text' => 'This applies the pattern’s JavaScript behaviour to each instance of the pattern on the page.',
                  'type' => 'paragraph',
                ],
                [
                  'text' => 'Notice we’re additionally passing in the window and document objects to the component’s constructor. This dependency injection enables us to mock these objects and subsequently write better tests.',
                  'type' => 'paragraph',
                ],
              ],
            ],
            [
              'type' => 'section',
              'title' => 'JavaScript testing',
              'content' => [
                [
                  'text' => 'We test in the browser using the tripod of the <a href="https://mochajs.org/">mocha</a> test framework, the <a href="http://chaijs.com/">chai</a> assertion library, and with <a href="http://sinonjs.org/">sinon</a> for providing spies, mocks and stubs. At the moment we’re using phantomjs as the test environment but, now this is not currently under active maintenance, we’re looking to switch to using puppeteer with headless Chrome.',
                  'type' => 'paragraph',
                ],
                [
                  'text' => 'For each pattern under test there are two files: the spec file and the fixture file. The spec file contains the tests. The fixture file contains the HTML of the pattern whose component is under test. This is obtained by finding the pattern’s compiled HTML generated by PatternLab and manually copying across the relevant code.',
                  'type' => 'paragraph',
                ],
                [
                  'text' => 'By way of example, these are the guts of the fixture file for the ToggleableCaption component:',
                  'type' => 'paragraph',
                ],
                [
                  'code' => $this->lines([
                    '&lt;!-- Results of test run end up here --&gt;',
                    ' &lt;div id="mocha"&gt;&lt;/div&gt;' . PHP_EOL,
                    ' &lt;!-- This is the test fixture --&gt; ',
                    ' &lt;div data-behaviour="ToggleableCaption" data-selector=".caption-text__body"&gt;' . PHP_EOL,
                    '   &lt;div class="caption-text__body"&gt;' . PHP_EOL,
                    '     &lt;p&gt;Lorem ipsum dolor sit amet, consectetur adipiscing elit. In interdum, metus quis sodales pharetra, odio justo',
                    '       blandit mi, at porta augue felis sit amet metus. Aliquam porta, justo dapibus vulputate aliquet, arcu quam tempor',
                    '       metus, et aliquam nisi nunc in est. Cras vitae leo pretium, tincidunt nisi ac, varius neque. Donec nec posuere',
                    '       sem. Integer felis risus, sagittis et pulvinar pretium, rutrum sit amet odio. Proin erat purus, sodales a gravida',
                    '       vitae, malesuada non sem. Integer semper enim ante. Donec odio ipsum, ultrices vel quam at, condimentum eleifend',
                    '       augue. Pellentesque id ipsum nec dui dictum commodo. Donec quis sagittis ex, sit amet tempus nisi. Nulla eu tortor',
                    '       vitae felis porta faucibus in eu leo. Donec ultrices vehicula enim, quis maximus nulla rhoncus sed. Vestibulum',
                    '       elementum ligula quis mi aliquet, tincidunt finibus mi hendrerit.&lt;/p&gt;' . PHP_EOL,
                    '   &lt;/div&gt;' . PHP_EOL,
                    ' &lt;/div&gt;' . PHP_EOL,
                    ' &lt;!-- Load the test frameworks -->',
                    ' &lt;script src="../node_modules/mocha/mocha.js"&gt;&lt;/script&gt;',
                    ' &lt;script src="../node_modules/chai/chai.js"&gt;&lt;/script&gt;',
                    ' &lt;script src="../node_modules/sinon/pkg/sinon.js"&gt;&lt;/script&gt;' . PHP_EOL,
                    ' &lt;!-- Initialise the test suite -->',
                    ' &lt;script&gt;mocha.setup(\'bdd\')&lt;/script&gt;' . PHP_EOL,
                    ' &lt;!-- Load the spec file --&gt;',
                    ' &lt;script src="build/toggleablecaption.spec.js"&gt;&lt;/script&gt;' . PHP_EOL,
                    ' &lt;!-- Run the tests --&gt;',
                    ' &lt;script&gt;',
                    '   mocha.run();',
                    ' &lt;/script&gt;',
                  ]),
                  'type' => 'code',
                ],
                [
                  'text' => 'We’ve found that keeping the test fixtures up-to-date can be difficult, because it takes a developer to remember to re-copy and paste the HTML from the compiled mustache pattern template every time a pattern’s source or source-generating JavaScript is updated.',
                  'type' => 'paragraph',
                ],
                [
                  'text' => 'The tests are run under Gulp using gulp-mocha-phantomjs.',
                  'type' => 'paragraph',
                ],
              ],
            ],
            [
              'type' => 'section',
              'title' => 'Where is it, tho’?',
              'content' => [
                [
                  'text' => 'The website using the patterns is <a href="https://elifesciences.org/">https://elifesciences.org/</a>.',
                  'type' => 'paragraph',
                ],
                [
                  'text' => 'All the code for the pattern library is available on GitHub under the MIT license at <a href="https://github.com/elifesciences/pattern-library">https://github.com/elifesciences/pattern-library</a>.',
                  'type' => 'paragraph',
                ],
                [
                  'text' => 'The PatternLab pattern library generated by this code is at <a href="https://ui-patterns.elifesciences.org/">https://ui-patterns.elifesciences.org/</a>.',
                  'type' => 'paragraph',
                ],
              ],
            ],
            [
              'type' => 'section',
              'title' => 'Lessons learned',
              'content' => [
                [
                  'type' => 'list',
                  'items' => [
                    'Take the time to agree your principles and build aims up front. Set the expectations that derive from these in the wider team, so no one’s surprised when you start challenging demands for moarLibrareez, etc.',
                    'When following Atomic Design principles, don’t worry too much about strict molecule/organism hierarchy: get it as good as you can, but a slightly fuzzy but usable system is better than a system that’s strictly correct but horrible to use.',
                    'Implementing a design system requires excellent communication between designers and front-end developers. A designer who understands front-end code, and developers with a design eye, both really help with this: the more shared context, the better the mutual understanding.',
                    'Regularly review your process: if we hadn’t had the retrospective that uncovered the frustrations of the Product team over sign off, work quality could have been reduced and relationships strained.',
                    'Don’t be afraid to iterate on the patterns and set the expectation early that this is a good thing. More complex patterns and/or patterns that have multiple variants may need a few shots at them to make them work. Remember that if you’re implementing a design system, the patterns don’t only have to work individually, but they have to be easily maintainable along with the design system. It’s worth spending more time to iterate to get it right at this stage, because fundamental changes later are bound to be more expensive in time, effort and complexity.',
                    'Concentrating the design system typography in one place made thinking about it, talking about it and subsequently maintaining it a lot easier than it might otherwise have been.',
                    'TDD FTW! The few times we didn’t use test-driven development for the JavaScript, it was always painful to fill in the gaps afterwards.',
                    'Grunt is good to get up and running quickly for simple build pipelines, but if you’re trying to do more complicated things, it’s probably easier to use Gulp: Gulp build files are written in JavaScript, which is more versatile and less confusing than trying to wrangle a Grunt config JavaScript object for a complex build pipeline.',
                  ],
                  'prefix' => 'bullet',
                ],
                [
                  'text' => 'For the future, it’d be great to improve the JavaScript loader so that only the components on a particular page are loaded, and then initialisation can occur after these loads have completed.',
                  'type' => 'paragraph',
                ],
                [
                  'text' => 'Also, because we have a 1:1 relationship between JavaScript component and pattern, we should be able to support HTTP/2 delivery of individual JavaScript assets, only providing what is required to the client (once we’ve upgraded our CDN).',
                  'type' => 'paragraph',
                ],
              ],
            ],
            [
              'type' => 'section',
              'title' => 'In conclusion',
              'content' => [
                [
                  'text' => 'This is one of the most rewarding projects I’ve worked on: a truly honourable combination of open-source code enabling open-access science publishing. There’s loads more I could say, but this post is already long enough. Be sure to check back soon for the companion post about how we solved ‘pattern rot’ through enabling use of the patterns we built in PatternLab directly in the website.',
                  'type' => 'paragraph',
                ],
                [
                  'text' => 'If you have feedback or questions about our approach, please contact David by email via innovation [at] elifesciences [dot] org.',
                  'type' => 'paragraph',
                ],
              ],
            ],
            [
              'text' => 'The original version of this article was published at <a href="http://decodeuri.net/2017/11/17/building-a-pattern-library-for-scholarly-publishing/">http://decodeuri.net/2017/11/17/building-a-pattern-library-for-scholarly-publishing/</a>. This post was updated with minor edits on January 5, 2018.',
              'type' => 'paragraph',
            ],
            [
              'text' => 'Would you like to get involved in developing for open research communication?<a href="https://elifesciences.org/events/c40798c3/save-the-date-for-the-elife-innovation-sprint?utm_source=Labs-FrontEnd1&amp;utm_medium=website"> Register your interest to participate in the eLife Innovation Sprint (May 2018)</a> or send a short outline of your idea for a Labs blogpost to innovation [at] elifesciences [dot] org.',
              'type' => 'paragraph',
            ],
            [
              'text' => 'For the latest in innovation, eLife Labs and new open-source tools, sign up for our <a href="https://crm.elifesciences.org/crm/tech-news?utm_source=Labs-FrontEnd1&amp;utm_medium=website&amp;utm_campaign=technews">technology and innovation newsletter</a>. You can also follow <a href="https://twitter.com/eLifeInnovation?lang=en">@eLifeInnovation</a> on Twitter.',
              'type' => 'paragraph',
            ],
          ],
        ],
        $this->lines([
          '<p>By David Moulton, Senior Front-End Developer</p>' . PHP_EOL,
          '<p>I recently had the privilege of being involved in <a href="https://elifesciences.org/labs/c8e0dddf/welcome-to-elife-2-0">the ground-up rebuild of eLife</a>. The whole stack was rebuilt from scratch using a microservices approach. The journal is building a reputation for innovation in science publishing, and it was a great opportunity to get involved in a green-field project to build best web practice into this arena. In this post I’ll be focusing on how we built the front end, covering our design strategy (Atomic Design using PatternLab) and principles as well as the nitty gritty of our front-end development process. A companion post is planned about how we integrated the pattern library into the site.</p>' . PHP_EOL,
          '<p>Note that the code examples throughout have been simplified for clarity.</p>' . PHP_EOL,
          '<h1>Design systems and Atomic Design</h1>' . PHP_EOL,
          '<p>During the design phase, I had many constructive conversations with our User Experience Designer, including prototyping some ideas to help decide on an overall approach to various things. <a href="https://elifesciences.org/labs/fa9f0f5e/redesigning-an-online-scientific-journal-from-the-article-up-iii-design-deliverables">He decided we needed a design system</a> in order to retain both flexibility and design coherence not only for the initial build, but for what we might want to create in the future.</p>' . PHP_EOL,
          '<p>Building a design system requires a modular, hierarchical approach, and this approach is well supported by using a pattern library. Brad Frost’s <a href="http://bradfrost.com/blog/post/atomic-web-design/">Atomic Design</a> principles are a natural fit with the designer’s concept for the design system, and so we chose Atomic Design as the mental model for our new site.</p>' . PHP_EOL,
          '<p>Atomic Design considers reusable, composable design patterns in a hierarchy described in terms of ‘atoms’, ‘molecules’ and ‘organisms’. An atom is the smallest unit of the design system, for example a button or a link. A more complex molecule pattern may be composed by assembling a collection of atom-level patterns, for example a teaser within a listing. An organism is more complex again and may comprise a number of included atoms and molecules.</p>' . PHP_EOL,
          '<figure class="image align-center"><img alt="Pictorial diagram of atom (single dot) to molecules (three dots) to organisms (twelve dots)" data-fid="2322" data-uuid="e1e18fe3-e079-4ff4-9304-b243276678a6" src="/sites/default/files/iiif/content/2018-01/image3.png" width="1999" height="1385" />',
          '<figcaption>Modified from Brad Frost’s <a href="http://bradfrost.com/blog/post/atomic-web-design/">blog</a> (<a href="https://creativecommons.org/licenses/by/4.0/">CC-BY 4.0</a>). This version of the image removes the mention of templates and pages.</figcaption>',
          '</figure>' . PHP_EOL,
          '<p>We discovered that it’s not really worth trying to impose a strict hierarchy to distinguish molecules from organisms. Whilst trying generally to maintain the distinction that organisms are higher-order patterns than molecules, it’s okay for molecules to contain other molecules as well as atoms, and for organisms to contain organisms as well as lower-order patterns. With only three hierarchy levels to play with, we found we got most benefit from a pragmatic interpretation of the Atomic Design hierarchy.</p>' . PHP_EOL,
          '<h1>PatternLab</h1>' . PHP_EOL,
          '<p>Having decided on Atomic Design, we chose Brad Frost’s <a href="http://patternlab.io/">PatternLab</a> as the natural tool to deliver it. PatternLab uses <a href="http://mustache.github.io/">mustache templating</a> and provides a web interface to display the patterns next to the markup that defines them, along with any optional annotations you may wish to supply.</p>' . PHP_EOL,
          '<figure class="image align-center"><img alt="Epidemiology and Global Health section header shown above Mustache template code" data-fid="2324" data-uuid="a948d917-3a46-445d-ae7c-ed7e7d8b9427" src="/sites/default/files/iiif/content/2018-01/image4.png" width="1999" height="998" />',
          '<figcaption>A variant of the content header pattern within PatternLab, showing the mustache code that generates it.</figcaption>',
          '</figure>' . PHP_EOL,
          '<p>Since we started the project, other pattern library candidates have appeared that may have served just as well, for example <a href="https://fractal.build/">Fractal</a>, but they weren’t available then; PatternLab was the the best available tool at the time, and it has served us well.</p>' . PHP_EOL,
          '<p>Useful abilities of PatternLab include:</p>' . PHP_EOL,
          '<ul>',
          '<li>You can view the template code and the compiled HTML next to its pattern.</li>',
          '<li>You can annotate a pattern with style-guide level information.</li>',
          '<li>You can search for patterns.</li>',
          '<li>It includes the option to view any PatternLab page without the PatternLab user interface in case it’s getting in the way.</li>',
          '<li>As well as dragging the viewport, you can set the effective viewport width in px or ems to see how patterns behave in that situation.</li>',
          '<li>It includes more general one-click buttons to set a general viewport width.</li>',
          '<li>DISCO MODE! This provides constant automatic changing of the viewport to random widths to see what breaks (music not included).</li>',
          '</ul>' . PHP_EOL,
          '<p>You can also break down Atomic Design levels to group patterns of a particular type within a level. This can make it easier to track down a pattern on the file system; for example, the molecule patterns might be grouped like this:</p>' . PHP_EOL,
          '<pre><code>',
          'molecules',
          '├── general',
          '│   ├── ...',
          '│   └── ... ',
          '├── navigation',
          '│   ├── ...',
          '│   └── ... ',
          '└── teasers',
          '    ├── ...',
          '    └── ...  ',
          '</code></pre>' . PHP_EOL,
          '<p>PatternLab also has template and page-level composition. This enables you to build up sample pages, illustrating how the patterns might work together. From a technical perspective this is very useful for checking things, such as a baseline grid, that can’t be properly set on a pattern in isolation without observing it in a higher-level context. It’s also great for stakeholders’ engagement too! It can sometimes be difficult to communicate the value of a modular build approach to people who are used to thinking of the web in terms of pages, not patterns. Being able to mock up a page displaying real patterns can help communicate this.</p>' . PHP_EOL,
          '<p>Before starting work, we agreed a set of principles that would guide our approach to decision making along the way.</p>' . PHP_EOL,
          '<h1>Principle: don’t lock out the readers</h1>' . PHP_EOL,
          '<ol>',
          '<li><b>Progressively enhance:</b> a reader could be on any platform anywhere in the world. It’s vital that the content (mainly results of scientific research) and core functionality be available to everyone, regardless of platform, so we couldn’t mandate a high technological baseline in order to read the journal. For this reason, and to be a good web citizen generally, we would use a <a href="https://alistapart.com/article/understandingprogressiveenhancement">progressive enhancement</a> approach to ensure that JavaScript is not required to use the site: you get an enhanced experience if it’s available, but content and core functionality does not require it.</li>',
          '<li><b>Make it responsive:</b> it should be a given these days, but it’s worth mentioning anyway that the website should be <a href="https://www.smashingmagazine.com/2011/01/guidelines-for-responsive-web-design/">responsive</a>, so it will display appropriately, whatever the size of the users’ screens.</li>',
          '<li><b>Make it performant:</b> no one likes waiting for a web page to load and, if it takes too long, users will bail. If a user is on a narrow bandwidth or a high-latency connection, then any performance problems are exacerbated. Data costs vary across the world, and we don’t want it to cost more in data charges than necessary to read our content. Performance was considered throughout the build, using ideas of a performance budget, techniques such as responsive images, allowing for the HTTP/2 serving of smaller resources, and not using a library unless we needed it.</li>',
          '<li><b>Make it accessible:</b> it’s vital that our site content is accessible to all to read and use.</li>',
          '</ol>' . PHP_EOL,
          '<h1>Principle: maintain the value of the pattern library</h1>' . PHP_EOL,
          '<p>One of the aims of a pattern library is to be the canonical source of truth for the design and front-end implementation of the design patterns. This is the case at launch, but it’s common for the value of a pattern library to drop dramatically over time if it’s not easy to update, or it’s difficult to do so in a way that maintains the value of the underlying design system.</p>' . PHP_EOL,
          '<p>After launch, pattern libraries are often susceptible to ‘pattern rot’ when, for whatever reason, the patterns used on the live website are updated but the pattern library is not. This usually occurs when there is some kind of copy/paste step necessary in order to apply an update from a pattern library pattern to its version on the live site. This step needs to be short-circuited only once with an update being applied only to the live site, then the pattern library and the live site would diverge. Once this happens, the pattern library is no longer the canonical source of truth, so you can no longer have complete confidence that what you see in the pattern library is what you get on the site. Much of the work that went into building the pattern library becomes lost.</p>' . PHP_EOL,
          '<p>In summary, for a pattern library to retain its value, it must:</p>' . PHP_EOL,
          '<ol>',
          '<li>Be built in such a way as to reflect the intent of the design system.</li>',
          '<li>Be easy to maintain.</li>',
          '<li>Be as easy as possible to integrate into the website it’s defining the patterns for (avoid ‘pattern rot’).</li>',
          '</ol>' . PHP_EOL,
          '<p>Integration is the hardest problem to solve and has been the holy grail for pattern libraries for years. We managed to crack this, but you’ll have to wait for the companion post to find out how!</p>' . PHP_EOL,
          '<h1>Our aims for the front-end build</h1>' . PHP_EOL,
          '<p>Before we started writing code, we documented our lower-level aims, based on the principles we’d already decided upon. Our priority of concerns, in decreasing order, were:</p>' . PHP_EOL,
          '<ol>',
          '<li>Access.</li>',
          '<li>Maintainability.</li>',
          '<li>Performance (with the assumption that we would not let maintainability negatively affect it).</li>',
          '<li>Taking advantage of browser capabilities.</li>',
          '<li>Visual appeal.</li>',
          '</ol>' . PHP_EOL,
          '<p>We also documented some of the techniques we’d use:</p>' . PHP_EOL,
          '<ul>',
          '<li>Progressive enhancement.</li>',
          '<li>min-width media queries.</li>',
          '<li>Small-screen-first responsive images.</li>',
          '<li>Only add libraries as needed.</li>',
          '</ul>' . PHP_EOL,
          '<h1>Identifying and naming the patterns</h1>' . PHP_EOL,
          '<p>Before we could build any patterns, we needed to identify what things we were building and decide how to talk about them: without a common vocabulary, things could fall apart very quickly. So, embarking on building a brand new look for an online-only journal, we took a large slice of irony pie and started by printing off wireframes of all the patterns.</p>' . PHP_EOL,
          '<p>Having cut out each pattern, we took up most of the floor of the room we were in, laying them out to take stock of what we had. We grouped similar patterns together, enabling us to distinguish those that were essentially duplicates from those we could treat as variants of the same underlying pattern, and to confirm which were actually distinct patterns.</p>' . PHP_EOL,
          '<figure class="image align-center"><img alt="Paper cutouts of various size content headers laid on the floor" data-fid="2326" data-uuid="bb591f30-8c41-4af2-aef4-9d1c86adbc35" src="/sites/default/files/iiif/content/2018-01/image1.jpg" width="1999" height="1500" />',
          '</figure>' . PHP_EOL,
          '<p>Once distinct patterns were identified, we opened up the room to anyone who wanted to help us agree names for each pattern. Fresh minds at this point helped us get better names.</p>' . PHP_EOL,
          '<p>We thought the whole process would take a couple of hours, but it took most of a day to complete. The benefits were well worth the time: it was a great way to expose many hidden assumptions, identify gaps in thinking and discover inconsistencies that had crept in during the design process. If we hadn’t done the exercise, all those problems would still exist, but they’d have only manifested later when they’d be more expensive in time and effort to fix.</p>' . PHP_EOL,
          '<h1>Process</h1>' . PHP_EOL,
          '<p>With two front-end developers liaising closely with the Product team (the designer and the product owner), and facilitated by our tireless scrum master, we decided on two-week scrum sprints, managing the scrum board in Trello.</p>' . PHP_EOL,
          '<p>The fact that we were using progressive enhancement affected the tickets we created: each pattern had a <b>first pass</b> ticket for building its markup and CSS. We used a checklist to manage the work for each ticket:</p>' . PHP_EOL,
          '<ul>',
          '<li>Semantic HTML is built.</li>',
          '<li>CSS is applied correctly.</li>',
          '<li>Core content and functionality is available without JavaScript or CSS.</li>',
          '<li>Accessibility testing performed.</li>',
          '<li>Browser testing performed.</li>',
          '</ul>' . PHP_EOL,
          '<p>If a pattern had some behaviour, then a <b>second pass</b> ticket was created for building its JavaScript. Each second pass ticket had the checklist:</p>' . PHP_EOL,
          '<ul>',
          '<li>JavaScript tests written.</li>',
          '<li>JavaScript behaviour written.</li>',
          '<li>JavaScript tests pass.</li>',
          '</ul>' . PHP_EOL,
          '<p>Some patterns have variants (a variant is a different way the pattern could be displayed, depending on the context). For these, the one ticket described building all the variants. They would all have to be considered at the same time in order to build them in a way that works for all of the variants anyway, so it didn’t make sense to split them up.</p>' . PHP_EOL,
          '<p>Individual tickets had additional, pattern-specific checklist items as necessary.</p>' . PHP_EOL,
          '<p>The <a href="https://trello.com/">Trello</a> board was initially created with the columns:</p>' . PHP_EOL,
          '<ul>',
          '<li>Backlog (all tickets start here).</li>',
          '<li>Sprint items (committed to in the current sprint).</li>',
          '<li>In progress (in active development).</li>',
          '<li>In testing (in browser/accessibility testing).</li>',
          '<li>In review (being reviewed).</li>',
          '<li>Done (finished).</li>',
          '</ul>' . PHP_EOL,
          '<p>We created a feature branch in the Git repository for each ticket as we started work on it. Once a ticket’s code was approved, it was merged into the GitHub master branch from the feature branch via pull request.</p>' . PHP_EOL,
          '<p>We discovered in the first retrospective that the Product team was not feeling sufficiently involved in the review process for a pattern, resulting in patterns sometimes being classed as done when they weren’t. This was quickly addressed in two ways:</p>' . PHP_EOL,
          '<p>1. The addition of two more columns to separate out the product review from the technical review. The board columns were then:</p>' . PHP_EOL,
          '<ul>',
          '<li>Backlog (to be done).</li>',
          '<li>Sprint items (committed to in the current sprint).</li>',
          '<li>In progress (in active development).</li>',
          '<li><b>For feedback (ready for product review).</b></li>',
          '<li><b>Feedback provided (reviewed by Product).</b></li>',
          '<li>In testing (in browser/accessibility testing).</li>',
          '<li>In review (in <b>technical</b> review).</li>',
          '<li>Done (finished).</li>',
          '</ul>' . PHP_EOL,
          '<p>2. The addition of an approval checklist, with each Product team member having a dedicated box to check to indicate sign off.</p>' . PHP_EOL,
          '<figure class="image align-center"><img alt="Screenshot of eLife2 patterns trello board with columns as listed above" data-fid="2328" data-uuid="1af9780a-d843-4aaf-b6b3-9f24108499ff" src="/sites/default/files/iiif/content/2018-01/trello-grouped.png" width="1999" height="974" />',
          '</figure>' . PHP_EOL,
          '<p>With the revised board, once the pattern was built and ready for product approval it was moved from ‘in progress’ to ‘for feedback’. The Product team members then specified any changes to be made or checked their name off the checklist if they were happy with the pattern as it was. They then moved the cards to ‘feedback provided’. Tickets were able to go around the ‘for feedback’ -&gt; ‘feedback provided’ -&gt; ‘for feedback’ loop a number of times until the pattern was right. Once a ticket arrived in ‘feedback provided’ with all Product team members’ approval boxes checked, the pattern was ready for testing.</p>' . PHP_EOL,
          '<h1>Build scope</h1>' . PHP_EOL,
          '<p>Currently, the pattern library defines just under 100 patterns. Each pattern comprises:</p>' . PHP_EOL,
          '<ul>',
          '<li>Exactly one .scss file.</li>',
          '<li>Exactly one .mustache file.</li>',
          '<li>One or more .json files, one per pattern variant.</li>',
          '<li>Zero or one .js files, built in the second pass.</li>',
          '<li>Exactly one .yaml file to define the data structure. This is helpful when integrating the patterns into the site and will be expanded on in the forthcoming companion post.</li>',
          '</ul>' . PHP_EOL,
          '<p>Each pattern’s accessibility was tested using Khan Academy’s <a href="https://khan.github.io/tota11y/">tota11y</a> for accessibility testing in the browser. We used <a href="https://www.browserstack.com/">Browserstack</a> for browser/device testing.</p>' . PHP_EOL,
          '<p>We started off with a minimal build pipeline, originally in <a href="https://gruntjs.com/">Grunt</a> (we switched to <a href="https://gulpjs.com/">Gulp</a> when we introduced JavaScript, see below), knowing that we could add to it when we needed to.</p>' . PHP_EOL,
          '<h1>SCSS</h1>' . PHP_EOL,
          '<p>Although PatternLab can compile SCSS, we elected not to do this as we wanted to control the SCSS compilation with the build pipeline. The first iteration was only for linting and compiling SCSS and moving asset files where they needed to be for the PatternLab server to display them correctly.</p>' . PHP_EOL,
          '<p>We agreed a few simple style rules with the aim of keeping the SCSS maintainable in the longer term:</p>' . PHP_EOL,
          '<ul>',
          '<li>One SCSS file per pattern.</li>',
          '<li>Use <a href="https://csswizardry.com/2013/01/mindbemding-getting-your-head-round-bem-syntax/">Harry Robert’s flavour of BEM</a> (Block, Element, Modifier) for CSS class naming. This works well with a pattern-based approach, as the root of each pattern can define a BEM block. Coupled with the decision to have one SCSS file per pattern, this namespacing kept the separation of the styles for individual patterns nice and clean.</li>',
          '<li>Keep selector specificity as low as possible, and with a maximum selector nesting of three (not including pseudo elements). As selectors need only to start from the root of a pattern (or deeper), this seemed a pragmatic maximum. We agreed that we’d increase it if we really, really needed to, but up to now we haven’t had to.</li>',
          '<li>Don’t use ‘&amp;’ as partial completion for a class name, as it makes searching/refactoring across a code base more error-prone.</li>',
          '<li><a href="https://www.sitepoint.com/avoid-sass-extend/">Avoid @extends</a>; use mixins instead for greater flexibility.</li>',
          '<li>Only mixins that are completely pattern-specific may live in a pattern’s SCSS file; other mixins must live in higher-level files (see Architecture).</li>',
          '<li>List property names alphabetically.</li>',
          '</ul>' . PHP_EOL,
          '<p>We implemented style linting with <a href="https://stylelint.io/">stylelint</a>.</p>' . PHP_EOL,
          '<p>We settled on the following architectural approach to try to keep the design system reasonably easily maintainable:</p>' . PHP_EOL,
          '<ul>',
          '<li>Meaningful values for the design system: colours, font sizes, quantities for spacing and element sizing, media query breakpoints, transition parameters and so on are defined in the variables partial.</li>',
          '<li>For a sensible reset starting point we included <a href="http://nicolasgallagher.com/about-normalize-css/">Nicholas Gallagher’s normalise CSS stylesheet</a> as an SCSS partial.</li>',
          '<li>Our own base styles and any necessary overrides to normalise are defined in the normalise-overrides partial.</li>',
          '<li>The typography component of the design system is defined in the typographic-hierarchy partial: this contains numerous mixins responsible for enforcing a consistent typographic style across the site.</li>',
          '<li>The grid partial contains all SCSS and mixins required for both the horizontal and baseline grid systems.</li>',
          '<li>The mixins partial contains all other mixins.</li>',
          '</ul>' . PHP_EOL,
          '<p>These are imported along with the pattern-specific SCSS files to create the main build CSS file like this:</p>' . PHP_EOL,
          '<pre><code>',
          'build.scss',
          '├── base.scss',
          '│   ├── _definitions',
          '│   │   ├── _variables',
          '│   │   ├── _mixins',
          '│   │   └── _typographic-hierarchy',
          '│   ├── reset',
          '│   │   ├── _normalize',
          '│   │   ├── _normalize-overrides',
          '│   └── _grid',
          '├── pattern-1.scss',
          '├── pattern-2.scss',
          '├── pattern-3.scss',
          '├── ...',
          '└── pattern-n.scss',
          '</code></pre>' . PHP_EOL,
          '<p>Each individual [pattern].scss file also imports the definitions partial so it has access to all the necessary shared knowledge of the design system as distilled into the variables and various mixins.</p>' . PHP_EOL,
          '<p>With an eye on delivery over HTTP/2, we ensured that we could produce individual pattern-level CSS files as well as one main stylesheet for traditional HTTP/1.1 delivery.</p>' . PHP_EOL,
          '<h1>Organising the typography</h1>' . PHP_EOL,
          '<p>The designer documented the typographical part of the design system as a hierarchy of definitions. The lowest level is used to set relevant variables in the variables partial, and set the base styles in normalise-overrides partial.</p>' . PHP_EOL,
          '<table><thead><tr><th>Style name</th><th>Default narrow screen</th><th>Media query</th></tr></thead><tbody><tr><td>Body</td><td>PT Serif 16px/1.5 space after 24px</td><td>None</td></tr><tr><td>H1</td><td>Avenir Next Demi Bold 24px/1.3</td><td>Font-size 36px</td></tr><tr><td>H2</td><td>Avenir Next Demi Bold 21px/1.3 space after 5px</td><td>None</td></tr><tr><td>...</td><td>...</td><td>...</td></tr></tbody></table>' . PHP_EOL,
          '<p>The media query column specifies what (if any) change occurs when the viewport is wider than the relevant site breakpoint. Note that the breakpoints themselves are not specified here, to keep things loosely coupled.</p>' . PHP_EOL,
          '<p>These base styles are then used as part of the specification for the next level up:</p>' . PHP_EOL,
          '<table><thead><tr><th>Style name</th><th>Default narrow screen</th><th>Media query</th></tr></thead><tbody><tr><td>Title-lg</td><td>PT Serif 16px/1.5 space after 24px</td><td>None</td></tr><tr><td>Title-md</td><td>h1 font-size 42px</td><td>Font-size 54px</td></tr><tr><td>Title-sm</td><td>h1 font-size 30px</td><td>Font-size 44px</td></tr><tr><td>Title-xs</td><td>h1</td><td>Font-size 36px</td></tr><tr><td>...</td><td>...</td><td>...</td></tr></tbody></table>' . PHP_EOL,
          '<p>These all specify different title sizes (which one gets applied depends on how long the title is).</p>' . PHP_EOL,
          '<p>Typographical styles are defined for all aspects of the design, like this tiny fraction of the set:</p>' . PHP_EOL,
          '<table><thead><tr><th>Style name</th><th>Default narrow screen</th><th>Media query</th></tr></thead><tbody><tr><td>CONTENT LABEL [grey]</td><td>Avenir Next Demi Bold, 11px/1</td><td>None</td></tr><tr><td>SUBJECT LABEL [blue]</td><td>Avenir Next Demi Bold, 11px/1</td><td>None</td></tr><tr><td>Article title (main list)</td><td>h2</td><td>None</td></tr><tr><td>Article title (side list)</td><td>PT Serif Bold 16px</td><td>None</td></tr><tr><td>...</td><td>...</td><td>...</td></tr></tbody></table>' . PHP_EOL,
          '<p>All these definitions are captured in mixins within typographical-hierarchy. For example, for the two labels, we abstract out the common aspects:</p>' . PHP_EOL,
          '<pre><code>',
          ' @mixin _label-typeg() {',
          '   font-family: $font-secondary;',
          '   font-weight: normal;',
          '   @include font-size-and-vertical-height(11);',
          '   letter-spacing: 0.5px;',
          '   text-transform: uppercase;',
          ' }',
          '</code></pre>' . PHP_EOL,
          '<p>and then create a mixin for each label type, based on it:</p>' . PHP_EOL,
          '<pre><code>',
          '@mixin label-content-typeg() {',
          '   @include _label-typeg();',
          '   color: $color-text-secondary; // grey',
          ' }' . PHP_EOL,
          ' @mixin label-subject-typeg() {',
          '   @include _label-typeg();',
          '   color: $color-primary; // blue',
          ' }',
          '</code></pre>' . PHP_EOL,
          '<p>Note that all typographical style mixin names include \'typeg\' for clarity when viewed out of context.</p>' . PHP_EOL,
          '<p>In the design system specification, all the patterns have their typography defined in terms of these typographical style names. Often a particular style name is used for more than one pattern. With this approach, we can easily apply the correct typographical style to the pattern’s CSS via the mixins and, at the same time, keep the design system highly maintainable. If a particular named style is updated, then a simple update to one ‘typeg’ mixin will permeate to all parts of the system that use it.</p>' . PHP_EOL,
          '<p><i>Note: these examples are taken from an early draft of the spec and the final values used on the site may have changed – although the system defining them hasn’t, indicating that it’s working well!</i></p>' . PHP_EOL,
          '<h1>Markup</h1>' . PHP_EOL,
          '<p>Compilation of the mustache templates with their JSON data files is handled by PatternLab, producing a user-friendly and, to some extent, configurable <a href="https://ui-patterns.elifesciences.org/">web view of the patterns</a>.</p>' . PHP_EOL,
          '<p>A basic pattern with no variants has exactly one mustache template and one corresponding JSON data file.</p>' . PHP_EOL,
          '<p>For a more complex pattern that has variants, an example of each variant can be produced by supplying a separate JSON file for each. For example, the teaser pattern has 13 variants (an extreme case; most variant patterns have fewer than a handful). It has only one mustache file, but 13 associated JSON files:</p>' . PHP_EOL,
          '<pre><code>',
          '.',
          '├── teaser.mustache',
          '├── teaser~05-main.json',
          '├── teaser~10-main-small-image.json',
          '├── teaser~15-main-big-image.json',
          '├── teaser~20-secondary.json',
          '├── teaser~25-secondary-small-image.json',
          '├── teaser~30-secondary-big-image.json',
          '├── teaser~35-related-item.json',
          '├── teaser~40-basic.json',
          '├── teaser~45-main-event.json',
          '├── teaser~50-secondary-event.json',
          '├── teaser~55-grid-style--labs.json',
          '├── teaser~60-grid-style--podcast.json',
          '└── teaser~65-main--with-list.json',
          '</code></pre>' . PHP_EOL,
          '<p>PatternLab uses the ~ in a filename to identify a variant. The numerals in the filenames control the ordering of the <a href="https://ui-patterns.elifesciences.org/?p=viewall-molecules-teasers">presentation of the variants</a>.</p>' . PHP_EOL,
          '<p>Sometimes, when coming to build a pattern with variants, we discovered that one or more variants required a significant change to the markup of the main pattern, which suggested a new pattern rather than just a variant. In these cases, we created the ticket(s) for it and put them into the backlog, moving the appropriate spec from the old to the new ticket.</p>' . PHP_EOL,
          '<h1>Images</h1>' . PHP_EOL,
          '<p>Scholarly content contains a lot of figures, mainly in the form of images. We use <a href="https://responsiveimages.org/">responsive images techniques</a> (&lt;picture&gt;, srcset and sometimes sizes) to stop the browser downloading more image data than it needs. For example, the compiled HTML from the captioned-asset pattern’s mustache template looks like this:</p>' . PHP_EOL,
          '<pre><code>',
          '&lt;figure class="captioned-asset"&gt;' . PHP_EOL,
          '   &lt;picture class="captioned-asset__picture"&gt;',
          '     &lt;source ',
          '       srcset="',
          '         /path-to-1076-px-wide-image 1076w, ',
          '         /path-to-538-px-wide-image 538w" ',
          '       type="image/jpeg" /&gt;',
          '     &lt;img ',
          '       src="/path-to-538-px-wide-image" ',
          '       alt=""',
          '       class="captioned-asset__image" /&gt;',
          '   &lt;/picture&gt;         ' . PHP_EOL,
          '   &lt;figcaption class="captioned-asset__caption"&gt;          ',
          '     &lt;h6 class="caption-text__heading"&gt;Title of the figure caption&lt;/h6&gt;',
          '     &lt;div class="caption-text__body"&gt;The figure caption&lt;/div&gt;',
          '     &lt;span class="doi doi--asset"&gt;The DOI link&lt;/span&gt;',
          '   &lt;/figcaption&gt;' . PHP_EOL,
          ' &lt;/figure&gt;',
          '</code></pre>' . PHP_EOL,
          '<p>Note the empty alt attribute: as the image is within a &lt;figure&gt;, the &lt;figcaption&gt; provides the description.</p>' . PHP_EOL,
          '<p>To handle the large amount of image variants that can be required when implementing responsive images, we used the <a href="http://iiif.io/">International Image Interoperability Framework</a> (IIIF) <a href="https://elifesciences.org/labs/d6044799/dynamically-serving-scientific-images-using-iiif">to serve most of our images</a>.</p>' . PHP_EOL,
          '<h1>Extended build pipeline</h1>' . PHP_EOL,
          '<p>When we came to build the patterns’ behaviours, we needed to add JavaScript linting, transpiling and test running to the build pipeline. It quickly became apparent that Gulp was much more flexible than Grunt for this, so we switched from a wild boar to a huge caffeinated beverage (and the caffeine would come in handy).</p>' . PHP_EOL,
          '<p>We author in ES6, and transpire using Babel. Linting is performed with a mixture of <a href="http://jshint.com/">jshint</a> and <a href="http://jscs.info/">jscs</a>, although there is a pending upgrade to <a href="https://eslint.org/">eslint</a>.</p>' . PHP_EOL,
          '<h1>Pattern behaviour with JavaScript</h1>' . PHP_EOL,
          '<p>A pattern’s JavaScript behaviour is defined in a discrete component with the same name as the pattern. This JavaScript component is referenced from the root element of the pattern’s mustache template by the attribute:</p>' . PHP_EOL,
          '<pre><code>',
          'data-behaviour="ComponentName".',
          '</code></pre>' . PHP_EOL,
          '<p>For example, the content-header pattern has its associated behaviour defined in the ContentHeader class, which is found in the ContentHeader.js file. The content-header.mustache template starts with:</p>' . PHP_EOL,
          '<pre><code>',
          '&lt;div... data-behaviour="ContentHeader"&gt;...',
          '</code></pre>' . PHP_EOL,
          '<p>which causes this HTML element to be passed as an argument to the class constructor in ContentHeader.js when the page’s JavaScript loads and runs:</p>' . PHP_EOL,
          '<pre><code>',
          '// 1. Load the components',
          ' const Components = {};',
          ' Components.ContentHeader = require(\'./components/ContentHeader\');',
          ' // ... load more components ...' . PHP_EOL,
          ' // 4. This bit does the actual initialising',
          ' function initialiseComponent($component) {',
          '   const handler = $component.getAttribute(\'data-behaviour\');',
          '   if (!!Components[handler] && typeof Components[handler] === \'function\') {',
          '     new Components[handler]($component, window, window.document);',
          '   }',
          '      ',
          '   $component.dataset.behaviourInitialised = true;',
          ' }',
          '    ',
          ' // 2. Find all patterns in the document that have declared  a js component',
          ' const components = document.querySelectorAll(\'[data-behaviour]\');',
          '    ',
          ' // 3. Initialise each component with the HTMLElement that declared it',
          ' if (components) {',
          '   [].forEach.call(components, (el) =&gt; initialiseComponent(el));  ',
          ' }',
          '</code></pre>' . PHP_EOL,
          '<p>This applies the pattern’s JavaScript behaviour to each instance of the pattern on the page.</p>' . PHP_EOL,
          '<p>Notice we’re additionally passing in the window and document objects to the component’s constructor. This dependency injection enables us to mock these objects and subsequently write better tests.</p>' . PHP_EOL,
          '<h1>JavaScript testing</h1>' . PHP_EOL,
          '<p>We test in the browser using the tripod of the <a href="https://mochajs.org/">mocha</a> test framework, the <a href="http://chaijs.com/">chai</a> assertion library, and with <a href="http://sinonjs.org/">sinon</a> for providing spies, mocks and stubs. At the moment we’re using phantomjs as the test environment but, now this is not currently under active maintenance, we’re looking to switch to using puppeteer with headless Chrome.</p>' . PHP_EOL,
          '<p>For each pattern under test there are two files: the spec file and the fixture file. The spec file contains the tests. The fixture file contains the HTML of the pattern whose component is under test. This is obtained by finding the pattern’s compiled HTML generated by PatternLab and manually copying across the relevant code.</p>' . PHP_EOL,
          '<p>By way of example, these are the guts of the fixture file for the ToggleableCaption component:</p>' . PHP_EOL,
          '<pre><code>',
          '&lt;!-- Results of test run end up here --&gt;',
          ' &lt;div id="mocha"&gt;&lt;/div&gt;' . PHP_EOL,
          ' &lt;!-- This is the test fixture --&gt; ',
          ' &lt;div data-behaviour="ToggleableCaption" data-selector=".caption-text__body"&gt;' . PHP_EOL,
          '   &lt;div class="caption-text__body"&gt;' . PHP_EOL,
          '     &lt;p&gt;Lorem ipsum dolor sit amet, consectetur adipiscing elit. In interdum, metus quis sodales pharetra, odio justo',
          '       blandit mi, at porta augue felis sit amet metus. Aliquam porta, justo dapibus vulputate aliquet, arcu quam tempor',
          '       metus, et aliquam nisi nunc in est. Cras vitae leo pretium, tincidunt nisi ac, varius neque. Donec nec posuere',
          '       sem. Integer felis risus, sagittis et pulvinar pretium, rutrum sit amet odio. Proin erat purus, sodales a gravida',
          '       vitae, malesuada non sem. Integer semper enim ante. Donec odio ipsum, ultrices vel quam at, condimentum eleifend',
          '       augue. Pellentesque id ipsum nec dui dictum commodo. Donec quis sagittis ex, sit amet tempus nisi. Nulla eu tortor',
          '       vitae felis porta faucibus in eu leo. Donec ultrices vehicula enim, quis maximus nulla rhoncus sed. Vestibulum',
          '       elementum ligula quis mi aliquet, tincidunt finibus mi hendrerit.&lt;/p&gt;' . PHP_EOL,
          '   &lt;/div&gt;' . PHP_EOL,
          ' &lt;/div&gt;' . PHP_EOL,
          ' &lt;!-- Load the test frameworks --&gt;',
          ' &lt;script src="../node_modules/mocha/mocha.js"&gt;&lt;/script&gt;',
          ' &lt;script src="../node_modules/chai/chai.js"&gt;&lt;/script&gt;',
          ' &lt;script src="../node_modules/sinon/pkg/sinon.js"&gt;&lt;/script&gt;' . PHP_EOL,
          ' &lt;!-- Initialise the test suite --&gt;',
          ' &lt;script&gt;mocha.setup(\'bdd\')&lt;/script&gt;' . PHP_EOL,
          ' &lt;!-- Load the spec file --&gt;',
          ' &lt;script src="build/toggleablecaption.spec.js"&gt;&lt;/script&gt;' . PHP_EOL,
          ' &lt;!-- Run the tests --&gt;',
          ' &lt;script&gt;',
          '   mocha.run();',
          ' &lt;/script&gt;',
          '</code></pre>' . PHP_EOL,
          '<p>We’ve found that keeping the test fixtures up-to-date can be difficult, because it takes a developer to remember to re-copy and paste the HTML from the compiled mustache pattern template every time a pattern’s source or source-generating JavaScript is updated.</p>' . PHP_EOL,
          '<p>The tests are run under Gulp using gulp-mocha-phantomjs.</p>' . PHP_EOL,
          '<h1>Where is it, tho’?</h1>' . PHP_EOL,
          '<p>The website using the patterns is <a href="https://elifesciences.org/">https://elifesciences.org/</a>.</p>' . PHP_EOL,
          '<p>All the code for the pattern library is available on GitHub under the MIT license at <a href="https://github.com/elifesciences/pattern-library">https://github.com/elifesciences/pattern-library</a>.</p>' . PHP_EOL,
          '<p>The PatternLab pattern library generated by this code is at <a href="https://ui-patterns.elifesciences.org/">https://ui-patterns.elifesciences.org/</a>.</p>' . PHP_EOL,
          '<h1>Lessons learned</h1>' . PHP_EOL,
          '<ul>',
          '<li>Take the time to agree your principles and build aims up front. Set the expectations that derive from these in the wider team, so no one’s surprised when you start challenging demands for moarLibrareez, etc.</li>',
          '<li>When following Atomic Design principles, don’t worry too much about strict molecule/organism hierarchy: get it as good as you can, but a slightly fuzzy but usable system is better than a system that’s strictly correct but horrible to use.</li>',
          '<li>Implementing a design system requires excellent communication between designers and front-end developers. A designer who understands front-end code, and developers with a design eye, both really help with this: the more shared context, the better the mutual understanding.</li>',
          '<li>Regularly review your process: if we hadn’t had the retrospective that uncovered the frustrations of the Product team over sign off, work quality could have been reduced and relationships strained.</li>',
          '<li>Don’t be afraid to iterate on the patterns and set the expectation early that this is a good thing. More complex patterns and/or patterns that have multiple variants may need a few shots at them to make them work. Remember that if you’re implementing a design system, the patterns don’t only have to work individually, but they have to be easily maintainable along with the design system. It’s worth spending more time to iterate to get it right at this stage, because fundamental changes later are bound to be more expensive in time, effort and complexity.</li>',
          '<li>Concentrating the design system typography in one place made thinking about it, talking about it and subsequently maintaining it a lot easier than it might otherwise have been.</li>',
          '<li>TDD FTW! The few times we didn’t use test-driven development for the JavaScript, it was always painful to fill in the gaps afterwards.</li>',
          '<li>Grunt is good to get up and running quickly for simple build pipelines, but if you’re trying to do more complicated things, it’s probably easier to use Gulp: Gulp build files are written in JavaScript, which is more versatile and less confusing than trying to wrangle a Grunt config JavaScript object for a complex build pipeline.</li>',
          '</ul>' . PHP_EOL,
          '<p>For the future, it’d be great to improve the JavaScript loader so that only the components on a particular page are loaded, and then initialisation can occur after these loads have completed.</p>' . PHP_EOL,
          '<p>Also, because we have a 1:1 relationship between JavaScript component and pattern, we should be able to support HTTP/2 delivery of individual JavaScript assets, only providing what is required to the client (once we’ve upgraded our CDN).</p>' . PHP_EOL,
          '<h1>In conclusion</h1>' . PHP_EOL,
          '<p>This is one of the most rewarding projects I’ve worked on: a truly honourable combination of open-source code enabling open-access science publishing. There’s loads more I could say, but this post is already long enough. Be sure to check back soon for the companion post about how we solved ‘pattern rot’ through enabling use of the patterns we built in PatternLab directly in the website.</p>' . PHP_EOL,
          '<p>If you have feedback or questions about our approach, please contact David by email via innovation [at] elifesciences [dot] org.</p>' . PHP_EOL,
          '<p>The original version of this article was published at <a href="http://decodeuri.net/2017/11/17/building-a-pattern-library-for-scholarly-publishing/">http://decodeuri.net/2017/11/17/building-a-pattern-library-for-scholarly-publishing/</a>. This post was updated with minor edits on January 5, 2018.</p>' . PHP_EOL,
          '<p>Would you like to get involved in developing for open research communication?<a href="https://elifesciences.org/events/c40798c3/save-the-date-for-the-elife-innovation-sprint?utm_source=Labs-FrontEnd1&amp;utm_medium=website"> Register your interest to participate in the eLife Innovation Sprint (May 2018)</a> or send a short outline of your idea for a Labs blogpost to innovation [at] elifesciences [dot] org.</p>' . PHP_EOL,
          '<p>For the latest in innovation, eLife Labs and new open-source tools, sign up for our <a href="https://crm.elifesciences.org/crm/tech-news?utm_source=Labs-FrontEnd1&amp;utm_medium=website&amp;utm_campaign=technews">technology and innovation newsletter</a>. You can also follow <a href="https://twitter.com/eLifeInnovation?lang=en">@eLifeInnovation</a> on Twitter.</p>',
        ]),
        [
          'fids' => [
            'public://iiif/content/2018-01/image3.png' => [
              'fid' => 2322,
              'uuid' => 'e1e18fe3-e079-4ff4-9304-b243276678a6',
              'src' => '/sites/default/files/iiif/content/2018-01/image3.png',
            ],
            'public://iiif/content/2018-01/image4.png' => [
              'fid' => 2324,
              'uuid' => 'a948d917-3a46-445d-ae7c-ed7e7d8b9427',
              'src' => '/sites/default/files/iiif/content/2018-01/image4.png',
            ],
            'public://iiif/content/2018-01/image1.jpg' => [
              'fid' => 2326,
              'uuid' => 'bb591f30-8c41-4af2-aef4-9d1c86adbc35',
              'src' => '/sites/default/files/iiif/content/2018-01/image1.jpg',
            ],
            'public://iiif/content/2018-01/trello-grouped.png' => [
              'fid' => 2328,
              'uuid' => '1af9780a-d843-4aaf-b6b3-9f24108499ff',
              'src' => '/sites/default/files/iiif/content/2018-01/trello-grouped.png',
            ],
          ],
        ],
      ],
      'preserve hierarchy' => [
        [
          'content' => [
            [
              'type' => 'paragraph',
              'text' => 'Paragraph 1.',
            ],
            [
              'type' => 'section',
              'title' => 'Section 1',
              'content' => [
                [
                  'type' => 'paragraph',
                  'text' => 'Paragraph 1 in Section 1.',
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'Paragraph 2 in Section 1.',
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'Paragraph 3 in Section 1.',
                ],
                [
                  'type' => 'section',
                  'title' => 'Section 1.1',
                  'content' => [
                    [
                      'type' => 'paragraph',
                      'text' => 'Paragraph 1 in Section 1.1.',
                    ],
                    [
                      'type' => 'quote',
                      'text' => [
                        [
                          'type' => 'paragraph',
                          'text' => 'Blockquote 1 in Section 1.1.',
                        ],
                      ],
                    ],
                    [
                      'type' => 'paragraph',
                      'text' => 'Paragraph 2 in Section 1.1.',
                    ],
                    [
                      'type' => 'code',
                      'code' => $this->lines([
                        'Code sample 1 line 1 in Section 1.1.',
                        'Code sample 1 line 2 in Section 1.1.',
                      ], 2),
                    ],
                  ],
                ],
                [
                  'type' => 'section',
                  'title' => 'Section 1.2',
                  'content' => [
                    [
                      'type' => 'paragraph',
                      'text' => 'Paragraph 1 in Section 1.2.',
                    ],
                  ],
                ],
              ],
            ],
            [
              'type' => 'section',
              'title' => 'Section 2',
              'content' => [
                [
                  'type' => 'paragraph',
                  'text' => 'Paragraph 1 in Section 2.',
                ],
                [
                  'type' => 'table',
                  'tables' => [
                    '<table><tr><td>Table 1 in Section 2.</td></tr></table>',
                  ],
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'Paragraph 2 in Section 2.',
                ],
              ],
            ],
          ],
        ],
        $this->lines([
          '<p>Paragraph 1.</p>' . PHP_EOL,
          '<h1>Section 1</h1>' . PHP_EOL,
          '<p>Paragraph 1 in Section 1.</p>' . PHP_EOL,
          '<p>Paragraph 2 in Section 1.</p>' . PHP_EOL,
          '<p>Paragraph 3 in Section 1.</p>' . PHP_EOL,
          '<h2>Section 1.1</h2>' . PHP_EOL,
          '<p>Paragraph 1 in Section 1.1.</p>' . PHP_EOL,
          '<blockquote>Blockquote 1 in Section 1.1.</blockquote>' . PHP_EOL,
          '<p>Paragraph 2 in Section 1.1.</p>' . PHP_EOL,
          '<pre><code>' . PHP_EOL . 'Code sample 1 line 1 in Section 1.1.' . PHP_EOL,
          'Code sample 1 line 2 in Section 1.1.' . PHP_EOL . '</code></pre>' . PHP_EOL,
          '<h2>Section 1.2</h2>' . PHP_EOL,
          '<p>Paragraph 1 in Section 1.2.</p>' . PHP_EOL,
          '<h1>Section 2</h1>' . PHP_EOL,
          '<p>Paragraph 1 in Section 2.</p>' . PHP_EOL,
          '<table><tr><td>Table 1 in Section 2.</td></tr></table>' . PHP_EOL,
          '<p>Paragraph 2 in Section 2.</p>',
        ]),
      ],
      'cv items' => [
        [
          'interviewee' => [
            'name' => [
              'preferred' => 'Adam Brooks',
            ],
            'cv' => [
              [
                'date' => '2017 - present',
                'text' => 'Current position',
              ],
              [
                'date' => '2015 - 2017',
                'text' => 'Previous position',
              ],
            ],
          ],
          'content' => [
            [
              'type' => 'paragraph',
              'text' => '<strong>Single</strong> paragraph',
            ],
          ],
        ],
        $this->lines([
          '<p><strong>Single</strong> paragraph</p>' . PHP_EOL,
          '<h1>Adam Brooks CV</h1>' . PHP_EOL,
          '<ul>',
          '<li><b>2017 - present</b>: Current position</li>',
          '<li><b>2015 - 2017</b>: Previous position</li>',
          '</ul>',
        ]),
      ],
      'single button' => [
        [
          'content' => [
            [
              'type' => 'button',
              'text' => 'Button text',
              'uri' => 'http://example.com',
            ],
          ],
        ],
        '<elifebutton class="elife-button--default" data-href="http://example.com">Button text</elifebutton>',
      ],
      'single youtube' => [
        [
          'content' => [
            [
              'type' => 'youtube',
              'id' => 'oyBX9l9KzU8',
              'width' => 16,
              'height' => 9,
            ],
          ],
        ],
        '<figure class="video no-caption"><oembed>https://www.youtube.com/watch?v=oyBX9l9KzU8</oembed></figure>',
      ],
      'another youtube' => [
        [
          'content' => [
            [
              'type' => 'youtube',
              'id' => 'uDi7EU_zKbQ',
              'width' => 1280,
              'height' => 720,
            ],
          ],
        ],
        '<figure class="video no-caption"><oembed>https://www.youtube.com/watch?v=uDi7EU_zKbQ</oembed></figure>',
      ],
      'list soup' => [
        [
          'content' => [
            [
              'type' => 'section',
              'title' => 'Planning the user study',
              'content' => [
                [
                  'type' => 'paragraph',
                  'text' => 'We had a made lot of decisions for the redesign of the new eLife website internally, and we now needed to see how well they fared when put in front of real users.',
                ],
                [
                  'type' => 'paragraph',
                  'text' => '<b>High-level goals</b>',
                ],
                [
                  'type' => 'paragraph',
                  'text' => '<ul><li>We wanted to know how users would feel about such dramatic visual change to the eLife website compared to the experience they were used to.</li><li>We wanted to make sure users would still be able to find their way around a restructured website.</li><li>We wanted to know if this redesign would improve the article reading experience for our visitors.</li><li>We wanted to see if we had created a great mobile experience for our users.</li></ul><b>Turning goals into questions</b>',
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'In order to achieve our goals, we needed to formulate some key questions. Typically we would take a goal and develop a set of questions that could be answered through direct observation of our users’ interaction with our design prototypes.',
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'An example of turning a goal into a question might be:',
                ],
                [
                  'type' => 'paragraph',
                  'text' => '<ul dir="ltr"><li><b>Goal: </b>We wanted to know how users would feel about such dramatic visual change to the eLife website compared to the experience they were used to.</li><li><b>Question:</b> How do users respond when they are presented with the prototype of the redesigned eLife website?</li></ul><b>Developing tasks to answer questions</b>',
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'Rather than directly asking our users questions, we developed a script of tasks allowing them to express their thoughts and feelings in a more natural way, by using the<a href="https://en.wikipedia.org/wiki/Think_aloud_protocol"> think aloud protocol</a>. This also helped us to uncover unexpected needs and test the usability of our designs in a non-leading way.',
                ],
                [
                  'type' => 'paragraph',
                  'text' => 'An example of turning one of the above questions into a task would be:',
                ],
                [
                  'type' => 'list',
                  'prefix' => 'bullet',
                  'items' => [
                    '<b>Question:</b> How do users respond when they are presented with the prototype of the redesigned eLife website?',
                    '<b>Task:</b> "Can you describe the webpage on the screen in front of you?"',
                  ],
                ],
              ],
            ],
          ],
        ],
        $this->lines([
          '<h1>Planning the user study</h1>' . PHP_EOL,
          '<p>We had a made lot of decisions for the redesign of the new eLife website internally, and we now needed to see how well they fared when put in front of real users.</p>' . PHP_EOL,
          '<p><b>High-level goals</b></p>' . PHP_EOL,
          '<ul>',
          '<li>We wanted to know how users would feel about such dramatic visual change to the eLife website compared to the experience they were used to.</li>',
          '<li>We wanted to make sure users would still be able to find their way around a restructured website.</li>',
          '<li>We wanted to know if this redesign would improve the article reading experience for our visitors.</li>',
          '<li>We wanted to see if we had created a great mobile experience for our users.</li>',
          '</ul>' . PHP_EOL,
          '<p><b>Turning goals into questions</b></p>' . PHP_EOL,
          '<p>In order to achieve our goals, we needed to formulate some key questions. Typically we would take a goal and develop a set of questions that could be answered through direct observation of our users’ interaction with our design prototypes.</p>' . PHP_EOL,
          '<p>An example of turning a goal into a question might be:</p>' . PHP_EOL,
          '<ul>',
          '<li><b>Goal: </b>We wanted to know how users would feel about such dramatic visual change to the eLife website compared to the experience they were used to.</li>',
          '<li><b>Question:</b> How do users respond when they are presented with the prototype of the redesigned eLife website?</li>',
          '</ul>' . PHP_EOL,
          '<p><b>Developing tasks to answer questions</b></p>' . PHP_EOL,
          '<p>Rather than directly asking our users questions, we developed a script of tasks allowing them to express their thoughts and feelings in a more natural way, by using the<a href="https://en.wikipedia.org/wiki/Think_aloud_protocol"> think aloud protocol</a>. This also helped us to uncover unexpected needs and test the usability of our designs in a non-leading way.</p>' . PHP_EOL,
          '<p>An example of turning one of the above questions into a task would be:</p>' . PHP_EOL,
          '<ul>',
          '<li><b>Question:</b> How do users respond when they are presented with the prototype of the redesigned eLife website?</li>',
          '<li><b>Task:</b> "Can you describe the webpage on the screen in front of you?"</li>',
          '</ul>',
        ]),
      ],
    ];
  }

  /**
   * Verify that denormalizer detected.
   *
   * @test
   */
  public function itIsDenormalizer() {
    $this->assertInstanceOf(DenormalizerInterface::class, $this->denormalizer);
  }

  /**
   * Verify that it can be denormalized.
   *
   * @test
   * @dataProvider canDenormalizeProvider
   */
  public function itCanDenormalizeSupportedTypes($data, $format, array $context, bool $expected) {
    $this->assertSame($expected, $this->denormalizer->supportsDenormalization($data, $format, $context));
  }

  /**
   * Provider.
   */
  public function canDenormalizeProvider() : array {
    return [
      'data with content' => [['content' => []], Model::class, [], TRUE],
      'non-supported' => [[], get_class($this), [], FALSE],
    ];
  }

  /**
   * It will Denormalize.
   *
   * @test
   * @dataProvider denormalizeProvider
   */
  public function itWillDenormalizeSupportedTypes(
        array $json,
        string $expected,
        array $context = []
    ) {
    $actual = $this->denormalizer->denormalize($json, Model::class, NULL, $context);

    $this->assertEquals($expected, $actual);
  }

  /**
   * Provider.
   */
  public function gatherImagesProvider() : array {
    return [
      'minimal' => [
        [
          'content' => [],
        ],
        [],
      ],
      'sample' => [
        [
          'content' => [
            [
              'type' => 'image',
              'image' => [
                'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-1.jpg',
              ],
            ],
            [
              'type' => 'paragraph',
              'text' => 'Paragraph text',
            ],
            [
              'type' => 'image',
              'image' => [
                'uri' => 'https://iiif.elifesciences.org/journal-cms/editor-images%2Fimage-2.jpg',
              ],
            ],
          ],
        ],
        [
          'public://iiif/editor-images/image-1.jpg',
          'public://iiif/editor-images/image-2.jpg',
        ],
      ],
    ];
  }

  /**
   * Gather images.
   *
   * @test
   * @dataProvider gatherImagesProvider
   */
  public function itWillGatherImages(array $json, array $expected) {
    $actual = $this->denormalizer->gatherImages($json['content']);

    $this->assertEquals($expected, $actual);
  }

}
