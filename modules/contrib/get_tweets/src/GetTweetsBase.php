<?php

namespace Drupal\get_tweets;

use Abraham\TwitterOAuth\TwitterOAuth;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\NodeInterface;

/**
 * Class GetTweetsImport.
 */
class GetTweetsBase {

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorage
   */
  protected $nodeStorage;

  /**
   * The GetTweets settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $getTweetsSettings;

  /**
   * Drupal logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a GetTweetsBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   * @param \Drupal\Core\Database\Connection $connection
   *   A Database connection to use for reading and writing configuration data.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger, FileSystemInterface $file_system, Connection $connection) {
    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->getTweetsSettings = $config_factory->get('get_tweets.settings');
    $this->logger = $logger->get('get_tweets');
    $this->fileSystem = $file_system;
    $this->connection = $connection;
  }

  /**
   * Returns TwitterOAuth object or null.
   *
   * @param string $consumer_key
   *   The Application Consumer Key.
   * @param string $consumer_secret
   *   The Application Consumer Secret.
   * @param string|null $oauth_token
   *   The Client Token (optional).
   * @param string|null $oauth_token_secret
   *   The Client Token Secret (optional).
   *
   * @return \Abraham\TwitterOAuth\TwitterOAuth|null
   *   Returns TwitterOAuth object or null.
   */
  public function getTwitterConnection($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {
    $connection = new TwitterOAuth($consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret);

    if ($connection) {
      return $connection;
    }

    return NULL;
  }

  /**
   * Import tweets.
   */
  public function import() {
    $config = $this->getTweetsSettings;
    $connection = $this->getTwitterConnection($config->get('consumer_key'), $config->get('consumer_secret'), $config->get('oauth_token'), $config->get('oauth_token_secret'));

    if (!$connection || !$config->get('import')) {
      return;
    }

    $count = $config->get('count');

    foreach ($config->get('queries') as $query) {
      $parameters = [
        $query['parameter'] => $query['query'],
        'count' => $count,
        'tweet_mode' => 'extended',
        'include_entities' => TRUE,
      ];

      $endpoint = $query['endpoint'];
      $handle = trim($query['query'], '@');

      // We need VARCHAR "field_tweet_id_value" as an integer.
      $max_id_query = 'SELECT field_tweet_id_value 
        FROM {node__field_tweet_id} AS i
        LEFT JOIN {node__field_tweet_author} AS a
        ON i.entity_id = a.entity_id
        WHERE a.field_tweet_author_title = :handle
        ORDER BY field_tweet_id_value * 1 DESC LIMIT 1';

      $max_id = $this->connection->query($max_id_query, [':handle' => $handle])->fetchField();

      if (!empty($max_id)) {
        $parameters['since_id'] = $max_id;
      }

      $tweets = $connection->get($endpoint, $parameters);

      if (isset($connection->getLastBody()->errors)) {
        $this->logger->error($connection->getLastBody()->errors[0]->message);
        return;
      }

      if ($endpoint === 'search/tweets') {
        $tweets = $tweets->statuses;
      }

      if ($tweets && empty($tweets->errors)) {
        foreach ($tweets as $tweet) {
          $this->createNode($tweet, $endpoint, $query['query']);
        }
      }
    }
  }

  /**
   * Creating node.
   *
   * @param \stdClass $tweet
   *   Tweet for import.
   * @param string $tweet_type
   *   Tweet type.
   * @param string $query_name
   *   Query name.
   */
  public function createNode(\stdClass $tweet, $tweet_type = 'statuses/user_timeline', $query_name = '') {
    $render_tweet = new RenderTweet($tweet);

    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->nodeStorage->create([
      'type' => 'tweet',
      'field_tweet_id' => $tweet->id,
      'field_tweet_author' => [
        'uri' => $tweet_type === 'statuses/user_timeline' ? 'https://twitter.com/' . $tweet->user->screen_name : 'https://twitter.com/search?q=' . str_replace('#', '%23', $query_name),
        'title' => $tweet_type === 'statuses/user_timeline' ? $tweet->user->screen_name : $query_name,
      ],
      'title' => 'Tweet #' . $tweet->id,
      'field_tweet_content' => [
        'value' => $render_tweet->build(),
        'format' => 'full_html',
      ],
      'created' => strtotime($tweet->created_at),
      'uid' => '1',
      'status' => 1,
    ]);

    if (isset($tweet->entities->user_mentions)) {
      foreach ($tweet->entities->user_mentions as $user_mention) {
        $node->field_tweet_mentions->appendItem($user_mention->screen_name);
      }
    }

    if (isset($tweet->entities->hashtags)) {
      foreach ($tweet->entities->hashtags as $hashtag) {
        $node->field_tweet_hashtags->appendItem($hashtag->text);
      }
    }

    if (isset($tweet->entities->media)) {
      foreach ($tweet->entities->media as $media) {
        $this->saveTweetLocalImage($node, $media);
      }
    }

    if (isset($tweet->retweeted_status)) {
      if (isset($tweet->retweeted_status->entities->user_mentions)) {
        foreach ($tweet->retweeted_status->entities->user_mentions as $user_mention) {
          if (!$this->checkDuplicateUsers($node->field_tweet_mentions, $user_mention->screen_name)) {
            $node->field_tweet_mentions->appendItem($user_mention->screen_name);
          }
        }
      }
      if (isset($tweet->retweeted_status->entities->hashtags)) {
        foreach ($tweet->retweeted_status->entities->hashtags as $hashtag) {
          if (!$this->checkDuplicateHashtags($node->field_tweet_hashtags, $hashtag->text)) {
            $node->field_tweet_hashtags->appendItem($hashtag->text);
          }
        }
      }
      if (isset($tweet->retweeted_status->entities->media)) {
        foreach ($tweet->retweeted_status->entities->media as $media) {
          $this->saveTweetLocalImage($node, $media);
        }
      }
    }

    $node->save();
  }

  /**
   * Check if a user_mention already exists.
   */
  public function checkDuplicateUsers($users, $tweetuser) {
    foreach ($users as $user) {
      if ($user == $tweetuser) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Check if a hashtag already exists.
   */
  public function checkDuplicateHashtags($hashtags, $tweethash) {
    foreach ($hashtags as $hashtag) {
      if ($hashtag == $tweethash) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Run all tasks.
   */
  public function runAll() {
    $this->import();
    $this->cleanup();
  }

  /**
   * Delete old tweets.
   */
  public function cleanup() {
    $config = $this->getTweetsSettings;
    $expire = $config->get('expire');

    if (!$expire) {
      return;
    }

    $storage = $this->nodeStorage;
    $query = $storage->getQuery();
    $query->condition('created', time() - $expire, '<');
    $query->condition('type', 'tweet');
    $result = $query->execute();
    $nodes = $storage->loadMultiple($result);

    foreach ($nodes as $node) {
      $node->delete();
    }
  }

  /**
   * Saves the twitter asset on a node field.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node instance.
   * @param \stdClass $media
   *   Twitter media obj.
   */
  private function saveTweetLocalImage(NodeInterface $node, \stdClass $media) {
    if ($media->type === 'photo') {
      $node->set('field_tweet_external_image', $media->media_url);
      $path_info = pathinfo($media->media_url_https);
      $data = file_get_contents($media->media_url_https);
      $dir = 'public://tweets/';
      if (
        $data &&
        $this->fileSystem->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY)
      ) {
        $file = file_save_data($data, $dir . $path_info['basename'], FileSystemInterface::EXISTS_RENAME);
        $node->set('field_tweet_local_image', $file);
      }
    }
  }

}
