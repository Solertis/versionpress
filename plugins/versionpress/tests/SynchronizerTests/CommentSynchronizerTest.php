<?php

namespace VersionPress\Tests\SynchronizerTests;

use Nette\Utils\Random;
use VersionPress\Storages\CommentStorage;
use VersionPress\Storages\PostStorage;
use VersionPress\Storages\UserStorage;
use VersionPress\Synchronizers\CommentsSynchronizer;
use VersionPress\Synchronizers\PostsSynchronizer;
use VersionPress\Synchronizers\Synchronizer;
use VersionPress\Synchronizers\UsersSynchronizer;
use VersionPress\Tests\SynchronizerTests\Utils\EntityUtils;
use VersionPress\Tests\Utils\DBAsserter;
use VersionPress\Utils\IdUtil;

class CommentSynchronizerTest extends SynchronizerTestCase {
    /** @var CommentStorage */
    private $storage;
    /** @var PostStorage */
    private $postStorage;
    /** @var UserStorage */
    private $userStorage;
    /** @var CommentsSynchronizer */
    private $synchronizer;
    /** @var PostsSynchronizer */
    private $postSynchronizer;
    /** @var UsersSynchronizer */
    private $userSynchronizer;
    private static $authorVpId;
    private static $postVpId;
    private static $vpId;

    protected function setUp() {
        parent::setUp();
        $this->storage = self::$storageFactory->getStorage('comment');
        $this->postStorage = self::$storageFactory->getStorage('post');
        $this->userStorage = self::$storageFactory->getStorage('user');
        $this->synchronizer = new CommentsSynchronizer($this->storage, self::$wpdb, self::$schemaInfo);
        $this->postSynchronizer = new PostsSynchronizer($this->postStorage, self::$wpdb, self::$schemaInfo);
        $this->userSynchronizer = new UsersSynchronizer($this->userStorage, self::$wpdb, self::$schemaInfo);
    }

    /**
     * @test
     * @testdox Synchronizer adds new comment to the database
     */
    public function synchronizerAddsNewCommentToDatabase() {
        $this->createComment();
        $this->userSynchronizer->synchronize(Synchronizer::SYNCHRONIZE_EVERYTHING);
        $this->postSynchronizer->synchronize(Synchronizer::SYNCHRONIZE_EVERYTHING);
        $this->synchronizer->synchronize(Synchronizer::SYNCHRONIZE_EVERYTHING);

        DBAsserter::assertFilesEqualDatabase();
    }

    /**
     * @test
     * @testdox Synchronizer updates changed comment in the database
     */
    public function synchronizerUpdatesChangedCommentInDatabase() {
        $this->editComment();
        $this->synchronizer->synchronize(Synchronizer::SYNCHRONIZE_EVERYTHING);
        DBAsserter::assertFilesEqualDatabase();
    }

    /**
     * @test
     * @testdox Synchronizer removes deleted comment from the database
     */
    public function synchronizerRemovesDeletedCommentFromDatabase() {
        $this->deleteComment();
        $this->synchronizer->synchronize(Synchronizer::SYNCHRONIZE_EVERYTHING);
        $this->postSynchronizer->synchronize(Synchronizer::SYNCHRONIZE_EVERYTHING);
        $this->userSynchronizer->synchronize(Synchronizer::SYNCHRONIZE_EVERYTHING);
        DBAsserter::assertFilesEqualDatabase();
    }

    /**
     * @test
     * @testdox Synchronizer adds new comment to the database (selective synchronization)
     */
    public function synchronizerAddsNewCommentToDatabase_selective() {
        $entitiesToSynchronize = $this->createComment();
        $this->userSynchronizer->synchronize(Synchronizer::SYNCHRONIZE_EVERYTHING, $entitiesToSynchronize);
        $this->postSynchronizer->synchronize(Synchronizer::SYNCHRONIZE_EVERYTHING, $entitiesToSynchronize);
        $this->synchronizer->synchronize(Synchronizer::SYNCHRONIZE_EVERYTHING, $entitiesToSynchronize);

        DBAsserter::assertFilesEqualDatabase();
    }

    /**
     * @test
     * @testdox Synchronizer updates changed comment in the database (selective synchronization)
     */
    public function synchronizerUpdatesChangedCommentInDatabase_selective() {
        $entitiesToSynchronize = $this->editComment();
        $this->synchronizer->synchronize(Synchronizer::SYNCHRONIZE_EVERYTHING, $entitiesToSynchronize);
        DBAsserter::assertFilesEqualDatabase();
    }

    /**
     * @test
     * @testdox Synchronizer removes deleted comment from the database (selective synchronization)
     */
    public function synchronizerRemovesDeletedCommentFromDatabase_selective() {
        $entitiesToSynchronize = $this->deleteComment();
        $this->synchronizer->synchronize(Synchronizer::SYNCHRONIZE_EVERYTHING, $entitiesToSynchronize);
        $this->postSynchronizer->synchronize(Synchronizer::SYNCHRONIZE_EVERYTHING, $entitiesToSynchronize);
        $this->userSynchronizer->synchronize(Synchronizer::SYNCHRONIZE_EVERYTHING, $entitiesToSynchronize);
        DBAsserter::assertFilesEqualDatabase();
    }

    private function createComment() {
        $author = EntityUtils::prepareUser();
        self::$authorVpId = $author['vp_id'];
        $this->userStorage->save($author);

        $post = EntityUtils::preparePost(null, self::$authorVpId);
        self::$postVpId = $post['vp_id'];
        $this->postStorage->save($post);

        $comment = EntityUtils::prepareComment(null, self::$postVpId, self::$authorVpId);
        self::$vpId = $comment['vp_id'];
        $this->storage->save($comment);

        return array(
            array('vp_id' => self::$authorVpId, 'parent' => self::$authorVpId),
            array('vp_id' => self::$postVpId, 'parent' => self::$postVpId),
            array('vp_id' => self::$vpId, 'parent' => self::$vpId),
        );
    }

    private function editComment() {
        $this->storage->save(EntityUtils::prepareComment(self::$vpId, null, null, array('comment_approved' => '0')));
        return array(
            array('vp_id' => self::$vpId, 'parent' => self::$vpId),
        );
    }

    private function deleteComment() {
        $this->storage->delete(EntityUtils::prepareComment(self::$vpId));
        $this->postStorage->delete(EntityUtils::preparePost(self::$postVpId));

        return array(
            array('vp_id' => self::$authorVpId, 'parent' => self::$authorVpId),
            array('vp_id' => self::$postVpId, 'parent' => self::$postVpId),
            array('vp_id' => self::$vpId, 'parent' => self::$vpId),
        );
    }
}