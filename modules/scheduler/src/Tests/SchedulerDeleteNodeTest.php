<?php

/**
 * @file
 * Contains \Drupal\scheduler\Tests\SchedulerDeleteNodeTest
 */

namespace Drupal\scheduler\Tests;

use Drupal\node\Entity\NodeType;

/**
 * Tests how the deletion of a node interacts with the Scheduler 'required'
 * options and scheduled dates in the past.
 *
 * @group scheduler
 */
class SchedulerDeleteNodeTest extends SchedulerTestBase {

  /**
   * Tests the deletion of a scheduled node.
   */
  public function testScheduledNodeDelete() {
    // Log in.
    $this->drupalLogin($this->adminUser);

    // 1. Test if it is possible to delete a node that does not have a
    // publication date set, when scheduled publishing is required, and likewise
    // for unpublishing.
    // @see https://drupal.org/node/1614880

    // Create a published and an unpublished node, both without scheduled dates.
    $type = $this->nodetype->get('type');
    $published_node = $this->drupalCreateNode(['type' => $type, 'status' => 1]);
    $unpublished_node = $this->drupalCreateNode(['type' => $type, 'status' => 0]);

    // Make scheduled publishing and unpublishing required.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_required', TRUE)
      ->setThirdPartySetting('scheduler', 'unpublish_required', TRUE)
      ->save();

    // Check that deleting the nodes does not throw form validation errors.
    // In 7.x the 'Delete' functionality was a button but in 8.x it is a link.
    // Hence, we get the form then use clickLink('Delete') which is a stronger
    // test than simply getting the 'node/<id>/delete' link directly.
    $this->drupalGet('node/' . $published_node->id() . '/edit');
    $this->clickLink('Delete');
    // The text 'error message' is used in a header h2 html tag which is
    // normally made hidden from browsers but will be in the page source.
    // It is also good when testing for the absense of something to also test
    // for the presence of text, hence the second assertion for each check.
    $this->assertNoRaw(t('Error message'), 'No error messages are shown when trying to delete a published node with no scheduling information.');
    $this->assertRaw(t('Are you sure you want to delete the content'), 'The deletion warning message is shown immediately when trying to delete a published node with no scheduling information.');

    $this->drupalGet('node/' . $unpublished_node->id() . '/edit');
    $this->clickLink('Delete');
    $this->assertNoRaw(t('Error message'), 'No error messages are shown when trying to delete an unpublished node with no scheduling information.');
    $this->assertRaw(t('Are you sure you want to delete the content'), 'The deletion warning message is shown immediately when trying to delete an unpublished node with no scheduling information.');

    // 2. Test that nodes can be deleted with no validation errors if the
    // dates are in the past.
    // @see http://drupal.org/node/2627370

    // Create nodes with publish_on and unpublish_on dates in the past.
    $published_node = $this->drupalCreateNode(['type' => $type, 'status' => 1, 'unpublish_on' => strtotime('- 2 day')]);
    $unpublished_node = $this->drupalCreateNode(['type' => $type, 'status' => 0, 'publish_on' => strtotime('- 2 day')]);

    // Turn off required publishing and unpublishing.
    $this->nodetype->setThirdPartySetting('scheduler', 'publish_required', FALSE)
      ->setThirdPartySetting('scheduler', 'unpublish_required', FALSE)
      ->save();

    // Attempt to delete the published node and check for no validation error.
    $this->drupalGet('node/' . $published_node->id() . '/edit');
    $this->clickLink('Delete');
    $this->assertNoRaw(t('Error message'), 'No error messages are shown when trying to delete a node with an unpublish date in the past.');
    $this->assertRaw(t('Are you sure you want to delete the content'), 'The deletion warning message is shown immediately when trying to delete a node with an unpublish date in the past.');

    // Attempt to delete the unpublished node and check for no validation error.
    $this->drupalGet('node/' . $unpublished_node->id() . '/edit');
    $this->clickLink('Delete');
    $this->assertNoRaw(t('Error message'), 'No error messages are shown when trying to delete a node with a publish date in the past.');
    $this->assertRaw(t('Are you sure you want to delete the content'), 'The deletion warning message is shown immediately when trying to delete a node with a publish date in the past.');

  }
}
