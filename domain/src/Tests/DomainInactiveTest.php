<?php

/**
 * @file
 * Definition of Drupal\domain\Tests\DomainInactiveTest.
 */

namespace Drupal\domain\Tests;
use Drupal\domain\DomainInterface;

/**
 * Tests the redirects for inactive domains.
 *
 * @group domain
 */
class DomainInactiveTest extends DomainTestBase {

  public function testInactiveDomain() {
    // Create three new domains programmatically.
    $this->domainCreateTestDomains(3);
    $domains = \Drupal::service('domain.loader')->loadMultiple();
    // Grab the last domain for testing/
    $domain = end($domains);
    $this->drupalGet($domain->getPath());
    $this->assertTrue($domain->status(), 'Tested domain is set to active.');
    $this->assertRaw($domain->getPath(), 'Loaded the active domain.');

    // Disable the domain and test for redirect.
    $domain->disable();
    $default = \Drupal::service('domain.loader')->loadDefaultDomain();
    // Must flush cache.
    drupal_flush_all_caches();
    $this->drupalGet($domain->getPath());

    $this->assertFalse($domain->status(), 'Tested domain is set to inactive.');
    $this->assertRaw($default->getPath(), 'Redirected an inactive domain to the default domain.');

    // Try to access with the proper permission.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access inactive domains'));
    $this->assertFalse($domain->status(), 'Tested domain is set to inactive.');
    // Must flush cache.
    drupal_flush_all_caches();
    $this->drupalGet($domain->getPath());
    $this->assertRaw($domain->getPath(), 'Loaded the inactive domain with permission.');
  }

}
