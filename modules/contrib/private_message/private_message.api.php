<?php

/**
 * @file
 * Hook documentation for Private Message module hooks.
 */

use Drupal\Core\Entity\EntityInterface;

/**
 * Alter the private message render array before it is rendered.
 *
 * @param array $build
 *   The render array representing the private message.
 * @param \Drupal\Core\Entity\EntityInterface $privateMessage
 *   The private message entity being rendered.
 * @param string $viewMode
 *   The view mode being rendered on the private message.
 */
function hook_private_message_view_alter(array &$build, EntityInterface $privateMessage, $viewMode) {
  // Create a new class specific to the author of the message.
  $class = 'private-message-author-' . $privateMessage->getOwnerId();
  // Add the class to the wrapper.
  $build['wrapper']['#attributes']['class'][] = $class;
}
