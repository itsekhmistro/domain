<?php

/**
 * @file
 * Definition of Drupal\domain_access\DomainAccessManager.
 */

namespace Drupal\domain_access;

use Drupal\domain\DomainInterface;
use Drupal\domain\DomainLoaderInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\domain_access\DomainAccessManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Checks the access status of entities based on domain settings.
 */
class DomainAccessManager implements DomainAccessManagerInterface {

  /**
   * @var \Drupal\domain\DomainLoaderInterface
   */
  protected $loader;

  /**
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Constructs a DomainCreator object.
   *
   * @param \Drupal\domain\DomainLoaderInterface $loader
   *   The domain loader.
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator.
   */
  public function __construct(DomainLoaderInterface $loader, DomainNegotiatorInterface $negotiator) {
    $this->loader = $loader;
    $this->negotiator = $negotiator;
  }

  /**
   * @inheritdoc
   */
  public function getAccessValues(EntityInterface $entity, $field_name = DOMAIN_ACCESS_FIELD) {
    // @TODO: static cache.
    $list = array();
    // @TODO In tests, $entity is returning NULL.
    if (is_null($entity)) {
      return $list;
    }
    // Get the values of an entity.
    $values = $entity->get($field_name);
    // Must be at least one item.
    if (!empty($values)) {
      foreach ($values as $item) {
        if ($target = $item->getValue()) {
          if ($domain = $this->loader->load($target['target_id'])) {
            $list[$domain->id()] = $domain->getDomainId();
          }
        }
      }
    }
    return $list;
  }

  /**
   * @inheritdoc
   */
  public function getAllValue(EntityInterface $entity) {
    return $entity->get(DOMAIN_ACCESS_ALL_FIELD)->value;
  }

  /**
   * @inheritdoc
   */
  public function checkEntityAccess(EntityInterface $entity, AccountInterface $account) {
    $entity_domains = $this->getAccessValues($entity);
    $user = \Drupal::entityManager()->getStorage('user')->load($account->id());
    if (!empty($this->getAllValue($user)) && !empty($entity_domains)) {
      return TRUE;
    }
    $user_domains = $this->getAccessValues($user);
    return (bool) !empty(array_intersect($entity_domains, $user_domains));
  }

  /**
   * @inheritdoc
   */
  public static function getDefaultValue(FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {
    $item = array();
    switch ($entity->getEntityType()) {
      case 'user':
      case 'node':
        if ($active = $this->negotiator->getActiveDomain()) {
          $item[0]['target_uuid'] = $active->uuid();
        }
        break;
      default:
        break;
    }
    return $item;
  }

  /**
   * @inheritdoc
   */
  public static function getDefaultAllValue(FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {
    // @TODO: This may become configurable.
    $item = FALSE;
    switch ($entity->getEntityType()) {
      case 'user':
      case 'node':
        $item = FALSE;
        break;
      default:
        break;
    }
    return $item;
  }

}
