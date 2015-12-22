Domain
======

Domain module for Drupal port to Drupal 8.

Active branch is 8-x.1-x. Begin any forks from there.

When the module is more stable, we will move it back to drupal.org.

This branch is unstable, as we are moving to config entities.

Implementation Notes
======

To use cross-domain logins, you must now set the *cookie_domain* value in
*sites/default/services.yml*. See https://www.drupal.org/node/2391871.

If using the trusted host security setting in Drupal 8, be sure to add each domain
and alias the the pattern list. For example:

```
$settings['trusted_host_patterns'] = array(
  '^*\.example\.com$',
  '^myexample\.com$',
  '^localhost$',
);
```

See https://www.drupal.org/node/1992030 for more information.
