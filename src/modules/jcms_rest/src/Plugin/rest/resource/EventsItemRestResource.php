<?php

namespace Drupal\jcms_rest\Plugin\rest\resource;

use Drupal\jcms_rest\Exception\JCMSNotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "events_item_rest_resource",
 *   label = @Translation("Events item rest resource"),
 *   uri_paths = {
 *     "canonical" = "/events/{id}"
 *   }
 * )
 */
class EventsItemRestResource extends AbstractRestResourceBase {
  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param int $number
   * @return array|\Symfony\Component\HttpFoundation\JsonResponse
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get($id) {
    $query = \Drupal::entityQuery('node')
      ->condition('status', NODE_PUBLISHED)
      ->condition('changed', REQUEST_TIME, '<')
      ->condition('type', 'event')
      ->condition('uuid', '%' . $id, 'LIKE');

    $nids = $query->execute();
    if ($nids) {
      $nid = reset($nids);
      /* @var \Drupal\node\Entity\Node $node */
      $node = \Drupal\node\Entity\Node::load($nid);

      $response = $this->processDefault($node, $id);
      unset($response['published']);

      $response['starts'] = $this->formatDate(strtotime($node->get('field_event_datetime')->first()->getValue()['value']));
      $response['ends'] = $this->formatDate(strtotime($node->get('field_event_datetime')->first()->getValue()['end_value']));

      // Timezone is optional.
      if ($node->get('field_event_timezone')->count()) {
        $response['timezone'] = $node->get('field_event_timezone')->getString();
      }

      // Impact statement is optional.
      if ($node->get('field_impact_statement')->count()) {
        $response['impactStatement'] = $this->fieldValueFormatted($node->get('field_impact_statement'));
      }

      // Venue is optional.
      if ($node->get('field_event_venue')->count()) {
        $venue_field = $node->get('field_event_venue')->first()->get('entity')->getTarget()->getValue();
        $venue = [
          'name' => array_values(array_filter(preg_split("(\r\n?|\n)", $venue_field->get('field_block_title_multiline')->getString()))),
        ];

        // Venue address is optional.
        if ($venue_field->get('field_block_address')->count()) {
          $locale = 'en';
          /* @var \CommerceGuys\Addressing\AddressInterface $address  */
          $address = $venue_field->get('field_block_address')->first();
          $postal_label_formatter = \Drupal::service('address.postal_label_formatter');
          $postal_label_formatter->setOriginCountryCode('no_origin');
          $postal_label_formatter->setLocale($locale);
          $components = [
            'streetAddress' => ['getAddressLine1', 'getAddressLine2'],
            'locality' => ['getLocality', 'getDependentLocality'],
            'area' => ['getAdministrativeArea'],
          ];

          $venue['address'] = [
            'formatted' => explode("\n", $postal_label_formatter->format($address)),
            'components' => [],
          ];

          foreach ($components as $section => $methods) {
            $values = [];
            foreach ($methods as $method) {
              if ($value = $address->{$method}()) {
                $values[] = $value;
              }
            }

            if (!empty($values)) {
              $venue['address']['components'][$section] = $values;
            }
          }

          $country_repository = \Drupal::service('address.country_repository');
          $countries = $country_repository->getList($locale);
          $venue['address']['components']['country'] = $countries[$address->getCountryCode()];

          if ($postal_code = $address->getPostalCode()) {
            $venue['address']['components']['postalCode'] = $postal_code;
          }
          elseif ($postal_code = $address->getSortingCode()) {
            $venue['address']['components']['postalCode'] = $postal_code;
          }
        }

        $response['venue'] = $venue;
      }

      if ($content = $this->processFieldContent($node->get('field_content'))) {
        $response['content'] = $content;
      }

      $response = new JsonResponse($response, Response::HTTP_OK, ['Content-Type' => 'application/vnd.elife.event+json;version=1']);
      return $response;
    }

    throw new JCMSNotFoundHttpException(t('Event with ID @id was not found', ['@id' => $id]), NULL, 'application/problem+json');
  }

}
