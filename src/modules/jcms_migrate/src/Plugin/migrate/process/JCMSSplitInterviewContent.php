<?php

namespace Drupal\jcms_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Process the interview content values into a the field_content structure.
 *
 * @MigrateProcessPlugin(
 *   id = "jcms_split_interview_content"
 * )
 */
class JCMSSplitInterviewContent extends JCMSSplitContent {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->configuration['limit_types'] = ['paragraph'];
    $paragraphs = parent::transform($value, $migrate_executable, $row, $destination_property);
    $paragraphs = $this->processQuestions($paragraphs);
    return $paragraphs;
  }

  /**
   * Develop questions from the paragraphs.
   *
   * @param array $paragraphs
   * @return array
   */
  public function processQuestions($paragraphs) {
    $paragraphs_with_questions = [];
    $paragraph_with_question = [];
    foreach ($paragraphs as $paragraph) {
      if (preg_match('~^<(strong|b)>(?P<question>.*)</(strong|b)>(<br\s*/>\s*|<br>\s*|\s*)(?P<answer>.*)$~', $paragraph['text'], $match)) {
        if (!empty($paragraph_with_question['answer'])) {
          $paragraphs_with_questions[] = $paragraph_with_question;
        }

        $paragraph_with_question = [
          'type' => 'question',
          'question' => $match['question'],
          'answer' => [],
        ];

        if (!empty($match['answer'])) {
          $paragraph_with_question['answer'][] = [
            'type' => 'paragraph',
            'text' => $match['answer'],
          ];
        }
      }
      elseif (!empty($paragraph_with_question)) {
        $paragraph_with_question['answer'][] = $paragraph;
      }
      else {
        $paragraphs_with_questions[] = $paragraph;
      }
    }

    if (!empty($paragraph_with_question['answer'])) {
      $paragraphs_with_questions[] = $paragraph_with_question;
    }

    return $paragraphs_with_questions;
  }

}
