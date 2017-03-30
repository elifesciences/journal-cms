<?php

namespace Drupal\Tests\jcms_migrate\Kernel\process;

use Drupal\filter\Entity\FilterFormat;
use Drupal\jcms_migrate\Plugin\migrate\process\JCMSSplitInterviewContent;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Tests the split paragraphs process plugin.
 *
 * @coversDefaultClass \Drupal\jcms_migrate\Plugin\migrate\process\JCMSSplitInterviewContent
 * @group jcms_migrate
 */
class JCMSSplitInterviewContentTest extends KernelTestBase {

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
            'allowed_html' => JCMSSplitContentTest::$allowedHtml,
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
  public function testTransform($html, $expected_result) {
    $split_content = $this->doTransform($html);
    $this->assertEquals($expected_result, $split_content);
  }

  public function transformDataProvider() {
    return [
      [
        "<div style=\"margin: 0px; padding: 0px; border: 0px; outline: 0px; vertical - align: baseline; font: inherit; color: rgb(0, 0, 15); font - family: Helvetica, Arial, Verdana, sans - serif; line - height: 23px; \">\n<div>&#xA0;</div>\n\n<p>Jessica Metcalf of the University of Colorado is rapidly becoming an expert regarding the science of death. \"I've been into dead things for a while,\" she told eLife. \"They hold a lot of information.\"&#xA0;</p>\n\n[caption align=left]<img alt=\"Jessica Metcalf in a cave during an archaeological dig in Tasmania.\" height=\"480\" src=\"https://cdn.elifesciences.org/images/early-career-interview-mock/Jess_cave.jpeg\" width=\"360\">Jessica Metcalf in a cave during an archaeological dig in Tasmania[/caption]\n</div>\n\n<div class=\"rteleft\" style=\"font-size: 80%; \">&#xA0;</div>\n\n<div>Metcalf's interest in death is not macabre; rather, she is a curious researcher interested in improving forensic science . Building on a wave of studies investigating the microbiome &#x2014; the library of bacteria, pathogens and other microorganisms that coexist with the body, particularly in the digestive system &#x2014; Metcalf has spent the past years intently analyzing how and when certain microbes congregate when animals decompose.</div>\n\n<div>&#xA0;</div>\n\n<div>\"My background is in ancient DNA &#x2014; I'm [trained as] an evolutionary biologist studying genes of vertebrates,\" Metcalf said. At first blush, her previous work may seem a far cry from her current interests examining the microbiome of dead animals. But in reality, her earlier research, which compared genetic signatures of old and new species samples, has provided her with the necessary knowledge to understand and analyze the microorganisms present in the body during decomposition.&#xA0;</div>\n\n<div>&#xA0;</div>\n\n<div>&#xA0;</div>\n\n<div>Metcalf's PhD thesis and early postdoctoral work focused on studying a particular endangered species: the cutthroat trout. Using some of the oldest samples of cutthroat trout collections at museums, such as the Smithsonian, she compared DNA signatures of fish that were more than 150 years old to present populations. Working with Andrew Martin (University of Colorado) and Alan Cooper (University of Adelaide) she used genetic analysis to study the fish in Colorado streams. The team found that fish thought to be the federally protected greenback cutthroat trout&#xA0;<a href=\"http://onlinelibrary.wiley.com/doi/10.1111/j.1365-294X.2007.03472.x/abstract;jsessionid=F9A544ED7B5C553F4E33272CDB0021E6.f02t02\">may in fact be another trout species</a>, pointing to a major flaw in recent conservation efforts.</div>\n\n<div>&#xA0;</div>\n\n<div>Metcalf jumped from studying old things to dead things when she went to <a href=\"https://knightlab.colorado.edu\">Rob Knight's lab at the University of Colorado</a> for her second postdoc position. She had become an expert on ancient DNA analysis and Knight knew how to study the microbiome. The pair wondered how the microbiome changed once an animal died. \"We were interested in asking fundamental questions about microbial ecology of decomposition, which obviously has an important forensic application,\" Metcalf said.</div>\n\n<div>&#xA0;</div>\n\n[caption align=right]<img alt=\"Jessica in the Lab.\" height=\"332\" src=\"https://cdn.elifesciences.org/images/Jess-in-the-lab.jpg\" width=\"250\">Jessica in the Lab[/caption]\n\n<div class=\"rteleft\" style=\"font-size: 80%; \">&#xA0;</div>\n\n<p>When animals are alive, the highest concentration of microbes is in the digestive system. So using a mouse model where she placed the animals on the top of tiny dirt graves, Metcalf sampled the gut every three days postmortem. However, taking DNA samples from the gut of the dead mice meant destroying the sample itself. In order to get data that spanned a long period of time from the same animal, she also swabbed the skin of the mice. In a series of experiments, Metcalf discovered a reproducible community of microbes in the skin and surrounding soil that correlated to different stages of animal tissue decomposition, providing a <a href=\"http://elifesciences.org/content/2/e01104\">&#x2018;microbial clock&#x2019;</a> that can accurately determine the time of death. &#xA0;</p>\n\n<div>\n<div>In the future she wants to repeat the mouse decomposition study, this time using three different types of soil. In this way she will be able to investigate whether the makeup of the dirt graves affects the microbial signature observed as animal bodies start to decay.</div>\n\n<div>&#xA0;</div>\n\n<div>Looking further ahead, she also wants to study dead swine. After that she plans to coordinate studies across \"body farms\" &#x2014;&#xA0;specialized centers that study decaying human bodies &#x2014;&#xA0;to see if the microbes once again match up during different stages of deterioration.</div>\n</div>\n\n<div>&#xA0;</div>\n\n<div>Though most of her <a href=\"http://www.jessicalmetcalf.com/#jessicalmetcalf\">current work</a> is focused on forensic science, Metcalf still dabbles in ancient DNA analysis, particularly as it relates to the gut. \"That's the cool thing about academics [and] science &#x2014; you have one tool and you can answer completely different questions with it.\"</div>\n\n<div>&#xA0;</div>\n\n<div>By Brian Mossop - Freelance science and technology writer</div>\n\n<div>&#xA0;</div>\n\n<div>Download a printable PDF version <a href=\"http://digest-pictures-200814.s3.amazonaws.com/JN2475%20EC%20Jessica%20Handout%20Aug%202014.pdf\">here</a>.</div>",
        [
          [
            'type' => 'paragraph',
            'text' => "Jessica Metcalf of the University of Colorado is rapidly becoming an expert regarding the science of death. \"I've been into dead things for a while,\" she told eLife. \"They hold a lot of information.\"",
          ],
          [
            'type' => 'image',
            'image' => 'https://cdn.elifesciences.org/images/early-career-interview-mock/Jess_cave.jpeg',
            'alt' => 'Jessica Metcalf in a cave during an archaeological dig in Tasmania.',
            'caption' => 'Jessica Metcalf in a cave during an archaeological dig in Tasmania',
          ],
          [
            'type' => 'paragraph',
            'text' => "Metcalf's interest in death is not macabre; rather, she is a curious researcher interested in improving forensic science . Building on a wave of studies investigating the microbiome — the library of bacteria, pathogens and other microorganisms that coexist with the body, particularly in the digestive system — Metcalf has spent the past years intently analyzing how and when certain microbes congregate when animals decompose.",
          ],
          [
            'type' => 'paragraph',
            'text' => "\"My background is in ancient DNA — I'm [trained as] an evolutionary biologist studying genes of vertebrates,\" Metcalf said. At first blush, her previous work may seem a far cry from her current interests examining the microbiome of dead animals. But in reality, her earlier research, which compared genetic signatures of old and new species samples, has provided her with the necessary knowledge to understand and analyze the microorganisms present in the body during decomposition.",
          ],
          [
            'type' => 'paragraph',
            'text' => "Metcalf's PhD thesis and early postdoctoral work focused on studying a particular endangered species: the cutthroat trout. Using some of the oldest samples of cutthroat trout collections at museums, such as the Smithsonian, she compared DNA signatures of fish that were more than 150 years old to present populations. Working with Andrew Martin (University of Colorado) and Alan Cooper (University of Adelaide) she used genetic analysis to study the fish in Colorado streams. The team found that fish thought to be the federally protected greenback cutthroat trout <a href=\"http://onlinelibrary.wiley.com/doi/10.1111/j.1365-294X.2007.03472.x/abstract;jsessionid=F9A544ED7B5C553F4E33272CDB0021E6.f02t02\">may in fact be another trout species</a>, pointing to a major flaw in recent conservation efforts.",
          ],
          [
            'type' => 'paragraph',
            'text' => "Metcalf jumped from studying old things to dead things when she went to <a href=\"https://knightlab.colorado.edu\">Rob Knight's lab at the University of Colorado</a> for her second postdoc position. She had become an expert on ancient DNA analysis and Knight knew how to study the microbiome. The pair wondered how the microbiome changed once an animal died. \"We were interested in asking fundamental questions about microbial ecology of decomposition, which obviously has an important forensic application,\" Metcalf said.",
          ],
          [
            'type' => 'image',
            'image' => 'https://cdn.elifesciences.org/images/Jess-in-the-lab.jpg',
            'alt' => 'Jessica in the Lab.',
            'caption' => 'Jessica in the Lab',
          ],
          [
            'type' => 'paragraph',
            'text' => "When animals are alive, the highest concentration of microbes is in the digestive system. So using a mouse model where she placed the animals on the top of tiny dirt graves, Metcalf sampled the gut every three days postmortem. However, taking DNA samples from the gut of the dead mice meant destroying the sample itself. In order to get data that spanned a long period of time from the same animal, she also swabbed the skin of the mice. In a series of experiments, Metcalf discovered a reproducible community of microbes in the skin and surrounding soil that correlated to different stages of animal tissue decomposition, providing a <a href=\"http://elifesciences.org/content/2/e01104\">‘microbial clock’</a> that can accurately determine the time of death.",
          ],
          [
            'type' => 'paragraph',
            'text' => "In the future she wants to repeat the mouse decomposition study, this time using three different types of soil. In this way she will be able to investigate whether the makeup of the dirt graves affects the microbial signature observed as animal bodies start to decay.",
          ],
          [
            'type' => 'paragraph',
            'text' => "Looking further ahead, she also wants to study dead swine. After that she plans to coordinate studies across \"body farms\" — specialized centers that study decaying human bodies — to see if the microbes once again match up during different stages of deterioration.",
          ],
          [
            'type' => 'paragraph',
            'text' => "Though most of her <a href=\"http://www.jessicalmetcalf.com/#jessicalmetcalf\">current work</a> is focused on forensic science, Metcalf still dabbles in ancient DNA analysis, particularly as it relates to the gut. \"That's the cool thing about academics [and] science — you have one tool and you can answer completely different questions with it.\"",
          ],
          [
            'type' => 'paragraph',
            'text' => "By Brian Mossop - Freelance science and technology writer",
          ],
          [
            'type' => 'paragraph',
            'text' => "Download a printable PDF version <a href=\"http://digest-pictures-200814.s3.amazonaws.com/JN2475%20EC%20Jessica%20Handout%20Aug%202014.pdf\">here</a>.",
          ],
        ],
      ],
      [
        "<p>For many years plant scientist <strong>Monica Alandete-Saez</strong> assumed that she would spend her whole career in academic research, but a desire to interact more directly with other sectors of society led her to explore other options. She now works for PIPRA, a small not-for-profit technology commercialization organization based on the campus of the University of California Davis.</p>\n\n<p>[caption]<img alt=\"Monica Alandete-Saez\" src=\"/sites/default/files/monica_alandete-saez.jpg\" style=\"height:427px; width:320px\" title=\"Monica Alandete-Saez. Image credit: Mily Ron\" />Monica Alandete-Saez. Image credit: Mily Ron[/caption]</p>\n\n<p><strong>What first inspired you to study biology?</strong><br />\nI was always intrigued by science, especially the life sciences and how organisms evolved during the history of Earth to become our present communities and ecosystems.</p>\n\n<p><strong>What did you love most about academic research?</strong><br />\nI loved the creative and talented atmosphere of the lab, and the excitement I felt before knowing the results of an important experiment. In retrospect though, I wouldn&#x2019;t have spent four years working as a postdoc. I would have made the transition out of the academic environment earlier.</p>\n\n<p><strong>Why?</strong><br />\nMy time as a postdoc didn&#x2019;t add any valuable skills that I hadn&#x2019;t already developed during my PhD. I think that if you&#x2019;re interested in pursuing a non-academic career, you should aim to get your PhD (and the advanced technical training and professional maturity that it develops) and then transition immediately after.&nbsp;</p>\n",
        [
          [
            'type' => 'paragraph',
            'text' => "For many years plant scientist <b>Monica Alandete-Saez</b> assumed that she would spend her whole career in academic research, but a desire to interact more directly with other sectors of society led her to explore other options. She now works for PIPRA, a small not-for-profit technology commercialization organization based on the campus of the University of California Davis.",
          ],
          [
            'type' => 'image',
            'image' => '/sites/default/files/monica_alandete-saez.jpg',
            'alt' => 'Monica Alandete-Saez',
            'caption' => 'Monica Alandete-Saez. Image credit: Mily Ron',
          ],
          [
            'type' => 'question',
            'question' => "What first inspired you to study biology?",
            'answer' => [
              [
                'type' => 'paragraph',
                'text' => "I was always intrigued by science, especially the life sciences and how organisms evolved during the history of Earth to become our present communities and ecosystems.",
              ],
            ],
          ],
          [
            'type' => 'question',
            'question' => "What did you love most about academic research?",
            'answer' => [
              [
                'type' => 'paragraph',
                'text' => "I loved the creative and talented atmosphere of the lab, and the excitement I felt before knowing the results of an important experiment. In retrospect though, I wouldn’t have spent four years working as a postdoc. I would have made the transition out of the academic environment earlier.",
              ],
            ],
          ],
          [
            'type' => 'question',
            'question' => "Why?",
            'answer' => [
              [
                'type' => 'paragraph',
                'text' => "My time as a postdoc didn’t add any valuable skills that I hadn’t already developed during my PhD. I think that if you’re interested in pursuing a non-academic career, you should aim to get your PhD (and the advanced technical training and professional maturity that it develops) and then transition immediately after.",
              ],
            ],
          ],
        ],
      ],
      [
        "<div>\n<div>Keren Yizhak majored in computational biology at the Hebrew University of Jerusalem and is currently a PhD student at the School of Computer Science at Tel-Aviv University, where she uses computational techniques to study biological phenomena, focusing on the metabolic changes that occur in cells during cancer and ageing. She will move to the Broad Institute at Harvard and MIT in March 2015 to begin her first postdoctoral position. Her main interest outside of science is ballet dancing, which she finds a source of inspiration and discipline.</div>\n\n<div>&nbsp;</div>\n\n<div>\n<div>[caption align = left]<img alt=\"Keren Yizhak\" src=\"/sites/default/files/pic_keren_yizhak.jpg\" style=\"height:374px; width:340px\" title=\"Keren Yizhak uses computational techniques to study biological phenomena . Image credit Keren Yizhak . \" />Keren Yizhak uses computational techniques to study biological phenomena. Image credit Keren Yizhak.[/caption]</div>\n\n<div>\n<div><strong>What attracted you to studying computational biology?</strong></div>\n\n<div>I believe that developing new and creative ways for analyzing and modelling the enormous amounts of biological data that are being generated is of great importance. With the right analyses we can potentially shed light on many biological phenomena that could not have otherwise been revealed.</div>\n\n<div>&nbsp;</div>\n\n<div><strong>How do you describe your research to your family and friends?</strong></div>\n\n<div>I am working on a computer model that includes all of the metabolic reactions that take place in a human cell. I develop computational methods that help us to integrate large amounts of detailed biological data collected from cells with this model. We use these methods to predict how cells will respond to different genetic and environmental perturbations, focusing on aging and cancer.&nbsp;</div>\n\n<div>&nbsp;</div></div>\n\n<p>&nbsp;</p>\n",
        [
          [
            'type' => 'paragraph',
            'text' => "Keren Yizhak majored in computational biology at the Hebrew University of Jerusalem and is currently a PhD student at the School of Computer Science at Tel-Aviv University, where she uses computational techniques to study biological phenomena, focusing on the metabolic changes that occur in cells during cancer and ageing. She will move to the Broad Institute at Harvard and MIT in March 2015 to begin her first postdoctoral position. Her main interest outside of science is ballet dancing, which she finds a source of inspiration and discipline.",
          ],
          [
            'type' => 'image',
            'image' => '/sites/default/files/pic_keren_yizhak.jpg',
            'alt' => 'Keren Yizhak',
            'caption' => 'Keren Yizhak uses computational techniques to study biological phenomena. Image credit Keren Yizhak.',
          ],
          [
            'type' => 'question',
            'question' => "What attracted you to studying computational biology?",
            'answer' => [
              [
                'type' => 'paragraph',
                'text' => "I believe that developing new and creative ways for analyzing and modelling the enormous amounts of biological data that are being generated is of great importance. With the right analyses we can potentially shed light on many biological phenomena that could not have otherwise been revealed.",
              ],
            ],
          ],
          [
            'type' => 'question',
            'question' => "How do you describe your research to your family and friends?",
            'answer' => [
              [
                'type' => 'paragraph',
                'text' => "I am working on a computer model that includes all of the metabolic reactions that take place in a human cell. I develop computational methods that help us to integrate large amounts of detailed biological data collected from cells with this model. We use these methods to predict how cells will respond to different genetic and environmental perturbations, focusing on aging and cancer.",
              ],
            ],
          ],
        ],
      ],
      [
        "<p>After finishing his PhD in cancer genomics, <strong>Yilong Li</strong> felt that most academic labs did not have the computational infrastructure needed to tackle clinically relevant questions in genomics. He is now a Principal Scientist at a company called <a href=\"https://www.sbgenomics.com\">Seven Bridges Genomics</a>.</p>",
        [
          [
            'type' => 'paragraph',
            'text' => 'After finishing his PhD in cancer genomics, <b>Yilong Li</b> felt that most academic labs did not have the computational infrastructure needed to tackle clinically relevant questions in genomics. He is now a Principal Scientist at a company called <a href="https://www.sbgenomics.com">Seven Bridges Genomics</a>.'
          ],
        ],
      ],
    ];
  }

  /**
   * Transforms html into interview content blocks.
   *
   * @param array|string $value
   *   Source html.
   *
   * @return array $actual
   *   The interview content blocks based on the source html.
   */
  protected function doTransform($value) {
    $row = new Row();
    $migration = $this->prophesize(MigrationInterface::class)->reveal();
    $executable = $this->prophesize(MigrateExecutableInterface::class)->reveal();

    $plugin = new JCMSSplitInterviewContent([], 'jcms_split_interview_content', [], $migration);
    $actual = $plugin->transform($value, $executable, $row, 'destinationproperty');
    return $actual;
  }

}
