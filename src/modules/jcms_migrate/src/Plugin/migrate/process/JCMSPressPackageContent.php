<?php

namespace Drupal\jcms_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Process the content values into a the field_content structure.
 *
 * @MigrateProcessPlugin(
 *   id = "jcms_press_package_content"
 * )
 */
class JCMSPressPackageContent extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $section = (isset($this->configuration['section']) && in_array($this->configuration['section'], ['relatedContent', 'mediaContacts', 'about'])) ? $this->configuration['section'] : 'content';
    $breakup = $this->breakupContent($value);

    return $breakup[$section];
  }

  function breakupContent($content) {
    $content = preg_replace("~&(nbsp|#xA0);~", ' ', trim($content));
    $content = preg_replace("~( ){2,}~", ' ', $content);

    $breakup = [
      'content' => preg_replace("~^(.*)<[^>]+>\\s*Reference.*~s", '$1', $content),
      'relatedContent' => NULL,
      'mediaContacts' => NULL,
      'about' => NULL,
    ];

    if (preg_match_all("~10\\.7554/elife\\.(?P<article_id>[0-9]{5})~i", $content, $matches)) {
      $breakup['relatedContent'] = [];
      foreach ($matches['article_id'] as $article_id) {
        if (!in_array($article_id, $breakup['relatedContent'])) {
          $breakup['relatedContent'][] = $article_id;
        }
      }

      foreach ($breakup['relatedContent'] as $k => $article_id) {
        $breakup['relatedContent'][$k] = [
          'type' => 'article',
          'source' => $article_id,
        ];
      }
    }

    if (preg_match("~(?P<media_contacts>Media contacts.*>\\s*About)~s", $content, $match)) {
      $split = preg_split("~\\s*\\n+\\s*~i", trim(strip_tags($match['media_contacts'])));
      $split = array_slice($split, 1, count($split) - 2);
      if ($contacts = $this->breakupMediaContacts($split)) {
        $breakup['mediaContacts'] = $contacts;
      }
    }

    if (preg_match("~(?P<about>>\\s*about( elife)?\\s*<.*<p>.*</p>.*<p>.*)~si", $content, $match)) {
      $split = preg_split("~\\s*\\n+\\s*~i", trim($match['about']));
      $split = array_slice($split, 1, count($split) - 1);
      $breakup['about'] = implode("\n\n", $split);
    }

    return $breakup;
  }

  function breakupMediaContacts($contacts_combined) {
    $contacts = [];
    $default_contact = [
      'name' =>  [],
      'affiliations' => [],
      'emailAddresses' => [],
      'phoneNumbers' => [],
    ];
    $contact = $default_contact;

    $clean_up = function ($contact, $strip_elife = TRUE) {
      if (empty($contact['name']) || ($strip_elife && !empty($contact['elife']))) {
        return [];
      }
      foreach ($contact as $k => $contact_item) {
        if (empty($contact_item)) {
          unset($contact[$k]);
        }
      }
      return $contact;
    };

    foreach ($contacts_combined as $contacts_item) {
      if (preg_match("~^\\s*about~i", $contacts_item)) {
        break;
      }
      if (preg_match("~^(?P<name>[a-z0-9, -']*[a-z]+[a-z0-9, -']*)$~i", $contacts_item, $match)) {
        $name = $match['name'];
        if (preg_match("~^(?P<name>[^,]+),\\s+(?P<aff>.*)$~", $name, $match)) {
          $name = $match['name'];
          $aff = $match['aff'];
        }
        if (!empty($contact) && $clean_contact = $clean_up($contact)) {
          $contacts[] = $clean_contact;
        }
        $contact = $default_contact;
        $contact['name'] = [
          'preferred' => $name,
          'index' => preg_replace("~^(.*)\\s+([^\\s]+)$~", '$2, $1', trim($name)),
        ];
        if (!empty($aff)) {
          $contact['affiliations'] = [['name' => [trim($aff)]]];
          if (preg_match("~eLife~i", $aff)) {
            $contact['elife'] = TRUE;
          }
        }
      }
      elseif (preg_match("~@~", $contacts_item)) {
        if (preg_match('/@elifesciences\.org/', $contacts_item)) {
          $contact['elife'] = TRUE;
        }
        $contact['emailAddresses'][] = trim($contacts_item);
      }
      else {
        $contacts_item = preg_replace("~\\s+~", '', $contacts_item);
        $phone_numbers = preg_split('(or|,|;|\|)', $contacts_item);
        foreach ($phone_numbers as $phone_number) {
          $phone_number = preg_replace("~ext[^0-9]*~", '#', $phone_number);
          $phone_number = preg_replace("~[^0-9\\-\\+#]~", '', $phone_number);
          $phone_number = preg_replace("~\\+0~", '0', $phone_number);
          $phone_number = preg_replace("~#~", ';ext=', $phone_number);
          if (!empty($phone_number)) {
            $phone_util = PhoneNumberUtil::getInstance();
            foreach (['US', 'GB'] + $phone_util->getSupportedRegions() as $region) {
              if ($phone_util->isPossibleNumber($phone_number, $region)) {
                $phone_proto = $phone_util->parse($phone_number, $region);
                $phone_util->getRegionCodeForNumber($phone_proto);
                $contact['phoneNumbers'][] = $phone_util->format($phone_proto, PhoneNumberFormat::E164);
                break;
              }
            }
          }
        }
      }
    }
    if (!empty($contact) && $clean_contact = $clean_up($contact)) {
      $contacts[] = $clean_contact;
    }

    return $contacts;
  }

}
