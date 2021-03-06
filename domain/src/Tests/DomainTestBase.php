<?php

/**
 * @file
 * Definition of Drupal\domain\Tests\DomainTestBase.
 */

namespace Drupal\domain\Tests;
use Drupal\simpletest\WebTestBase;
use Drupal\Component\Utility\Crypt;
use Drupal\domain\DomainInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class with helper methods for domain tests.
 */
abstract class DomainTestBase extends WebTestBase {

  use StringTranslationTrait;

  /**
   * Sets a base hostname for running tests.
   *
   * When creating test domains, try to use $this->base_hostname or the
   * domainCreateTestDomains() method.
   */
  public $base_hostname;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('domain', 'node');

  function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }

    // Set the base hostname for domains.
    $this->base_hostname = \Drupal::service('domain.creator')->createHostname();
  }

  /**
   * Reusable test function for checking initial / empty table status.
   */
  public function domainTableIsEmpty() {
    $domains = \Drupal::service('domain.loader')->loadMultiple(NULL, TRUE);
    $this->assertTrue(empty($domains), 'No domains have been created.');
    $default_id = \Drupal::service('domain.loader')->loadDefaultId();
    $this->assertTrue(empty($default_id), 'No default domain has been set.');
  }

  /**
   * Creates domain record for use with POST request tests.
   */
  public function domainPostValues() {
    $edit = array();
    $domain = \Drupal::service('domain.creator')->createDomain();
    $required = \Drupal::service('domain.validator')->getRequiredFields();
    foreach ($required as $key) {
      $edit[$key] = $domain->get($key);
    }
    return $edit;
  }

  public function domainCreateTestDomains($count = 1, $base_hostname = NULL, $list = array()) {
    $original_domains = \Drupal::service('domain.loader')->loadMultiple(NULL, TRUE);
    if (empty($base_hostname)) {
      $base_hostname = $this->base_hostname;
    }
    // Note: these domains are rigged to work on my test server.
    // For proper testing, yours should be set up similarly, but you can pass a
    // $list array to change the default.
    if (empty($list)) {
      $list = array('', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten');
    }
    for ($i = 0; $i < $count; $i++) {
      if (!empty($list[$i])) {
        if ($i < 11) {
          $hostname = $list[$i] . '.' . $base_hostname;
          $name = ucfirst($list[$i]);
        }
        // These domains are not setup and are just for UX testing.
        else {
          $hostname = 'test' . $i . '.' . $base_hostname;
          $name = 'Test ' . $i;
        }
      }
      else {
        $hostname = $base_hostname;
        $name = 'Example';
      }
      // Create a new domain programmatically.
      $values = array(
        'hostname' => $hostname,
        'name' => $name,
        'id' => \Drupal::service('domain.creator')->createMachineName($hostname),
      );
      $domain = \Drupal::entityManager()->getStorage('domain')->create($values);
      $domain->save();
    }
    $domains = \Drupal::service('domain.loader')->loadMultiple(NULL, TRUE);
    $this->assertTrue((count($domains) - count($original_domains)) == $count, format_string('Created %count new domains.', array('%count' => $count)));
  }

  /**
   * Returns whether a given user account is logged in.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account object to check.
   */
  protected function drupalUserIsLoggedIn($account) {
    // @TODO: This is a temporary hack for the test login fails when setting $cookie_domain.
    if (!isset($account->session_id)) {
      return (bool) $account->id();
    }
    // The session ID is hashed before being stored in the database.
    // @see \Drupal\Core\Session\SessionHandler::read()
    return (bool) db_query("SELECT sid FROM {users_field_data} u INNER JOIN {sessions} s ON u.uid = s.uid WHERE s.sid = :sid", array(':sid' => Crypt::hashBase64($account->session_id)))->fetchField();
  }

  /**
   * Adds a test domain to an entity.
   *
   * @param $entity_type
   *   The entity type being acted upon.
   * @param $entity_id
   *   The entity id.
   * @param $id
   *   The id of the domain to add.
   * @param $field
   *   The name of the domain field used to attach to the entity.
   */
  public function addDomainToEntity($entity_type, $entity_id, $id, $field = DOMAIN_ACCESS_FIELD) {
    if ($entity = \Drupal::entityManager()->getStorage($entity_type)->load($entity_id)) {
      $entity->set($field, $id);
      $entity->save();
    }
  }

  /**
   * Login a user on a specific domain.
   *
   * @param Drupal\domain\DomainInterface $domain
   *  The domain to log the user into.
   * @param Drupal\Core\Session\AccountInterface $account
   *  The user account to login.
   */
  public function domainLogin(DomainInterface $domain, AccountInterface $account) {
    if ($this->loggedInUser) {
      $this->drupalLogout();
    }

    // For this to work, we must reset the password to a known value.
    $pass = 'thisissatestpassword';
    $user = \Drupal::entityManager()->getStorage('user')->load($account->id());
    $user->setPassword($pass)->save();
    $url = $domain->getPath() . '/user/login';
    $edit = ['name' => $account->getUsername(), 'pass' => $pass];
    $this->drupalPostForm($url, $edit, t('Log in'));

    // @see WebTestBase::drupalUserIsLoggedIn()
    if (isset($this->sessionId)) {
      $account->session_id = $this->sessionId;
    }
    $pass = $this->assert($this->drupalUserIsLoggedIn($account), format_string('User %name successfully logged in.', array('%name' => $account->getUsername())), 'User login');
    if ($pass) {
      $this->loggedInUser = $account;
      $this->container->get('current_user')->setAccount($account);
    }
  }

}
