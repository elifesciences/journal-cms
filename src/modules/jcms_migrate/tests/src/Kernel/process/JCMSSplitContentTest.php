<?php

namespace Drupal\Tests\jcms_migrate\Kernel\process;

use Drupal\filter\Entity\FilterFormat;
use Drupal\jcms_migrate\Plugin\migrate\process\JCMSSplitContent;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Tests the split paragraphs process plugin.
 *
 * @coversDefaultClass \Drupal\jcms_migrate\Plugin\migrate\process\JCMSSplitContent
 * @group jcms_migrate
 */
class JCMSSplitContentTest extends KernelTestBase {

  /**
   * @var string
   */
  public static $allowedHtml = '<i> <sub> <sup> <span class> <del> <math> <a href> <b> <br> <table>';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['jcms_migrate', 'filter'];

  protected function setUp() {
    parent::setUp();

    // Create basic HTML format.
    $basic_format = FilterFormat::create([
      'format' => 'basic_html',
      'name' => 'Basic HTML',
      'weight' => 1,
      'filters' => [
        'filter_html' => [
          'weight' => -10,
          'status' => 1,
          'settings' => [
            'allowed_html' => self::$allowedHtml,
          ],
        ],
      ],
    ]);
    $basic_format->save();
  }

  /**
   * @test
   * @covers ::transform
   * @dataProvider transformDataProvider
   * @group  journal-cms-tests
   */
  public function testTransform($html, $limit_types, $expected_result) {
    $split_content = $this->doTransform($html, $limit_types);
    $this->assertEquals($expected_result, $split_content);
  }

  public function transformDataProvider() {
    return [
      [
        '<p>Paragraph <b>1</b></p><p>Paragraph 2</p>',
        [
          'paragraph',
        ],
        [
          ['type' => 'paragraph', 'text' => 'Paragraph <b>1</b>'],
          ['type' => 'paragraph', 'text' => 'Paragraph 2'],
        ],
      ],
      [
        '<p>Paragraph</p><table><tr><td>Table cell</td></tr></table><ul><li>Unordered list item 1</li><li>Unordered list item 2</li></ul><ol><li>Ordered list item</li></ol><p>Paragraph</p>',
        [],
        [
          ['type' => 'paragraph', 'text' => 'Paragraph'],
          ['type' => 'table', 'html' => '<table><tr><td>Table cell</td></tr></table>'],
          ['type' => 'list', 'ordered' => FALSE, 'items' => ['Unordered list item 1', 'Unordered list item 2']],
          ['type' => 'list', 'ordered' => TRUE, 'items' => ['Ordered list item']],
          ['type' => 'paragraph', 'text' => 'Paragraph'],
        ],
      ],
      [
        '<p>Paragraph 1</p><p>&nbsp;</p><p>Paragraph 2</p>',
        [],
        [
          ['type' => 'paragraph', 'text' => 'Paragraph 1'],
          ['type' => 'paragraph', 'text' => 'Paragraph 2'],
        ],
      ],
      [
        '<p>Paragraph 1</p><img src="png/image.png" alt="Alt text" /><p>Paragraph 2</p>',
        [],
        [
          ['type' => 'paragraph', 'text' => 'Paragraph 1'],
          ['type' => 'image', 'image' => 'png/image.png', 'alt' => 'Alt text'],
          ['type' => 'paragraph', 'text' => 'Paragraph 2'],
        ],
      ],
      [
        '<div><a href="png/image.png"><img src="png/image.png"></a></div>',
        [],
        [
          ['type' => 'image', 'image' => 'png/image.png'],
        ],
      ],
      [
        "[caption align=left]<img src=\"https://journal-cms.dev/image.jpg\" /> Image Caption[/caption]",
        [],
        [
          ['type' => 'image', 'image' => 'https://journal-cms.dev/image.jpg', 'caption' => 'Image Caption'],
        ],
      ],
      [
        '<p>Paragraph 1</p><p><iframe allowfullscreen="" frameborder="0" height="315" src="//www.youtube.com/embed/Ykk0ELhUAxo" width="560"></iframe></p><p>Paragraph 2</p>',
        [],
        [
          ['type' => 'paragraph', 'text' => 'Paragraph 1'],
          ['type' => 'youtube', 'id' => 'Ykk0ELhUAxo', 'width' => '560', 'height' => '315'],
          ['type' => 'paragraph', 'text' => 'Paragraph 2'],
        ],
      ],
      [
        '<p>Paragraph 1</p><p><iframe allowfullscreen="" frameborder="0" height="315" src="//www.youtube.com/embed/Ykk0ELhUAxo" width="560"></iframe></p><p>Paragraph 2</p>',
        [
          'paragraph',
          'image',
        ],
        [
          ['type' => 'paragraph', 'text' => 'Paragraph 1'],
          ['type' => 'paragraph', 'text' => 'Paragraph 2'],
        ],
      ],
      [
        "We have turned on markdown as a format for our articles that you can request by setting your header to <code>text/plain</code>. We are not exactly sure how this might be useful, but we think it’s pretty cool anyway, and we hope it can be of benefit to those looking at markdown as a potential tool for the future of scholarly communication.\n\nHere is an example call:\n<pre><code>curl --header \"Accept:text/plain\"  \\n-L http://elife.elifesciences.org/content/2/e00334\n</code></pre>",
        [],
        [
          ['type' => 'paragraph', 'text' => 'We have turned on markdown as a format for our articles that you can request by setting your header to'],
          ['type' => 'code', 'code' => 'text/plain'],
          ['type' => 'paragraph', 'text' => '. We are not exactly sure how this might be useful, but we think it’s pretty cool anyway, and we hope it can be of benefit to those looking at markdown as a potential tool for the future of scholarly communication.'],
          ['type' => 'paragraph', 'text' => 'Here is an example call:'],
          ['type' => 'code', 'code' => 'curl --header "Accept:text/plain"  \\n-L http://elife.elifesciences.org/content/2/e00334'],
        ],
      ],
      [
        "<p>As part of our <a href=\"https://elifesciences.org/collections/plain-language-summaries\">&#x201C;Plain-language summaries of research&#x201D;</a> series, we have compiled a list of over 50 journals and other organizations that publish plain-language summaries of scientific research.</p>\n\n<p>Click <a href=\"https://docs.google.com/spreadsheets/d/1xqOMlzSI2rqxe6Eb3SZRRxmckXVXYACZAwbg3no4ZuI/edit#gid=0\">this link</a> to view the list and find out where you can read these summaries online.</p>\n\n<p>We need your help to keep this list up-to-date. To add a new organization to the&nbsp;list, or to update existing information, please contact us by email at <a href=\"mailto:features@elifesciences.org?subject=Update to plain-language summaries list\">features@elifesciences.org</a>.</p>\n\n<p>[caption]<img src=\"/sites/default/files/news/plain_language_summaries_v2.jpg\" style=\"height:352px; width:500px\" />Image credit: vividbiology.com[/caption]</p>",
        [],
        [
          ['type' => 'paragraph', 'text' => 'As part of our <a href="https://elifesciences.org/collections/plain-language-summaries">“Plain-language summaries of research”</a> series, we have compiled a list of over 50 journals and other organizations that publish plain-language summaries of scientific research.'],
          ['type' => 'paragraph', 'text' => 'Click <a href="https://docs.google.com/spreadsheets/d/1xqOMlzSI2rqxe6Eb3SZRRxmckXVXYACZAwbg3no4ZuI/edit#gid=0">this link</a> to view the list and find out where you can read these summaries online.'],
          ['type' => 'paragraph', 'text' => 'We need your help to keep this list up-to-date. To add a new organization to the list, or to update existing information, please contact us by email at <a href="mailto:features@elifesciences.org?subject=Update%20to%20plain-language%20summaries%20list">features@elifesciences.org</a>.'],
          ['type' => 'image', 'image' => '/sites/default/files/news/plain_language_summaries_v2.jpg', 'caption' => 'Image credit: vividbiology.com'],
        ],
      ],
      [
        "<p><strong>Following the recent open-source release of our new publishing platform, eLife held a webinar to introduce the software and to discuss its features, requirements and capabilities with the publishing community. </strong></p>\n\n<p>In the webinar, Ian Mulvany, Head of Technology, included a step-by-step guide to how eLife Continuum works, with a live demonstration of the publishing process, and Giuliano Maciocci, Head of Product, mentioned upcoming innovations already underway at eLife. To find out more about future developments, please <a href=\"https://crm.elifesciences.org/crm/node/8\">sign up to our technology and innovation newsletter</a>.</p>\n\n<p>The source code of eLife Continuum is now openly available at eLife&#x2019;s GitHub repository. Anyone interested in exploring the new publishing platform can <a href=\"https://github.com/elifesciences/elife-continuum-documentation\">start by reviewing the documentation</a>. We have also included <a href=\"https://github.com/elifesciences/elife-continuum-documentation/tree/master/presentations\">the webinar slides</a>.&nbsp;</p>\n\n<p>We invite publishers and developers to <a href=\"http://groups.google.com/d/forum/elife-continuum-list\">join our discussion forum</a>, to ask questions and share their thoughts about eLife Continuum. The answers to the questions from the webinar have already been included in this discussion.</p>\n\n<p>Watch the recording of the &#x201C;Introducing eLife Continuum&#x201D; webinar to learn more.</p>\n\n<p>&lt;iframe width=\"640\" height=\"360\" src=\"https://www.youtube.com/embed/rWmjeHX7c88\" frameborder=\"0\" allowfullscreen&gt;&lt;/iframe&gt;</p>",
        [],
        [
          ['type' => 'paragraph', 'text' => '<b>Following the recent open-source release of our new publishing platform, eLife held a webinar to introduce the software and to discuss its features, requirements and capabilities with the publishing community. </b>'],
          ['type' => 'paragraph', 'text' => 'In the webinar, Ian Mulvany, Head of Technology, included a step-by-step guide to how eLife Continuum works, with a live demonstration of the publishing process, and Giuliano Maciocci, Head of Product, mentioned upcoming innovations already underway at eLife. To find out more about future developments, please <a href="https://crm.elifesciences.org/crm/node/8">sign up to our technology and innovation newsletter</a>.'],
          ['type' => 'paragraph', 'text' => 'The source code of eLife Continuum is now openly available at eLife’s GitHub repository. Anyone interested in exploring the new publishing platform can <a href="https://github.com/elifesciences/elife-continuum-documentation">start by reviewing the documentation</a>. We have also included <a href="https://github.com/elifesciences/elife-continuum-documentation/tree/master/presentations">the webinar slides</a>.'],
          ['type' => 'paragraph', 'text' => 'We invite publishers and developers to <a href="http://groups.google.com/d/forum/elife-continuum-list">join our discussion forum</a>, to ask questions and share their thoughts about eLife Continuum. The answers to the questions from the webinar have already been included in this discussion.'],
          ['type' => 'paragraph', 'text' => 'Watch the recording of the “Introducing eLife Continuum” webinar to learn more.'],
          ['type' => 'youtube', 'id' => 'rWmjeHX7c88', 'width' => '640', 'height' => '360'],
        ],
      ],
      [
        "<p><strong>A survey of over 300 people reveals what our readers&nbsp;really think of eLife digests.</strong></p>\n\n<p>eLife has been producing plain-language summaries &#x2013; known as eLife digests &#x2013; for research articles since the journal launched in 2012. The digests are written to explain the background and significance of the research clearly&nbsp;to people outside the field, including other scientists and members of the general public.</p>\n\n<p>Who reads eLife digests? Is there anything we can do to improve them? To help us answer these questions we carried out a survey of our readers in late 2016. We advertised the survey on our website and social media over a six-week period and received 313 responses from readers of eLife digests. As part of our <a href=\"https://elifesciences.org/collections/plain-language-summaries\">\"Plain-language summaries of research\"</a> series we now present the results of the survey in detail below.</p>\n\n<p><strong>eLife digest readers have a variety of different occupations</strong></p>\n\n<p>Over 80% of the digest readers who completed the survey are currently working or studying in the life sciences or biomedicine (referred to as &#x201C;life scientists&#x201D; from now on). More than half of the life scientists are currently graduate students and postdoctoral scientists, but some research group leaders and other senior scientists also read eLife digests (Figure 1 &#x2013; Life scientists).</p>\n\n<p>The digest readers who are not currently working in the life sciences or biomedicine (referred to as &#x201C;non-life scientists&#x201D;) work in a range of areas, but particularly in the education sector or in science policy, communication or publishing. Other readers work as writers, dancers, patient advocates, or have retired (Figure 1 &#x2013; Non-life scientists).</p>\n\n<p>[caption]<img src=\"/sites/default/files/news/figure_1_v2.png\" style=\"height:260px; width:720px\" />Figure 1: What is your current job role? 271 respondents are currently working in the life sciences or biomedicine (left), while 42 respondents have other roles (right).[/caption]</p>\n\n<p><strong>Most respondents regularly read digests on the eLife website</strong></p>\n\n<p>eLife digests are displayed within their related research articles on the eLife website (HTML, Lens-view&nbsp;and PDF), PubMed Central and&nbsp;Europe PMC. We also share a selection of digests on&nbsp;<a href=\"https://medium.com/@elife\">our blogs</a>&nbsp;on the social publishing platform, Medium. The vast majority of respondents said that they read digests on the eLife website (Figure 2). This is perhaps to be expected given that monthly traffic to digests on the eLife website&nbsp;is typically at least five times higher than the traffic to our Medium blogs, and we didn't advertise the survey on PubMed Central/Europe PMC. We also asked our readers how often they read eLife digests, with 85% saying that they read them at least once a month.</p>\n\n<p>[caption]<img alt=\"Reader behavior\" src=\"/sites/default/files/news/figure_2_v4.png\" style=\"height:259px; width:720px\" />Figure 2: Reader behavior. Life scientists (271); Non-life scientists (42).[/caption]</p>\n\n<p>All eLife content is published under <a href=\"https://creativecommons.org/lice%E2%80%A6\">Creative Commons</a> licenses that enable anyone to share or reuse the content (usually providing they cite the original authors). 43&nbsp;respondents said they have shared or reused eLife digests with the most popular platforms being Facebook, teaching materials and Twitter (Figure 3).</p>\n\n<p>[caption]<img alt=\"\" src=\"/sites/default/files/news/figure_3_v3_0.png\" style=\"height:295px; width:400px\" />Figure 3: On which platforms have you shared or reused an eLife digest?[/caption]</p>\n\n<p>One of the life scientists who uses eLife digests as teaching tools for their university teaching said:&nbsp;<em>&#x201C;I teach a 4th year literature based course aimed at getting students to effectively use the primary literature. For one assignment students need to choose an article from EMBO or eLife (both have a transparent review process). Students almost always choose eLife, mostly because of the digests.&#x201D;</em></p>\n\n<p><strong>Most readers are happy with the length of eLife digests and the language used</strong></p>\n\n<p>eLife digests are typically between 200&#x2013;400 words long and we try to use as few technical terms as possible so that the text is accessible to a broad audience. 80% of life scientists and 90% of non-life scientists thought that the language used is &#x201C;just right&#x201D; (Figure 4). Similar numbers also thought that the digests are &#x201C;the right length&#x201D;.</p>\n\n<p>[caption]<img src=\"/sites/default/files/news/Figure_4_v3.png\" style=\"height:211px; width:720px\" />Figure 4: The content of eLife digests. Life scientists (271); Non-life scientists (42).[/caption]</p>\n\n<p>Along with ensuring that the language of digests is pitched at the right level for our audience, we want to ensure that the content is also useful. Over 90% of life&nbsp;scientists and non-life scientists said that most or all of the digests they&#x2019;ve read were informative (Figure 5).</p>\n\n<p>[caption]<img src=\"/sites/default/files/news/figure_5_v2.png\" style=\"height:256px; width:400px\" />Figure 5: How many of the digests that you have read were informative?[/caption]</p>\n\n<p>Although most of the survey respondents read digests regularly, we did get some responses from people that had only just discovered digests, including this non-life scientist: <em>&#x201C;I was halfway through reading my first digest and I found that it was easy to understand, and gave a concise background and overview of the article I was going to read. I had already noted down key words and jargon from the abstract that I needed to read up on, so clicking on 'digest' has already saved me some time.&#x201D;</em></p>\n\n<p><strong>Next steps</strong></p>\n\n<p>The survey shows that current eLife digest readers are largely happy with the digests as they are. Furthermore, 89% of them think that other journals should consider providing plain-language summaries of research articles. However, there is always room for improvement so we were very pleased to receive many constructive suggestions for what we could do to make eLife digests even better. The comments centered on two main themes:</p>\n\n<ul>\n      <li>Content &#x2013; make the significance of the research clearer, and consider including images/figures/diagrams/videos to help explain the findings.</li>\n      <li>Visibility &#x2013; make digests easier to find online, especially for general readers who don&#x2019;t want to read the full research article.</li>\n</ul>\n\n<p>We are already looking into how we can change the way we write and display digests to incorporate these suggestions. In the future, we are also hoping to raise more awareness of eLife digests outside of the research community so that other general readers are able to make use of this resource.</p>\n",
        [],
        [
          ['type' => 'paragraph', 'text' => '<b>A survey of over 300 people reveals what our readers really think of eLife digests.</b>'],
          ['type' => 'paragraph', 'text' => 'eLife has been producing plain-language summaries – known as eLife digests – for research articles since the journal launched in 2012. The digests are written to explain the background and significance of the research clearly to people outside the field, including other scientists and members of the general public.'],
          ['type' => 'paragraph', 'text' => 'Who reads eLife digests? Is there anything we can do to improve them? To help us answer these questions we carried out a survey of our readers in late 2016. We advertised the survey on our website and social media over a six-week period and received 313 responses from readers of eLife digests. As part of our <a href="https://elifesciences.org/collections/plain-language-summaries">"Plain-language summaries of research"</a> series we now present the results of the survey in detail below.'],
          ['type' => 'paragraph', 'text' => '<b>eLife digest readers have a variety of different occupations</b>'],
          ['type' => 'paragraph', 'text' => 'Over 80% of the digest readers who completed the survey are currently working or studying in the life sciences or biomedicine (referred to as “life scientists” from now on). More than half of the life scientists are currently graduate students and postdoctoral scientists, but some research group leaders and other senior scientists also read eLife digests (Figure 1 – Life scientists).'],
          ['type' => 'paragraph', 'text' => 'The digest readers who are not currently working in the life sciences or biomedicine (referred to as “non-life scientists”) work in a range of areas, but particularly in the education sector or in science policy, communication or publishing. Other readers work as writers, dancers, patient advocates, or have retired (Figure 1 – Non-life scientists).'],
          ['type' => 'image', 'image' => '/sites/default/files/news/figure_1_v2.png', 'caption' => 'Figure 1: What is your current job role? 271 respondents are currently working in the life sciences or biomedicine (left), while 42 respondents have other roles (right).'],
          ['type' => 'paragraph', 'text' => '<b>Most respondents regularly read digests on the eLife website</b>'],
          ['type' => 'paragraph', 'text' => 'eLife digests are displayed within their related research articles on the eLife website (HTML, Lens-view and PDF), PubMed Central and Europe PMC. We also share a selection of digests on <a href="https://medium.com/@elife">our blogs</a> on the social publishing platform, Medium. The vast majority of respondents said that they read digests on the eLife website (Figure 2). This is perhaps to be expected given that monthly traffic to digests on the eLife website is typically at least five times higher than the traffic to our Medium blogs, and we didn\'t advertise the survey on PubMed Central/Europe PMC. We also asked our readers how often they read eLife digests, with 85% saying that they read them at least once a month.'],
          ['type' => 'image', 'image' => '/sites/default/files/news/figure_2_v4.png', 'alt' => 'Reader behavior', 'caption' => 'Figure 2: Reader behavior. Life scientists (271); Non-life scientists (42).'],
          ['type' => 'paragraph', 'text' => 'All eLife content is published under <a href="https://creativecommons.org/lice%E2%80%A6">Creative Commons</a> licenses that enable anyone to share or reuse the content (usually providing they cite the original authors). 43 respondents said they have shared or reused eLife digests with the most popular platforms being Facebook, teaching materials and Twitter (Figure 3).'],
          ['type' => 'image', 'image' => '/sites/default/files/news/figure_3_v3_0.png', 'caption' => 'Figure 3: On which platforms have you shared or reused an eLife digest?'],
          ['type' => 'paragraph', 'text' => 'One of the life scientists who uses eLife digests as teaching tools for their university teaching said:“I teach a 4th year literature based course aimed at getting students to effectively use the primary literature. For one assignment students need to choose an article from EMBO or eLife (both have a transparent review process). Students almost always choose eLife, mostly because of the digests.”'],
          ['type' => 'paragraph', 'text' => '<b>Most readers are happy with the length of eLife digests and the language used</b>'],
          ['type' => 'paragraph', 'text' => 'eLife digests are typically between 200–400 words long and we try to use as few technical terms as possible so that the text is accessible to a broad audience. 80% of life scientists and 90% of non-life scientists thought that the language used is “just right” (Figure 4). Similar numbers also thought that the digests are “the right length”.'],
          ['type' => 'image', 'image' => '/sites/default/files/news/Figure_4_v3.png', 'caption' => 'Figure 4: The content of eLife digests. Life scientists (271); Non-life scientists (42).'],
          ['type' => 'paragraph', 'text' => 'Along with ensuring that the language of digests is pitched at the right level for our audience, we want to ensure that the content is also useful. Over 90% of life scientists and non-life scientists said that most or all of the digests they’ve read were informative (Figure 5).'],
          ['type' => 'image', 'image' => '/sites/default/files/news/figure_5_v2.png', 'caption' => 'Figure 5: How many of the digests that you have read were informative?'],
          ['type' => 'paragraph', 'text' => 'Although most of the survey respondents read digests regularly, we did get some responses from people that had only just discovered digests, including this non-life scientist:“I was halfway through reading my first digest and I found that it was easy to understand, and gave a concise background and overview of the article I was going to read. I had already noted down key words and jargon from the abstract that I needed to read up on, so clicking on \'digest\' has already saved me some time.”'],
          ['type' => 'paragraph', 'text' => '<b>Next steps</b>'],
          ['type' => 'paragraph', 'text' => 'The survey shows that current eLife digest readers are largely happy with the digests as they are. Furthermore, 89% of them think that other journals should consider providing plain-language summaries of research articles. However, there is always room for improvement so we were very pleased to receive many constructive suggestions for what we could do to make eLife digests even better. The comments centered on two main themes:'],
          ['type' => 'list', 'ordered' => FALSE, 'items' => ['Content – make the significance of the research clearer, and consider including images/figures/diagrams/videos to help explain the findings.', 'Visibility – make digests easier to find online, especially for general readers who don’t want to read the full research article.']],
          ['type' => 'paragraph', 'text' => 'We are already looking into how we can change the way we write and display digests to incorporate these suggestions. In the future, we are also hoping to raise more awareness of eLife digests outside of the research community so that other general readers are able to make use of this resource.'],
        ],
      ],
    ];
  }

  /**
   * Transforms html into content blocks.
   *
   * @param array|string $value
   *   Source html.
   * @param array $limit_types
   *   Blocks to limit to in transfer, leave blank if no limit.
   *
   * @return array $actual
   *   The content blocks based on the source html.
   */
  protected function doTransform($value, $limit_types = []) {
    $row = new Row();
    $migration = $this->prophesize(MigrationInterface::class)->reveal();
    $executable = $this->prophesize(MigrateExecutableInterface::class)->reveal();

    $plugin = new TestJCMSSplitContent([], 'jcms_split_content', [], $migration);
    $plugin->setLimitTypes($limit_types);
    $actual = $plugin->transform($value, $executable, $row, 'destinationproperty');
    return $actual;
  }

}

class TestJCMSSplitContent extends JCMSSplitContent {

  /**
   * Set limit_types configuration.
   *
   * @param array $limit_types
   */
  public function setLimitTypes($limit_types) {
    $this->configuration['limit_types'] = $limit_types;
  }

}
