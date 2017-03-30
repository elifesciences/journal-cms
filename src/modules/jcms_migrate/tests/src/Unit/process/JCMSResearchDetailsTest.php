<?php

namespace Drupal\Tests\jcms_migrate\Unit\process;

use Drupal\jcms_migrate\Plugin\migrate\process\JCMSResearchDetails;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests for the research details process plugin.
 *
 * @coversDefaultClass \Drupal\jcms_migrate\Plugin\migrate\process\JCMSResearchDetails
 * @group jcms_migrate
 */
class JCMSResearchDetailsTest extends MigrateProcessTestCase {

  /**
   * @var \Drupal\migrate\Plugin\MigrationInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $migrationPlugin;

  /**
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $migrationPluginManager;

  /**
   * @var \Drupal\migrate\Plugin\MigratePluginManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $processPluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->migrationPlugin = $this->prophesize(MigrationInterface::class);
    $this->migrationPluginManager = $this->prophesize(MigrationPluginManagerInterface::class);
    $this->processPluginManager = $this->prophesize(MigratePluginManager::class);
  }

  /**
   * @test
   * @covers ::gatherFocusesFromProfile
   * @dataProvider gatherFocusesFromProfileDataProvider
   * @group  journal-cms-tests
   */
  public function testGatherFocusesFromProfile($profile, $expected_result) {
    $plugin = new JCMSResearchDetails([], 'jcms_research_details', [], $this->migrationPlugin->reveal(), $this->migrationPluginManager->reveal(), $this->processPluginManager->reveal());
    $focuses = $plugin->gatherFocusesFromProfile($profile);
    $this->assertEquals($expected_result, $focuses, '', 0.0, 10, TRUE, TRUE);
  }

  public function gatherFocusesFromProfileDataProvider() {
    return [
      [
        "<p>Patricia Wittkopp received a BS from the University of Michigan, a PhD from the University of Wisconsin, and did postdoctoral work at Cornell University.&nbsp;In 2005, she began a faculty position at the University of Michigan, where she is now an Arthur F&nbsp;Thurnau Professor in the Department of Ecology and Evolutionary Biology, Department of Molecular, Cellular, and Developmental Biology, Center for Computational Medicine and Bioinformatics, and LSA Honors Program. Her research investigates the genetic basis of phenotypic evolution, with an emphasis on the evolution of gene expression. Molecular and developmental biology, population and quantitative genetics, genomics and bioinformatics are integrated in this work. She was a Damon Runyon Cancer Research Fellow, an Alfred P&nbsp;Sloan Research Fellow, and a recipient of a March of Dimes Starter Scholar Award.</p>\n\n<p>&nbsp;</p>\n\n<p><strong>Keywords</strong></p>\n\n<p>Evolutionary genetics; evolution and development; gene expression; regulatory networks; allele-specific expression</p>\n",
        [
          'allele-specific expression',
          'evolutionary genetics',
          'evolution and development',
          'gene expression',
          'regulatory networks',
        ],
      ],
      [
        "<p>Ian Baldwin studied biology and chemistry at Dartmouth College in Hanover, New Hampshire and graduated 1981 with an AB. In 1989, he received a PhD in Chemical Ecology from Cornell University, in the Section of Neurobiology and Behavior. He was an Assistant (1989), Associate (1993) and Full Professor (1996) in the Department of Biology at SUNY Buffalo. In 1996 he became the Founding Director of the Max Planck Institute for Chemical Ecology, where he now heads of the Department of Molecular Ecology. In 1999 he was appointed Honorary Professor at Friedrich Schiller University in Jena, Germany. In 2002 he founded the International Max Planck Research School at the Max Planck Institute in Jena. Baldwin's scientific work is devoted to understanding the traits that allow plants to survive in the real world. To achieve this, he has developed a molecular toolbox for the native tobacco, Nicotiana attenuata (coyote tobacco) and a graduate program that trains “genome-enabled field biologists” to combine genomic and molecular genetic tools with field work to understand the genes that matter for plant-herbivore, -pollinator, -plant, -microbial interactions under real-world conditions. He has also been driver behind the open-access publication efforts of the Max Planck Society.</p>\n\n\n\n<p><strong>Major subject area(s)</strong><br>\nPlant biology; evolution and ecology; secondary metabolism; organismic level gene function</p>",
        [
          'plant biology',
          'evolution and ecology',
          'secondary metabolism',
          'organismic level gene function',
        ],
      ],
      [
        "<p><strong>Wendy Garrett</strong> is the Melvin J and Geraldine L. Glimcher Associate Professor of Immunology and Infectious Diseases at the Harvard T. H. Chan School of Public Health and an Associate Member of the Broad Institute. Her work explores host-microbiota interactions underlying mucosal immune homeostasis, gastrointestinal inflammatory disorders, and cancer. She graduated from the Yale College; received her MD PhD from Yale University and completed post-graduate training at Harvard.</p>\n\n<p><strong>Keywords</strong><br />\nHost&#x2013;microbiota interactions; microbiome;&nbsp;mucosal immunology</p>",
        [
          'host-microbiota interactions',
          'microbiome',
          'mucosal immunology',
        ],
      ],
      [
        "<p>Christian Hardtke obtained a PhD&nbsp;in Developmental Biology from the Ludwig-Maximilians University of Munich in 1997 for his work on plant embryogenesis. He then moved to Yale University as an HFSP postdoctoral fellow to study photomorphogenesis, before joining McGill University as Assistant Professor in 2001. He was appointed Associate Professor at the University of Lausanne in 2004, where he became Full Professor and director of the Department of Plant Molecular Biology in 2010. His research revolves around the molecular genetic control of plant development, with a focus on quantitative aspects of plant growth and morphology. He is particularly interested in mechanisms of vascular tissue differentiation and their relation to root system architecture, as well as the intersection of these mechanisms with natural genetic variation.&nbsp;</p>\n\n<p><strong>Keywords</strong><br />\nArabidopsis, Brachypodium, natural variation, developmental cell biology</p>\n",
        [
          'arabidopsis',
          'brachypodium',
          'natural variation',
          'developmental cell biology',
        ],
      ],
      [
        "<p>Chris' research has had a substantial impact across diverse biomedical areas. The SMART domain database has been a major organisational principle that has proved to be of benefit across all of cellular and molecular biology; his evolutionary genomics research has provided guiding principles in differentiating genes that are similar – and those that are different – between model organisms and humans; his demonstration that approximately 8.2% of the human genome is functional demarcates the experiments necessary to fully understand transcriptional regulation; and, his evolutionary studies on non-coding RNAs provided the justification required for many that these contribute greatly to biological complexity. Chris is Deputy Director of the Medical Research Council (MRC) Functional Genomics Unit, is an Associate Faculty Member of the Wellcome Trust Sanger Institute, and is Professor of Genomics at the University of Oxford. He holds grants from the MRC, Parkinson's UK, Wellcome Trust, and the European Research Council (Advanced Grant).</p>",
        [],
      ],
      [
        "<p>Vijay&#x2019;s research aims to understand motor- and olfactory- circuit assembly: from deciphering how each component is made, interacts, and stabilises into functioning in the animal to allow behaviour in the real world. Related to the development of network function is its maintenance in the mature animal; another aspect of the work in the laboratory addresses how mature neurons and muscles are maintained. The laboratory uses a genetic approach, mainly using the fruit fly but also collaborating with those using mouse and cell-culture. VijayRaghavan is Secretary to the Government of India in the Ministry of Science and Technology in the Department of Biotechnology. He temporarily holds additional charge of the Department of Biotechnology. VijayRaghavan&#x2019;s research continues at the National Centre for Biological Sciences (NCBS) of the Tata Institute of Fundamental Research (TIFR) in Bangalore, India, where he is Distinguished Professor. He studied engineering at the Indian Institute of Technology, Kanpur. His doctoral work was at TIFR, Mumbai and postdoctoral work at the California Institute of Technology. VijayRaghavan is a Fellow of the Royal Society, a Foreign Associate of the US National Academy of Sciences and a Foreign Associate of the European Molecular Biology Organization.</p>\n\n<p>&nbsp;</p>\n\n<p><strong>Keywords:&nbsp;</strong>Developmental biology; neurogenetics; neurobiology; genetic basis of behavior</p>\n",
        [
          'developmental biology',
          'neurogenetics',
          'neurobiology',
          'genetic basis of behavior',
        ],
      ],
      [
        "<p>Ian Baldwin&nbsp;studied biology and chemistry at Dartmouth College in Hanover, New Hampshire and graduated 1981 with an AB. In 1989, he received a PhD in Chemical Ecology from Cornell University, in the Section of Neurobiology and Behavior. He was an Assistant (1989), Associate (1993) and Full Professor (1996) in the Department of Biology at SUNY Buffalo. In 1996 he became the Founding Director of the Max Planck Institute for Chemical Ecology, where he now heads of the Department of Molecular Ecology. In 1999 he was appointed Honorary Professor at Friedrich Schiller University in Jena, Germany. In 2002 he founded the International Max Planck Research School at the Max Planck Institute in Jena. Baldwin's scientific work is devoted to understanding the traits that allow plants to survive in the real world. To achieve this, he has developed a molecular toolbox for the native tobacco, Nicotiana attenuata (coyote tobacco) and a graduate program that trains &#x201C;genome-enabled field biologists&#x201D; to combine genomic and molecular genetic tools with field work to understand the genes that matter for plant-herbivore, -pollinator, -plant, -microbial interactions under real-world conditions. He has also been a driver behind the open-access publication efforts of the Max Planck Society.</p>\n\n<p>&nbsp;</p>\n\n<p><strong>Keywords:&nbsp;</strong>Plant biology; evolution and&nbsp;ecology;&nbsp;secondary metabolism; organismic level gene function</p>",
        [
          'plant biology',
          'evolution and ecology',
          'secondary metabolism',
          'organismic level gene function',
        ],
      ],
    ];
  }

  /**
   * @test
   * @covers ::cleanupString
   * @dataProvider cleanupStringBasicDataProvider
   * @group  journal-cms-tests
   */
  public function testCleanupStringBasic($string, $expected_result) {
    $plugin = new JCMSResearchDetails([], 'jcms_research_details', [], $this->migrationPlugin->reveal(), $this->migrationPluginManager->reveal(), $this->processPluginManager->reveal());
    $cleanup = $plugin->cleanupString($string);
    $this->assertEquals($expected_result, $cleanup);
  }

  public function cleanupStringBasicDataProvider() {
    return [
      [
        'animal tracking (bio-logging/bio-telemetry)',
        'animal tracking (bio-logging/bio-telemetry)',
      ],
      [
        'APC / regulation of expression of growth control genes',
        'apc / regulation of expression of growth control genes',
      ],
      [
        'structure, function and motion in membrane proteins',
        'structure, function and motion in membrane proteins',
      ],
      [
        'nimal models of human disease & behavioural sciences',
        'nimal models of human disease and behavioural sciences',
      ],
      [
        'physiological functions of redox active "secondary metabolites"',
        'physiological functions of redox active "secondary metabolites"',
      ],
      [
        'cytoskeleton - membrane interplay',
        'cytoskeleton-membrane interplay',
      ],
      [
        'protein nucleic acid interactions',
        'protein-nucleic acid interactions',
      ],
      [
        'Thymus development, maintainance, and regeneration',
        'thymus development, maintainance, and regeneration',
      ],
    ];
  }

}
