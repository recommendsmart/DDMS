<?php

namespace Drupal\personal_notes\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates a 'personal_notes' Block.
 *
 * @Block(
 *   id = "block_personal_notesblk",
 *   admin_label = @Translation("personal_notesblock"),
 * )
 */
class PersonalNotesBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  /**
   * The constructor for Personal Notes Block object.
   *
   * @param array $configuration
   *   The array configuration.
   * @param string $plugin_id
   *   The id for plugin.
   * @param mixed $plugin_definition
   *   The definition of plugin.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user in site.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // User must be logged on to have personal notes.
    if (!$this->currentUser->isAnonymous()) {
      // Get their notes.
      $results = _personal_notes_fetch_content_db();
      $notedata = [];
      $notes = [];
      foreach ($results as $result) {
        foreach ($result as $field => $value) {
          if (preg_match('/^(title)|(note)|(created)|(notenum)$/', $field)) {
            // Save note's number, title and message.
            $notedata[$field] = $value;
          }
        }
        $notes[$notedata['title'] .
        str_pad(
          $notedata['notenum'],
          5,
          '0',
          STR_PAD_LEFT
        // Gives a constant length index display in the title.
        // Serialize the data.
        )] =
          // Store in a twig template friendly array.
          [
            'note' => $notedata['note'],
            'created' => date("F d, Y", $notedata['created']),
          ];
      }
      $build = [
        '#theme' => 'block--personal_notes',
        '#notes' => $notes,
        // Attach the stylesheet library.
        '#attached' => [
          'library' => [
            'personal_notes/personal_notes',
          ],
        ],
      ];
      return $build;
    }                                                            //    end if user is logged in
  }                                                                //    end build method

}
